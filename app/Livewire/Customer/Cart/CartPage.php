<?php

namespace App\Livewire\Customer\Cart;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class CartPage extends Component
{
    public function render()
    {
        return view('livewire.customer.cart.cart-page')
            ->layoutData(['title' => 'My Cart']);
    }
}
