<?php

namespace App\Livewire\Admin\Labor;

use App\Models\Labor;
use App\Models\JobLaborAllocation;
use App\Models\Task;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('components.admin.layout')]
class LaborDetail extends Component
{
    use WithPagination;

    public int $laborId;
    public string $date_from = '';
    public string $date_to = '';
    public string $batch_filter = '';
    public string $task_filter = '';
    public string $search = '';

    protected $queryString = [
        'date_from' => ['except' => ''],
        'date_to' => ['except' => ''],
        'batch_filter' => ['except' => ''],
        'task_filter' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function mount(int $id)
    {
        $this->laborId = $id;
        // Default filter: All time or current month
    }

    public function setPresetFilter(string $preset)
    {
        $this->resetPage();
        if ($preset === 'this_month') {
            $this->date_from = Carbon::now()->startOfMonth()->format('Y-m-d');
            $this->date_to = Carbon::now()->endOfDay()->format('Y-m-d');
        } elseif ($preset === 'last_30') {
            $this->date_from = Carbon::now()->subDays(30)->format('Y-m-d');
            $this->date_to = Carbon::now()->endOfDay()->format('Y-m-d');
        } elseif ($preset === 'this_year') {
            $this->date_from = Carbon::now()->startOfYear()->format('Y-m-d');
            $this->date_to = Carbon::now()->endOfDay()->format('Y-m-d');
        } elseif ($preset === 'all') {
            $this->date_from = '';
            $this->date_to = '';
        }
    }

    public function resetFilters()
    {
        $this->reset(['date_from', 'date_to', 'batch_filter', 'task_filter', 'search']);
        $this->resetPage();
    }

    public function render()
    {
        $labor = Labor::with('tasks')->findOrFail($this->laborId);

        $query = JobLaborAllocation::where('labor_id', $this->laborId)
            ->with(['productionJob.task', 'productionJob.manufacturingProduct', 'inventoryBaleRoll.bale', 'manufacturingProduct']);

        if ($this->date_from) {
            $query->whereDate('created_at', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $query->whereDate('created_at', '<=', $this->date_to);
        }

        if ($this->batch_filter) {
            $query->where(function ($q) {
                $q->where('production_batch_id', 'like', '%' . $this->batch_filter . '%')
                  ->orWhere('job_id', 'like', '%' . $this->batch_filter . '%');
            });
        }

        if ($this->task_filter) {
            $query->where('task_id', $this->task_filter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('job_id', 'like', '%' . $this->search . '%')
                  ->orWhere('production_batch_id', 'like', '%' . $this->search . '%')
                  ->orWhereHas('manufacturingProduct', fn($pq) => $pq->where('name', 'like', '%' . $this->search . '%')->orWhere('code', 'like', '%' . $this->search . '%'));
            });
        }

        // Clone query for stats calculations
        $allAllocations = (clone $query)->get();

        $totalPieces = (int) $allAllocations->sum('quantity_processed');
        $totalDirectWages = (float) $allAllocations->sum('calculated_wage');
        
        $totalJobCostValue = 0.0;
        foreach ($allAllocations as $alloc) {
            $rate = (float) $alloc->piece_rate;
            if ($rate <= 0 && $alloc->manufacturingProduct) {
                $rate = (float) $alloc->manufacturingProduct->getStandardLaborRateForTask($alloc->task_id);
            }
            $totalJobCostValue += round((float)$alloc->quantity_processed * $rate, 2);
        }

        $uniqueBatches = $allAllocations->pluck('production_batch_id')->filter()->unique();
        $uniqueJobs = $allAllocations->pluck('job_id')->filter()->unique();

        // Batch Breakdown
        $batchBreakdown = [];
        $groupedByBatch = $allAllocations->groupBy('production_batch_id');
        foreach ($groupedByBatch as $batchCode => $items) {
            $bPieces = $items->sum('quantity_processed');
            $bWages = $items->sum('calculated_wage');
            $bValuation = 0.0;
            foreach ($items as $it) {
                $r = (float) $it->piece_rate;
                if ($r <= 0 && $it->manufacturingProduct) {
                    $r = (float) $it->manufacturingProduct->getStandardLaborRateForTask($it->task_id);
                }
                $bValuation += round((float)$it->quantity_processed * $r, 2);
            }

            $batchBreakdown[] = [
                'batch_code' => $batchCode ?: 'General Batch',
                'total_pieces' => $bPieces,
                'total_wages' => $bWages,
                'total_valuation' => $bValuation,
                'jobs_count' => $items->pluck('job_id')->unique()->count(),
            ];
        }

        // Paginated list
        $allocations = $query->orderBy('created_at', 'desc')->paginate(15);

        // Fetch all distinct tasks and batches for filter dropdowns
        $availableTasks = Task::where('status', true)->get();
        $availableBatches = JobLaborAllocation::where('labor_id', $this->laborId)
            ->distinct()
            ->pluck('production_batch_id')
            ->filter();

        return view('livewire.admin.labor.labor-detail', [
            'labor' => $labor,
            'allocations' => $allocations,
            'totalPieces' => $totalPieces,
            'totalDirectWages' => $totalDirectWages,
            'totalJobCostValue' => $totalJobCostValue,
            'totalBatchesCount' => $uniqueBatches->count(),
            'totalJobsCount' => $uniqueJobs->count(),
            'batchBreakdown' => $batchBreakdown,
            'availableTasks' => $availableTasks,
            'availableBatches' => $availableBatches,
        ])->title("Worker Profile & Earnings — {$labor->name} ({$labor->code})");
    }
}
