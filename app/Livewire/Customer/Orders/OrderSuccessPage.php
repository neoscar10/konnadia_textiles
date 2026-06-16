<?php

namespace App\Livewire\Customer\Orders;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class OrderSuccessPage extends Component
{
    public $orderData = null;

    public function mount()
    {
        $this->orderData = session('order_success');

        if (!$this->orderData) {
            return redirect()->route('customer.orders.index');
        }
    }

    public function render()
    {
        return view('livewire.customer.orders.order-success-page')
            ->layoutData(['title' => 'Order Placed Successfully']);
    }
}
