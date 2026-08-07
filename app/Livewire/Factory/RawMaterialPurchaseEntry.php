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

    // View helper variables
    public ?string $unitType = null; // 'length_based' or 'other'
    public ?string $unitName = null; // e.g. Meters, Pieces

    public function mount()
    {
        $this->purchase_date = Carbon::now()->format('Y-m-d');
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
            $rules['quantity_received'] = 'required|numeric|gt:0';
            $rules['purchase_rate'] = 'required|numeric|gt:0';
        } else {
            $rules['quantity_received'] = 'required|numeric|gt:0';
            $rules['purchase_rate'] = 'required|numeric|gt:0';
        }

        return $rules;
    }

    protected function messages()
    {
        $qtyLabel = $this->unitType === 'length_based' ? 'Length Received' : 'Quantity Received';
        $rateLabel = $this->unitType === 'length_based' ? 'Rate per Length Unit' : 'Rate per Unit';

        return [
            'supplier_name.required' => 'Supplier Name is required.',
            'purchase_date.required' => 'Purchase Date is required.',
            'invoice_number.required' => 'Invoice Number is required.',
            'raw_material_id.required' => 'Please select a raw material.',
            'quantity_received.required' => "{$qtyLabel} is required.",
            'quantity_received.numeric' => "{$qtyLabel} must be a number.",
            'quantity_received.gt' => "{$qtyLabel} must be greater than zero.",
            'purchase_rate.required' => "{$rateLabel} is required.",
            'purchase_rate.numeric' => "{$rateLabel} must be a number.",
            'purchase_rate.gt' => "{$rateLabel} must be greater than zero.",
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
                'status' => 'active',
            ]);
            // Log creation
            InventoryBatchLogger::log($batch->id, 'created', $batch->quantity_received, null, 'Purchase entry recorded');
        });

        session()->flash('toast', [
            'message' => 'Purchase entry saved and inventory batch created successfully!',
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
