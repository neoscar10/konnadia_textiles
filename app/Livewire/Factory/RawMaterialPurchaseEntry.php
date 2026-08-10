<?php

namespace App\Livewire\Factory;

use App\Models\RawMaterial;
use App\Models\InventoryBatch;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\InventoryBatchLogger;

#[Layout('components.admin.layout')]
class RawMaterialPurchaseEntry extends Component
{
    public string $supplier_name = '';
    public string $purchase_date = '';
    public string $invoice_number = '';
    public ?int $raw_material_id = null;
    public $quantity_received = '';
    public $purchase_rate = '';
    public $total_amount = 0.00;

    // Fabric Bale specific properties
    public $num_bales = 1;
    public $declared_bale_length = '';
    public bool $all_bales_equal_length = true;
    public array $individual_bale_lengths = [];

    // View helper variables
    public ?string $unitType = null; // 'length_based' or 'other'
    public ?string $unitName = null; // e.g. Meters, Pieces

    public function mount()
    {
        $this->purchase_date = Carbon::now()->format('Y-m-d');
        $this->individual_bale_lengths = [''];
    }

    public function updatedRawMaterialId($value)
    {
        if ($value) {
            $material = RawMaterial::with('category')->find($value);
            if ($material && $material->category) {
                $this->unitType = $material->category->unit_type->value;
                $this->unitName = $material->unit;
            } else {
                $this->unitType = null;
                $this->unitName = null;
            }
        } else {
            $this->unitType = null;
            $this->unitName = null;
        }

        $this->recalculateTotal();
    }

    public function updatedNumBales($value)
    {
        if ($value === '' || $value === null) {
            $this->num_bales = '';
            $this->recalculateTotal();
            return;
        }

        $count = max(1, min(100, intval($value)));
        $this->num_bales = $count;

        $currentCount = count($this->individual_bale_lengths);
        $defaultLen = $this->declared_bale_length !== '' ? $this->declared_bale_length : '';

        if ($count > $currentCount) {
            for ($i = $currentCount; $i < $count; $i++) {
                $this->individual_bale_lengths[$i] = $defaultLen;
            }
        } elseif ($count < $currentCount) {
            $this->individual_bale_lengths = array_slice($this->individual_bale_lengths, 0, $count);
        }

        $this->recalculateTotal();
    }

    public function updatedAllBalesEqualLength($value)
    {
        if (!$value) {
            $count = max(1, intval($this->num_bales ?: 1));
            $defaultLen = $this->declared_bale_length !== '' ? $this->declared_bale_length : '';
            $this->individual_bale_lengths = array_fill(0, $count, $defaultLen);
        }
        $this->recalculateTotal();
    }

    public function updatedIndividualBaleLengths()
    {
        $this->recalculateTotal();
    }

    public function updatedDeclaredBaleLength($value)
    {
        if ($this->all_bales_equal_length) {
            $count = max(1, intval($this->num_bales ?: 1));
            $this->individual_bale_lengths = array_fill(0, $count, $value);
        }
        $this->recalculateTotal();
    }

    public function updatedQuantityReceived()
    {
        $this->recalculateTotal();
    }

    public function updatedPurchaseRate()
    {
        $this->recalculateTotal();
    }

    protected function recalculateTotal()
    {
        if ($this->unitType === 'length_based') {
            $bales = max(1, intval($this->num_bales ?: 1));
            if ($this->all_bales_equal_length) {
                $lengthPerBale = floatval($this->declared_bale_length ?: 0);
                $totalLength = $bales * $lengthPerBale;
            } else {
                $totalLength = array_sum(array_map('floatval', $this->individual_bale_lengths));
                if ($bales > 0 && $totalLength > 0) {
                    $this->declared_bale_length = (string) round($totalLength / $bales, 2);
                }
            }
            $this->quantity_received = $totalLength > 0 ? (string) $totalLength : '';
        }

        $qty = floatval($this->quantity_received ?: 0);
        $rate = floatval($this->purchase_rate ?: 0);
        $this->total_amount = round($qty * $rate, 2);
    }

