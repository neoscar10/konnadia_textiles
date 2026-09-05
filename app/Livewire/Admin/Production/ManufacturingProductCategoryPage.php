<?php

namespace App\Livewire\Admin\Production;

use App\Models\ManufacturingProductCategory;
use App\Models\Task;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class ManufacturingProductCategoryPage extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    // Modal form state
    public ?int $categoryId = null;
    public string $name = '';
    public bool $status = true;

    // Default Task Sequence list
    public array $defaultTasksList = [];

    public function mount(): void
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor'])
            && !auth()->user()->can('manage_labor')) {
            abort(403, 'Unauthorized access to Manufacturing Product Categories.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->reset(['categoryId', 'name']);
        $this->status = true;
        
        // Auto-load default tasks starting sequence (e.g., Cutting)
        $this->loadDefaultCuttingTask();
        
        $this->dispatch('open-modal', 'category-modal');
    }

    private function loadDefaultCuttingTask(): void
    {
        $cuttingTask = Task::where('name', 'Cutting')->orWhere('code', 'TSK-001')->first()
            ?? Task::where('status', true)->first();

        if ($cuttingTask) {
            $this->defaultTasksList = [
                [
                    'task_id'             => (string) $cuttingTask->id,
                    'standard_labor_rate' => '',
                    'is_final_step'       => true,
                ]
            ];
        } else {
            $this->defaultTasksList = [];
        }
    }

    public function addDefaultTaskRow(): void
    {
        foreach ($this->defaultTasksList as &$row) {
            $row['is_final_step'] = false;
        }

        $this->defaultTasksList[] = [
            'task_id'             => '',
            'standard_labor_rate' => '',
            'is_final_step'       => true,
        ];
    }

    public function removeDefaultTaskRow(int $index): void
    {
        $wasFinal = $this->defaultTasksList[$index]['is_final_step'] ?? false;
        array_splice($this->defaultTasksList, $index, 1);
        $this->defaultTasksList = array_values($this->defaultTasksList);

        if ($wasFinal && !empty($this->defaultTasksList)) {
            $lastIdx = count($this->defaultTasksList) - 1;
            foreach ($this->defaultTasksList as $i => &$row) {
                $row['is_final_step'] = ($i === $lastIdx);
            }
        }
    }

    public function setFinalStep(int $index): void
    {
        foreach ($this->defaultTasksList as $i => &$row) {
            $row['is_final_step'] = ($i === $index);
        }
    }

    public function moveDefaultTaskRow(int $index, string $direction): void
    {
        $targetIndex = ($direction === 'up') ? $index - 1 : $index + 1;
        if ($targetIndex < 0 || $targetIndex >= count($this->defaultTasksList)) {
            return;
        }

        $temp = $this->defaultTasksList[$index];
        $this->defaultTasksList[$index] = $this->defaultTasksList[$targetIndex];
        $this->defaultTasksList[$targetIndex] = $temp;
        $this->defaultTasksList = array_values($this->defaultTasksList);
    }

    public function editCategory(int $id): void
    {
        $this->resetValidation();
        $cat = ManufacturingProductCategory::with('defaultTasks')->findOrFail($id);
        $this->categoryId = $cat->id;
        $this->name = $cat->name;
        $this->status = (bool) $cat->status;

        if ($cat->defaultTasks->isNotEmpty()) {
            $this->defaultTasksList = $cat->defaultTasks->map(fn($t) => [
                'task_id'             => (string) $t->id,
                'standard_labor_rate' => (string) ($t->pivot->standard_labor_rate ?? ''),
                'is_final_step'       => (bool) ($t->pivot->is_final_step ?? false),
            ])->toArray();
        } else {
            $this->loadDefaultCuttingTask();
        }

        $this->dispatch('open-modal', 'category-modal');
    }

    public function saveCategory(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:manufacturing_product_categories,name,' . $this->categoryId,
            'status' => 'required|boolean',
            'defaultTasksList' => 'nullable|array',
            'defaultTasksList.*.task_id' => 'nullable|exists:tasks,id',
        ], [
            'name.required' => 'Category name is required.',
            'name.unique' => 'A manufacturing product category with this name already exists.',
        ]);

        if ($this->categoryId) {
            $cat = ManufacturingProductCategory::findOrFail($this->categoryId);
            $cat->update(['name' => $this->name, 'status' => $this->status]);
            $msg = "Category \"{$cat->name}\" updated successfully!";
        } else {
            $cat = ManufacturingProductCategory::create(['name' => $this->name, 'status' => $this->status]);
            $msg = "Category \"{$cat->name}\" created successfully!";
        }

        // Sync default task sequence
        $syncData = [];
        $validTasks = array_filter($this->defaultTasksList, fn($row) => !empty($row['task_id']));
        
        if (!empty($validTasks)) {
            // Ensure exactly one final step
            $hasFinal = false;
            foreach ($validTasks as $r) {
                if (!empty($r['is_final_step'])) $hasFinal = true;
            }
            $lastIndex = count($validTasks) - 1;

            $seq = 1;
            foreach ($validTasks as $idx => $r) {
                $isFinal = $hasFinal ? !empty($r['is_final_step']) : ($idx === $lastIndex);
                $syncData[$r['task_id']] = [
                    'sequence_number'     => $seq++,
                    'standard_labor_rate' => !empty($r['standard_labor_rate']) ? $r['standard_labor_rate'] : null,
                    'is_final_step'       => $isFinal,
                ];
            }
            $cat->defaultTasks()->sync($syncData);
        } else {
            $cat->defaultTasks()->detach();
        }

        $this->dispatch('close-modal', 'category-modal');
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function toggleStatus(int $id): void
    {
        $cat = ManufacturingProductCategory::findOrFail($id);
        $cat->update(['status' => !$cat->status]);
        $label = $cat->fresh()->status ? 'Active' : 'Inactive';
        $this->dispatch('toast', message: "Category \"{$cat->name}\" set to {$label}.", type: 'success');
    }

    public function deleteCategory(int $id): void
    {
        $cat = ManufacturingProductCategory::withCount('manufacturingProducts')->findOrFail($id);

        if ($cat->manufacturing_products_count > 0) {
            $this->dispatch('toast',
                message: "Cannot delete \"{$cat->name}\" — it is linked to {$cat->manufacturing_products_count} manufacturing product(s). Deactivate it instead.",
                type: 'error'
            );
            return;
        }

        $name = $cat->name;
        $cat->defaultTasks()->detach();
        $cat->delete();
        $this->dispatch('toast', message: "Category \"{$name}\" deleted successfully.", type: 'success');
    }

    public function render()
    {
        $query = ManufacturingProductCategory::with(['defaultTasks'])->withCount('manufacturingProducts');

        if (!empty($this->search)) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        $categories = $query->orderBy('name')->paginate(15);
        $availableTasks = Task::where('status', true)->orderBy('id')->get();

        return view('livewire.admin.production.manufacturing-product-category-page', [
            'categories' => $categories,
            'availableTasks' => $availableTasks,
        ])->title('Manufacturing Product Category Master');
    }
}
