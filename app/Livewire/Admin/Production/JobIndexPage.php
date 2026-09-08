<?php

namespace App\Livewire\Admin\Production;

use App\Models\ProductionJob;
use App\Models\ManufacturingProduct;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

use App\Models\Product;
use App\Models\ProductCombination;
use App\Services\Manufacturing\FinishedGoodsConversionService;
use Exception;

#[Layout('components.admin.layout')]
class JobIndexPage extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public string $statusFilter = '';

    // Create Modal Properties
    public $manufacturing_product_id = null;
    public $pattern_id = null;
    public $factory_supervisor_id = null;
    public int $planned_quantity = 200;
    public string $priority = 'Normal';
    public string $notes = '';

    // Storefront Conversion Modal Properties
    public ?int $target_product_id = null;
    public string $productSearch = '';
    public int $target_unit_level = 1; // 1 for Unit 1 (Base Pcs), 2 for Unit 2 (Boxes/Packs)
    public string $conversion_notes = '';
    public array $conversionComponents = [];
    public array $conversionPackaging = [];

    public function mount(): void
    {
        if (empty($this->batchProducts)) {
            $firstProduct = ManufacturingProduct::first();
            $firstPattern = null;
            if ($firstProduct) {
                $patterns = \App\Models\ManufacturingProductPattern::where('manufacturing_product_id', $firstProduct->id)->get();
                $firstPattern = $patterns->firstWhere('is_default', true) ?? $patterns->first();
            }
            $this->batchProducts = [
                [
                    'manufacturing_product_id' => $firstProduct?->id,
                    'pattern_id'               => $firstPattern?->id,
                    'planned_quantity'         => 200,
                ]
            ];
        }
    }

    public function updatedManufacturingProductId(): void
    {
        $this->loadDefaultPattern();
    }

    protected function loadDefaultPattern(): void
    {
        if ($this->manufacturing_product_id) {
            $patterns = \App\Models\ManufacturingProductPattern::where('manufacturing_product_id', $this->manufacturing_product_id)->get();
            $defaultPattern = $patterns->firstWhere('is_default', true) ?? $patterns->first();
            $this->pattern_id = $defaultPattern?->id;
        } else {
            $this->pattern_id = null;
        }
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->reset(['notes']);
        $this->planned_quantity = 200;
        $this->priority = 'Normal';
        
        $firstProduct = ManufacturingProduct::first();
        $firstPattern = null;
        if ($firstProduct) {
            $this->manufacturing_product_id = $firstProduct->id;
            $patterns = \App\Models\ManufacturingProductPattern::where('manufacturing_product_id', $firstProduct->id)->get();
            $firstPattern = $patterns->firstWhere('is_default', true) ?? $patterns->first();
            $this->pattern_id = $firstPattern?->id;
        }

        $firstSupervisor = \App\Models\FactorySupervisor::active()->orderBy('name')->first();
        $this->factory_supervisor_id = $firstSupervisor?->id;

        $this->batchProducts = [
            [
                'manufacturing_product_id' => $firstProduct?->id,
                'pattern_id'               => $firstPattern?->id,
                'planned_quantity'         => 200,
            ]
        ];

        $this->dispatch('open-modal', 'create-job-modal');
    }

    public function addBatchProductRow(): void
    {
        $firstProduct = ManufacturingProduct::first();
        $firstPattern = null;
        if ($firstProduct) {
            $patterns = \App\Models\ManufacturingProductPattern::where('manufacturing_product_id', $firstProduct->id)->get();
            $firstPattern = $patterns->firstWhere('is_default', true) ?? $patterns->first();
        }

        $this->batchProducts[] = [
            'manufacturing_product_id' => $firstProduct?->id,
            'pattern_id'               => $firstPattern?->id,
            'planned_quantity'         => 200,
        ];
    }

    public function removeBatchProductRow(int $index): void
    {
        unset($this->batchProducts[$index]);
        $this->batchProducts = array_values($this->batchProducts);
        if (empty($this->batchProducts)) {
            $this->addBatchProductRow();
        }
    }

    public function updatedBatchProducts($value, $key): void
    {
        if (str_contains($key, 'manufacturing_product_id')) {
            $parts = explode('.', $key);
            $idx = intval($parts[0]);
            $prodId = intval($value);
            if ($prodId) {
                $patterns = \App\Models\ManufacturingProductPattern::where('manufacturing_product_id', $prodId)->get();
                $defaultPattern = $patterns->firstWhere('is_default', true) ?? $patterns->first();
                $this->batchProducts[$idx]['pattern_id'] = $defaultPattern?->id;
            }
        }
    }

    public function openConversionModal(?int $preSelectedJobId = null): void
    {
        $this->resetValidation();
        $this->target_product_id = null;
        $this->productSearch = '';
        $this->target_unit_level = 1;
        $this->conversion_notes = '';
        $this->conversionComponents = [];
        $this->conversionPackaging = [];
        $this->addConversionPackagingRow();

        if ($preSelectedJobId) {
            $job = ProductionJob::find($preSelectedJobId);
            $this->conversionComponents[] = [
                'production_job_id' => $preSelectedJobId,
                'quantity_per_set' => 1,
                'total_pieces_input' => $job ? $job->remaining_unconverted_quantity : 0,
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
        if (empty($this->conversionComponents)) {
            $this->addConversionComponentRow();
        }
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

    public function updatedTargetProductId($value): void
    {
        $this->target_unit_level = 1;
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
        $unitFactor = $this->targetUnitConversionFactor;

        if (empty($this->conversionComponents)) {
            return ['max_sets' => 0, 'effective_base_items' => 0, 'unit_factor' => $unitFactor, 'rows' => []];
        }

        $possibleSets = [];
        $rowDetails = [];

        foreach ($this->conversionComponents as $idx => $comp) {
            $jobId = intval($comp['production_job_id'] ?? 0);
            $job = $jobId ? ProductionJob::with('manufacturingProduct')->find($jobId) : null;
            $ratio = max(1, intval($comp['quantity_per_set'] ?? 1));
            $inputPcs = max(0, intval($comp['total_pieces_input'] ?? 0));

            $sets = $jobId && $inputPcs > 0 ? (int) floor(($inputPcs / $ratio) / $unitFactor) : 0;
            if ($jobId) {
                $possibleSets[] = $sets;
            }

            $rowDetails[$idx] = [
                'job' => $job,
                'ratio' => $ratio,
                'inputPcs' => $inputPcs,
                'setsPossible' => $sets,
            ];
        }

        $maxSets = !empty($possibleSets) ? (int) min($possibleSets) : 0;
        $effectiveBaseItems = intval(round($maxSets * $unitFactor));

        foreach ($rowDetails as $idx => $det) {
            $consumed = $effectiveBaseItems * $det['ratio'];
            $leftover = max(0, $det['inputPcs'] - $consumed);
            $rowDetails[$idx]['consumedPcs'] = $consumed;
            $rowDetails[$idx]['leftoverPcs'] = $leftover;
        }

        return [
            'max_sets' => $maxSets,
            'effective_base_items' => $effectiveBaseItems,
            'unit_factor' => $unitFactor,
            'rows' => $rowDetails,
        ];
    }

    public function processConversion(): void
    {
        if (empty($this->target_product_id)) {
            $this->addError('target_product_id', 'Please select a Storefront Product.');
            return;
        }

        if (empty($this->conversionComponents)) {
            $this->addError('conversionComponents', 'Please add at least one completed production job component.');
            return;
        }

        foreach ($this->conversionComponents as $idx => $comp) {
            if (empty($comp['production_job_id'])) {
                $this->addError("conversionComponents.{$idx}.production_job_id", 'Production Job is required.');
            }
            if (empty($comp['quantity_per_set']) || intval($comp['quantity_per_set']) <= 0) {
                $this->addError("conversionComponents.{$idx}.quantity_per_set", 'Pieces per set must be at least 1.');
            }
            if (empty($comp['total_pieces_input']) || intval($comp['total_pieces_input']) <= 0) {
                $this->addError("conversionComponents.{$idx}.total_pieces_input", 'Pieces to process must be at least 1.');
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
        $maxSets = $summary['max_sets'];
        $effectiveBaseItems = $summary['effective_base_items'];

        if ($maxSets <= 0) {
            $this->addError('conversionComponents', 'Cannot assemble any complete storefront sets with the entered piece quantities and set ratio. Please check your inputs.');
            return;
        }

        try {
            $conversionService = resolve(FinishedGoodsConversionService::class);
            $filteredPackaging = array_filter($this->conversionPackaging, fn($p) => !empty($p['raw_material_id']));

            $unitLabel = $this->target_unit_level === 2 ? "Unit 2" : "Unit 1";

            $bundle = $conversionService->convertJobsToStorefrontBundle(
                intval($this->target_product_id),
                $effectiveBaseItems,
                $this->conversionComponents,
                $this->conversion_notes ?: "Converted {$maxSets} {$unitLabel} set(s) ({$effectiveBaseItems} base pcs) from Production Jobs Hub",
                $filteredPackaging
            );

            $this->dispatch('close-modal', 'storefront-conversion-modal');
            $this->dispatch('toast', message: "Successfully converted {$maxSets} storefront set(s) under Bundle {$bundle->bundle_code}! Storefront stock added: +{$effectiveBaseItems} Pcs", type: 'success');
        } catch (Exception $e) {
            $this->addError('conversionComponents', $e->getMessage());
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function saveJob(): void
    {
        $this->validate([
            'factory_supervisor_id' => 'required|exists:factory_supervisors,id',
            'priority'              => 'required|in:Urgent,Normal,Low',
            'notes'                 => 'nullable|string|max:1000',
            'batchProducts'         => 'required|array|min:1',
            'batchProducts.*.manufacturing_product_id' => 'required|exists:manufacturing_products,id',
            'batchProducts.*.pattern_id'               => 'nullable|exists:manufacturing_product_patterns,id',
            'batchProducts.*.planned_quantity'         => 'required|numeric|min:1',
        ], [
            'factory_supervisor_id.required' => 'Please select a supervisor.',
            'batchProducts.required'         => 'Please add at least one manufacturing product.',
        ]);

        $firstItem = $this->batchProducts[0] ?? ['manufacturing_product_id' => null, 'pattern_id' => null, 'planned_quantity' => 200];
        $totalPlanned = array_sum(array_column($this->batchProducts, 'planned_quantity'));

        $workflowService = resolve(\App\Services\Manufacturing\ProductionWorkflowService::class);
        $response = $workflowService->initiateBatch(
            $firstItem['manufacturing_product_id'],
            $this->factory_supervisor_id,
            $totalPlanned,
            $this->priority,
            $this->notes,
            now()->format('Y-m-d'),
            $firstItem['pattern_id'],
            $this->batchProducts
        );

        $responseData = $response->getData(true);

        if (isset($responseData['success']) && $responseData['success']) {
            $batchCode = $responseData['data']['batch']['batch_code'] ?? 'Batch';
            $jobCount = count($responseData['data']['jobs'] ?? []);
            $this->dispatch('close-modal', 'create-job-modal');
            $this->dispatch('toast', message: "Production Batch {$batchCode} initiated successfully with {$jobCount} Job(s)!", type: 'success');
        } else {
            $errorMessage = $responseData['message'] ?? 'Failed to initiate production batch.';
            $this->addError('factory_supervisor_id', $errorMessage);
        }
    }

    // Batch Conversion Properties
    public ?int $selectedBatchDbId = null;

    public function openBatchConversionModal(string $batchCode): void
    {
        $this->resetValidation();
        $this->target_product_id = null;
        $this->productSearch = '';
        $this->conversion_notes = '';
        $this->conversionComponents = [];
        $this->conversionPackaging = [];
        $this->addConversionPackagingRow();
        $this->addConversionComponentRow();

        $this->dispatch('open-modal', 'storefront-conversion-modal');
    }

    public function render()
    {
        $query = ProductionJob::with(['manufacturingProduct', 'supervisor', 'stageExecutions.task', 'allocations']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('job_code', 'like', "%{$this->search}%")
                  ->orWhere('production_batch_id', 'like', "%{$this->search}%")
                  ->orWhereHas('manufacturingProduct', fn($mp) => $mp->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"));
            });
        }

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $allJobs = $query->orderBy('created_at', 'desc')->get();

        // Group jobs by production_batch_id (or job_code if empty)
        $groupedBatches = $allJobs->groupBy(function ($job) {
            return !empty($job->production_batch_id) ? $job->production_batch_id : $job->job_code;
        });

        // Paginate grouped batches manually
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $perPage = 10;
        $currentPageBatches = $groupedBatches->slice(($page - 1) * $perPage, $perPage);
        $paginatedBatches = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentPageBatches,
            $groupedBatches->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        $allProducts = ManufacturingProduct::with('patterns')->get();
        $selectedProduct = $this->manufacturing_product_id ? ManufacturingProduct::with('tasks')->find($this->manufacturing_product_id) : null;
        $availablePatterns = $this->manufacturing_product_id 
            ? \App\Models\ManufacturingProductPattern::with('tasks')->where('manufacturing_product_id', $this->manufacturing_product_id)->get()
            : collect();
        $selectedPattern = $availablePatterns->firstWhere('id', $this->pattern_id);
        $supervisors = \App\Models\FactorySupervisor::active()->orderBy('name')->get();

        // Eligible Completed Jobs for Conversion Picker
        $completedJobsForPicker = ProductionJob::with(['manufacturingProduct'])
            ->get()
            ->filter(fn($j) => $j->status === 'completed' && $j->remaining_unconverted_quantity > 0);

        // Storefront Products & Variants for Target Picker
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

        // Summary Statistics
        $totalCompletedJobsCount = ProductionJob::all()->filter(fn($j) => $j->status === 'completed')->count();
        $totalFinishedUnitsProduced = ProductionJob::all()->sum(fn($j) => $j->completed_quantity);
        $totalStorefrontConvertedUnits = ProductionJob::sum('converted_quantity');
        $availableUnconvertedPoolUnits = max(0, $totalFinishedUnitsProduced - $totalStorefrontConvertedUnits);

        return view('livewire.admin.production.job-index-page', [
            'batchProducts'                 => $this->batchProducts,
            'paginatedBatches'              => $paginatedBatches,
            'allProducts'                   => $allProducts,
            'selectedProduct'               => $selectedProduct,
            'availablePatterns'             => $availablePatterns,
            'selectedPattern'               => $selectedPattern,
            'supervisors'                   => $supervisors,
            'completedJobsForPicker'        => $completedJobsForPicker,
            'storefrontProducts'            => $storefrontProducts,
            'totalCompletedJobsCount'       => $totalCompletedJobsCount,
            'totalFinishedUnitsProduced'    => $totalFinishedUnitsProduced,
            'totalStorefrontConvertedUnits' => $totalStorefrontConvertedUnits,
            'availableUnconvertedPoolUnits' => $availableUnconvertedPoolUnits,
            'packagingRawMaterials'         => $packagingRawMaterials,
            'conversionSummary'             => $this->conversionSummary,
        ])->title('Production Jobs Hub');
    }
}
