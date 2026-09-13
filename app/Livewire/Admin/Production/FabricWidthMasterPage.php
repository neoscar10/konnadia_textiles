<?php

namespace App\Livewire\Admin\Production;

use App\Models\FabricWidth;
use App\Models\UnitGroup;
use App\Models\Unit;
use App\Services\UnitConversionService;
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
    public ?int $unit_id = null;
    public string $unit = 'IN';
    public bool $status = true;

    // Delete confirm modal state
    public ?int $deletingWidthId = null;
    public string $deletingWidthName = '';

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
        
        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();
        if (!$lengthGroup) {
            app(\Database\Seeders\SystemLengthUnitsSeeder::class)->run();
            $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();
        }

        $defaultUnit = $lengthGroup ? $lengthGroup->units()->where('short_code', 'IN')->first() ?? $lengthGroup->baseUnit : null;

        $this->unit_id = $defaultUnit?->id;
        $this->unit = $defaultUnit?->short_code ?? 'IN';
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
        $this->unit_id = $width->unit_id;
        $this->unit = $width->unit;
        
        if (!$this->unit_id) {
            $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();
            if (!$lengthGroup) {
                app(\Database\Seeders\SystemLengthUnitsSeeder::class)->run();
                $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();
            }

            if ($lengthGroup) {
                $matchedUnit = UnitConversionService::resolveUnit($width->unit, $lengthGroup->id);
                if ($matchedUnit) {
                    $this->unit_id = $matchedUnit->id;
                }
            }
        }

        $this->status = (bool) $width->status;
        $this->dispatch('open-modal', 'width-modal');
    }

    public function saveWidth(): void
    {
        if (!$this->unit_id && !empty($this->unit)) {
            $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();
            if (!$lengthGroup) {
                app(\Database\Seeders\SystemLengthUnitsSeeder::class)->run();
                $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();
            }

            if ($lengthGroup) {
                $matchedUnit = UnitConversionService::resolveUnit($this->unit, $lengthGroup->id);
                if ($matchedUnit) {
                    $this->unit_id = $matchedUnit->id;
                }
            }
        }

        $this->validate([
            'value' => 'required|numeric|min:0.01',
            'unit_id' => 'required|exists:units,id',
            'status' => 'required|boolean',
        ], [
            'value.required' => 'Fabric width value is required.',
            'value.numeric'  => 'Fabric width value must be a valid number.',
            'unit_id.required' => 'Measurement Unit is required.',
            'unit_id.exists' => 'Selected measurement unit is invalid.',
        ]);

        $unitModel = Unit::find($this->unit_id);
        $unitStr = !empty(trim($this->unit)) ? trim($this->unit) : ($unitModel ? $unitModel->short_code : 'IN');
        $name = trim($this->value) . ' ' . $unitStr;

        if ($this->widthId) {
            $width = FabricWidth::findOrFail($this->widthId);
            $width->update([
                'name'    => $name,
                'value'   => $this->value,
                'unit_id' => $this->unit_id,
                'unit'    => $unitStr,
                'status'  => $this->status,
            ]);
            $msg = "Fabric Width \"{$width->name}\" updated successfully!";
        } else {
            $width = FabricWidth::create([
                'name'    => $name,
                'value'   => $this->value,
                'unit_id' => $this->unit_id,
                'unit'    => $unitStr,
                'status'  => $this->status,
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

    public function confirmDelete(int $id): void
    {
        $width = FabricWidth::findOrFail($id);

        if ($width->isInUse()) {
            $this->dispatch('toast',
                message: "Cannot delete \"{$width->name}\" — it is currently referenced in Raw Materials or Product Patterns.",
                type: 'error'
            );
            return;
        }

        $this->deletingWidthId = $width->id;
        $this->deletingWidthName = $width->name;
        $this->dispatch('open-modal', 'delete-width-modal');
    }

    public function performDelete(): void
    {
        if (!$this->deletingWidthId) {
            return;
        }

        $width = FabricWidth::findOrFail($this->deletingWidthId);

        if ($width->isInUse()) {
            $this->dispatch('toast',
                message: "Cannot delete \"{$width->name}\" — it is currently referenced in Raw Materials or Product Patterns.",
                type: 'error'
            );
            $this->dispatch('close-modal', 'delete-width-modal');
            return;
        }

        $name = $width->name;
        $width->delete();

        $this->reset(['deletingWidthId', 'deletingWidthName']);
        $this->dispatch('close-modal', 'delete-width-modal');
        $this->dispatch('toast', message: "Fabric Width \"{$name}\" deleted successfully.", type: 'success');
    }

    public function deleteWidth(int $id): void
    {
        $this->deletingWidthId = $id;
        $this->performDelete();
    }

    public function render()
    {
        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();
        if (!$lengthGroup) {
            app(\Database\Seeders\SystemLengthUnitsSeeder::class)->run();
            $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();
        }

        $lengthUnits = $lengthGroup ? $lengthGroup->activeUnits : Unit::all();

        $query = FabricWidth::with('unitModel');

        if (!empty($this->search)) {
            $query->where('name', 'like', "%{$this->search}%")
                  ->orWhere('value', 'like', "%{$this->search}%")
                  ->orWhere('unit', 'like', "%{$this->search}%");
        }

        $widths = $query->orderBy('value', 'asc')->paginate(15);

        return view('livewire.admin.production.fabric-width-master-page', [
            'widths' => $widths,
            'lengthUnits' => $lengthUnits,
        ])->title('Fabric Width Master');
    }
}
