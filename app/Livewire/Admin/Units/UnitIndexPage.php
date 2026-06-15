<?php

namespace App\Livewire\Admin\Units;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class UnitIndexPage extends Component
{
    public function render()
    {
        return view('livewire.admin.units.unit-index-page');
    }
}
