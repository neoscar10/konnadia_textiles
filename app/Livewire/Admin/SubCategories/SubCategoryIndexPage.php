<?php

namespace App\Livewire\Admin\SubCategories;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class SubCategoryIndexPage extends Component
{
    public function render()
    {
        return view('livewire.admin.sub-categories.sub-category-index-page');
    }
}