    protected function rules()
    {
        $rules = [
            'supplier_name' => 'required|string|max:255',
            'purchase_date' => 'required|date|before_or_equal:today',
            'invoice_number' => 'required|string|max:100',
            'raw_material_id' => 'required|exists:raw_materials,id',
        ];

        if ($this->unitType === 'length_based') {
            $rules['num_bales'] = 'required|integer|min:1';
            if ($this->all_bales_equal_length) {
                $rules['declared_bale_length'] = 'required|numeric|gt:0';
            } else {
                $rules['individual_bale_lengths'] = 'required|array|size:' . intval($this->num_bales);
                $rules['individual_bale_lengths.*'] = 'required|numeric|gt:0';
            }
            $rules['purchase_rate'] = 'required|numeric|gt:0';
        } else {
            $rules['quantity_received'] = 'required|numeric|gt:0';
            $rules['purchase_rate'] = 'required|numeric|gt:0';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'supplier_name.required' => 'Supplier Name is required.',
            'purchase_date.required' => 'Purchase Date is required.',
            'invoice_number.required' => 'Invoice Number is required.',
            'raw_material_id.required' => 'Please select a raw material.',
            'num_bales.required' => 'Number of Bales is required.',
            'num_bales.min' => 'Number of Bales must be at least 1.',
            'declared_bale_length.required' => 'Declared Length per Bale is required.',
            'declared_bale_length.gt' => 'Declared Length per Bale must be greater than zero.',
            'individual_bale_lengths.*.required' => 'Declared length is required for each bale.',
            'individual_bale_lengths.*.gt' => 'Length for each bale must be greater than zero.',
            'quantity_received.required' => 'Quantity Received is required.',
            'quantity_received.gt' => 'Quantity Received must be greater than zero.',
            'purchase_rate.required' => 'Purchase Rate is required.',
            'purchase_rate.gt' => 'Purchase Rate must be greater than zero.',
        ];
    }

    public function savePurchaseEntry()
    {
        $this->validate();

        $material = RawMaterial::with(['unitGroup', 'unitModel'])->findOrFail($this->raw_material_id);

        $batch = null;
        DB::transaction(function () use ($material, &$batch) {
            $qtyReceived = floatval($this->quantity_received);
            
            // Calculate base quantity using Unit conversion if available
            $baseQty = $qtyReceived;
            if ($material->unitModel) {
                $baseQty = $material->unitModel->toBaseQuantity($qtyReceived);
            }

            $numBales = $this->unitType === 'length_based' ? intval($this->num_bales) : null;
            $declaredLengthArg = null;
            if ($this->unitType === 'length_based') {
                if ($this->all_bales_equal_length) {
                    $declaredLengthArg = floatval($this->declared_bale_length);
                } else {
                    $declaredLengthArg = array_map('floatval', $this->individual_bale_lengths);
                }
            }
            $avgDeclaredLength = is_array($declaredLengthArg) ? (array_sum($declaredLengthArg) / max(1, count($declaredLengthArg))) : $declaredLengthArg;

            $batch = InventoryBatch::create([
                'raw_material_id' => $this->raw_material_id,
                'supplier_name' => $this->supplier_name,
                'purchase_date' => $this->purchase_date,
                'invoice_number' => $this->invoice_number,
                'quantity_received' => $qtyReceived,
                'balance_quantity' => $qtyReceived,
                'base_quantity' => $baseQty,
                'base_current_balance' => $baseQty,
                'quantity_consumed' => 0.0000,
                'purchase_rate' => floatval($this->purchase_rate),
                'total_amount' => $this->total_amount,
                'unit' => $material->unit,
                'purchase_unit_id' => $material->unit_id,
                'num_bales' => $numBales,
                'declared_bale_length' => $avgDeclaredLength,
                'status' => 'active',
            ]);

            // If fabric length-based material, create unopened bales!
            if ($this->unitType === 'length_based' && $numBales > 0) {
                $batch->createBales($numBales, $declaredLengthArg);
            }

            // Log creation
            InventoryBatchLogger::log($batch->id, 'created', $batch->quantity_received, null, "Purchase entry recorded ({$numBales} bales)");
        });

        session()->flash('toast', [
            'message' => 'Purchase entry saved and inventory batch with bales created successfully!',
            'type' => 'success'
        ]);

        return redirect()->route('factory.raw-materials.index');
    }

    public function render()
    {
        $materials = RawMaterial::active()->orderBy('name')->get();

        return view('livewire.factory.raw-material-purchase-entry', [
            'materials' => $materials,
        ])->title('Record Raw Material Purchase');
    }
}
