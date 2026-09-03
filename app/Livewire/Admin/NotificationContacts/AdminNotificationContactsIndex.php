<?php

namespace App\Livewire\Admin\NotificationContacts;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AdminNotificationContact;

class AdminNotificationContactsIndex extends Component
{
    use WithPagination;

    public $search = '';

    // Modal state
    public $showModal = false;
    public $editingContactId = null;

    // Form fields
    public $name = '';
    public $phone_number = '';
    public $is_active = true;
    public $notify_new_orders = true;
    public $notify_goods_transfers = true;
    public $notify_order_dispatches = true;
    public $notes = '';

    // Confirmation modal
    public $confirmingDeletion = false;
    public $contactIdBeingDeleted = null;

    protected $rules = [
        'name'                    => 'required|string|max:255',
        'phone_number'            => 'required|string|max:30',
        'is_active'               => 'boolean',
        'notify_new_orders'       => 'boolean',
        'notify_goods_transfers'   => 'boolean',
        'notify_order_dispatches' => 'boolean',
        'notes'                   => 'nullable|string|max:1000',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function editContact(int $id)
    {
        $contact = AdminNotificationContact::findOrFail($id);
        $this->editingContactId = $contact->id;
        $this->name = $contact->name;
        $this->phone_number = $contact->phone_number;
        $this->is_active = (bool) $contact->is_active;
        $this->notify_new_orders = (bool) $contact->notify_new_orders;
        $this->notify_goods_transfers = (bool) $contact->notify_goods_transfers;
        $this->notify_order_dispatches = (bool) $contact->notify_order_dispatches;
        $this->notes = $contact->notes;

        $this->showModal = true;
    }

    public function saveContact()
    {
        $validated = $this->validate();

        if ($this->editingContactId) {
            $contact = AdminNotificationContact::findOrFail($this->editingContactId);
            $contact->update($validated);
            session()->flash('message', 'Admin notification contact updated successfully.');
        } else {
            AdminNotificationContact::create($validated);
            session()->flash('message', 'New admin notification contact added successfully.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleStatus(int $id)
    {
        $contact = AdminNotificationContact::findOrFail($id);
        $contact->update(['is_active' => !$contact->is_active]);

        $statusLabel = $contact->is_active ? 'activated' : 'deactivated';
        session()->flash('message', "Contact \"{$contact->name}\" {$statusLabel} successfully.");
    }

    public function confirmDelete(int $id)
    {
        $this->contactIdBeingDeleted = $id;
        $this->confirmingDeletion = true;
    }

    public function deleteContact()
    {
        if ($this->contactIdBeingDeleted) {
            $contact = AdminNotificationContact::findOrFail($this->contactIdBeingDeleted);
            $contact->delete();
            session()->flash('message', 'Admin notification contact deleted successfully.');
        }

        $this->confirmingDeletion = false;
        $this->contactIdBeingDeleted = null;
    }

    public function resetForm()
    {
        $this->editingContactId = null;
        $this->name = '';
        $this->phone_number = '';
        $this->is_active = true;
        $this->notify_new_orders = true;
        $this->notify_goods_transfers = true;
        $this->notify_order_dispatches = true;
        $this->notes = '';
        $this->resetValidation();
    }

    public function render()
    {
        $contacts = AdminNotificationContact::query()
            ->when($this->search, function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('phone_number', 'like', "%{$this->search}%")
                  ->orWhere('notes', 'like', "%{$this->search}%");
            })
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('livewire.admin.notification-contacts.admin-notification-contacts-index', [
            'contacts' => $contacts,
        ])->layout('layouts.admin', ['title' => 'Admin WhatsApp Notification Contacts']);
    }
}
