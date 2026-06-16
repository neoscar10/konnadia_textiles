<?php

namespace App\Livewire\Customer\Orders;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Services\Order\OrderService;

#[Layout('components.customer.layout')]
class OrderIndexPage extends Component
{
    use WithPagination;

    public $search = '';
    public $status = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function setStatus($status)
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function render(OrderService $orderService)
    {
        $orders = $orderService->listOrdersForCustomer(auth()->user(), [
            'search' => $this->search,
            'status' => $this->status,
        ]);

        // Calculate stats
        $user = auth()->user();
        $totalActive = \App\Models\Order::where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->count();
        $pendingReview = \App\Models\Order::where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'pending_payment_verification', 'pending_credit_review'])
            ->count();

        return view('livewire.customer.orders.order-index-page', [
            'orders' => $orders,
            'totalActive' => $totalActive,
            'pendingReview' => $pendingReview,
        ])->layoutData(['title' => 'My Orders']);
    }
}
