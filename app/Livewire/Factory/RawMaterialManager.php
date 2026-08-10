<?php

namespace App\Livewire\Factory;

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\UnitGroup;
use App\Models\Unit;
use App\Enums\RawMaterialUnitType;
use Livewire\Component;
use Livewire\Attributes\On;

class RawMaterialManager extends Component
{
    public bool $showModal = false;
    public ?int $materialId = null;
    public string $name = '';
    public string $code = '';
    public string $unit = '';
    public ?float $standard_width = null;
    public ?string $width_unit = 'Inch'; // Default to Inch
    public bool $is_active = true;
    public ?int $raw_material_category_id = null;
    public ?int $unit_group_id = null;
    public ?int $unit_id = null;

    // Dynamic unit options based on selected Unit Group / Category
    public array $availableUnits = [];

    public function isLengthBased(): bool
    {
        if ($this->unit_group_id) {
            $group = UnitGroup::find($this->unit_group_id);
            if ($group && (strtoupper($group->code) === 'LENGTH' || str_contains(strtolower($group->name), 'length'))) {
                return true;
            }
        }

        if ($this->raw_material_category_id) {
            $category = RawMaterialCategory::with('unitGroup')->find($this->raw_material_category_id);
            if ($category && $category->unitGroup && (strtoupper($category->unitGroup->code) === 'LENGTH' || str_contains(strtolower($category->unitGroup->name), 'length'))) {
                return true;
            }
            if ($category && $category->unit_type === RawMaterialUnitType::LENGTH_BASED) {
                return true;
            }
        }

        return false;
    }

    protected function rules()
    {
        $validUnits = $this->getValidUnitsForSelectedCategory();
        if (empty($validUnits) && !empty($this->availableUnits)) {
            $validUnits = $this->availableUnits;
        }

        $unitRule = !empty($validUnits) ? 'required|in:' . implode(',', $validUnits) : 'required|string|max:50';

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'raw_material_category_id' => 'required|exists:raw_material_categories,id',
            'unit_group_id' => 'nullable|exists:unit_groups,id',
            'unit' => $unitRule,
            'is_active' => 'required|boolean',
        ];

