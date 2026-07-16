<?php

namespace App\Livewire\Admin\Support;

use App\Models\ContactMessage;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class ContactMessagesPage extends Component
{
    use WithPagination;

    public $search = '';
    public $status = ''; // 'read', 'unread'
    public $selectedMessageId = null;
    public ?ContactMessage $selectedMessage = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function showMessage($id)
    {
        $message = ContactMessage::findOrFail($id);
        
        if (!$message->is_read) {
            $message->update(['is_read' => true]);
            // Dispatch a window event or sidebar refresh if needed, but since wire:navigate or livewire updates on render, it will refresh the unread count.
        }

        $this->selectedMessageId = $id;
        $this->selectedMessage = $message;
    }

    public function closeMessage()
    {
        $this->selectedMessageId = null;
        $this->selectedMessage = null;
    }

    public function deleteMessage($id)
    {
        ContactMessage::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Message deleted successfully.');
        
        if ($this->selectedMessageId == $id) {
            $this->selectedMessageId = null;
            $this->selectedMessage = null;
        }
    }

    public function markAsUnread($id)
    {
        ContactMessage::findOrFail($id)->update(['is_read' => false]);
        $this->dispatch('toast', type: 'success', message: 'Message marked as unread.');
        
        if ($this->selectedMessageId == $id) {
            $this->selectedMessageId = null;
            $this->selectedMessage = null;
        }
    }

    public function render()
    {
        $messages = ContactMessage::query()
            ->when($this->search, function($q) {
                $q->where(function($sq) {
                    $sq->where('name', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%")
                      ->orWhere('subject', 'like', "%{$this->search}%")
                      ->orWhere('message', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, function($q) {
                if ($this->status === 'read') {
                    $q->where('is_read', true);
                } elseif ($this->status === 'unread') {
                    $q->where('is_read', false);
                }
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.admin.support.contact-messages-page', [
            'messages' => $messages,
        ]);
    }
}
