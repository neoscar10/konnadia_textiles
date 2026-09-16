<?php

namespace App\Livewire\Admin\Labor;

use App\Models\Labor;
use Livewire\Component;
use Livewire\WithPagination;

class LaborList extends Component
{
    use WithPagination;

    public $search = '';
    public $payment_method_filter = '';
    public $status_filter = '';
    public $labor_category_filter = '';

    public ?int $editingId = null;
    public $name = '';
    public $mobile_number = '';
    public $status = true;
    public $payment_method = 'monthly_salary';
    public $monthly_salary = null;
    public ?int $labor_category_id = null;
    public $authorized_tasks = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'payment_method_filter' => ['except' => ''],
        'status_filter' => ['except' => ''],
        'labor_category_filter' => ['except' => '']
    ];

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'mobile_number' => 'nullable|string|max:20',
            'status' => 'boolean',
            'payment_method' => 'required|in:monthly_salary,job_work',
            'monthly_salary' => $this->payment_method === 'monthly_salary' ? 'required|numeric|min:0' : 'nullable',
            'labor_category_id' => 'nullable|exists:labor_categories,id',
            'authorized_tasks' => 'array',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedPaymentMethod($value)
    {
        if ($value === 'job_work') {
            $this->monthly_salary = null;
        }
    }

    public function updatedLaborCategoryId($value)
    {
        if ($value) {
            $categoryTaskIds = \App\Models\Task::where('labor_category_id', $value)
                ->where('status', true)
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();

            if (!$this->editingId) {
                // When creating a new labor, auto pre-select all tasks attached to this category
                $this->authorized_tasks = $categoryTaskIds;
            }
        }
    }

    public function create()
    {
        $this->resetValidation();
        $this->reset(['editingId', 'name', 'mobile_number', 'status', 'monthly_salary', 'authorized_tasks', 'labor_category_id']);
        $this->payment_method = 'monthly_salary';
        $this->status = true;
        
        $this->dispatch('open-modal', 'labor-form-modal');
    }

    public function edit($id)
    {
        $this->resetValidation();
        $labor = Labor::with(['category', 'tasks'])->findOrFail($id);
        
        $this->editingId = $labor->id;
        $this->name = $labor->name;
        $this->mobile_number = $labor->mobile_number;
        $this->status = (bool) $labor->status;
        $this->payment_method = $labor->payment_method;
        $this->monthly_salary = $labor->monthly_salary;
        $this->labor_category_id = $labor->labor_category_id;
        $this->authorized_tasks = $labor->tasks->pluck('id')->map(fn($id) => (string)$id)->toArray();

        $this->dispatch('open-modal', 'labor-form-modal');
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'mobile_number' => $this->mobile_number,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'monthly_salary' => $this->payment_method === 'monthly_salary' ? $this->monthly_salary : null,
            'labor_category_id' => $this->labor_category_id,
        ];

        if ($this->editingId) {
            $labor = Labor::findOrFail($this->editingId);
            $labor->update($data);
            $labor->tasks()->sync($this->authorized_tasks);
            $this->dispatch('toast', message: 'Labor details updated successfully.', type: 'success');
        } else {
            $labor = Labor::create($data);
            $labor->tasks()->sync($this->authorized_tasks);
            $this->dispatch('toast', message: 'Labor added successfully.', type: 'success');
        }

        $this->dispatch('close-modal', 'labor-form-modal');
    }

    public function render()
    {
        $query = Labor::with(['category', 'tasks']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%')
                  ->orWhere('mobile_number', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->payment_method_filter) {
            $query->where('payment_method', $this->payment_method_filter);
        }

        if ($this->status_filter !== '') {
            $query->where('status', $this->status_filter);
        }

        if ($this->labor_category_filter) {
            $query->where('labor_category_id', $this->labor_category_filter);
        }

        $labors = $query->paginate(10);
        $laborCategories = \App\Models\LaborCategory::active()->orderBy('name')->get();

        // Tasks listing logic: if a labor_category_id is selected in modal, load category tasks + currently authorized tasks; otherwise load all active tasks.
        if ($this->labor_category_id) {
            $availableTasks = \App\Models\Task::where('status', true)
                ->where(function ($q) {
                    $q->where('labor_category_id', $this->labor_category_id)
                      ->orWhereIn('id', $this->authorized_tasks);
                })
                ->ordered()
                ->get();
        } else {
            $availableTasks = \App\Models\Task::where('status', true)->ordered()->get();
        }

        return view('livewire.admin.labor.labor-list', [
            'labors' => $labors,
            'allTasks' => $availableTasks,
            'laborCategories' => $laborCategories,
        ])->layout('components.admin.layout', ['title' => 'Labor Management']);
    }
}
