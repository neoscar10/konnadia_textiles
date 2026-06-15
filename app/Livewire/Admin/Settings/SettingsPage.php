<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class SettingsPage extends Component
{
    public function render()
    {
        return view('livewire.admin.settings.settings-page');
    }
}
