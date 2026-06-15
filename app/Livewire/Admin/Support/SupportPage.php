<?php

namespace App\Livewire\Admin\Support;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class SupportPage extends Component
{
    public function render()
    {
        return view('livewire.admin.support.support-page');
    }
}
