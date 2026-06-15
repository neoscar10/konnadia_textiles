<?php

namespace App\Livewire\Customer\Orders;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class OrderReviewPage extends Component
{
    public function render()
    {
        return view('livewire.customer.orders.order-review-page')
            ->layoutData(['title' => 'Review Order']);
    }
}
