<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

class LoginPage extends Component
{
    #[Layout('components.layouts.public')]
    public function render()
    {
        return view('livewire.auth.login-page');
    }
}
