<?php

namespace App\Livewire\Factory;

use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use App\Models\ManufacturingProductPattern;
use App\Models\FabricWidth;
use App\Models\RawMaterial;
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
    public $standard_labor_rate = 0.00;
    public $imageUpload = null;
    public $existing_image_path = '';

    // Customised Product ID toggle UI placeholder (from prototype step 1)
    public bool $is_customised_product = false;
    public string $customised_mode = 'fixed'; // 'fixed' or 'calc'

    // Patterns repeater for Step 2
    // Structure per pattern:
    // [
    //   'id' => null,
    //   'name' => 'Standard Fold',
    //   'fabric_width_id' => '',
    //   'fabric_length' => '',
    //   'fabric_length_unit' => 'm',
    //   'standard_labor_rate' => '',
    //   'tasks' => [ ['task_id' => '', 'standard_labor_rate' => '', 'is_final_step' => true] ]
    // ]
    public array $patternsList = [];

    // Subsidiary material configuration properties (Step 3)
    public bool $is_subsidiary_used = false;
    public bool $is_common_subsidiary = true;
    public array $subsidiaryMaterialsList = []; // [['raw_material_id' => '', 'consumption_quantity' => '', 'unit' => '']]

    // Backwards compatibility properties for legacy tests & components
    public bool $is_stitching_used = false;
    public array $stitchingMaterialsList = [];
    public array $routingTasksList = [];

    // Wizard State
    public int $wizardStep = 1;
    public int $maxSteps = 3;

    public function updatedRoutingTasksList($value): void
    {
        if (isset($this->patternsList[0])) {
            $this->patternsList[0]['tasks'] = $value;
        }
    }

    protected function rules()
    {
        $rules = [
            'name'                              => 'required|string|max:255',
            'manufacturing_product_category_id' => 'required|exists:manufacturing_product_categories,id',
            'status'                            => 'required|in:active,inactive',
            'imageUpload'                       => 'nullable|image|max:10240',
        ];

        if ($this->wizardStep === 2 || $this->wizardStep === 3) {
            $rules['patternsList'] = 'required|array|min:1';
            $rules['patternsList.*.name'] = 'required|string|max:255';
            $rules['patternsList.*.fabric_width_id'] = 'required|exists:fabric_widths,id';
            $rules['patternsList.*.fabric_length'] = 'required|numeric|min:0.01';
            $rules['patternsList.*.fabric_length_unit'] = 'required|string';
            $rules['patternsList.*.tasks'] = 'required|array|min:1';
            $rules['patternsList.*.tasks.*.task_id'] = 'required|exists:tasks,id';
        }

        if ($this->wizardStep === 3 && $this->is_subsidiary_used) {
            $rules['subsidiaryMaterialsList']                         = 'array';
            $rules['subsidiaryMaterialsList.*.raw_material_id']      = 'required|exists:raw_materials,id';
            $rules['subsidiaryMaterialsList.*.consumption_quantity'] = 'required|numeric|min:0.0001';
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'name.required'                              => 'Product Name is required.',
            'manufacturing_product_category_id.required' => 'Please select a product category.',
            'manufacturing_product_category_id.exists'   => 'Selected category is invalid.',
            'patternsList.required'                      => 'At least one pattern must be configured for this product.',
            'patternsList.min'                           => 'At least one pattern must be configured for this product.',
            'patternsList.*.name.required'               => 'Pattern name is required.',
            'patternsList.*.fabric_width_id.required'    => 'Please select a fabric width option.',
            'patternsList.*.fabric_length.required'      => 'Pattern length is required.',
            'patternsList.*.tasks.required'              => 'Each pattern must have a valid task routing sequence.',
            'patternsList.*.tasks.*.task_id.required'    => 'Please select a task for each routing step in the pattern.',
            'subsidiaryMaterialsList.*.raw_material_id.required' => 'Please select a subsidiary material.',
            'subsidiaryMaterialsList.*.consumption_quantity.required' => 'Consumption quantity is required.',
        ];
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    // --- Pattern Repeater Actions ---
    public function addPatternRow(): void
    {
        $defaultWidth = FabricWidth::where('status', true)->first();
        $newPattern = [
            'id'                  => null,
            'name'                => 'Pattern ' . (count($this->patternsList) + 1),
            'fabric_width_id'     => $defaultWidth ? (string) $defaultWidth->id : '',
            'fabric_length'       => '2.50',
            'fabric_length_unit'  => 'm',
            'standard_labor_rate' => '',
            'tasks'               => [],
        ];

        $this->patternsList[] = $newPattern;
        $patternIndex = count($this->patternsList) - 1;
        $this->loadCategoryDefaultTasksForPattern($patternIndex);
    }

    public function removePatternRow(int $index): void
    {
        if (count($this->patternsList) > 1) {
            array_splice($this->patternsList, $index, 1);
            $this->patternsList = array_values($this->patternsList);
        } else {
            $this->dispatch('toast', message: 'A manufacturing product must have at least one pattern.', type: 'error');
        }
    }

    public function loadCategoryDefaultTasksForPattern(int $patternIndex): void
    {
        if (empty($this->manufacturing_product_category_id)) {
            $this->dispatch('toast', message: 'Please select a Category first to load its default sequence.', type: 'error');
            return;
        }

        $category = ManufacturingProductCategory::with('defaultTasks')->find($this->manufacturing_product_category_id);
        
        if ($category && $category->defaultTasks->isNotEmpty()) {
            $this->patternsList[$patternIndex]['tasks'] = $category->defaultTasks->map(fn($t) => [
                'task_id'             => (string) $t->id,
                'standard_labor_rate' => (string) ($t->pivot->standard_labor_rate ?? ''),
                'is_final_step'       => (bool) ($t->pivot->is_final_step ?? false),
            ])->toArray();
            $this->dispatch('toast', message: "Loaded default task sequence for Category \"{$category->name}\".", type: 'success');
        } else {
            // Default Cutting task fallback
            $cuttingTask = Task::where('name', 'Cutting')->orWhere('code', 'TSK-001')->first()
                ?? Task::where('status', true)->first();

            if ($cuttingTask) {
                $this->patternsList[$patternIndex]['tasks'] = [
                    [
                        'task_id'             => (string) $cuttingTask->id,
                        'standard_labor_rate' => '',
                        'is_final_step'       => true,
                    ]
                ];
            } else {
                $this->patternsList[$patternIndex]['tasks'] = [];
            }
        }
    }

    public function addPatternTaskRow(int $patternIndex): void
    {
        if (!isset($this->patternsList[$patternIndex]['tasks'])) {
            $this->patternsList[$patternIndex]['tasks'] = [];
        }

        foreach ($this->patternsList[$patternIndex]['tasks'] as &$r) {
            $r['is_final_step'] = false;
        }

        $this->patternsList[$patternIndex]['tasks'][] = [
            'task_id'             => '',
            'standard_labor_rate' => '',
            'is_final_step'       => true,
        ];
    }

    public function removePatternTaskRow(int $patternIndex, int $taskIndex): void
    {
        $wasFinal = $this->patternsList[$patternIndex]['tasks'][$taskIndex]['is_final_step'] ?? false;
        array_splice($this->patternsList[$patternIndex]['tasks'], $taskIndex, 1);
        $this->patternsList[$patternIndex]['tasks'] = array_values($this->patternsList[$patternIndex]['tasks']);

        if ($wasFinal && !empty($this->patternsList[$patternIndex]['tasks'])) {
            $lastIdx = count($this->patternsList[$patternIndex]['tasks']) - 1;
            foreach ($this->patternsList[$patternIndex]['tasks'] as $i => &$r) {
                $r['is_final_step'] = ($i === $lastIdx);
            }
        }
    }

    public function setPatternFinalStep(int $patternIndex, int $taskIndex): void
    {
        foreach ($this->patternsList[$patternIndex]['tasks'] as $i => &$r) {
            $r['is_final_step'] = ($i === $taskIndex);
        }
    }

    public function movePatternTaskRow(int $patternIndex, int $taskIndex, string $direction): void
    {
        $targetIndex = ($direction === 'up') ? $taskIndex - 1 : $taskIndex + 1;
        $tasks = &$this->patternsList[$patternIndex]['tasks'];
        
        if ($targetIndex < 0 || $targetIndex >= count($tasks)) {
            return;
        }

        $temp = $tasks[$taskIndex];
        $tasks[$taskIndex] = $tasks[$targetIndex];
        $tasks[$targetIndex] = $temp;
        $tasks = array_values($tasks);
    }

    // --- Subsidiary Material Actions ---
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

    public function mount($id = null)
    {
        if ($id) {
            $product = ManufacturingProduct::with(['patterns.tasks', 'patterns.fabricWidth', 'subsidiaryMaterials'])->findOrFail($id);
            $this->productId                         = $product->id;
            $this->name                              = $product->name;
            $this->code                              = $product->code;
            $this->existing_image_path               = $product->image_path ?? '';
            $this->manufacturing_product_category_id = $product->manufacturing_product_category_id ?? '';
            $this->status                            = $product->status ?? 'active';
            $this->standard_labor_rate               = $product->standard_labor_rate ?? 0.00;

            // Load Subsidiary materials
            $this->is_subsidiary_used = (bool)($product->is_subsidiary_used ?? false);
            if ($this->is_subsidiary_used && $product->subsidiaryMaterials->isNotEmpty()) {
                $this->subsidiaryMaterialsList = $product->subsidiaryMaterials->map(fn($m) => [
                    'raw_material_id'      => (string)$m->id,
                    'consumption_quantity' => (string)$m->pivot->consumption_quantity,
                    'unit'                 => $m->unit,
                ])->toArray();
            } else {
                $this->subsidiaryMaterialsList = [['raw_material_id' => '', 'consumption_quantity' => '', 'unit' => '']];
            }

            // Load Patterns
            if ($product->patterns->isNotEmpty()) {
                $this->patternsList = $product->patterns->map(function ($p) {
                    return [
                        'id'                  => $p->id,
                        'name'                => $p->name,
                        'fabric_width_id'     => (string) ($p->fabric_width_id ?? ''),
                        'fabric_length'       => (string) ($p->fabric_length ?? ''),
                        'fabric_length_unit'  => $p->fabric_length_unit ?? 'm',
                        'standard_labor_rate' => (string) ($p->standard_labor_rate ?? ''),
                        'tasks'               => $p->tasks->map(fn($t) => [
                            'task_id'             => (string) $t->id,
                            'standard_labor_rate' => (string) ($t->pivot->standard_labor_rate ?? ''),
                            'is_final_step'       => (bool) ($t->pivot->is_final_step ?? false),
                        ])->toArray(),
                    ];
                })->toArray();
            } else {
                $this->initDefaultPatternFromProduct($product);
            }
        } else {
            // Code preview
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

            // Initialize pattern 1
            $defaultWidth = FabricWidth::where('status', true)->first();
            $this->patternsList = [
                [
                    'id'                  => null,
                    'name'                => 'Standard Fold',
                    'fabric_width_id'     => $defaultWidth ? (string) $defaultWidth->id : '',
                    'fabric_length'       => '2.50',
                    'fabric_length_unit'  => 'm',
                    'standard_labor_rate' => '',
                    'tasks'               => [],
                ]
            ];

            $this->subsidiaryMaterialsList = [['raw_material_id' => '', 'consumption_quantity' => '', 'unit' => '']];
        }

        $this->routingTasksList = $this->patternsList[0]['tasks'] ?? [];
    }

    private function initDefaultPatternFromProduct($product): void
    {
        $matchedWidth = null;
        if (!empty($product->standard_fabric_width)) {
            $matchedWidth = FabricWidth::where('value', $product->standard_fabric_width)->first();
        }
        if (!$matchedWidth) {
            $matchedWidth = FabricWidth::where('status', true)->first();
        }

        $tasks = $product->tasks->isNotEmpty()
            ? $product->tasks->map(fn($t) => [
                'task_id'             => (string) $t->id,
                'standard_labor_rate' => (string) ($t->pivot->standard_labor_rate ?? ''),
                'is_final_step'       => (bool) ($t->pivot->is_final_step ?? false),
            ])->toArray()
            : [];

        $this->patternsList = [
            [
                'id'                  => null,
                'name'                => 'Standard Fold',
                'fabric_width_id'     => $matchedWidth ? (string) $matchedWidth->id : '',
                'fabric_length'       => (string) ($product->standard_fabric_length ?? '2.50'),
                'fabric_length_unit'  => $product->fabric_length_unit ?? 'm',
                'standard_labor_rate' => (string) ($product->standard_labor_rate ?? ''),
                'tasks'               => $tasks,
            ]
        ];

        if (empty($tasks)) {
            $this->loadCategoryDefaultTasksForPattern(0);
        }
    }

    public function setWizardStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->maxSteps) {
            if ($step > $this->wizardStep) {
                $this->validateCurrentStep();
            }
            $this->wizardStep = $step;
        }
    }

    public function validateCurrentStep(): void
    {
        if ($this->wizardStep === 1) {
            $this->validate([
                'name'                              => 'required|string|max:255',
                'manufacturing_product_category_id' => 'required|exists:manufacturing_product_categories,id',
            ]);

            // If patterns tasks are empty, pre-fill pattern 1 with category defaults
            if (!empty($this->patternsList[0]) && empty($this->patternsList[0]['tasks'])) {
                $this->loadCategoryDefaultTasksForPattern(0);
            }
        } elseif ($this->wizardStep === 2) {
            $this->validate([
                'patternsList'                            => 'required|array|min:1',
                'patternsList.*.name'                     => 'required|string|max:255',
                'patternsList.*.fabric_width_id'          => 'required|exists:fabric_widths,id',
                'patternsList.*.fabric_length'            => 'required|numeric|min:0.01',
                'patternsList.*.tasks'                    => 'required|array|min:1',
                'patternsList.*.tasks.*.task_id'          => 'required|exists:tasks,id',
            ]);
        }
    }

    public function nextStep(): void
    {
        $this->validateCurrentStep();

        if ($this->wizardStep < $this->maxSteps) {
            $this->wizardStep++;
            $this->dispatch('scroll-to-top');
        }
    }

    public function previousStep(): void
    {
        if ($this->wizardStep > 1) {
            $this->wizardStep--;
            $this->dispatch('scroll-to-top');
        }
    }

    public function save()
    {
        // Full validation
        $this->validate();

        // 1. Check duplicate subsidiary materials
        if ($this->is_subsidiary_used && count($this->subsidiaryMaterialsList) > 0) {
            $selectedSubIds = array_column($this->subsidiaryMaterialsList, 'raw_material_id');
            $selectedSubIds = array_filter($selectedSubIds);
            if (count($selectedSubIds) !== count(array_unique($selectedSubIds))) {
                $this->addError('subsidiaryMaterialsList', 'Duplicate subsidiary materials selected. Each material must only appear once per product.');
                return;
            }
        }

        // 2. Validate category status
        $category = ManufacturingProductCategory::findOrFail($this->manufacturing_product_category_id);
        if (!$category->status) {
            $this->addError('manufacturing_product_category_id', 'Selected category is inactive.');
            return;
        }

        // Extract primary fabric values from default/first pattern for legacy backward compatibility
        $firstPattern = $this->patternsList[0] ?? null;
        $firstWidth = $firstPattern ? FabricWidth::find($firstPattern['fabric_width_id']) : null;

        $fabricData = [
            'is_fabric_used'         => true,
            'standard_fabric_width'  => $firstWidth?->value ?? 44.00,
            'standard_fabric_length' => $firstPattern ? $firstPattern['fabric_length'] : 2.50,
            'fabric_width_unit'      => $firstWidth?->unit ?? 'in',
            'fabric_length_unit'     => $firstPattern ? $firstPattern['fabric_length_unit'] : 'm',
        ];

        $materialData = [
            'is_subsidiary_used' => $this->is_subsidiary_used,
            'is_stitching_used'  => $this->is_stitching_used || !empty($this->stitchingMaterialsList),
            'is_packaging_used'  => false,
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
                'standard_labor_rate'               => $this->standard_labor_rate ?: 0.00,
            ], $fabricData, $materialData, $imageData));
            $message = "Manufacturing Product {$product->name} updated successfully!";
        } else {
            $product = ManufacturingProduct::create(array_merge([
                'name'                              => $this->name,
                'manufacturing_product_category_id' => $this->manufacturing_product_category_id,
                'status'                            => $this->status,
                'standard_labor_rate'               => $this->standard_labor_rate ?: 0.00,
            ], $fabricData, $materialData, $imageData));
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
        if ($this->is_stitching_used || !empty($this->stitchingMaterialsList)) {
            $product->stitchingMaterials()->sync(array_filter($this->stitchingMaterialsList));
        } else {
            $product->stitchingMaterials()->detach();
        }

        // Save Patterns & Pattern Tasks
        $keptPatternIds = [];
        foreach ($this->patternsList as $pIndex => $pRow) {
            $isDefault = ($pIndex === 0);
            
            $patternModel = ManufacturingProductPattern::updateOrCreate(
                [
                    'id'                       => $pRow['id'] ?? null,
                    'manufacturing_product_id' => $product->id,
                ],
                [
                    'name'                => $pRow['name'] ?? 'Standard Fold',
                    'fabric_width_id'     => $pRow['fabric_width_id'],
                    'fabric_length'       => $pRow['fabric_length'],
                    'fabric_length_unit'  => $pRow['fabric_length_unit'] ?? 'm',
                    'standard_labor_rate' => $pRow['standard_labor_rate'] ?: 0.00,
                    'is_default'          => $isDefault,
                ]
            );

            $keptPatternIds[] = $patternModel->id;

            // Sync Pattern Tasks
            $taskSyncData = [];
            $validTasks = array_filter($pRow['tasks'] ?? [], fn($t) => !empty($t['task_id']));
            
            $hasFinal = false;
            foreach ($validTasks as $vt) {
                if (!empty($vt['is_final_step'])) $hasFinal = true;
            }
            $lastTaskIndex = count($validTasks) - 1;

            $seq = 1;
            foreach ($validTasks as $idx => $tRow) {
                $isFinal = $hasFinal ? !empty($tRow['is_final_step']) : ($idx === $lastTaskIndex);
                $taskSyncData[$tRow['task_id']] = [
                    'sequence_number'     => $seq++,
                    'standard_labor_rate' => !empty($tRow['standard_labor_rate']) ? $tRow['standard_labor_rate'] : ($pRow['standard_labor_rate'] ?: 0.00),
                    'is_final_step'       => $isFinal,
                ];
            }
            $patternModel->tasks()->sync($taskSyncData);

            // Copy default pattern tasks to product task pivot for legacy system compatibility
            if ($isDefault) {
                $product->tasks()->sync($taskSyncData);
            }
        }

        // Delete removed patterns
        ManufacturingProductPattern::where('manufacturing_product_id', $product->id)
            ->whereNotIn('id', $keptPatternIds)
            ->delete();

        session()->flash('toast', ['message' => $message, 'type' => 'success']);
        return redirect()->route('factory.products.index');
    }

    public function render()
    {
        $activeCategories = ManufacturingProductCategory::active()->orderBy('name')->get();
        $availableTasks = Task::where('status', true)->orderBy('id')->get();
        $fabricWidths = FabricWidth::active()->orderBy('value', 'asc')->get();

        // CAT-SUB materials for subsidiary picker
        $subsidiaryRawMaterials = RawMaterial::whereHas('category', fn($q) => $q->where('code', 'CAT-SUB'))
            ->orderBy('name')
            ->get();

        return view('livewire.factory.add-manufacturing-product-form', [
            'categories'             => $activeCategories,
            'availableTasks'         => $availableTasks,
            'fabricWidths'           => $fabricWidths,
            'subsidiaryRawMaterials' => $subsidiaryRawMaterials,
        ])->title($this->productId ? 'Edit Manufacturing Product' : 'Add Manufacturing Product');
    }
}
