<?php

namespace App\Livewire\Customer\Cart;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class SavedCartsPage extends Component
{
    public function render()
    {
        return view('livewire.customer.cart.saved-carts-page')
            ->layoutData(['title' => 'Saved Carts']);
    }
}
