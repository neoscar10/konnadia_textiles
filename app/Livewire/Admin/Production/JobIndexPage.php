<?php

namespace App\Livewire\Admin\Production;

use App\Models\ProductionJob;
use App\Models\ManufacturingProduct;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class JobIndexPage extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public string $statusFilter = '';

    // Create Modal Properties
    public $production_batch_id = '';
    public $manufacturing_product_id = '';
    public $target_quantity = 100;
    public $status = 'in_progress';
    public $notes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->reset(['manufacturing_product_id', 'notes']);
        $this->target_quantity = 100;
        $this->status = 'in_progress';
        
        // Default batch ID suggestion
        $latestId = ProductionJob::max('id') ?? 0;
        $this->production_batch_id = 'PB-2026-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);

        $this->dispatch('open-modal', 'create-job-modal');
    }

    public function saveJob(): void
    {
        $this->validate([
            'production_batch_id' => 'nullable|string|max:100',
            'manufacturing_product_id' => 'required|exists:manufacturing_products,id',
            'target_quantity' => 'required|numeric|min:1',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:1000',
        ], [
            'manufacturing_product_id.required' => 'Please select a manufacturing product.',
            'target_quantity.min' => 'Target quantity must be at least 1 unit.',
        ]);

        $job = ProductionJob::create([
            'production_batch_id' => $this->production_batch_id,
            'manufacturing_product_id' => $this->manufacturing_product_id,
            'target_quantity' => $this->target_quantity,
            'status' => $this->status,
            'notes' => $this->notes,
        ]);

        $this->dispatch('close-modal', 'create-job-modal');
        $this->dispatch('toast', message: "Production Job {$job->job_code} created successfully!", type: 'success');

        // Redirect directly to Job Detail page to start managing stages
        redirect()->route('admin.production.jobs.show', $job->id);
    }

    public function render()
    {
        $query = ProductionJob::with(['manufacturingProduct', 'allocations']);

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

        return view('livewire.admin.production.job-index-page', [
            'jobs' => $jobs,
            'allProducts' => $allProducts,
        ])->title('Production Jobs Hub');
    }
}
