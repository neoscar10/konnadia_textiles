<?php

namespace App\Livewire\Factory;

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
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

    // Dynamic unit options based on selected category / Unit Group
    public array $availableUnits = [];

    public function isLengthBased(): bool
    {
        if ($this->unit_group_id) {
            $group = \App\Models\UnitGroup::find($this->unit_group_id);
            if ($group && $group->code === 'LENGTH') {
                return true;
            }
        }

        if (!$this->raw_material_category_id) {
            return false;
        }

        $category = RawMaterialCategory::with('unitGroup')->find($this->raw_material_category_id);
        if ($category && $category->unitGroup && $category->unitGroup->code === 'LENGTH') {
            return true;
        }

        return $category && $category->unit_type === RawMaterialUnitType::LENGTH_BASED;
    }

    protected function rules()
    {
        $validUnits = $this->getValidUnitsForSelectedCategory();
        $unitRule = !empty($validUnits) ? 'required|in:' . implode(',', $validUnits) : 'required|string|max:50';

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'raw_material_category_id' => 'required|exists:raw_material_categories,id',
            'unit' => $unitRule,
            'is_active' => 'required|boolean',
        ];

        if ($this->isLengthBased()) {
            $rules['standard_width'] = 'required|numeric|gt:0';
            $rules['width_unit'] = 'required|in:Inch,CM';
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
            'unit.in' => 'The selected unit is not valid for this category.',
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
                }
                $this->updateAvailableUnits();

                $defaultUnit = $category->default_unit;
                if (!in_array($this->unit, $this->availableUnits)) {
                    $this->unit = $defaultUnit;
                }
            }
        } else {
            $this->updateAvailableUnits();
        }
    }

    public function save()
    {
        $this->validate();

        $isLengthBased = $this->isLengthBased();

        // Resolve unit_group_id and unit_id
        $category = RawMaterialCategory::with('unitGroup')->find($this->raw_material_category_id);
        $groupId = $this->unit_group_id ?: ($category ? $category->unit_group_id : null);
        
        $unitModel = null;
        if ($groupId) {
            $unitModel = \App\Models\Unit::where('unit_group_id', $groupId)
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
        if ($this->raw_material_category_id) {
            $category = RawMaterialCategory::with(['unitGroup.activeUnits'])->find($this->raw_material_category_id);
            if ($category && $category->unitGroup) {
                $this->availableUnits = $category->unitGroup->activeUnits->pluck('name')->toArray();
                return;
            } else if ($category) {
                $this->availableUnits = $category->valid_units;
                return;
            }
        }

        if ($this->unit_group_id) {
            $group = \App\Models\UnitGroup::with('activeUnits')->find($this->unit_group_id);
            if ($group) {
                $this->availableUnits = $group->activeUnits->pluck('name')->toArray();
                return;
            }
        }

        // Fallback: load units from all active groups
        $this->availableUnits = \App\Models\Unit::active()->pluck('name')->unique()->toArray();
    }

    protected function getValidUnitsForSelectedCategory(): array
    {
        if ($this->raw_material_category_id) {
            $category = RawMaterialCategory::find($this->raw_material_category_id);
            return $category ? $category->valid_units : [];
        }

        return [];
    }

    public function render()
    {
        $categories = RawMaterialCategory::active()->orderBy('name')->get();

        return view('livewire.factory.raw-material-manager', [
            'categories' => $categories,
        ]);
    }
}
