<?php

namespace App\Livewire\Customer\Categories;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Models\Category;

#[Layout('components.customer.layout')]
class CategoryIndexPage extends Component
{
    #[Url(history: true)]
    public $search = '';

    public function render()
    {
        $categories = Category::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return view('livewire.customer.categories.category-index-page', [
            'categories' => $categories
        ])->layoutData(['title' => 'Product Categories']);
    }
}
