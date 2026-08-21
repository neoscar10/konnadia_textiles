<?php

namespace App\Livewire\Admin\Production;

use App\Models\ProductionBatch;
use App\Models\Product;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class FinishedGoodsConversion extends Component
{
    public ProductionBatch $batch;
    public $productId = '';
    public string $targetWarehouse = 'Finished Goods WH - Zone A';
    public string $lotNumber = '';
    public int $goodUnits = 0;
    public array $conversionPackaging = [];

    public function mount($id)
    {
        $this->batch = ProductionBatch::with([
            'manufacturingProduct',
            'job.stageExecutions.task',
            'productOutputs',
        ])->findOrFail($id);

        // Enforce conversion readiness guard
        if (!$this->batch->isReadyForConversion()) {
            if ($this->batch->is_converted) {
                session()->flash('error', 'This production batch has already been converted to finished goods.');
                return redirect()->route('admin.production.batches.ledger', $this->batch->id);
            }
            session()->flash('error', 'Cannot convert batch. The designated Final Production Step is pending or the batch status is not Completed.');
            return redirect()->route('admin.production.batches.ledger', $this->batch->id);
        }

        // Output of the final step is the batch's total finished quantity
        $this->goodUnits = $this->batch->total_finished_quantity ?: $this->batch->planned_quantity;

        // Auto-generate a Lot Number
        $productCode = $this->batch->manufacturingProduct ? $this->batch->manufacturingProduct->code : 'PROD';
        $this->lotNumber = "LOT-{$productCode}-" . now()->format('Y-m-d');

        // Pre-select matching product by name if available
        $matchingProduct = Product::where('title', 'like', '%' . ($this->batch->manufacturingProduct->name ?? '') . '%')->first();
        if ($matchingProduct) {
            $this->productId = $matchingProduct->id;
        } else {
            $firstProduct = Product::first();
            if ($firstProduct) {
                $this->productId = $firstProduct->id;
            }
        }

        $this->addConversionPackagingRow();
    }

    public function addConversionPackagingRow(): void
    {
        $this->conversionPackaging[] = [
            'raw_material_id' => '',
            'quantity_used' => '',
        ];
    }

    public function removeConversionPackagingRow(int $index): void
    {
        unset($this->conversionPackaging[$index]);
        $this->conversionPackaging = array_values($this->conversionPackaging);
    }

    public function convert(\App\Services\Manufacturing\FinishedGoodsConversionService $conversionService)
    {
        $this->validate([
            'productId' => 'required|exists:products,id',
            'targetWarehouse' => 'required|string',
            'lotNumber' => 'required|string|max:50',
        ], [
            'productId.required' => 'Please select a sales product to map finished goods to.',
        ]);

        foreach ($this->conversionPackaging as $idx => $pkg) {
            if (!empty($pkg['raw_material_id'])) {
                if (empty($pkg['quantity_used']) || floatval($pkg['quantity_used']) <= 0) {
                    $this->addError("conversionPackaging.{$idx}.quantity_used", 'Quantity must be greater than 0.');
                }
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        try {
            $filteredPackaging = array_filter($this->conversionPackaging, fn($p) => !empty($p['raw_material_id']));

            $conversionService->convertBatchToFinishedGoods($this->batch, [
                'productId' => $this->productId,
                'targetWarehouse' => $this->targetWarehouse,
                'lotNumber' => $this->lotNumber,
                'packaging' => $filteredPackaging,
            ]);

            session()->flash('toast', ['message' => "Successfully converted {$this->goodUnits} units to storefront stock!", 'type' => 'success']);
            return redirect()->route('admin.production.batches.ledger', $this->batch->id);
        } catch (\Exception $e) {
            $this->addError('productId', $e->getMessage());
        }
    }

    public function render()
    {
        $products = Product::where('is_active', true)->orderBy('title')->get();
        $packagingRawMaterials = \App\Models\RawMaterial::whereHas('category', fn($q) => $q->where('code', 'CAT-PKG'))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.production.finished-goods-conversion', [
            'products' => $products,
            'packagingRawMaterials' => $packagingRawMaterials,
        ])->title('Finished Goods Conversion');
    }
}
