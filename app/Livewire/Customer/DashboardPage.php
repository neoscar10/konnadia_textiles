<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class DashboardPage extends Component
{
    public function render()
    {
        return view('livewire.customer.dashboard-page')
            ->layoutData(['title' => 'Dashboard']);
    }
}
