<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

#[Layout('components.admin.layout')]
class SettingsPage extends Component
{
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $confirmPassword = '';

    public function changePassword()
    {
        $this->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'currentPassword.required' => 'Current password is required.',
            'newPassword.required' => 'New password is required.',
            'newPassword.min' => 'New password must be at least 8 characters.',
            'newPassword.confirmed' => 'Passwords do not match.',
        ]);

        $user = auth()->user();

        if (!Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        if (Hash::check($this->newPassword, $user->password)) {
            $this->addError('newPassword', 'New password cannot be the same as current password.');
            return;
        }

        $user->update(['password' => Hash::make($this->newPassword)]);

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Password changed successfully.');
    }

    public function resetForm()
    {
        $this->currentPassword = '';
        $this->newPassword = '';
        $this->confirmPassword = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.settings.settings-page');
    }
}
