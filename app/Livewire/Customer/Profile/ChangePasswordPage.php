<?php

namespace App\Livewire\Customer\Profile;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class ChangePasswordPage extends Component
{
    public function render()
    {
        return view('livewire.customer.profile.change-password-page')
            ->layoutData(['title' => 'Change Password']);
    }
}
