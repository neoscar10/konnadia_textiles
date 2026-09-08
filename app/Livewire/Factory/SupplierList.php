<?php

namespace App\Livewire\Factory;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class SupplierList extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $portalAccessFilter = '';

    // Modal state
    public bool $showModal = false;
    public ?int $editingSupplierId = null;

    // Form fields
    public string $name = '';
    public string $contact_person = '';
    public string $whatsapp_number = '';
    public string $email = '';
    public string $portal_access = 'not_invited';
    public string $address = '';
    public string $gstin = '';
    public string $notes = '';

    // Dummy UI toggle for portal access (as requested)
    public bool $grant_portal_access = false;

    // Delete modal
    public ?int $confirmingDeletionId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPortalAccessFilter(): void
    {
        $this->resetPage();
    }

    public function createSupplier(): void
    {
        $this->resetForm();
        $this->editingSupplierId = null;
        $this->showModal = true;
    }

    public function editSupplier(int $id): void
    {
        $this->resetForm();
        $supplier = Supplier::findOrFail($id);
        $this->editingSupplierId = $supplier->id;
        $this->name = $supplier->name ?? '';
        $this->contact_person = $supplier->contact_person ?? '';
        $this->whatsapp_number = $supplier->whatsapp_number ?? '';
        $this->email = $supplier->email ?? '';
        $this->portal_access = $supplier->portal_access ?? 'not_invited';
        $this->address = $supplier->address ?? '';
        $this->gstin = $supplier->gstin ?? '';
        $this->notes = $supplier->notes ?? '';
        $this->grant_portal_access = ($supplier->portal_access === 'enabled');
        $this->showModal = true;
    }

    public function saveSupplier(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'portal_access' => 'required|in:not_invited,enabled,disabled',
            'address' => 'nullable|string',
            'gstin' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ];

        $this->validate($rules);

        // Synchronize dummy grant_portal_access toggle with portal_access state if modified
        $portalAccessState = $this->portal_access;
        if ($this->grant_portal_access && $portalAccessState === 'not_invited') {
            $portalAccessState = 'enabled';
        }

        $data = [
            'name' => trim($this->name),
            'contact_person' => trim($this->contact_person) ?: null,
            'whatsapp_number' => trim($this->whatsapp_number) ?: null,
            'email' => trim($this->email) ?: null,
            'portal_access' => $portalAccessState,
            'address' => trim($this->address) ?: null,
            'gstin' => trim($this->gstin) ?: null,
            'notes' => trim($this->notes) ?: null,
        ];

        if ($this->editingSupplierId) {
            $supplier = Supplier::findOrFail($this->editingSupplierId);
            $supplier->update($data);
            $this->dispatch('toast', type: 'success', message: 'Supplier updated successfully.');
        } else {
            Supplier::create($data);
            $this->dispatch('toast', type: 'success', message: 'Supplier created successfully.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeletionId = $id;
    }

    public function deleteSupplier(): void
    {
        if ($this->confirmingDeletionId) {
            $supplier = Supplier::findOrFail($this->confirmingDeletionId);
            $supplier->delete();
            $this->confirmingDeletionId = null;
            $this->dispatch('toast', type: 'success', message: 'Supplier deleted successfully.');
        }
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingSupplierId',
            'name',
            'contact_person',
            'whatsapp_number',
            'email',
            'portal_access',
            'address',
            'gstin',
            'notes',
            'grant_portal_access',
            'confirmingDeletionId',
        ]);
        $this->resetValidation();
        $this->portal_access = 'not_invited';
    }

    public function render()
    {
        $query = Supplier::query()->withCount('inventoryBatches');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('contact_person', 'like', "%{$this->search}%")
                  ->orWhere('whatsapp_number', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('gstin', 'like', "%{$this->search}%");
            });
        }

        if ($this->portalAccessFilter) {
            $query->where('portal_access', $this->portalAccessFilter);
        }

        $suppliers = $query->orderBy('name')->paginate(12);

        return view('livewire.factory.supplier-list', [
            'suppliers' => $suppliers,
        ])->layoutData(['title' => 'Suppliers Management']);
    }
}
