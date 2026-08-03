<?php

namespace App\Livewire\Factory;

use App\Models\Task;
use App\Models\RawMaterialCategory;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class TaskMasterManager extends Component
{
    public $taskId = null;
    public string $name = '';
    public string $code = '';
    public bool $status = true;
    public bool $consumes_raw_material = false;
    public array $selected_category_ids = [];
    public bool $is_labor_required = true;
    public ?int $sequence_number = null;

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

    public function mount($id = null)
    {
        if ($id) {
            $task = Task::with('rawMaterialCategories')->findOrFail($id);
            $this->taskId = $task->id;
            $this->name = $task->name;
            $this->code = $task->code;
            $this->status = (bool) $task->status;
            $this->consumes_raw_material = (bool) $task->consumes_raw_material;
            $this->is_labor_required = (bool) $task->is_labor_required;
            $this->sequence_number = $task->sequence_number;
            $this->selected_category_ids = $task->rawMaterialCategories->pluck('id')->map(fn($id) => (string)$id)->toArray();
        }
    }

    public function updatedConsumesRawMaterial($value)
    {
        if (!$value) {
            $this->selected_category_ids = [];
        }
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'status' => $this->status,
            'consumes_raw_material' => $this->consumes_raw_material,
            'is_labor_required' => $this->is_labor_required,
            'sequence_number' => $this->sequence_number ?: null,
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

        // Sync allowed categories if consumption enabled
        if ($this->consumes_raw_material) {
            $task->rawMaterialCategories()->sync($this->selected_category_ids);
        } else {
            $task->rawMaterialCategories()->detach();
        }

        session()->flash('toast', ['message' => $message, 'type' => 'success']);
        return redirect()->route('factory.tasks.index');
    }

    public function render()
    {
        $categories = RawMaterialCategory::all();

        return view('livewire.factory.task-master-manager', [
            'categories' => $categories,
        ])->title($this->taskId ? 'Edit Task Master' : 'Create Task Master');
    }
}