        if ($this->isLengthBased()) {
            $rules['standard_width'] = 'required|numeric|gt:0';
            $rules['width_unit'] = 'required|string|max:50';
        } else {
            $rules['standard_width'] = 'nullable';
            $rules['width_unit'] = 'nullable';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'name.required' => 'Raw Material Name is required.',
            'raw_material_category_id.required' => 'Please select a category.',
            'unit.required' => 'Please select a unit of measurement.',
            'unit.in' => 'The selected unit is not valid for the chosen unit class.',
            'standard_width.required' => 'Standard Width is required for length-based materials.',
            'standard_width.gt' => 'Standard Width must be greater than zero.',
            'width_unit.required' => 'Width Unit is required for length-based materials.',
        ];
    }

    #[On('open-raw-material-modal')]
    public function openModal($materialId = null)
    {
        $this->resetValidation();
        $this->resetForm();

        if ($materialId) {
            $material = RawMaterial::with(['category.unitGroup', 'unitGroup', 'unitModel'])->findOrFail($materialId);
            $this->materialId = $material->id;
            $this->name = $material->name;
            $this->code = $material->code;
            $this->unit = $material->unit;
            $this->unit_group_id = $material->unit_group_id;
            $this->unit_id = $material->unit_id;
            $this->standard_width = $material->standard_width;
            $this->width_unit = $material->width_unit ?? 'Inch';
            $this->is_active = (bool) $material->is_active;
            $this->raw_material_category_id = $material->raw_material_category_id;
        }

        $this->updateAvailableUnits();
        $this->showModal = true;
    }

    public function updatedRawMaterialCategoryId()
    {
        if ($this->raw_material_category_id) {
            $category = RawMaterialCategory::with('unitGroup')->find($this->raw_material_category_id);
            if ($category) {
                if ($category->unit_group_id) {
                    $this->unit_group_id = $category->unit_group_id;
                } else {
                    // Try matching category's unit_type enum to a UnitGroup
                    $unitGroup = UnitGroup::where('code', strtoupper($category->unit_type->value ?? ''))->first();
                    if ($unitGroup) {
                        $this->unit_group_id = $unitGroup->id;
                    }
                }
                $this->updateAvailableUnits();

                $defaultUnit = $category->default_unit;
                if (!in_array($this->unit, $this->availableUnits)) {
                    $this->unit = in_array($defaultUnit, $this->availableUnits) ? $defaultUnit : ($this->availableUnits[0] ?? '');
                }
            }
        } else {
            $this->updateAvailableUnits();
        }
    }

    public function updatedUnitGroupId()
    {
        $this->updateAvailableUnits();

        if (!empty($this->availableUnits) && !in_array($this->unit, $this->availableUnits)) {
            $this->unit = $this->availableUnits[0] ?? '';
        }

        if ($this->isLengthBased() && empty($this->width_unit)) {
            $this->width_unit = 'Inch';
        }
    }

    public function save()
    {
        // Auto-resolve unit_group_id if not set explicitly
        if (!$this->unit_group_id && $this->raw_material_category_id) {
            $category = RawMaterialCategory::with('unitGroup')->find($this->raw_material_category_id);
            if ($category && $category->unit_group_id) {
                $this->unit_group_id = $category->unit_group_id;
            } else {
                $unitModel = Unit::where('name', $this->unit)->orWhere('short_code', $this->unit)->first();
                if ($unitModel) {
                    $this->unit_group_id = $unitModel->unit_group_id;
                } else if ($category) {
                    $unitGroup = UnitGroup::where('code', strtoupper($category->unit_type->value ?? ''))->first();
                    if ($unitGroup) {
                        $this->unit_group_id = $unitGroup->id;
                    }
                }
            }
        }

        $this->validate();

        $isLengthBased = $this->isLengthBased();

        // Resolve unit_group_id and unit_id
        $category = RawMaterialCategory::with('unitGroup')->find($this->raw_material_category_id);
        $groupId = $this->unit_group_id ?: ($category ? $category->unit_group_id : null);
        
        $unitModel = null;
        if ($groupId) {
            $unitModel = Unit::where('unit_group_id', $groupId)
                ->where(function ($q) {
                    $q->where('name', $this->unit)->orWhere('short_code', $this->unit);
                })->first();
        }

        $data = [
            'name' => $this->name,
            'raw_material_category_id' => $this->raw_material_category_id,
            'unit_group_id' => $groupId,
            'unit_id' => $unitModel ? $unitModel->id : null,
            'unit' => $this->unit,
            'standard_width' => $isLengthBased ? $this->standard_width : null,
            'width_unit' => $isLengthBased ? $this->width_unit : null,
            'is_active' => $this->is_active,
        ];

        if ($this->materialId) {
            $material = RawMaterial::findOrFail($this->materialId);
            $material->update($data);
            $message = "Raw Material [{$material->code}] updated successfully!";
        } else {
            $material = RawMaterial::create($data);
            $message = "Raw Material [{$material->code}] created successfully!";
        }

        $this->showModal = false;
        $this->dispatch('toast', message: $message, type: 'success');
        $this->dispatch('raw-material-saved');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function resetForm()
    {
        $this->materialId = null;
        $this->name = '';
        $this->code = '';
        $this->unit = '';
        $this->unit_group_id = null;
        $this->unit_id = null;
        $this->standard_width = null;
        $this->width_unit = 'Inch';
        $this->is_active = true;
        $this->raw_material_category_id = null;
        $this->availableUnits = [];
    }

    protected function updateAvailableUnits()
    {
        if ($this->unit_group_id) {
            $group = UnitGroup::with('activeUnits')->find($this->unit_group_id);
            if ($group && $group->activeUnits->isNotEmpty()) {
                $this->availableUnits = $group->activeUnits->pluck('name')->toArray();
                return;
            }
        }

        if ($this->raw_material_category_id) {
            $category = RawMaterialCategory::with(['unitGroup.activeUnits'])->find($this->raw_material_category_id);
            if ($category) {
                if ($category->unitGroup && $category->unitGroup->activeUnits->isNotEmpty()) {
                    $this->availableUnits = $category->unitGroup->activeUnits->pluck('name')->toArray();
                } else {
                    $this->availableUnits = $category->valid_units;
                }
                return;
            }
        }

        // Before Unit Class or Category is selected, do NOT list all units!
        $this->availableUnits = [];
    }

    protected function getValidUnitsForSelectedCategory(): array
    {
        if ($this->unit_group_id) {
            $group = UnitGroup::with('activeUnits')->find($this->unit_group_id);
            if ($group && $group->activeUnits->isNotEmpty()) {
                return $group->activeUnits->pluck('name')->toArray();
            }
        }

        if ($this->raw_material_category_id) {
            $category = RawMaterialCategory::find($this->raw_material_category_id);
            return $category ? $category->valid_units : [];
        }

        return [];
    }

    public function render()
    {
        $categories = RawMaterialCategory::active()->orderBy('name')->get();
        $unitGroups = UnitGroup::active()->with('activeUnits')->orderBy('name')->get();

        return view('livewire.factory.raw-material-manager', [
            'categories' => $categories,
            'unitGroups' => $unitGroups,
        ]);
    }
}
