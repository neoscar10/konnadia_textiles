<?php

namespace App\Livewire\Customer\Categories;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class CategoryShowPage extends Component
{
    public $slug;
    public $categoryName;

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->categoryName = match($slug) {
            'mens-wear' => "Men's Wear",
            'womens-wear' => "Women's Wear",
            'kids-wear' => "Kids Wear",
            'home-decor' => "Home Decor",
            'accessories' => "Accessories",
            default => ucfirst(str_replace('-', ' ', $slug))
        };
    }

    public function render()
    {
        return view('livewire.customer.categories.category-show-page')
            ->layoutData(['title' => $this->categoryName]);
    }
}
