<?php

namespace App\Livewire\Admin\Production;

use App\Models\JobLaborAllocation;
use App\Models\Labor;
use App\Models\ProductionJob;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class TrackingHistory extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public string $paymentMethodFilter = '';
    public string $jobFilter = '';
    public string $workerFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    
    public function updatingPaymentMethodFilter(): void
    {
        $this->resetPage();
    }

    public function updatingJobFilter(): void
    {
        $this->resetPage();
    }

    public function updatingWorkerFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = JobLaborAllocation::with(['labor', 'task', 'manufacturingProduct']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('job_id', 'like', "%{$this->search}%")
                  ->orWhere('production_batch_id', 'like', "%{$this->search}%")
                  ->orWhereHas('labor', fn($l) => $l->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
                  ->orWhereHas('task', fn($t) => $t->where('name', 'like', "%{$this->search}%"));
            });
        }

        if (!empty($this->paymentMethodFilter)) {
            $query->whereHas('labor', fn($l) => $l->where('payment_method', $this->paymentMethodFilter));
        }

        if (!empty($this->jobFilter)) {
            $query->where('job_id', $this->jobFilter);
        }

        if (!empty($this->workerFilter)) {
            $query->where('labor_id', $this->workerFilter);
        }

        if (!empty($this->dateFrom)) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $allocations = $query->orderBy('created_at', 'desc')->paginate(15);
        $workers = Labor::orderBy('name')->get();
        $jobs = ProductionJob::select('job_code')->orderBy('created_at', 'desc')->get();

        return view('livewire.admin.production.tracking-history', [
            'allocations' => $allocations,
            'workers' => $workers,
            'jobs' => $jobs,
        ])->title('Labor Production Tracking History');
    }
}
