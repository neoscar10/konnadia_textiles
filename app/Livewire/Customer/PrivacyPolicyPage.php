<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class PrivacyPolicyPage extends Component
{
    public function render()
    {
        return view('livewire.customer.privacy-policy-page')
            ->layoutData(['title' => 'Privacy Policy']);
    }
}
