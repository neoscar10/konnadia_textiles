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
    public ?int $target_combination_id = null;
    public int $assembled_sets_quantity = 1;
    public string $conversion_notes = '';
    public array $conversionComponents = [];

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
        $this->target_combination_id = null;
        $this->assembled_sets_quantity = 1;
        $this->conversion_notes = '';
        $this->conversionComponents = [];

        if ($preSelectedJobId) {
            $this->conversionComponents[] = [
                'production_job_id' => $preSelectedJobId,
                'quantity_per_set' => 1,
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
        ];
    }

    public function removeConversionComponentRow(int $index): void
    {
        unset($this->conversionComponents[$index]);
        $this->conversionComponents = array_values($this->conversionComponents);
    }

    public function processConversion(): void
    {
        if (empty($this->target_product_id) && empty($this->target_combination_id)) {
            $this->addError('target_product_id', 'Please select a Storefront Product or Variant.');
            return;
        }

        if (intval($this->assembled_sets_quantity) <= 0) {
            $this->addError('assembled_sets_quantity', 'Quantity of sets to convert must be at least 1.');
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
                $this->addError("conversionComponents.{$idx}.quantity_per_set", 'Quantity per set must be at least 1.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        try {
            $conversionService = resolve(FinishedGoodsConversionService::class);
            $bundle = $conversionService->convertJobsToStorefrontBundle(
                $this->target_product_id ?: null,
                $this->target_combination_id ?: null,
                intval($this->assembled_sets_quantity),
                $this->conversionComponents,
                $this->conversion_notes ?: "Converted from Production Jobs Hub"
            );

            $this->dispatch('close-modal', 'storefront-conversion-modal');
            $this->dispatch('toast', message: "Successfully converted {$this->assembled_sets_quantity} storefront set(s) under Bundle {$bundle->bundle_code}!", type: 'success');
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

    public function render()
    {
        $query = ProductionJob::with(['manufacturingProduct', 'stageExecutions.task', 'allocations']);

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

        $jobs = $query->orderBy('created_at', 'desc')->paginate(10);
        $allProducts = ManufacturingProduct::all();

        // Eligible Completed Jobs for Conversion Picker
        $completedJobsForPicker = ProductionJob::with(['manufacturingProduct'])
            ->get()
            ->filter(fn($j) => $j->status === 'completed' || $j->remaining_unconverted_quantity > 0);

        // Storefront Products & Variants for Target Picker
        $storefrontProducts = Product::where('is_active', true)->with('combinations')->orderBy('name')->get();

        // Summary Statistics
        $totalCompletedJobsCount = ProductionJob::all()->filter(fn($j) => $j->status === 'completed')->count();
        $totalFinishedUnitsProduced = ProductionJob::all()->sum(fn($j) => $j->completed_quantity);
        $totalStorefrontConvertedUnits = ProductionJob::sum('converted_quantity');
        $availableUnconvertedPoolUnits = max(0, $totalFinishedUnitsProduced - $totalStorefrontConvertedUnits);

        return view('livewire.admin.production.job-index-page', [
            'jobs' => $jobs,
            'allProducts' => $allProducts,
            'completedJobsForPicker' => $completedJobsForPicker,
            'storefrontProducts' => $storefrontProducts,
            'totalCompletedJobsCount' => $totalCompletedJobsCount,
            'totalFinishedUnitsProduced' => $totalFinishedUnitsProduced,
            'totalStorefrontConvertedUnits' => $totalStorefrontConvertedUnits,
            'availableUnconvertedPoolUnits' => $availableUnconvertedPoolUnits,
        ])->title('Production Jobs Hub');
    }
}
