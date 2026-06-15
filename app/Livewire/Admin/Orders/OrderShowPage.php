<?php

namespace App\Livewire\Admin\Orders;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class OrderShowPage extends Component
{
    public function render()
    {
        return view('livewire.admin.orders.order-show-page');
    }
}
