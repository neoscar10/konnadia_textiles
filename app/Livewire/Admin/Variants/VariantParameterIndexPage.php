<?php

namespace App\Livewire\Admin\Variants;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class VariantParameterIndexPage extends Component
{
    public function render()
    {
        return view('livewire.admin.variants.variant-parameter-index-page');
    }
}
