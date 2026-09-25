<?php

namespace App\Livewire\Admin\Production;

use App\Models\ProductionJob;
use App\Models\ManufacturingProduct;
use App\Models\Product;
use App\Services\Manufacturing\FinishedGoodsConversionService;
use Exception;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class BatchJobsDetailPage extends Component
{
    use WithFileUploads;

    public string $batchCode = '';

    // Storefront Conversion Modal Properties
    public ?int $target_product_id = null;
    public string $productSearch = '';
    public int $target_sets_desired = 1;
    public int $target_unit_level = 1; // 1 for Unit 1 (Base Pcs), 2 for Unit 2 (Boxes/Packs)
    public string $conversion_notes = '';
    public array $conversionComponents = [];
    public array $conversionPackaging = [];

    // Batch Design Selection & Conversion Properties
    public ?int $selectedBatchDbId = null;
    public ?string $selectedBatchCode = null;
    public array $batchDesignOptions = [];
    public ?string $selectedDesignId = null;
    public ?int $selectedCategoryIdForBatchConv = null;
    public int $prefilledTargetSets = 1;
    public array $availableSpareProducts = [];
    public array $selectedSpareProductAllocations = []; // [spare_id => qty_to_use]
    public string $imageOptionMode = 'use_fabric'; // 'use_fabric', 'upload', 'none'
    public $newProductImage = null;

    // Barcode Print Modal Properties
    public bool $showPrintModal = false;
    public ?int $activePrintBatchId = null;
    public $printStickerQty = 10;
    public string $printStickerSize = 'Standard Sticker (50mm × 25mm)';

    public function openPrintBarcodeModal(int $id): void
    {
        $batch = \App\Models\FinishedGoodsBatch::with('frontEndProduct')->findOrFail($id);
        $this->activePrintBatchId = $batch->id;
        $this->printStickerQty = $batch->converted_qty ?: 10;
        $this->showPrintModal = true;
    }


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

    public string $activeTab = 'jobs'; // 'jobs' or 'discrepancies'

    // Discrepancy Resolution Modal Properties
    public ?int $discrepancyJobId = null;
    public ?int $scrapQty = null;
    public string $scrapNotes = '';
    public ?int $damageQty = null;
    public string $damageNotes = '';
    public array $alterationRows = [];
    public string $discrepancyRemarks = '';

    public function openDiscrepancyModal(int $jobId): void
    {
        $this->resetValidation();
        $this->discrepancyJobId = $jobId;
        $job = ProductionJob::with(['manufacturingProduct', 'pattern'])->findOrFail($jobId);

        $this->scrapQty = null;
        $this->scrapNotes = '';
        $this->damageQty = null;
        $this->damageNotes = '';
        $this->discrepancyRemarks = '';
        $this->alterationRows = [
            [
                'altered_qty'       => null,
                'target_product_id' => '',
                'target_pattern_id' => '',
            ]
        ];

        $this->dispatch('open-modal', 'discrepancy-resolution-modal');
    }

    public function fillAllScrap(): void
    {
        if (!$this->discrepancyJobId) return;
        $job = ProductionJob::find($this->discrepancyJobId);
        if (!$job) return;

        $this->scrapQty = $job->discrepancy_quantity;
        $this->damageQty = null;
        foreach ($this->alterationRows as $idx => $row) {
            $this->alterationRows[$idx]['altered_qty'] = null;
        }
    }

    public function fillAllDamage(): void
    {
        if (!$this->discrepancyJobId) return;
        $job = ProductionJob::find($this->discrepancyJobId);
        if (!$job) return;

        $this->damageQty = $job->discrepancy_quantity;
        $this->scrapQty = null;
        foreach ($this->alterationRows as $idx => $row) {
            $this->alterationRows[$idx]['altered_qty'] = null;
        }
    }

    public function addDiscrepancyAlterationRow(): void
    {
        $this->alterationRows[] = [
            'altered_qty'       => null,
            'target_product_id' => '',
            'target_pattern_id' => '',
        ];
    }

    public function removeDiscrepancyAlterationRow(int $index): void
    {
        unset($this->alterationRows[$index]);
        $this->alterationRows = array_values($this->alterationRows);
    }

    public function updatedDiscrepancyAlterationRows($value, $key): void
    {
        if (str_ends_with($key, '.target_product_id')) {
            $index = (int) explode('.', $key)[0];
            $productId = $value;
            if ($productId) {
                $firstPattern = \App\Models\ManufacturingProductPattern::where('manufacturing_product_id', $productId)->first();
                $this->alterationRows[$index]['target_pattern_id'] = $firstPattern?->id ?? '';
            } else {
                $this->alterationRows[$index]['target_pattern_id'] = '';
            }
        }
    }

    public function saveDiscrepancyResolution(): void
    {
        if (!$this->discrepancyJobId) return;

        $job = ProductionJob::findOrFail($this->discrepancyJobId);
        $requiredDiscrepancy = $job->discrepancy_quantity;

        $totalScrap   = max(0, intval($this->scrapQty));
        $totalDamage  = max(0, intval($this->damageQty));
        $totalAltered = 0;
        foreach ($this->alterationRows as $r) {
            $totalAltered += max(0, intval($r['altered_qty'] ?? 0));
        }

        $totalRecorded = $totalScrap + $totalDamage + $totalAltered;

        if ($totalRecorded !== $requiredDiscrepancy) {
            $this->addError('discrepancyTotal', "Total recorded discrepancy ({$totalRecorded} Pcs) must exactly match the job discrepancy amount ({$requiredDiscrepancy} Pcs).");
            return;
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($job) {
                $taskId = $job->stageExecutions->sortByDesc('sequence_number')->first()?->task_id ?? $job->task_id;

                if ($this->scrapQty > 0) {
                    \App\Models\JobWastage::create([
                        'job_code'                 => $job->job_code,
                        'production_job_id'        => $job->id,
                        'manufacturing_product_id' => $job->manufacturing_product_id,
                        'pattern_id'               => $job->pattern_id,
                        'task_id'                  => $taskId,
                        'wastage_type'             => 'scrap',
                        'quantity_wasted'          => $this->scrapQty,
                        'reason'                   => $this->scrapNotes ?: "Scrap / Cutting Waste",
                    ]);
                }

                if ($this->damageQty > 0) {
                    \App\Models\JobWastage::create([
                        'job_code'                 => $job->job_code,
                        'production_job_id'        => $job->id,
                        'manufacturing_product_id' => $job->manufacturing_product_id,
                        'pattern_id'               => $job->pattern_id,
                        'task_id'                  => $taskId,
                        'wastage_type'             => 'damage',
                        'quantity_wasted'          => $this->damageQty,
                        'reason'                   => $this->damageNotes ?: "Partially damaged items",
                    ]);
                }

                $workflowService = resolve(\App\Services\Manufacturing\ProductionWorkflowService::class);
                foreach ($this->alterationRows as $altRow) {
                    $altQty    = intval($altRow['altered_qty'] ?? 0);
                    $targetPId = $altRow['target_product_id'] ?? null;
                    $targetPat = $altRow['target_pattern_id'] ?? null;

                    if ($altQty > 0 && $targetPId) {
                        $workflowService->recordJobAlteration(
                            job: $job,
                            sourceProductId: $job->manufacturing_product_id ?? $targetPId,
                            sourceQty: $altQty,
                            targetProductId: $targetPId,
                            targetQty: $altQty,
                            reason: $this->discrepancyRemarks ?: "Job Discrepancy Alteration",
                            targetPatternId: $targetPat
                        );
                    }
                }
            });
        } catch (Exception $e) {
            $this->addError('discrepancyTotal', $e->getMessage());
            return;
        }

        $this->dispatch('close-modal', 'discrepancy-resolution-modal');
        $this->dispatch('toast', message: "Discrepancy for Job {$job->job_code} successfully resolved!", type: 'success');
    }

    public function openBatchDesignModal(int $batchId): void
    {
        $this->resetValidation();
        $batch = \App\Models\ProductionBatch::with(['jobs.manufacturingProduct', 'manufacturingProduct'])->findOrFail($batchId);
        $this->selectedBatchDbId = $batch->id;
        $this->selectedBatchCode = $batch->batch_code;
        $this->batchDesignOptions = $batch->getDesignIdsWithProductCounts();
        $this->selectedDesignId = null;

        $this->dispatch('open-modal', 'select-batch-design-modal');
    }

    public function selectDesignForConversion(string $designId): void
    {
        $this->selectedDesignId = $designId;
        $this->dispatch('close-modal', 'select-batch-design-modal');
        $this->prepareBatchConversionWizard();
    }

    public function prepareBatchConversionWizard(): void
    {
        if (!$this->selectedBatchDbId || !$this->selectedDesignId) {
            return;
        }

        $batch = \App\Models\ProductionBatch::with(['jobs.manufacturingProduct', 'manufacturingProduct'])->find($this->selectedBatchDbId);
        if (!$batch) return;

        // Fetch available spare products matching this Design ID
        $spares = \App\Models\SpareProduct::with(['manufacturingProduct', 'productionBatch'])
            ->where('design_id', $this->selectedDesignId)
            ->get()
            ->filter(fn($sp) => $sp->available_quantity > 0);

        $this->availableSpareProducts = $spares->map(fn($sp) => [
            'id' => $sp->id,
            'manufacturing_product_id' => $sp->manufacturing_product_id,
            'product_name' => $sp->manufacturingProduct?->name ?? 'Spare Item',
            'available_qty' => $sp->available_quantity,
            'source_batch' => $sp->productionBatch?->batch_code ?? 'Prev Batch',
            'qty_to_use' => 0,
        ])->toArray();

        $this->selectedSpareProductAllocations = [];

        $this->newProductImage = null;
        $fabricPhoto = \App\Models\InventoryBale::where('design_number', $this->selectedDesignId)
            ->whereNotNull('photo_path')->where('photo_path', '!=', '')->latest()->value('photo_path')
            ?? \App\Models\InventoryBaleItem::where('design_number', $this->selectedDesignId)
            ->whereNotNull('photo_path')->where('photo_path', '!=', '')->latest()->value('photo_path')
            ?? \App\Models\InventoryBale::whereNotNull('photo_path')->where('photo_path', '!=', '')->latest()->value('photo_path');
        $this->imageOptionMode = $fabricPhoto ? 'use_fabric' : 'none';

        // Auto-select leaf category matching exact batch manufacturing products
        $batchMfgProductIds = $batch->jobs->pluck('manufacturing_product_id')->filter()->map(fn($id) => (int)$id)->unique()->values()->toArray();
        $leafCatService = app(\App\Services\Catalog\CategoryService::class);
        $leafCategories = $leafCatService->getLeafCategories(manufacturedOnly: true, matchingMfgProductIds: $batchMfgProductIds);

        $this->selectedCategoryIdForBatchConv = $leafCategories->first()?->id;

        $this->recalculateBatchConversionMaxSets();

        $this->dispatch('open-modal', 'batch-conversion-wizard-modal');
    }

    public function updatedSelectedCategoryIdForBatchConv(): void
    {
        $this->recalculateBatchConversionMaxSets();
    }

    public function toggleAddAllSpareStock(int $index): void
    {
        if (isset($this->availableSpareProducts[$index])) {
            $avail = $this->availableSpareProducts[$index]['available_qty'];
            $current = $this->availableSpareProducts[$index]['qty_to_use'];
            $this->availableSpareProducts[$index]['qty_to_use'] = $current > 0 ? 0 : $avail;
            $this->recalculateBatchConversionMaxSets();
        }
    }

    public function updatedAvailableSpareProducts(): void
    {
        $this->recalculateBatchConversionMaxSets();
    }

    public function recalculateBatchConversionMaxSets(): void
    {
        if (!$this->selectedBatchDbId || !$this->selectedCategoryIdForBatchConv) {
            $this->prefilledTargetSets = 1;
            return;
        }

        $feProduct = \App\Models\FrontEndProduct::with(['components.manufacturingProduct'])
            ->where('category_id', $this->selectedCategoryIdForBatchConv)
            ->first();

        if (!$feProduct || $feProduct->components->isEmpty()) {
            $this->prefilledTargetSets = 1;
            return;
        }

        $batch = \App\Models\ProductionBatch::with('jobs')->find($this->selectedBatchDbId);
        $possibleSets = [];

        foreach ($feProduct->components as $comp) {
            $mfgId = $comp->manufacturing_product_id;
            $reqPerSet = max(1, (int) $comp->quantity);

            // Sum batch output for this component
            $batchQty = 0;
            if ($batch) {
                foreach ($batch->jobs as $bJob) {
                    if ($bJob->manufacturing_product_id == $mfgId) {
                        $batchQty += $bJob->remaining_unconverted_quantity;
                    }
                }
            }

            // Sum selected spare stock for this component
            $spareQty = 0;
            foreach ($this->availableSpareProducts as $sp) {
                if (($sp['manufacturing_product_id'] ?? 0) == $mfgId) {
                    $spareQty += max(0, intval($sp['qty_to_use'] ?? 0));
                }
            }

            $totalCompAvail = $batchQty + $spareQty;
            $setsPossible = (int) floor($totalCompAvail / $reqPerSet);
            $possibleSets[] = $setsPossible;
        }

        $this->prefilledTargetSets = !empty($possibleSets) ? max(1, (int) min($possibleSets)) : 1;
    }

    public function getBatchConversionSummaryProperty(): array
    {
        if (!$this->selectedBatchDbId || !$this->selectedCategoryIdForBatchConv) {
            return ['rows' => [], 'hasLeftovers' => false, 'leftoverItems' => [], 'targetSets' => 0];
        }

        $feProduct = \App\Models\FrontEndProduct::with(['components.manufacturingProduct'])
            ->where('category_id', $this->selectedCategoryIdForBatchConv)
            ->first();

        if (!$feProduct || $feProduct->components->isEmpty()) {
            return ['rows' => [], 'hasLeftovers' => false, 'leftoverItems' => [], 'targetSets' => 0];
        }

        $batch = \App\Models\ProductionBatch::with('jobs.manufacturingProduct')->find($this->selectedBatchDbId);
        $targetSets = max(1, intval($this->prefilledTargetSets));

        $rows = [];
        $leftoverItems = [];

        foreach ($feProduct->components as $comp) {
            $mfgProduct = $comp->manufacturingProduct;
            $mfgId = $comp->manufacturing_product_id;
            $reqPerSet = max(1, (int) $comp->quantity);
            $totalReq = $reqPerSet * $targetSets;

            $batchQty = 0;
            if ($batch) {
                foreach ($batch->jobs as $bJob) {
                    if ($bJob->manufacturing_product_id == $mfgId) {
                        $batchQty += $bJob->remaining_unconverted_quantity;
                    }
                }
            }

            $spareQtyUsed = 0;
            foreach ($this->availableSpareProducts as $sp) {
                if (($sp['manufacturing_product_id'] ?? 0) == $mfgId) {
                    $spareQtyUsed += max(0, intval($sp['qty_to_use'] ?? 0));
                }
            }

            $totalAvail = $batchQty + $spareQtyUsed;
            $consumed = min($totalAvail, $totalReq);
            $leftover = max(0, $totalAvail - $consumed);

            $rows[] = [
                'manufacturing_product' => $mfgProduct?->name ?? 'Manufacturing Product',
                'req_per_set' => $reqPerSet,
                'total_required' => $totalReq,
                'batch_qty' => $batchQty,
                'spare_qty' => $spareQtyUsed,
                'total_available' => $totalAvail,
                'consumed' => $consumed,
                'leftover' => $leftover,
            ];

            if ($leftover > 0) {
                $leftoverItems[] = [
                    'name' => $mfgProduct?->name ?? 'Manufacturing Product',
                    'leftover_qty' => $leftover,
                ];
            }
        }

        return [
            'rows' => $rows,
            'hasLeftovers' => !empty($leftoverItems),
            'leftoverItems' => $leftoverItems,
            'targetSets' => $targetSets,
            'categoryName' => $feProduct->name ?? 'Storefront Category',
        ];
    }

    public function processBatchConversionSubmit(): void
    {
        if (!$this->selectedBatchDbId || !$this->selectedCategoryIdForBatchConv) {
            $this->addError('selectedCategoryIdForBatchConv', 'Please select a valid Category.');
            return;
        }

        if ($this->prefilledTargetSets <= 0) {
            $this->addError('prefilledTargetSets', 'Target sets must be at least 1.');
            return;
        }

        try {
            $spareSelections = [];
            foreach ($this->availableSpareProducts as $sp) {
                $qtyToUse = intval($sp['qty_to_use'] ?? 0);
                if ($qtyToUse > 0) {
                    $spareSelections[] = [
                        'spare_product_id' => $sp['id'],
                        'quantity_used' => $qtyToUse,
                    ];
                }
            }

            $productImagePath = null;
            $reuseCuttingPhoto = false;

            if ($this->imageOptionMode === 'upload' && $this->newProductImage) {
                $storedPath = $this->newProductImage->store('products', 'public');
                $productImagePath = 'storage/' . $storedPath;
            } elseif ($this->imageOptionMode === 'use_fabric') {
                $reuseCuttingPhoto = true;
            }

            $conversionService = resolve(FinishedGoodsConversionService::class);
            $fgBatch = $conversionService->convertCategoryToFinishedGoods([
                'category_id' => $this->selectedCategoryIdForBatchConv,
                'target_qty' => $this->prefilledTargetSets,
                'design_type' => 'new',
                'design_id' => $this->selectedDesignId ?: 'DEFAULT',
                'production_batch_id' => $this->selectedBatchDbId,
                'spare_stock_selections' => $spareSelections,
                'product_image' => $productImagePath,
                'reuse_cutting_photo' => $reuseCuttingPhoto,
                'notes' => "Converted from Production Batch Code {$this->selectedBatchCode} with Design ID {$this->selectedDesignId}",
            ]);

            $this->dispatch('close-modal', 'batch-conversion-wizard-modal');
            $this->dispatch('toast', message: "Production Batch {$this->selectedBatchCode} converted to Storefront Lot {$fgBatch->barcode}! Created {$fgBatch->converted_qty} set(s). Any leftover items saved to Spare Products.", type: 'success');

            $this->openPrintBarcodeModal($fgBatch->id);
        } catch (\Exception $e) {
            $this->addError('prefilledTargetSets', $e->getMessage());
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $batchRecord = null;
        if (!empty($this->batchCode)) {
            $batchRecord = \App\Models\ProductionBatch::where('batch_code', $this->batchCode)->first();
        }

        $jobs = ProductionJob::where(function ($query) use ($batchRecord) {
                $query->where('production_batch_id', $this->batchCode);
                if ($batchRecord) {
                    $query->orWhere('production_batch_db_id', $batchRecord->id);
                }
            })
            ->orWhere('job_code', $this->batchCode)
            ->with(['manufacturingProduct', 'pattern', 'factorySupervisor', 'batch.factorySupervisor', 'supervisor', 'stageExecutions.task', 'allocations', 'wastages', 'alterations'])
            ->orderBy('created_at', 'asc')
            ->get();

        $discrepancyJobs = $jobs->filter(fn($j) => $j->has_unresolved_discrepancy);
        $activeDiscrepancyJob = $this->discrepancyJobId ? ProductionJob::with(['manufacturingProduct', 'pattern'])->find($this->discrepancyJobId) : null;
        $allProducts = ManufacturingProduct::with('patterns')->get();

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
        $packagingRawMaterials = \App\Models\RawMaterial::packagingOnly()->orderBy('name')->get();
        if ($packagingRawMaterials->isEmpty()) {
            $packagingRawMaterials = \App\Models\RawMaterial::orderBy('name')->get();
        }

        $firstJob = $jobs->first();
        $product = $firstJob?->manufacturingProduct;
        $supervisor = $firstJob?->factorySupervisor 
            ?? $firstJob?->batch?->factorySupervisor 
            ?? $firstJob?->supervisor;
        
        $batchDbId = $firstJob?->production_batch_db_id ?? $batchRecord?->id;
        if (!$batchRecord && $batchDbId) {
            $batchRecord = \App\Models\ProductionBatch::find($batchDbId);
        }

        $isBatchComplete = $batchRecord ? $batchRecord->isFullyCompleted() : false;
        $isBatchConverted = $batchRecord ? (bool)$batchRecord->is_converted : false;

        $unconvertedSum = $jobs->sum(fn($j) => $j->remaining_unconverted_quantity);
        $totalProducedSum = $jobs->sum(fn($j) => $j->total_produced_quantity);
        $convertedSum = $jobs->sum(fn($j) => $j->converted_quantity);

        $convertedFgBatches = \App\Models\FinishedGoodsBatch::with(['frontEndProduct', 'creator'])
            ->where(function ($query) use ($batchDbId, $jobs) {
                $query->whereHas('items', function ($q) use ($batchDbId, $jobs) {
                    if ($batchDbId) {
                        $q->where('production_batch_id', $batchDbId);
                    }
                    if ($jobs->isNotEmpty()) {
                        $q->orWhereIn('production_job_id', $jobs->pluck('id')->toArray());
                    }
                });
                if (!empty($this->batchCode)) {
                    $query->orWhere('barcode', 'like', "%{$this->batchCode}%")
                          ->orWhere('notes', 'like', "%{$this->batchCode}%");
                }
            })
            ->latest()
            ->get()
            ->unique('id');

        $activePrintBatch = $this->activePrintBatchId ? \App\Models\FinishedGoodsBatch::with('frontEndProduct')->find($this->activePrintBatchId) : null;

        return view('livewire.admin.production.batch-jobs-detail-page', [
            'jobs' => $jobs,
            'discrepancyJobs' => $discrepancyJobs,
            'activeDiscrepancyJob' => $activeDiscrepancyJob,
            'allProducts' => $allProducts,
            'firstJob' => $firstJob,
            'product' => $product,
            'supervisor' => $supervisor,
            'batchDbId' => $batchDbId,
            'batchRecord' => $batchRecord,
            'isBatchComplete' => $isBatchComplete,
            'isBatchConverted' => $isBatchConverted,
            'unconvertedSum' => $unconvertedSum,
            'totalProducedSum' => $totalProducedSum,
            'convertedSum' => $convertedSum,
            'completedJobsForPicker' => $completedJobsForPicker,
            'storefrontProducts' => $storefrontProducts,
            'packagingRawMaterials' => $packagingRawMaterials,
            'conversionSummary' => $this->conversionSummary,
            'convertedFgBatches' => $convertedFgBatches,
            'activePrintBatch' => $activePrintBatch,
        ])->title("Batch Jobs — {$this->batchCode}");
    }
}
