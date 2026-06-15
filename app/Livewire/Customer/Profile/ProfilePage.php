<?php

namespace App\Livewire\Customer\Profile;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class ProfilePage extends Component
{
    public function render()
    {
        return view('livewire.customer.profile.profile-page')
            ->layoutData(['title' => 'My Profile']);
    }
}
