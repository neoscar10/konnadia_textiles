<?php

namespace App\Livewire\Customer\Orders;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class OrderSuccessPage extends Component
{
    public function render()
    {
        return view('livewire.customer.orders.order-success-page')
            ->layoutData(['title' => 'Order Placed Successfully']);
    }
}
