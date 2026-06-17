<?php

namespace App\Livewire\Admin\Credit;

use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Services\Credit\CreditExportService;
use App\Services\Credit\CreditManagementService;
use App\Services\Credit\CustomerCreditService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Exception;

#[Layout('components.admin.layout')]
class CreditManagementPage extends Component
{
    use WithPagination;

    // Filters
    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $level_id = '';

    #[Url(history: true)]
    public string $credit_status = '';

    #[Url(history: true)]
    public string $allow_beyond_limit = '';

    #[Url(history: true)]
    public string $credit_hold = '';

    #[Url(history: true)]
    public string $sort_field = 'company_name';

    #[Url(history: true)]
    public string $sort_order = 'asc';

    // UI/Modal state
    public ?int $selectedCustomerId = null;
    
    // Form data
    public array $limitForm = [
        'credit_limit' => '',
        'note' => '',
    ];

    public array $paymentForm = [
        'amount' => '',
        'note' => '',
    ];

    public array $adjustForm = [
        'amount' => '',
        'direction' => 'increase',
        'note' => '',
    ];

    public array $holdForm = [
        'reason' => '',
    ];

    public array $releaseForm = [
        'note' => '',
    ];

    // Reset pagination when filters change
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingLevelId(): void { $this->resetPage(); }
    public function updatingCreditStatus(): void { $this->resetPage(); }
    public function updatingAllowBeyondLimit(): void { $this->resetPage(); }
    public function updatingCreditHold(): void { $this->resetPage(); }

