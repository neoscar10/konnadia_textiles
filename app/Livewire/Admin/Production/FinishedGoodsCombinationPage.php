<?php

namespace App\Livewire\Admin\Production;

use App\Models\ProductionBatch;
use App\Models\ManufacturingProduct;
use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\StorefrontProductBundle;
use App\Models\StorefrontProductBundleItem;
use App\Models\InventoryMovement;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Exception;

#[Layout('components.admin.layout')]
class FinishedGoodsCombinationPage extends Component
{
    public ?int $target_product_id = null;
    public ?int $target_combination_id = null;
    public int $bundle_quantity = 1;
    public string $notes = '';

    // Array of component inputs to bundle
    public array $bundleComponents = [];
    // Structure: ['production_batch_id' => int, 'manufacturing_product_id' => int, 'quantity_per_bundle' => int]

    public function mount()
    {
        $this->addComponentRow();
    }

    public function addComponentRow()
    {
        $this->bundleComponents[] = [
            'production_batch_id' => '',
            'manufacturing_product_id' => '',
            'quantity_per_bundle' => 1,
        ];
    }

    public function removeComponentRow($index)
    {
        unset($this->bundleComponents[$index]);
        $this->bundleComponents = array_values($this->bundleComponents);
    }

    public function updatedBundleComponents($value, $key)
    {
        // Auto-set manufacturing_product_id if production_batch_id selected
        if (str_contains($key, 'production_batch_id')) {
            $parts = explode('.', $key);
            $idx = $parts[0];
            $batchId = intval($value);
            if ($batchId) {
                $batch = ProductionBatch::find($batchId);
                if ($batch) {
                    $this->bundleComponents[$idx]['manufacturing_product_id'] = $batch->manufacturing_product_id;
                }
            }
        }
    }

    public function createBundleAndConvert()
    {
        if (empty($this->target_product_id) && empty($this->target_combination_id)) {
            $this->addError('target_product_id', 'Please select a Storefront Product or Variant.');
            return;
        }

        if (intval($this->bundle_quantity) <= 0) {
            $this->addError('bundle_quantity', 'Quantity to assemble must be at least 1.');
            return;
        }

        if (empty($this->bundleComponents)) {
            $this->addError('bundleComponents', 'Please add at least one component batch.');
            return;
        }

        foreach ($this->bundleComponents as $idx => $comp) {
            if (empty($comp['production_batch_id'])) {
                $this->addError("bundleComponents.{$idx}.production_batch_id", 'Production Batch is required.');
            }
            if (empty($comp['quantity_per_bundle']) || intval($comp['quantity_per_bundle']) <= 0) {
                $this->addError("bundleComponents.{$idx}.quantity_per_bundle", 'Qty per bundle must be > 0.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        DB::transaction(function () {
            $assembledSets = intval($this->bundle_quantity);

            // 1. Create StorefrontProductBundle Record
            $bundle = StorefrontProductBundle::create([
                'product_id' => $this->target_product_id ?: null,
                'product_combination_id' => $this->target_combination_id ?: null,
                'created_by' => auth()->id(),
                'quantity_created' => $assembledSets,
                'notes' => $this->notes ?: "Storefront Finished Goods Combination Bundling",
            ]);

            // 2. Validate availability and deduct unconverted quantities from batches
            foreach ($this->bundleComponents as $comp) {
                $batch = ProductionBatch::findOrFail($comp['production_batch_id']);
                $qtyPerSet = intval($comp['quantity_per_bundle']);
                $totalNeeded = $assembledSets * $qtyPerSet;

                $remaining = $batch->remaining_unconverted_quantity;
                if ($totalNeeded > $remaining) {
                    throw new Exception("Insufficient unconverted factory units in Batch {$batch->batch_code}. Requested: {$totalNeeded}, Available: {$remaining}.");
                }

                // Update converted quantity on batch
                $batch->update([
                    'converted_quantity' => $batch->converted_quantity + $totalNeeded,
                    'is_converted' => ($batch->converted_quantity + $totalNeeded) >= ($batch->total_finished_quantity ?: $batch->planned_quantity),
                ]);

                // Create Item link
                StorefrontProductBundleItem::create([
                    'storefront_product_bundle_id' => $bundle->id,
                    'production_batch_id' => $batch->id,
                    'manufacturing_product_id' => $batch->manufacturing_product_id,
                    'quantity_used' => $totalNeeded,
                ]);
            }

            // 3. Increment Storefront Product / Combination Stock
            $targetEntity = null;
            if ($this->target_combination_id) {
                $targetEntity = ProductCombination::findOrFail($this->target_combination_id);
            } else {
                $targetEntity = Product::findOrFail($this->target_product_id);
            }

            $targetEntity->increment('stock_quantity', $assembledSets);

            // 4. Log Immutable Stock Movement
            InventoryMovement::create([
                'product_id' => $this->target_combination_id ? $targetEntity->product_id : $targetEntity->id,
                'product_combination_id' => $this->target_combination_id ? $targetEntity->id : null,
                'quantity_change' => $assembledSets,
                'unit_cost' => 0.00,
                'reference_type' => StorefrontProductBundle::class,
                'reference_id' => $bundle->id,
                'movement_type' => 'manufacturing_inward',
                'notes' => "Storefront Finished Goods Combination Bundle {$bundle->bundle_code} ({$assembledSets} Sets assembled)",
            ]);

            session()->flash('toast', [
                'message' => "Successfully assembled {$assembledSets} storefront sets under Bundle Code {$bundle->bundle_code}!",
                'type' => 'success'
            ]);
        });

        return redirect()->route('admin.production.finished-goods-combination');
    }

    public function render()
    {
        $completedBatches = ProductionBatch::with(['manufacturingProduct', 'job'])
            ->where('status', 'Completed')
            ->get()
            ->filter(fn($b) => $b->remaining_unconverted_quantity > 0);

        $storefrontProducts = Product::where('is_active', true)->with('combinations')->orderBy('title')->get();
        $recentBundles = StorefrontProductBundle::with(['product', 'productCombination', 'items.productionBatch', 'items.manufacturingProduct'])->latest()->take(10)->get();

        return view('livewire.admin.production.finished-goods-combination-page', [
            'completedBatches' => $completedBatches,
            'storefrontProducts' => $storefrontProducts,
            'recentBundles' => $recentBundles,
        ])->title('Storefront Finished Goods Combination');
    }
}
