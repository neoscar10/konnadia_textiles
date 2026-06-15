<?php

namespace App\Livewire\Admin\Notifications;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class NotificationIndexPage extends Component
{
    public function render()
    {
        return view('livewire.admin.notifications.notification-index-page');
    }
}