    public function sort(string $field): void
    {
        if ($this->sort_field === $field) {
            $this->sort_order = $this->sort_order === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort_field = $field;
            $this->sort_order = 'asc';
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'level_id', 'credit_status', 'allow_beyond_limit', 'credit_hold', 'sort_field', 'sort_order']);
    }

    // Modal Triggers
    public function openLimitModal(int $id): void
    {
        $customer = Customer::findOrFail($id);
        $this->selectedCustomerId = $id;
        $this->limitForm = [
            'credit_limit' => $customer->credit_limit,
            'note' => '',
        ];
        $this->dispatch('open-modal', 'edit-credit');
    }

    public function openPaymentModal(int $id): void
    {
        $this->selectedCustomerId = $id;
        $this->paymentForm = [
            'amount' => '',
            'note' => '',
        ];
        $this->dispatch('open-modal', 'record-payment');
    }

    public function openAdjustModal(int $id): void
    {
        $this->selectedCustomerId = $id;
        $this->adjustForm = [
            'amount' => '',
            'direction' => 'increase',
            'note' => '',
        ];
        $this->dispatch('open-modal', 'adjust-outstanding');
    }

    public function openHoldModal(int $id): void
    {
        $this->selectedCustomerId = $id;
        $this->holdForm = [
            'reason' => '',
        ];
        $this->dispatch('open-modal', 'place-hold');
    }

    public function openReleaseModal(int $id): void
    {
        $this->selectedCustomerId = $id;
        $this->releaseForm = [
            'note' => '',
        ];
        $this->dispatch('open-modal', 'release-hold');
    }

    public function openLedgerModal(int $id): void
    {
        $this->selectedCustomerId = $id;
        $this->dispatch('open-modal', 'view-ledger');
    }

    // Actions
    public function saveLimit(CustomerCreditService $creditService): void
    {
        $customer = Customer::findOrFail($this->selectedCustomerId);
        
        $this->validate([
            'limitForm.credit_limit' => 'required|numeric|min:0',
            'limitForm.note' => 'nullable|string|max:500',
        ]);

        try {
            $creditService->updateCreditLimit(
                $customer,
                (float) $this->limitForm['credit_limit'],
                Auth::user(),
                $this->limitForm['note']
            );

            $this->dispatch('close-modal', 'edit-credit');
            $this->dispatch('toast', message: 'Credit limit updated successfully.', type: 'success');
        } catch (Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function savePayment(CustomerCreditService $creditService): void
    {
        $customer = Customer::findOrFail($this->selectedCustomerId);

        $this->validate([
            'paymentForm.amount' => 'required|numeric|min:0.01',
            'paymentForm.note' => 'nullable|string|max:500',
        ]);

        try {
            $creditService->recordPayment(
                $customer,
                (float) $this->paymentForm['amount'],
                Auth::user(),
                $this->paymentForm['note']
            );

            $this->dispatch('close-modal', 'record-payment');
            $this->dispatch('toast', message: 'Payment recorded successfully.', type: 'success');
        } catch (Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function saveAdjust(CustomerCreditService $creditService): void
    {
        $customer = Customer::findOrFail($this->selectedCustomerId);

        $this->validate([
            'adjustForm.amount' => 'required|numeric|min:0.01',
            'adjustForm.direction' => 'required|in:increase,decrease',
            'adjustForm.note' => 'nullable|string|max:500',
        ]);

        try {
            $creditService->adjustOutstanding(
                $customer,
                (float) $this->adjustForm['amount'],
                $this->adjustForm['direction'],
                Auth::user(),
                $this->adjustForm['note']
            );

            $this->dispatch('close-modal', 'adjust-outstanding');
            $this->dispatch('toast', message: 'Outstanding balance adjusted successfully.', type: 'success');
        } catch (Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function toggleBeyondLimit(int $id, CustomerCreditService $creditService): void
    {
        $customer = Customer::findOrFail($id);
        $newVal = !$customer->allow_credit_beyond_limit;

        try {
            $creditService->toggleCreditBeyondLimit(
                $customer,
                $newVal,
                Auth::user(),
                "Toggled allow_credit_beyond_limit to " . ($newVal ? 'true' : 'false')
            );
            $this->dispatch('toast', message: 'Credit privilege updated successfully.', type: 'success');
        } catch (Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function saveHold(CustomerCreditService $creditService): void
    {
        $customer = Customer::findOrFail($this->selectedCustomerId);

        $this->validate([
            'holdForm.reason' => 'required|string|max:500',
        ]);

        try {
            $creditService->setCreditHold(
                $customer,
                Auth::user(),
                $this->holdForm['reason']
            );

            $this->dispatch('close-modal', 'place-hold');
            $this->dispatch('toast', message: 'Credit account placed on hold successfully.', type: 'success');
        } catch (Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function saveRelease(CustomerCreditService $creditService): void
    {
        $customer = Customer::findOrFail($this->selectedCustomerId);

        $this->validate([
            'releaseForm.note' => 'nullable|string|max:500',
        ]);

        try {
            $creditService->releaseCreditHold(
                $customer,
                Auth::user(),
                $this->releaseForm['note']
            );

            $this->dispatch('close-modal', 'release-hold');
            $this->dispatch('toast', message: 'Credit hold released successfully.', type: 'success');
        } catch (Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function export(CreditExportService $exportService)
    {
        return $exportService->exportCsv([
            'search' => $this->search,
            'level_id' => $this->level_id,
            'credit_status' => $this->credit_status,
            'allow_beyond_limit' => $this->allow_beyond_limit,
            'credit_hold' => $this->credit_hold,
        ]);
    }

    public function render(CreditManagementService $managementService)
    {
        $filters = [
            'search' => $this->search,
            'level_id' => $this->level_id,
            'credit_status' => $this->credit_status,
            'allow_beyond_limit' => $this->allow_beyond_limit,
            'credit_hold' => $this->credit_hold,
            'sort_field' => $this->sort_field,
            'sort_order' => $this->sort_order,
        ];

        $stats = $managementService->getStats();
        $customers = $managementService->list($filters, 10);
        $customerLevels = CustomerLevel::active()->ordered()->get();

        $selectedCustomer = $this->selectedCustomerId 
            ? Customer::with(['level', 'user', 'creditHoldBy'])->find($this->selectedCustomerId)
            : null;

        $ledgerEntries = $selectedCustomer 
            ? $managementService->getLedgerForCustomer($selectedCustomer, [], 5) 
            : null;

        return view('livewire.admin.credit.credit-management-page', [
            'stats' => $stats,
            'customers' => $customers,
            'customerLevels' => $customerLevels,
            'selectedCustomer' => $selectedCustomer,
            'ledgerEntries' => $ledgerEntries,
        ]);
    }
}
