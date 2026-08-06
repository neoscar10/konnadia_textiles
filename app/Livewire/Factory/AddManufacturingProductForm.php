<?php

namespace App\Livewire\Factory;

use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\Task;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class AddManufacturingProductForm extends Component
{
    use WithFileUploads;

    public $productId = null;
    public string $name = '';
    public string $code = '';
    public $manufacturing_product_category_id = '';
    public string $status = 'active';
    public $standard_labor_rate = 15.00;
    public $imageUpload = null;
    public $existing_image_path = '';

    // Fabric configuration properties
    public bool $is_fabric_used = false;
    public $standard_fabric_width = '';
    public $standard_fabric_length = '';
    public string $fabric_width_unit = 'inch';
    public string $fabric_length_unit = 'meter';

    // Storefront Product Mapping (Section 10)
    public $mapped_product_id = '';
    public $mapped_product_combination_id = '';

    // Subsidiary material configuration properties
    public bool $is_subsidiary_used = false;
    public array $subsidiaryMaterialsList = []; // [['raw_material_id' => '', 'consumption_quantity' => '', 'unit' => '']]

    // Stitching material configuration properties
    public bool $is_stitching_used = false;
    public array $stitchingMaterialsList = []; // array of raw_material_id integers

    // Packaging material configuration properties
    public bool $is_packaging_used = false;
    public array $packagingMaterialsList = []; // [['raw_material_id' => '', 'required_quantity' => '', 'unit' => '']]

    // Manufacturing Task Sequence / Routing Repeater
    public array $routingTasksList = []; // [['task_id' => '', 'standard_labor_rate' => '15.00', 'is_final_step' => false]]

    protected function rules()
    {
        $rules = [
            'name'                              => 'required|string|max:255',
            'manufacturing_product_category_id' => 'required|exists:manufacturing_product_categories,id',
            'status'                            => 'required|in:active,inactive',
            'standard_labor_rate'               => 'nullable|numeric|min:0',
            'is_fabric_used'                    => 'boolean',
            'is_subsidiary_used'                => 'boolean',
            'is_stitching_used'                 => 'boolean',
            'is_packaging_used'                 => 'boolean',
            'routingTasksList'                  => 'required|array|min:1',
            'routingTasksList.*.task_id'        => 'required|exists:tasks,id',
            'routingTasksList.*.standard_labor_rate' => 'nullable|numeric|min:0',
            'mapped_product_id'                 => 'nullable|exists:products,id',
            'mapped_product_combination_id'     => 'nullable|exists:product_combinations,id',
            'imageUpload'                       => 'nullable|image|max:10240',
        ];

        if ($this->is_fabric_used) {
            $rules['standard_fabric_width']  = 'required|numeric|min:0.01';
            $rules['standard_fabric_length'] = 'required|numeric|min:0.01';
            $rules['fabric_width_unit']      = 'required|in:inch,cm';
            $rules['fabric_length_unit']     = 'required|in:meter,yard';
        }

        if ($this->is_subsidiary_used) {
            $rules['subsidiaryMaterialsList']                               = 'array';
            $rules['subsidiaryMaterialsList.*.raw_material_id']            = 'required|exists:raw_materials,id';
            $rules['subsidiaryMaterialsList.*.consumption_quantity']       = 'required|numeric|min:0.0001';
        }

        if ($this->is_packaging_used) {
            $rules['packagingMaterialsList']                                = 'array';
            $rules['packagingMaterialsList.*.raw_material_id']             = 'required|exists:raw_materials,id';
            $rules['packagingMaterialsList.*.required_quantity']            = 'required|numeric|min:0.0001';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'name.required'                              => 'Product Name is required.',
            'manufacturing_product_category_id.required' => 'Please select a product category.',
            'manufacturing_product_category_id.exists'   => 'Selected category is invalid.',
            'status.required'                            => 'Product Status is required.',
            'status.in'                                  => 'Selected status is invalid.',
            'standard_fabric_width.required'             => 'Fabric Width is required.',
            'standard_fabric_length.required'            => 'Fabric Length is required.',
            'subsidiaryMaterialsList.*.raw_material_id.required'      => 'Please select a subsidiary material.',
            'subsidiaryMaterialsList.*.consumption_quantity.required'  => 'Consumption quantity is required.',
            'subsidiaryMaterialsList.*.consumption_quantity.min'       => 'Consumption quantity must be greater than 0.',
            'packagingMaterialsList.*.raw_material_id.required'       => 'Please select a packaging material.',
            'packagingMaterialsList.*.required_quantity.required'      => 'Required quantity is required.',
            'packagingMaterialsList.*.required_quantity.min'           => 'Required quantity must be greater than 0.',
            'routingTasksList.required'                  => 'At least one task sequence must be configured for the product routing.',
            'routingTasksList.min'                       => 'At least one task sequence must be configured for the product routing.',
            'routingTasksList.*.task_id.required'        => 'Please select a task for each routing step.',
            'routingTasksList.*.task_id.exists'          => 'Selected task is invalid.',
        ];
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function updatedSubsidiaryMaterialsList($value, $key)
    {
        if (str_ends_with($key, '.raw_material_id') && !empty($value)) {
            $index = (int) explode('.', $key)[0];
            $material = RawMaterial::find($value);
            $this->subsidiaryMaterialsList[$index]['unit'] = $material?->unit ?? '';
        }
    }

    public function addSubsidiaryRow(): void
    {
        $this->subsidiaryMaterialsList[] = ['raw_material_id' => '', 'consumption_quantity' => '', 'unit' => ''];
    }

    public function removeSubsidiaryRow(int $index): void
    {
        if (count($this->subsidiaryMaterialsList) > 1) {
            array_splice($this->subsidiaryMaterialsList, $index, 1);
            $this->subsidiaryMaterialsList = array_values($this->subsidiaryMaterialsList);
        } else {
            $this->subsidiaryMaterialsList = [['raw_material_id' => '', 'consumption_quantity' => '', 'unit' => '']];
        }
    }

    public function updatedIsSubsidiaryUsed($value): void
    {
        if ($value && empty($this->subsidiaryMaterialsList)) {
            $this->subsidiaryMaterialsList = [['raw_material_id' => '', 'consumption_quantity' => '', 'unit' => '']];
        }
    }

    // --- Packaging Actions ---
    public function updatedPackagingMaterialsList($value, $key)
    {
        if (str_ends_with($key, '.raw_material_id') && !empty($value)) {
            $index = (int) explode('.', $key)[0];
            $material = RawMaterial::find($value);
            $this->packagingMaterialsList[$index]['unit'] = $material?->unit ?? '';
        }
    }

    public function addPackagingRow(): void
    {
        $this->packagingMaterialsList[] = ['raw_material_id' => '', 'required_quantity' => '', 'unit' => ''];
    }

    public function removePackagingRow(int $index): void
    {
        if (count($this->packagingMaterialsList) > 1) {
            array_splice($this->packagingMaterialsList, $index, 1);
            $this->packagingMaterialsList = array_values($this->packagingMaterialsList);
        } else {
            $this->packagingMaterialsList = [['raw_material_id' => '', 'required_quantity' => '', 'unit' => '']];
        }
    }

    public function updatedIsPackagingUsed($value): void
    {
        if ($value && empty($this->packagingMaterialsList)) {
            $this->packagingMaterialsList = [['raw_material_id' => '', 'required_quantity' => '', 'unit' => '']];
        }
    }

    // --- Task Sequence / Routing Repeater Actions ---

    public function addRoutingRow(): void
    {
        $isFirst = empty($this->routingTasksList);
        $this->routingTasksList[] = [
            'task_id'             => '',
            'standard_labor_rate' => $this->standard_labor_rate ?: '15.00',
            'is_final_step'       => $isFirst,
        ];
    }

    public function removeRoutingRow(int $index): void
    {
        $wasFinal = $this->routingTasksList[$index]['is_final_step'] ?? false;
        array_splice($this->routingTasksList, $index, 1);
        $this->routingTasksList = array_values($this->routingTasksList);

        // If removed row was the final step, make the last remaining row final
        if ($wasFinal && !empty($this->routingTasksList)) {
            $lastIndex = count($this->routingTasksList) - 1;
            foreach ($this->routingTasksList as $i => &$row) {
                $row['is_final_step'] = ($i === $lastIndex);
            }
        }
    }

    public function setFinalStep(int $index): void
    {
        foreach ($this->routingTasksList as $i => &$row) {
            $row['is_final_step'] = ($i === $index);
        }
    }

    public function moveRoutingRow(int $index, string $direction): void
    {
        $targetIndex = ($direction === 'up') ? $index - 1 : $index + 1;
        if ($targetIndex < 0 || $targetIndex >= count($this->routingTasksList)) {
            return;
        }

        $temp = $this->routingTasksList[$index];
        $this->routingTasksList[$index] = $this->routingTasksList[$targetIndex];
        $this->routingTasksList[$targetIndex] = $temp;
        $this->routingTasksList = array_values($this->routingTasksList);
    }

    public function mount($id = null)
    {
        if ($id) {
            $product = ManufacturingProduct::with(['subsidiaryMaterials', 'stitchingMaterials', 'packagingMaterials', 'tasks'])->findOrFail($id);
            $this->productId                         = $product->id;
            $this->name                              = $product->name;
            $this->code                              = $product->code;
            $this->existing_image_path               = $product->image_path ?? '';
            $this->manufacturing_product_category_id = $product->manufacturing_product_category_id ?? '';
            $this->status                            = $product->status ?? 'active';
            $this->standard_labor_rate               = $product->standard_labor_rate ?? 15.00;
            $this->mapped_product_id                 = $product->product_id ?? '';
            $this->mapped_product_combination_id     = $product->product_combination_id ?? '';
            $this->is_fabric_used                    = (bool)($product->is_fabric_used ?? false);
            $this->standard_fabric_width             = $product->standard_fabric_width ?? '';
            $this->standard_fabric_length            = $product->standard_fabric_length ?? '';
            $this->fabric_width_unit                 = $product->fabric_width_unit ?? 'inch';
            $this->fabric_length_unit                = $product->fabric_length_unit ?? 'meter';

            // Subsidiary materials
            $this->is_subsidiary_used = (bool)($product->is_subsidiary_used ?? false);
            if ($this->is_subsidiary_used && $product->subsidiaryMaterials->isNotEmpty()) {
                $this->subsidiaryMaterialsList = $product->subsidiaryMaterials->map(fn($m) => [
                    'raw_material_id'      => (string)$m->id,
                    'consumption_quantity' => (string)$m->pivot->consumption_quantity,
                    'unit'                 => $m->unit,
                ])->toArray();
            } elseif ($this->is_subsidiary_used) {
                $this->subsidiaryMaterialsList = [['raw_material_id' => '', 'consumption_quantity' => '', 'unit' => '']];
            }

            // Stitching materials
            $this->is_stitching_used = (bool)($product->is_stitching_used ?? false);
            $this->stitchingMaterialsList = $product->stitchingMaterials->pluck('id')->map(fn($id) => (string)$id)->toArray();

            // Packaging materials
            $this->is_packaging_used = (bool)($product->is_packaging_used ?? false);
            if ($this->is_packaging_used && $product->packagingMaterials->isNotEmpty()) {
                $this->packagingMaterialsList = $product->packagingMaterials->map(fn($m) => [
                    'raw_material_id'   => (string)$m->id,
                    'required_quantity' => (string)$m->pivot->required_quantity,
                    'unit'              => $m->unit,
                ])->toArray();
            } elseif ($this->is_packaging_used) {
                $this->packagingMaterialsList = [['raw_material_id' => '', 'required_quantity' => '', 'unit' => '']];
            }

            // Routing tasks
            if ($product->tasks->isNotEmpty()) {
                $this->routingTasksList = $product->tasks->map(fn($t) => [
                    'task_id'             => (string)$t->id,
                    'standard_labor_rate' => (string)($t->pivot->standard_labor_rate ?? $product->standard_labor_rate),
                    'is_final_step'       => (bool)($t->pivot->is_final_step ?? false),
                ])->toArray();
            } else {
                $this->loadDefaultRoutingTasks();
            }
        } else {
            // Generate preview code
            $year   = date('Y');
            $latest = ManufacturingProduct::where('code', 'like', "MP-{$year}-%")->latest('id')->first();
            $seq    = 1;
            if ($latest) {
                $parts = explode('-', $latest->code);
                $seq   = ((int) end($parts)) + 1;
            } else {
                $latestId = ManufacturingProduct::max('id') ?? 0;
                $seq      = $latestId + 1;
            }
            $this->code = "MP-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);

            // Default task sequence from Task Master
            $this->loadDefaultRoutingTasks();
        }
    }

    private function loadDefaultRoutingTasks(): void
    {
        $allTasks = Task::where('status', true)->get();
        if ($allTasks->isNotEmpty()) {
            $total = $allTasks->count();
            $this->routingTasksList = $allTasks->values()->map(fn($t, $idx) => [
                'task_id'             => (string)$t->id,
                'standard_labor_rate' => (string)($this->standard_labor_rate ?: 15.00),
                'is_final_step'       => ($idx === $total - 1),
            ])->toArray();
        } else {
            $this->routingTasksList = [
                ['task_id' => '', 'standard_labor_rate' => '15.00', 'is_final_step' => true]
            ];
        }
    }

    public function save()
    {
        $this->validate();

        // 1. Ensure no duplicate subsidiary materials
        if ($this->is_subsidiary_used && count($this->subsidiaryMaterialsList) > 0) {
            $selectedSubIds = array_column($this->subsidiaryMaterialsList, 'raw_material_id');
            $selectedSubIds = array_filter($selectedSubIds);
            if (count($selectedSubIds) !== count(array_unique($selectedSubIds))) {
                $this->addError('subsidiaryMaterialsList', 'Duplicate subsidiary materials selected. Each material must only appear once per product.');
                return;
            }
        }

        // 2. Ensure no duplicate packaging materials
        if ($this->is_packaging_used && count($this->packagingMaterialsList) > 0) {
            $selectedPkgIds = array_column($this->packagingMaterialsList, 'raw_material_id');
            $selectedPkgIds = array_filter($selectedPkgIds);
            if (count($selectedPkgIds) !== count(array_unique($selectedPkgIds))) {
                $this->addError('packagingMaterialsList', 'Duplicate packaging materials selected. Each material must only appear once per product.');
                return;
            }
        }

        // 3. Validate Routing Sequence Rules
        if (empty($this->routingTasksList)) {
            $this->addError('routingTasksList', 'At least one manufacturing task sequence must be configured.');
            return;
        }

        $selectedTaskIds = array_column($this->routingTasksList, 'task_id');
        $selectedTaskIds = array_filter($selectedTaskIds);

        if (count($selectedTaskIds) !== count($this->routingTasksList)) {
            $this->addError('routingTasksList', 'All routing steps must have a valid task selected.');
            return;
        }

        if (count($selectedTaskIds) !== count(array_unique($selectedTaskIds))) {
            $this->addError('routingTasksList', 'Duplicate tasks found in sequence. Each task can only appear once in the routing.');
            return;
        }

        // SRS Rule: Exactly ONE task must be designated as the Final Production Step
        $finalStepCount = 0;
        foreach ($this->routingTasksList as $rRow) {
            if (!empty($rRow['is_final_step'])) {
                $finalStepCount++;
            }
        }

        if ($finalStepCount !== 1) {
            // Auto-default last step as final step if none or multiple selected
            $lastIdx = count($this->routingTasksList) - 1;
            foreach ($this->routingTasksList as $i => $rRow) {
                $this->routingTasksList[$i]['is_final_step'] = ($i === $lastIdx);
            }
        }

        // 4. Ensure Category is active
        $category = ManufacturingProductCategory::findOrFail($this->manufacturing_product_category_id);
        if (!$category->status) {
            $this->addError('manufacturing_product_category_id', 'The selected category is inactive. Products can only be linked to active categories.');
            return;
        }

        $fabricData = [
            'is_fabric_used'        => $this->is_fabric_used,
            'standard_fabric_width'  => $this->is_fabric_used ? $this->standard_fabric_width : null,
            'standard_fabric_length' => $this->is_fabric_used ? $this->standard_fabric_length : null,
            'fabric_width_unit'      => $this->is_fabric_used ? $this->fabric_width_unit : null,
            'fabric_length_unit'     => $this->is_fabric_used ? $this->fabric_length_unit : null,
        ];

        $materialData = [
            'is_subsidiary_used' => $this->is_subsidiary_used,
            'is_stitching_used'  => $this->is_stitching_used,
            'is_packaging_used'  => $this->is_packaging_used,
        ];

        $mappingData = [
            'product_id' => $this->mapped_product_id ?: null,
            'product_combination_id' => $this->mapped_product_combination_id ?: null,
        ];

        $imageData = [];
        if ($this->imageUpload) {
            $imageData['image_path'] = $this->imageUpload->store('manufacturing_products', 'public');
        } elseif ($this->existing_image_path) {
            $imageData['image_path'] = $this->existing_image_path;
        }

        if ($this->productId) {
            $product = ManufacturingProduct::findOrFail($this->productId);
            $product->update(array_merge([
                'name'                              => $this->name,
                'manufacturing_product_category_id' => $this->manufacturing_product_category_id,
                'status'                            => $this->status,
                'standard_labor_rate'               => $this->standard_labor_rate ?: 15.00,
            ], $fabricData, $materialData, $mappingData, $imageData));
            $message = "Manufacturing Product {$product->name} updated successfully!";
        } else {
            $product = ManufacturingProduct::create(array_merge([
                'name'                              => $this->name,
                'manufacturing_product_category_id' => $this->manufacturing_product_category_id,
                'status'                            => $this->status,
                'standard_labor_rate'               => $this->standard_labor_rate ?: 15.00,
            ], $fabricData, $materialData, $mappingData, $imageData));
            $message = "Manufacturing Product {$product->name} created successfully!";
        }

        // Sync subsidiary materials pivot
        if ($this->is_subsidiary_used) {
            $syncData = [];
            foreach ($this->subsidiaryMaterialsList as $row) {
                if (!empty($row['raw_material_id'])) {
                    $syncData[$row['raw_material_id']] = [
                        'consumption_quantity' => $row['consumption_quantity'],
                    ];
                }
            }
            $product->subsidiaryMaterials()->sync($syncData);
        } else {
            $product->subsidiaryMaterials()->detach();
        }

        // Sync stitching materials pivot
        if ($this->is_stitching_used) {
            $product->stitchingMaterials()->sync(array_filter($this->stitchingMaterialsList));
        } else {
            $product->stitchingMaterials()->detach();
        }

        // Sync packaging materials pivot
        if ($this->is_packaging_used) {
            $syncPkgData = [];
            foreach ($this->packagingMaterialsList as $row) {
                if (!empty($row['raw_material_id'])) {
                    $syncPkgData[$row['raw_material_id']] = [
                        'required_quantity' => $row['required_quantity'],
                    ];
                }
            }
            $product->packagingMaterials()->sync($syncPkgData);
        } else {
            $product->packagingMaterials()->detach();
        }

        // Sync manufacturing_product_task pivot (Task Sequence / Routing)
        $taskSyncData = [];
        foreach ($this->routingTasksList as $seqIndex => $rRow) {
            $taskSyncData[$rRow['task_id']] = [
                'sequence_number'     => $seqIndex + 1,
                'standard_labor_rate' => !empty($rRow['standard_labor_rate']) ? $rRow['standard_labor_rate'] : ($this->standard_labor_rate ?: 15.00),
                'is_final_step'       => !empty($rRow['is_final_step']),
            ];
        }
        $product->tasks()->sync($taskSyncData);

        session()->flash('toast', ['message' => $message, 'type' => 'success']);
        return redirect()->route('factory.products.index');
    }

    public function render()
    {
        $activeCategories = ManufacturingProductCategory::active()->orderBy('name')->get();
        $availableTasks = Task::where('status', true)->orderBy('id')->get();

        // CAT-SUB materials for subsidiary picker
        $subsidiaryRawMaterials = RawMaterial::whereHas('category', fn($q) => $q->where('code', 'CAT-SUB'))
            ->orderBy('name')
            ->get();

        // CAT-STITCH materials for stitching picker
        $stitchingRawMaterials = RawMaterial::whereHas('category', fn($q) => $q->where('code', 'CAT-STITCH'))
            ->orderBy('name')
            ->get();

        // CAT-PKG materials for packaging picker
        $packagingRawMaterials = RawMaterial::whereHas('category', fn($q) => $q->where('code', 'CAT-PKG'))
            ->orderBy('name')
            ->get();

        $storefrontProducts = \App\Models\Product::where('is_active', true)->orderBy('title')->get();
        $storefrontCombinations = $this->mapped_product_id 
            ? \App\Models\ProductCombination::where('product_id', $this->mapped_product_id)->where('is_active', true)->get() 
            : collect();

        return view('livewire.factory.add-manufacturing-product-form', [
            'categories'             => $activeCategories,
            'availableTasks'         => $availableTasks,
            'subsidiaryRawMaterials' => $subsidiaryRawMaterials,
            'stitchingRawMaterials'  => $stitchingRawMaterials,
            'packagingRawMaterials'  => $packagingRawMaterials,
            'storefrontProducts'     => $storefrontProducts,
            'storefrontCombinations' => $storefrontCombinations,
        ])->title($this->productId ? 'Edit Manufacturing Product' : 'Add Manufacturing Product');
    }
}
