<?php

namespace App\Livewire\Admin\Orders;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Services\Order\AdminOrderService;

#[Layout('components.admin.layout')]
class OrderIndexPage extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(except: 'all')]
    public string $checkout_method = 'all';

    #[Url(except: 'all')]
    public string $payment_status = 'all';

    #[Url(except: 'all')]
    public string $credit_status = 'all';

    #[Url(except: '')]
    public string $date_from = '';

    #[Url(except: '')]
    public string $date_to = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingCheckoutMethod()
    {
        $this->resetPage();
    }

    public function updatingPaymentStatus()
    {
        $this->resetPage();
    }

    public function updatingCreditStatus()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'status', 'checkout_method', 'payment_status', 'credit_status', 'date_from', 'date_to']);
        $this->resetPage();
    }

    public function render(AdminOrderService $adminOrderService)
    {
        $filters = [
            'search' => $this->search,
            'status' => $this->status,
            'checkout_method' => $this->checkout_method,
            'payment_status' => $this->payment_status,
            'credit_status' => $this->credit_status,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];

        $orders = $adminOrderService->listOrders($filters);
        $stats = $adminOrderService->getDashboardStats($filters);

        return view('livewire.admin.orders.order-index-page', [
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }
}
