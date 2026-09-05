<?php

namespace App\Livewire\Admin\Production;

use App\Models\FabricWidth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class FabricWidthMasterPage extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    // Modal form state
    public ?int $widthId = null;
    public string $name = '';
    public string $value = '';
    public string $unit = 'Inch';
    public bool $status = true;

    public function mount(): void
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor'])
            && !auth()->user()->can('manage_labor')) {
            abort(403, 'Unauthorized access to Fabric Width Master.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->reset(['widthId', 'name', 'value']);
        $this->unit = 'Inch';
        $this->status = true;
        $this->dispatch('open-modal', 'width-modal');
    }

    public function editWidth(int $id): void
    {
        $this->resetValidation();
        $width = FabricWidth::findOrFail($id);
        $this->widthId = $width->id;
        $this->name = $width->name;
        $this->value = (string) $width->value;
        $this->unit = $width->unit;
        $this->status = (bool) $width->status;
        $this->dispatch('open-modal', 'width-modal');
    }

    public function saveWidth(): void
    {
        $this->validate([
            'value' => 'required|numeric|min:0.01',
            'unit'  => 'required|string|max:50',
            'status' => 'required|boolean',
        ], [
            'value.required' => 'Fabric width value is required.',
            'value.numeric'  => 'Fabric width value must be a valid number.',
            'unit.required'  => 'Unit is required.',
        ]);

        $name = trim($this->value) . ' ' . trim($this->unit);

        if ($this->widthId) {
            $width = FabricWidth::findOrFail($this->widthId);
            $width->update([
                'name'   => $name,
                'value'  => $this->value,
                'unit'   => $this->unit,
                'status' => $this->status,
            ]);
            $msg = "Fabric Width \"{$width->name}\" updated successfully!";
        } else {
            $width = FabricWidth::create([
                'name'   => $name,
                'value'  => $this->value,
                'unit'   => $this->unit,
                'status' => $this->status,
            ]);
            $msg = "Fabric Width \"{$width->name}\" created successfully!";
        }

        $this->dispatch('close-modal', 'width-modal');
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function toggleStatus(int $id): void
    {
        $width = FabricWidth::findOrFail($id);
        $width->update(['status' => !$width->status]);
        $label = $width->fresh()->status ? 'Active' : 'Inactive';
        $this->dispatch('toast', message: "Fabric Width \"{$width->name}\" set to {$label}.", type: 'success');
    }

    public function deleteWidth(int $id): void
    {
        $width = FabricWidth::findOrFail($id);

        if ($width->isInUse()) {
            $this->dispatch('toast',
                message: "Cannot delete \"{$width->name}\" — it is currently referenced in Raw Materials or Product Patterns.",
                type: 'error'
            );
            return;
        }

        $name = $width->name;
        $width->delete();
        $this->dispatch('toast', message: "Fabric Width \"{$name}\" deleted successfully.", type: 'success');
    }

    public function render()
    {
        $query = FabricWidth::query();

        if (!empty($this->search)) {
            $query->where('name', 'like', "%{$this->search}%")
                  ->orWhere('value', 'like', "%{$this->search}%")
                  ->orWhere('unit', 'like', "%{$this->search}%");
        }

        $widths = $query->orderBy('value', 'asc')->paginate(15);

        return view('livewire.admin.production.fabric-width-master-page', [
            'widths' => $widths,
        ])->title('Fabric Width Master');
    }
}
