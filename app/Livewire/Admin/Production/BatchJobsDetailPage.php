<?php

namespace App\Livewire\Admin\Production;

use App\Models\ProductionJob;
use App\Models\ManufacturingProduct;
use App\Models\Product;
use App\Services\Manufacturing\FinishedGoodsConversionService;
use Exception;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class BatchJobsDetailPage extends Component
{
    public string $batchCode = '';

    // Storefront Conversion Modal Properties
    public ?int $target_product_id = null;
    public string $productSearch = '';
    public int $target_sets_desired = 1;
    public int $target_unit_level = 1; // 1 for Unit 1 (Base Pcs), 2 for Unit 2 (Boxes/Packs)
    public string $conversion_notes = '';
    public array $conversionComponents = [];
    public array $conversionPackaging = [];

    public function mount(string $batchCode)
    {
        $this->batchCode = $batchCode;
    }

    public function openBatchConversionModal(): void
    {
        $this->resetValidation();
        $this->target_product_id = null;
        $this->productSearch = '';
        $this->target_sets_desired = 1;
        $this->target_unit_level = 1;
        $this->conversion_notes = '';
        $this->conversionComponents = [];
        $this->conversionPackaging = [];
        $this->addConversionPackagingRow();
        $this->addConversionComponentRow();

        $this->dispatch('open-modal', 'storefront-conversion-modal');
    }

    public function openJobConversionModal(int $jobId): void
    {
        $this->resetValidation();
        $this->target_product_id = null;
        $this->productSearch = '';
        $this->target_sets_desired = 1;
        $this->target_unit_level = 1;
        $this->conversion_notes = '';
        $this->conversionComponents = [];

        $job = ProductionJob::find($jobId);
        $this->conversionPackaging = [];
        $this->addConversionPackagingRow();
        if ($job && $job->status === 'completed' && $job->remaining_unconverted_quantity > 0) {
            $this->conversionComponents[] = [
                'production_job_id' => $job->id,
                'quantity_per_set' => 1,
                'total_pieces_input' => $job->remaining_unconverted_quantity,
            ];
        } else {
            $this->addConversionComponentRow();
        }

        $this->dispatch('open-modal', 'storefront-conversion-modal');
    }

    public function addConversionComponentRow(): void
    {
        $this->conversionComponents[] = [
            'production_job_id' => '',
            'quantity_per_set' => 1,
            'total_pieces_input' => 0,
        ];
    }

    public function removeConversionComponentRow(int $index): void
    {
        unset($this->conversionComponents[$index]);
        $this->conversionComponents = array_values($this->conversionComponents);
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

    public function updatedTargetProductId($value): void
    {
        $this->target_unit_level = 1;
    }

    public function updatedConversionComponents($value, $key): void
    {
        if (str_contains($key, 'production_job_id')) {
            $parts = explode('.', $key);
            $idx = intval($parts[0]);
            $jobId = intval($value);
            if ($jobId) {
                $job = ProductionJob::find($jobId);
                if ($job) {
                    $this->conversionComponents[$idx]['total_pieces_input'] = $job->remaining_unconverted_quantity;
                }
            }
        }
    }

    public function getSelectedTargetProductProperty()
    {
        return $this->target_product_id ? Product::with('units')->find($this->target_product_id) : null;
    }

    public function getTargetUnitConversionFactorProperty(): float
    {
        $product = $this->selectedTargetProduct;
        if (!$product) return 1.0;

        if ($this->target_unit_level === 2) {
            $unit2 = $product->units->firstWhere('level', 2);
            if ($unit2 && (float)$unit2->conversion_to_base > 0) {
                return (float)$unit2->conversion_to_base;
            }
        }

        return 1.0;
    }

    public function getConversionSummaryProperty(): array
    {
        $inputSetsDesired = max(1, intval($this->target_sets_desired ?? 1));
        $unitFactor = $this->targetUnitConversionFactor;
        $effectiveBaseItemsDesired = intval(round($inputSetsDesired * $unitFactor));

        if (empty($this->conversionComponents)) {
            return [
                'max_sets' => 0,
                'rows' => [],
                'desired_sets' => $inputSetsDesired,
                'effective_base_items' => $effectiveBaseItemsDesired,
                'unit_factor' => $unitFactor,
                'can_fulfill' => false
            ];
        }

        $possibleSets = [];
        $rowDetails = [];

        foreach ($this->conversionComponents as $idx => $comp) {
            $jobId = intval($comp['production_job_id'] ?? 0);
            $job = $jobId ? ProductionJob::with('manufacturingProduct')->find($jobId) : null;
            $ratio = max(1, intval($comp['quantity_per_set'] ?? 1));
            $inputPcs = max(0, intval($comp['total_pieces_input'] ?? 0));

            $setsPossible = $jobId && $inputPcs > 0 ? (int) floor(($inputPcs / $ratio) / $unitFactor) : 0;
            if ($jobId) {
                $possibleSets[] = $setsPossible;
            }

            $requiredPcsForDesired = $effectiveBaseItemsDesired * $ratio;
            $consumedPcs = min($inputPcs, $requiredPcsForDesired);
            $leftoverPcs = max(0, $inputPcs - $requiredPcsForDesired);

            $rowDetails[$idx] = [
                'job' => $job,
                'ratio' => $ratio,
                'inputPcs' => $inputPcs,
                'setsPossible' => $setsPossible,
                'requiredPcs' => $requiredPcsForDesired,
                'consumedPcs' => $consumedPcs,
                'leftoverPcs' => $leftoverPcs,
                'hasDeficit' => $inputPcs < $requiredPcsForDesired,
            ];
        }

        $maxPossibleSets = !empty($possibleSets) ? (int) min($possibleSets) : 0;
        $canFulfill = $inputSetsDesired <= $maxPossibleSets;

        return [
            'max_sets' => $maxPossibleSets,
            'desired_sets' => $inputSetsDesired,
            'effective_base_items' => $effectiveBaseItemsDesired,
            'unit_factor' => $unitFactor,
            'can_fulfill' => $canFulfill,
            'rows' => $rowDetails,
        ];
    }

    public function processConversion(): void
    {
        if (empty($this->target_product_id)) {
            $this->addError('target_product_id', 'Please select a Storefront Product.');
            return;
        }

        if (empty($this->target_sets_desired) || intval($this->target_sets_desired) <= 0) {
            $this->addError('target_sets_desired', 'Please enter a valid target quantity of products to convert.');
            return;
        }

        if (empty($this->conversionComponents)) {
            $this->addError('conversionComponents', 'Please add at least one finished job product component.');
            return;
        }

        foreach ($this->conversionComponents as $idx => $comp) {
            if (empty($comp['production_job_id'])) {
                $this->addError("conversionComponents.{$idx}.production_job_id", 'Production Job selection is required.');
            }
            if (empty($comp['quantity_per_set']) || intval($comp['quantity_per_set']) <= 0) {
                $this->addError("conversionComponents.{$idx}.quantity_per_set", 'Pieces per product set must be at least 1.');
            }
            if (empty($comp['total_pieces_input']) || intval($comp['total_pieces_input']) <= 0) {
                $this->addError("conversionComponents.{$idx}.total_pieces_input", 'Available finished pieces must be greater than 0.');
            }
        }

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

        $summary = $this->conversionSummary;
        $desiredSets = $summary['desired_sets'];
        $effectiveBaseItems = $summary['effective_base_items'];

        if (!$summary['can_fulfill']) {
            $this->addError('target_sets_desired', "Insufficient finished job pieces to produce {$desiredSets} product set(s). Based on your components, you can only produce up to {$summary['max_sets']} set(s).");
            return;
        }

        try {
            $conversionService = resolve(FinishedGoodsConversionService::class);
            
            // Filter out empty packaging rows
            $filteredPackaging = array_filter($this->conversionPackaging, fn($p) => !empty($p['raw_material_id']));

            $unitLabel = $this->target_unit_level === 2 ? "Unit 2" : "Unit 1";

            $bundle = $conversionService->convertJobsToStorefrontBundle(
                intval($this->target_product_id),
                $effectiveBaseItems,
                $this->conversionComponents,
                $this->conversion_notes ?: "Converted {$desiredSets} {$unitLabel} set(s) ({$effectiveBaseItems} base pcs) from Production Batch {$this->batchCode}",
                $filteredPackaging
            );

            $this->dispatch('close-modal', 'storefront-conversion-modal');
            $this->dispatch('toast', message: "Successfully converted {$desiredSets} storefront product set(s) under Bundle {$bundle->bundle_code}! Storefront stock updated: +{$effectiveBaseItems} Pcs", type: 'success');
        } catch (Exception $e) {
            $this->addError('conversionComponents', $e->getMessage());
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $jobs = ProductionJob::where('production_batch_id', $this->batchCode)
            ->orWhere('job_code', $this->batchCode)
            ->with(['manufacturingProduct', 'supervisor', 'stageExecutions.task', 'allocations'])
            ->orderBy('created_at', 'asc')
            ->get();

        $completedJobsForPicker = $jobs->filter(fn($j) => $j->status === 'completed' && $j->remaining_unconverted_quantity > 0);
        $storefrontProducts = Product::where('is_active', true)
            ->when(!empty($this->productSearch), function ($q) {
                $q->where(function ($sub) {
                    $sub->where('title', 'like', '%' . $this->productSearch . '%')
                        ->orWhere('sku', 'like', '%' . $this->productSearch . '%');
                });
            })
            ->with(['combinations', 'units'])
            ->orderBy('title')
            ->get();
        $packagingRawMaterials = \App\Models\RawMaterial::whereHas('category', fn($q) => $q->where('code', 'CAT-PKG'))
            ->orderBy('name')
            ->get();

        $firstJob = $jobs->first();
        $product = $firstJob?->manufacturingProduct;
        $supervisor = $firstJob?->supervisor;
        
        $batchDbId = $firstJob?->production_batch_db_id;
        if (!$batchDbId) {
            $batch = \App\Models\ProductionBatch::where('batch_code', $this->batchCode)->first();
            $batchDbId = $batch?->id;
        }

        $unconvertedSum = $jobs->sum(fn($j) => $j->remaining_unconverted_quantity);
        $totalProducedSum = $jobs->sum(fn($j) => $j->total_produced_quantity);
        $convertedSum = $jobs->sum(fn($j) => $j->converted_quantity);

        return view('livewire.admin.production.batch-jobs-detail-page', [
            'jobs' => $jobs,
            'firstJob' => $firstJob,
            'product' => $product,
            'supervisor' => $supervisor,
            'batchDbId' => $batchDbId,
            'unconvertedSum' => $unconvertedSum,
            'totalProducedSum' => $totalProducedSum,
            'convertedSum' => $convertedSum,
            'completedJobsForPicker' => $completedJobsForPicker,
            'storefrontProducts' => $storefrontProducts,
            'packagingRawMaterials' => $packagingRawMaterials,
            'conversionSummary' => $this->conversionSummary,
        ])->title("Batch Jobs — {$this->batchCode}");
    }
}
