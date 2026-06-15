<?php

namespace App\Livewire\Customer\Notifications;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.customer.layout')]
class NotificationPage extends Component
{
    public function render()
    {
        return view('livewire.customer.notifications.notification-page')
            ->layoutData(['title' => 'Notifications']);
    }
}
