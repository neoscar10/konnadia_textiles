<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Auth\AuthService;
use App\Services\Auth\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginPage extends Component
{
    public string $loginMode = 'password'; // 'password' or 'otp'
    
    // Password login properties
    public string $email = '';
    public string $password = '';
    
    // OTP login properties
    public string $otpLogin = '';
    public string $otp = '';
    public bool $otpSent = false;
    
    public bool $remember = false;

    protected function rules(): array
    {
        if ($this->loginMode === 'password') {
            return [
                'email' => ['required', 'email'],
                'password' => ['required'],
            ];
        }

        if (!$this->otpSent) {
            return [
                'otpLogin' => ['required', 'string', 'min:3'],
            ];
        }

        return [
            'otpLogin' => ['required', 'string', 'min:3'],
            'otp' => ['required', 'string', 'size:6'],
        ];
    }

    public function setLoginMode(string $mode): void
    {
        $this->loginMode = $mode;
        $this->resetErrorBag();
    }

    /**
     * Handle Password Login via Livewire.
     */
    public function loginWithPassword(AuthService $authService)
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', trans('auth.failed'));
            return;
        }

        session()->regenerate();

        return redirect()->intended($authService->getWebRedirectRoute(Auth::user()));
    }

    /**
     * Send OTP.
     */
    public function requestOtp(OtpService $otpService): void
    {
        $this->validate([
            'otpLogin' => ['required', 'string', 'min:3'],
        ]);

        $user = $otpService->resolveUser($this->otpLogin);
        if (!$user) {
            $this->addError('otpLogin', 'No account found with that email or mobile number.');
            return;
        }

        if (!$user->is_active) {
            $this->addError('otpLogin', 'Your account is inactive. Please contact support.');
            return;
        }

        if ($otpService->sendOtp($this->otpLogin)) {
            $this->otpSent = true;
            $this->resetErrorBag('otpLogin');
        } else {
            $this->addError('otpLogin', 'Failed to send OTP. Please try again.');
        }
    }

    /**
     * Verify OTP and Login.
     */
    public function loginWithOtp(OtpService $otpService, AuthService $authService)
    {
        $this->validate([
            'otpLogin' => ['required', 'string', 'min:3'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        if (!$otpService->verifyOtp($this->otpLogin, $this->otp)) {
            $this->addError('otp', 'Invalid OTP code. Any 6-digit code will pass.');
            return;
        }

        $user = $otpService->resolveUser($this->otpLogin);
        if (!$user) {
            $this->addError('otpLogin', 'Account could not be resolved.');
            return;
        }

        Auth::login($user, $this->remember);
        session()->regenerate();

        return redirect()->intended($authService->getWebRedirectRoute($user));
    }

    /**
     * Go back to OTP request step.
     */
    public function resetOtp(): void
    {
        $this->otpSent = false;
        $this->otp = '';
        $this->resetErrorBag();
    }

    #[Layout('components.layouts.public')]
    public function render()
    {
        return view('livewire.auth.login-page');
    }
}
