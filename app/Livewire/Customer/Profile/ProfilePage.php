<?php

namespace App\Livewire\Customer\Profile;

use App\Services\Customer\Dashboard\CustomerDashboardService;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class ProfilePage extends Component
{
    public array $customer = [];
    public array $cart = [];
    public array $orders = [];
    public array $recentOrders = [];
    public array $alerts = [];

    public $showChangePasswordModal = false;
    public $current_password = '';
    public $new_password = '';
    public $new_password_confirmation = '';

    public function mount(CustomerDashboardService $dashboardService)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && $user->customer) {
            $data = $dashboardService->getDashboard($user);
            if (!empty($data)) {
                $this->customer        = $data['customer'];
                $this->cart            = $data['cart'];
                $this->orders          = $data['orders'];
                $this->recentOrders    = $data['recent_orders'];
                $this->alerts          = $data['alerts'];
            }
        }
    }

    public function openChangePasswordModal()
    {
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->resetValidation();
        $this->showChangePasswordModal = true;
    }

    public function changePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed|different:current_password',
        ], [
            'new_password.different' => 'The new password must be different from the current password.',
        ]);

        $user = \Illuminate\Support\Facades\Auth::user();

        if (!\Illuminate\Support\Facades\Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'The current password you entered is incorrect.');
            return;
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($this->new_password)
        ]);

        $this->showChangePasswordModal = false;
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        $this->dispatch('toast', type: 'success', message: 'Password updated successfully.');
    }

    public function render()
    {
        return view('livewire.customer.profile.profile-page')
            ->layoutData(['title' => 'My Profile']);
    }
}
