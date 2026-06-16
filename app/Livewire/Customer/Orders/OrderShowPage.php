<?php

namespace App\Livewire\Customer\Orders;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Order\OrderService;

#[Layout('components.customer.layout')]
class OrderShowPage extends Component
{
    public $orderNumber;
    public $order = null;

    public function mount($orderNumber, OrderService $orderService)
    {
        $this->orderNumber = $orderNumber;
        $this->order = $orderService->getOrderForCustomer(auth()->user(), $orderNumber);

        if (!$this->order) {
            abort(404, 'Order not found.');
        }
    }

    public function render()
    {
        return view('livewire.customer.orders.order-show-page')
            ->layoutData(['title' => 'Order Details #' . $this->orderNumber]);
    }
}
