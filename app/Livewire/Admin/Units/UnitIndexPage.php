<?php

namespace App\Livewire\Admin\Units;

use App\Models\UnitGroup;
use App\Models\Unit;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class UnitIndexPage extends Component
{
    use WithPagination;

    public string $search = '';

    // Unit Group Modal State
    public bool $showGroupModal = false;
    public ?int $editingGroupId = null;
    public string $groupName = '';
    public string $groupCode = '';
    public string $groupDescription = '';
    public bool $groupIsActive = true;

    // Unit Management Sub-Modal State
    public ?int $selectedGroupId = null;
    public bool $showUnitModal = false;
    public ?int $editingUnitId = null;
    public string $unitName = '';
    public string $unitShortCode = '';
    public bool $unitIsBase = false;
    public float $unitRatio = 1.0;
    public bool $unitIsActive = true;

    protected function groupRules(): array
    {
        return [
            'groupName' => 'required|string|max:255',
            'groupCode' => 'required|string|max:50|unique:unit_groups,code,' . ($this->editingGroupId ?? 'NULL'),
            'groupDescription' => 'nullable|string|max:500',
            'groupIsActive' => 'boolean',
        ];
    }

    protected function unitRules(): array
    {
        return [
            'unitName' => 'required|string|max:255',
            'unitShortCode' => 'required|string|max:50',
            'unitIsBase' => 'boolean',
            'unitRatio' => 'required|numeric|gt:0',
            'unitIsActive' => 'boolean',
        ];
    }

    public function openCreateGroupModal()
    {
        $this->resetGroupForm();
        $this->showGroupModal = true;
    }

    public function editGroup(int $groupId)
    {
        $group = UnitGroup::findOrFail($groupId);
        $this->editingGroupId = $group->id;
        $this->groupName = $group->name;
        $this->groupCode = $group->code;
        $this->groupDescription = $group->description ?? '';
        $this->groupIsActive = (bool) $group->is_active;

        $this->showGroupModal = true;
    }

    public function saveGroup()
    {
        $this->validate($this->groupRules());

        $data = [
            'name' => $this->groupName,
            'code' => strtoupper($this->groupCode),
            'description' => $this->groupDescription,
            'is_active' => $this->groupIsActive,
        ];

        if ($this->editingGroupId) {
            UnitGroup::findOrFail($this->editingGroupId)->update($data);
            $msg = "Unit Group [{$this->groupName}] updated successfully.";
        } else {
            UnitGroup::create($data);
            $msg = "Unit Group [{$this->groupName}] created successfully.";
        }

        $this->showGroupModal = false;
        $this->resetGroupForm();
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function deleteGroup(int $groupId)
    {
        $group = UnitGroup::withCount('units')->findOrFail($groupId);
        $group->delete();

        $this->dispatch('toast', message: "Unit Group deleted successfully.", type: 'success');
    }

    public function manageUnits(int $groupId)
    {
        $this->selectedGroupId = $groupId;
    }

    public function closeUnitsDrawer()
    {
        $this->selectedGroupId = null;
    }

    public function openCreateUnitModal()
    {
        $this->resetUnitForm();
        // If no units exist in group yet, default to base unit
        $group = UnitGroup::with('units')->find($this->selectedGroupId);
        if ($group && $group->units->isEmpty()) {
            $this->unitIsBase = true;
            $this->unitRatio = 1.0;
        }
        $this->showUnitModal = true;
    }

    public function editUnit(int $unitId)
    {
        $unit = Unit::findOrFail($unitId);
        $this->editingUnitId = $unit->id;
        $this->unitName = $unit->name;
        $this->unitShortCode = $unit->short_code;
        $this->unitIsBase = (bool) $unit->is_base;
        $this->unitRatio = (float) $unit->ratio_to_base;
        $this->unitIsActive = (bool) $unit->is_active;

        $this->showUnitModal = true;
    }

    public function saveUnit()
    {
        $this->validate($this->unitRules());

        if (!$this->selectedGroupId) {
            return;
        }

        $data = [
            'unit_group_id' => $this->selectedGroupId,
            'name' => $this->unitName,
            'short_code' => $this->unitShortCode,
            'is_base' => $this->unitIsBase,
            'ratio_to_base' => $this->unitIsBase ? 1.0 : $this->unitRatio,
            'is_active' => $this->unitIsActive,
        ];

        if ($this->unitIsBase) {
            // Unset previous base unit in this group
            Unit::where('unit_group_id', $this->selectedGroupId)->update(['is_base' => false]);
        }

        if ($this->editingUnitId) {
            Unit::findOrFail($this->editingUnitId)->update($data);
            $msg = "Unit [{$this->unitName}] updated successfully.";
        } else {
            Unit::create($data);
            $msg = "Unit [{$this->unitName}] added successfully.";
        }

        $this->showUnitModal = false;
        $this->resetUnitForm();
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function deleteUnit(int $unitId)
    {
        $unit = Unit::findOrFail($unitId);
        if ($unit->is_base) {
            $this->dispatch('toast', message: "Cannot delete the Base Unit of a group. Set another unit as base first.", type: 'error');
            return;
        }

        $unit->delete();
        $this->dispatch('toast', message: "Unit deleted successfully.", type: 'success');
    }

    public function setBaseUnit(int $unitId)
    {
        $unit = Unit::findOrFail($unitId);
        Unit::where('unit_group_id', $unit->unit_group_id)->update(['is_base' => false]);
        $unit->update(['is_base' => true, 'ratio_to_base' => 1.0]);

        $this->dispatch('toast', message: "[{$unit->name}] is now set as Base Unit.", type: 'success');
    }

    protected function resetGroupForm()
    {
        $this->editingGroupId = null;
        $this->groupName = '';
        $this->groupCode = '';
        $this->groupDescription = '';
        $this->groupIsActive = true;
    }

    protected function resetUnitForm()
    {
        $this->editingUnitId = null;
        $this->unitName = '';
        $this->unitShortCode = '';
        $this->unitIsBase = false;
        $this->unitRatio = 1.0;
        $this->unitIsActive = true;
    }

    public function render()
    {
        $groups = UnitGroup::with(['units'])
            ->when(!empty($this->search), function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%");
            })
            ->orderBy('name')
            ->paginate(10);

        $selectedGroup = $this->selectedGroupId ? UnitGroup::with('units')->find($this->selectedGroupId) : null;

        return view('livewire.admin.units.unit-index-page', [
            'groups' => $groups,
            'selectedGroup' => $selectedGroup,
        ]);
    }
}
