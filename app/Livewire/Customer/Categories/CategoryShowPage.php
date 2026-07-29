<?php

namespace App\Livewire\Customer\Categories;

use Livewire\Component;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryShowPage extends Component
{
    public function mount($slug)
    {
        $category = Category::where('id', $slug)
            ->orWhere('name', 'like', str_replace('-', ' ', $slug))
            ->first();

        $categoryParam = $category ? $category->id : $slug;

        return redirect()->route('customer.products.index', ['category' => $categoryParam]);
    }
}
