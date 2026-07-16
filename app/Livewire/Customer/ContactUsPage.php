<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class ContactUsPage extends Component
{
    public string $name = '';
    public string $email = '';
    public string $subject = '';
    public string $message = '';

    protected array $rules = [
        'name' => 'required|string|min:2|max:100',
        'email' => 'required|email|max:150',
        'subject' => 'required|string|min:5|max:150',
        'message' => 'required|string|min:10|max:1000',
    ];

    public function sendMessage()
    {
        $this->validate();

        \App\Models\ContactMessage::create([
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
        ]);

        // Normally we would dispatch/store the contact email, we will show a success notification here.
        $this->dispatch('toast', type: 'success', message: 'Thank you! Your message has been sent successfully.');

        $this->reset(['name', 'email', 'subject', 'message']);
    }

    public function render()
    {
        return view('livewire.customer.contact-us-page')
            ->layoutData(['title' => 'Contact Us']);
    }
}
