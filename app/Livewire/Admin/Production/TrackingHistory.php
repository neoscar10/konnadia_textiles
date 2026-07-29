<?php

namespace App\Livewire\Admin\Production;

use App\Models\JobLaborAllocation;
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

    public function updatingSearch(): void
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

        $allocations = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('livewire.admin.production.tracking-history', [
            'allocations' => $allocations,
        ])->title('Labor Production Tracking History');
    }
}
