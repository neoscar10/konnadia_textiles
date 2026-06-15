<?php

namespace App\Livewire\Admin\Credit;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class CreditManagementPage extends Component
{
    public function render()
    {
        return view('livewire.admin.credit.credit-management-page');
    }
}
