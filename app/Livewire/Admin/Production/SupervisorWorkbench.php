<?php

namespace App\Livewire\Admin\Production;

use App\Models\ProductionBatch;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class SupervisorWorkbench extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public string $statusFilter = '';

    public $selectedBatchId = null;

    public function mount()
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor']) && !auth()->user()->can('manage_labor')) {
            abort(403, 'Unauthorized access to supervisor workbench.');
        }

        $firstBatch = ProductionBatch::first();
        if ($firstBatch) {
            $this->selectedBatchId = $firstBatch->id;
        }
    }

    public function selectBatch($batchId): void
    {
        $this->selectedBatchId = $batchId;
    }

    public function render()
    {
        $query = ProductionBatch::with(['manufacturingProduct.tasks', 'job.stageExecutions.task', 'supervisor']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('batch_code', 'like', "%{$this->search}%")
                  ->orWhereHas('manufacturingProduct', fn($mp) => $mp->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"));
            });
        }

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $batches = $query->orderBy('created_at', 'desc')->paginate(10);
        $selectedBatch = ProductionBatch::with(['manufacturingProduct.tasks', 'job.stageExecutions.task', 'job.allocations.labor', 'supervisor'])->find($this->selectedBatchId);

        // KPI Counts
        $totalWaiting = ProductionBatch::where('status', 'Created')->count();
        $totalInProgress = ProductionBatch::where('status', 'In Progress')->count();
        $totalCompleted = ProductionBatch::where('status', 'Completed')->count();
        $totalUrgent = ProductionBatch::where('priority', 'Urgent')->count();

        return view('livewire.admin.production.supervisor-workbench', [
            'batches' => $batches,
            'selectedBatch' => $selectedBatch,
            'totalWaiting' => $totalWaiting,
            'totalInProgress' => $totalInProgress,
            'totalCompleted' => $totalCompleted,
            'totalUrgent' => $totalUrgent,
        ])->title('Supervisor Workbench | Production Control');
    }
}
