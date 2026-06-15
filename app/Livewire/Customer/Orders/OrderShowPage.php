<?php

namespace App\Livewire\Customer\Orders;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class OrderShowPage extends Component
{
    public $orderNumber;

    public function mount($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    public function render()
    {
        return view('livewire.customer.orders.order-show-page')
            ->layoutData(['title' => 'Order Details #' . $this->orderNumber]);
    }
}
