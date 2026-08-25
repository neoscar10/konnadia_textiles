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
    public $production_batch_id = '';
    public $status = 'in_progress';
    public $notes = '';

    // Storefront Conversion Modal Properties
    public ?int $target_product_id = null;
    public string $productSearch = '';
    public int $target_unit_level = 1; // 1 for Unit 1 (Base Pcs), 2 for Unit 2 (Boxes/Packs)
    public string $conversion_notes = '';
    public array $conversionComponents = [];
    public array $conversionPackaging = [];

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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->reset(['notes']);
        $this->status = 'in_progress';
        
        // Default batch ID suggestion
        $latestId = ProductionJob::max('id') ?? 0;
        $this->production_batch_id = 'PB-2026-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);

        $this->dispatch('open-modal', 'create-job-modal');
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
            'production_batch_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $job = ProductionJob::create([
            'production_batch_id' => $this->production_batch_id,
            'manufacturing_product_id' => null,
            'target_quantity' => 0,
            'status' => 'in_progress',
            'notes' => $this->notes,
        ]);

        $this->dispatch('close-modal', 'create-job-modal');
        $this->dispatch('toast', message: "Production Job {$job->job_code} created successfully!", type: 'success');

        // Redirect directly to Job Detail page to start managing stages
        redirect()->route('admin.production.jobs.show', $job->id);
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

        $allProducts = ManufacturingProduct::all();

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
            'paginatedBatches' => $paginatedBatches,
            'allProducts' => $allProducts,
            'completedJobsForPicker' => $completedJobsForPicker,
            'storefrontProducts' => $storefrontProducts,
            'totalCompletedJobsCount' => $totalCompletedJobsCount,
            'totalFinishedUnitsProduced' => $totalFinishedUnitsProduced,
            'totalStorefrontConvertedUnits' => $totalStorefrontConvertedUnits,
            'availableUnconvertedPoolUnits' => $availableUnconvertedPoolUnits,
            'packagingRawMaterials' => $packagingRawMaterials,
            'conversionSummary' => $this->conversionSummary,
        ])->title('Production Jobs Hub');
    }
}
