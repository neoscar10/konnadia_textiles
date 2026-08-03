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

    // Dynamic unit options based on selected category
    public array $availableUnits = [];

    public function isLengthBased(): bool
    {
        if (!$this->raw_material_category_id) {
            return false;
        }
        $category = RawMaterialCategory::find($this->raw_material_category_id);
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
            'unit.required' => 'Please select a unit.',
            'unit.in' => 'The selected unit is not valid for this category type.',
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
            $material = RawMaterial::with('category')->findOrFail($materialId);
            $this->materialId = $material->id;
            $this->name = $material->name;
            $this->code = $material->code;
            $this->unit = $material->unit;
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
        $this->updateAvailableUnits();

        // Auto-select default unit when category changes
        if ($this->raw_material_category_id) {
            $category = RawMaterialCategory::find($this->raw_material_category_id);
            if ($category) {
                $defaultUnit = $category->default_unit;
                // Only auto-set if current unit is not valid for new category
                if (!in_array($this->unit, $category->valid_units)) {
                    $this->unit = $defaultUnit;
                }

                if ($category->unit_type !== RawMaterialUnitType::LENGTH_BASED) {
                    $this->standard_width = null;
                    $this->width_unit = null;
                } else if (empty($this->width_unit)) {
                    $this->width_unit = 'Inch';
                }
            }
        }
    }

    public function save()
    {
        $this->validate();

        $isLengthBased = $this->isLengthBased();

        $data = [
            'name' => $this->name,
            'raw_material_category_id' => $this->raw_material_category_id,
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
        $this->standard_width = null;
        $this->width_unit = 'Inch';
        $this->is_active = true;
        $this->raw_material_category_id = null;
        $this->availableUnits = [];
    }

    protected function updateAvailableUnits()
    {
        if ($this->raw_material_category_id) {
            $category = RawMaterialCategory::find($this->raw_material_category_id);
            $this->availableUnits = $category ? $category->valid_units : [];
        } else {
            // Show all units when no category is selected
            $this->availableUnits = array_merge(
                RawMaterialUnitType::LENGTH_BASED->validUnits(),
                RawMaterialUnitType::OTHER->validUnits()
            );
        }
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
