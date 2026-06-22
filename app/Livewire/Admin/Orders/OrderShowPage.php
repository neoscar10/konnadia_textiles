<?php

namespace App\Livewire\Admin\Orders;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Order\AdminOrderService;
use App\Services\Order\OrderStatusService;
use Illuminate\Validation\ValidationException;

#[Layout('components.admin.layout')]
class OrderShowPage extends Component
{
    public $orderNumber;
    public $orderData;

    public bool $showReviewModal = false;
    public bool $showApproveModal = false;
    public bool $showRejectModal = false;
    public bool $showVerifyReceiptModal = false;
    public bool $showRejectReceiptModal = false;
    public bool $showDispatchModal = false;
    public bool $showCancelModal = false;

    public string $adminComment = '';
    public string $rejectionReason = '';

    public function mount($orderNumber, AdminOrderService $adminOrderService)
    {
        $this->orderNumber = $orderNumber;
        $this->loadOrder($adminOrderService);
    }

    protected function loadOrder(AdminOrderService $adminOrderService)
    {
        $this->orderData = $adminOrderService->getOrderDetail($this->orderNumber);
    }

    public function markUnderReview(AdminOrderService $adminOrderService)
    {
        $order = \App\Models\Order::findOrFail($this->orderData['id']);
        $adminOrderService->markUnderReview($order, auth()->user(), $this->adminComment);

        session()->flash('success', 'Order marked as under review successfully.');
        $this->reset(['showReviewModal', 'adminComment']);
        $this->loadOrder($adminOrderService);
    }

    public function verifyReceipt(AdminOrderService $adminOrderService)
    {
        $order = \App\Models\Order::findOrFail($this->orderData['id']);
        $adminOrderService->verifyReceipt($order, auth()->user(), $this->adminComment);

        session()->flash('success', 'Payment receipt verified successfully.');
        $this->reset(['showVerifyReceiptModal', 'adminComment']);
        $this->loadOrder($adminOrderService);
    }

    public function rejectReceipt(AdminOrderService $adminOrderService)
    {
        $this->validate([
            'rejectionReason' => 'required|string|min:5|max:500',
        ], [
            'rejectionReason.required' => 'A rejection reason is required.',
        ]);

        $order = \App\Models\Order::findOrFail($this->orderData['id']);
        $adminOrderService->rejectReceipt($order, auth()->user(), $this->rejectionReason);

        session()->flash('success', 'Payment receipt rejected successfully.');
        $this->reset(['showRejectReceiptModal', 'rejectionReason']);
        $this->loadOrder($adminOrderService);
    }

    public function approveOrder(AdminOrderService $adminOrderService)
    {
        try {
            $order = \App\Models\Order::findOrFail($this->orderData['id']);
            $adminOrderService->approve($order, auth()->user(), $this->adminComment);

            session()->flash('success', 'Order approved successfully.');
            $this->reset(['showApproveModal', 'adminComment']);
        } catch (ValidationException $e) {
            session()->flash('error', $e->getMessage());
            $this->showApproveModal = false;
        }

        $this->loadOrder($adminOrderService);
    }

    public function rejectOrder(AdminOrderService $adminOrderService)
    {
        $this->validate([
            'rejectionReason' => 'required|string|min:5|max:500',
        ], [
            'rejectionReason.required' => 'A rejection reason is required.',
        ]);

        $order = \App\Models\Order::findOrFail($this->orderData['id']);
        $adminOrderService->reject($order, auth()->user(), $this->rejectionReason);

        session()->flash('success', 'Order rejected successfully.');
        $this->reset(['showRejectModal', 'rejectionReason']);
        $this->loadOrder($adminOrderService);
    }

    public function dispatchOrder(AdminOrderService $adminOrderService)
    {
        $order = \App\Models\Order::findOrFail($this->orderData['id']);
        $adminOrderService->dispatch($order, auth()->user(), $this->adminComment);

        session()->flash('success', 'Order marked as dispatched successfully.');
        $this->reset(['showDispatchModal', 'adminComment']);
        $this->loadOrder($adminOrderService);
    }

    public function cancelOrder(AdminOrderService $adminOrderService)
    {
        $order = \App\Models\Order::findOrFail($this->orderData['id']);
        $adminOrderService->cancel($order, auth()->user(), $this->adminComment);

        session()->flash('success', 'Order cancelled successfully.');
        $this->reset(['showCancelModal', 'adminComment']);
        $this->loadOrder($adminOrderService);
    }

    public function render(OrderStatusService $statusService)
    {
        $order = \App\Models\Order::findOrFail($this->orderData['id']);
        $allowedActions = $statusService->getAllowedActions($order, auth()->user());

        return view('livewire.admin.orders.order-show-page', [
            'allowedActions' => $allowedActions,
        ]);
    }
}
