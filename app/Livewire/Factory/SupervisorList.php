<?php

namespace App\Livewire\Factory;

use App\Models\FactorySupervisor;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class SupervisorList extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $statusFilter = '';

    // Modal state
    public bool $showModal = false;
    public ?int $editingSupervisorId = null;

    // Form fields
    public string $name = '';
    public string $code = '';
    public string $phone = '';
    public string $email = '';
    public string $department = '';
    public string $notes = '';
    public bool $is_active = true;

    // Delete modal
    public ?int $confirmingDeletionId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function createSupervisor(): void
    {
        $this->resetForm();
        $this->editingSupervisorId = null;
        $this->showModal = true;
    }

    public function editSupervisor(int $id): void
    {
        $this->resetForm();
        $supervisor = FactorySupervisor::findOrFail($id);
        $this->editingSupervisorId = $supervisor->id;
        $this->name        = $supervisor->name ?? '';
        $this->code        = $supervisor->code ?? '';
        $this->phone       = $supervisor->phone ?? '';
        $this->email       = $supervisor->email ?? '';
        $this->department  = $supervisor->department ?? '';
        $this->notes       = $supervisor->notes ?? '';
        $this->is_active   = (bool) $supervisor->is_active;
        $this->showModal   = true;
    }

    public function saveSupervisor(): void
    {
        $uniqueEmailRule = 'nullable|email|max:255|unique:factory_supervisors,email';
        $uniqueCodeRule  = 'nullable|string|max:50|unique:factory_supervisors,code';

        if ($this->editingSupervisorId) {
            $uniqueEmailRule .= ',' . $this->editingSupervisorId;
            $uniqueCodeRule  .= ',' . $this->editingSupervisorId;
        }

        $this->validate([
            'name'       => 'required|string|max:255',
            'code'       => $uniqueCodeRule,
            'phone'      => 'nullable|string|max:50',
            'email'      => $uniqueEmailRule,
            'department' => 'nullable|string|max:255',
            'notes'      => 'nullable|string',
            'is_active'  => 'required|boolean',
        ]);

        $data = [
            'name'       => trim($this->name),
            'phone'      => trim($this->phone) ?: null,
            'email'      => trim($this->email) ?: null,
            'department' => trim($this->department) ?: null,
            'notes'      => trim($this->notes) ?: null,
            'is_active'  => $this->is_active,
        ];

        // Only set code if explicitly provided
        if (trim($this->code) !== '') {
            $data['code'] = strtoupper(trim($this->code));
        }

        if ($this->editingSupervisorId) {
            $supervisor = FactorySupervisor::findOrFail($this->editingSupervisorId);
            $supervisor->update($data);
            $this->dispatch('toast', type: 'success', message: 'Supervisor updated successfully.');
        } else {
            FactorySupervisor::create($data);
            $this->dispatch('toast', type: 'success', message: 'Supervisor created successfully.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $supervisor = FactorySupervisor::findOrFail($id);
        $supervisor->update(['is_active' => !$supervisor->is_active]);
        $status = $supervisor->is_active ? 'Active' : 'Inactive';
        $this->dispatch('toast', type: 'success', message: "Supervisor {$supervisor->code} set to {$status}.");
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeletionId = $id;
    }

    public function deleteSupervisor(): void
    {
        if ($this->confirmingDeletionId) {
            $supervisor = FactorySupervisor::withCount('productionBatches')->findOrFail($this->confirmingDeletionId);

            if ($supervisor->production_batches_count > 0) {
                $this->dispatch('toast', type: 'error', message: "Cannot delete supervisor [{$supervisor->name}] — they are linked to {$supervisor->production_batches_count} production batch(es).");
                $this->confirmingDeletionId = null;
                return;
            }

            $supervisor->delete();
            $this->confirmingDeletionId = null;
            $this->dispatch('toast', type: 'success', message: 'Supervisor deleted successfully.');
        }
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingSupervisorId',
            'name',
            'code',
            'phone',
            'email',
            'department',
            'notes',
            'confirmingDeletionId',
        ]);
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $query = FactorySupervisor::withCount('productionBatches');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('department', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        $supervisors = $query->orderBy('name')->paginate(12);

        return view('livewire.factory.supervisor-list', [
            'supervisors' => $supervisors,
        ])->layoutData(['title' => 'Supervisors Management']);
    }
}
