<?php

namespace App\Livewire\Customer\Orders;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class OrderIndexPage extends Component
{
    public $search = '';
    public $status = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
    ];

    public function render()
    {
        return view('livewire.customer.orders.order-index-page')
            ->layoutData(['title' => 'My Orders']);
    }
}
