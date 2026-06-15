<?php

namespace App\Livewire\Customer\Categories;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class CategoryIndexPage extends Component
{
    public function render()
    {
        return view('livewire.customer.categories.category-index-page')
            ->layoutData(['title' => 'Product Categories']);
    }
}
