<?php

namespace App\Livewire\Admin\Orders;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class OrderIndexPage extends Component
{
    public function render()
    {
        return view('livewire.admin.orders.order-index-page');
    }
}
