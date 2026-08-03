<?php

namespace App\Livewire\Factory;

use App\Models\Task;
use App\Models\RawMaterialCategory;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class TaskList extends Component
{
    use WithPagination;

    public string $search = '';

    // Modal State for Task Form
    public bool $showModal = false;
    public $taskId = null;
    public string $name = '';
    public string $code = '';
    public bool $status = true;
    public bool $consumes_raw_material = false;
    public array $selected_category_ids = [];
    public bool $is_labor_required = true;
    public ?int $sequence_number = null;

    // Delete Modal State
    public ?int $deleteId = null;
    public string $deletingTaskName = '';

    protected $queryString = ['search' => ['except' => '']];

    protected function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('tasks', 'name')->ignore($this->taskId),
            ],
            'code' => 'nullable|string|max:50|unique:tasks,code,' . $this->taskId,
            'status' => 'required|boolean',
            'consumes_raw_material' => 'required|boolean',
            'is_labor_required' => 'required|boolean',
            'selected_category_ids' => 'required_if:consumes_raw_material,true|array',
            'selected_category_ids.*' => 'exists:raw_material_categories,id',
            'sequence_number' => 'nullable|integer|min:1',
        ];
    }

    protected function messages()
    {
        return [
            'name.required' => 'Task Name is required.',
            'selected_category_ids.required_if' => 'Please select at least one raw material category when raw material consumption is enabled.',
        ];
    }

    public function mount()
    {
        if (request()->query('action') === 'create') {
            $this->openCreateModal();
        } elseif ($editId = request()->query('edit')) {
            $this->openEditModal($editId);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetModal();
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $this->resetModal();
        $task = Task::with('rawMaterialCategories')->findOrFail($id);
        $this->taskId = $task->id;
        $this->name = $task->name;
        $this->code = $task->code;
        $this->status = (bool) $task->status;
        $this->consumes_raw_material = (bool) $task->consumes_raw_material;
        $this->is_labor_required = (bool) $task->is_labor_required;
        $this->sequence_number = $task->sequence_number;
        $this->selected_category_ids = $task->rawMaterialCategories->pluck('id')->map(fn($catId) => (string)$catId)->toArray();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetModal();
    }

    public function resetModal()
    {
        $this->reset([
            'taskId',
            'name',
            'code',
            'status',
            'consumes_raw_material',
            'selected_category_ids',
            'is_labor_required',
            'sequence_number',
        ]);
        $this->status = true;
        $this->is_labor_required = true;
        $this->resetValidation();
    }

    public function updatedConsumesRawMaterial($value)
    {
        if (!$value) {
            $this->selected_category_ids = [];
        }
    }

    public function saveTask()
    {
        $this->validate();

        $seq = $this->sequence_number;
        if (!$this->taskId && empty($seq)) {
            $maxSeq = Task::max('sequence_number') ?? 0;
            $seq = $maxSeq + 1;
        }

        $data = [
            'name' => $this->name,
            'status' => $this->status,
            'consumes_raw_material' => $this->consumes_raw_material,
            'is_labor_required' => $this->is_labor_required,
            'sequence_number' => $seq ?: null,
        ];

        if (!empty($this->code)) {
            $data['code'] = $this->code;
        }

        if ($this->taskId) {
            $task = Task::findOrFail($this->taskId);
            $task->update($data);
            $message = "Task [{$task->name}] updated successfully!";
        } else {
            $task = Task::create($data);
            $message = "Task [{$task->name}] created successfully!";
        }

        if ($this->consumes_raw_material) {
            $task->rawMaterialCategories()->sync($this->selected_category_ids);
        } else {
            $task->rawMaterialCategories()->detach();
        }

        $this->dispatch('toast', message: $message, type: 'success');
        $this->closeModal();
    }

    public function reorderTasks(array $orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            Task::where('id', $id)->update([
                'sequence_number' => $index + 1,
            ]);
        }

        $this->dispatch('toast', message: 'Task sequence order updated successfully.', type: 'success');
    }

    public function toggleStatus($id)
    {
        $task = Task::findOrFail($id);
        $task->update(['status' => !$task->status]);

        $statusText = $task->status ? 'Active' : 'Inactive';
        $this->dispatch('toast', message: "Task {$task->code} status set to {$statusText}.", type: 'success');
    }

    public function confirmDelete(int $id)
    {
        $task = Task::findOrFail($id);

        if ($task->manufacturingProducts()->count() > 0) {
            $this->dispatch('toast', message: "Cannot delete task [{$task->name}] because it is currently linked to one or more manufacturing product routings.", type: 'error');
            return;
        }

        $this->deleteId = $id;
        $this->deletingTaskName = $task->name;
        $this->dispatch('open-modal', 'delete-task-modal');
    }

    public function delete()
    {
        if ($this->deleteId) {
            $task = Task::findOrFail($this->deleteId);
            
            if ($task->manufacturingProducts()->count() > 0) {
                $this->dispatch('toast', message: "Cannot delete task [{$task->name}] because it is currently linked to one or more manufacturing product routings.", type: 'error');
                $this->dispatch('close-modal', 'delete-task-modal');
                $this->deleteId = null;
                return;
            }

            $task->delete();
            $this->dispatch('toast', message: "Task [{$task->name}] deleted successfully.", type: 'success');
            $this->dispatch('close-modal', 'delete-task-modal');
            $this->deleteId = null;
        }
    }

    public function render()
    {
        $tasks = Task::with('rawMaterialCategories')
            ->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->ordered()
            ->paginate(10);

        $categories = RawMaterialCategory::all();

        return view('livewire.factory.task-list', [
            'tasks' => $tasks,
            'categories' => $categories,
        ])->title('Task Master Manager');
    }
}
