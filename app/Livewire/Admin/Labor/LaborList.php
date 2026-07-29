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

    public ?int $editingId = null;
    public $name = '';
    public $mobile_number = '';
    public $status = true;
    public $payment_method = 'monthly_salary';
    public $monthly_salary = null;
    public $authorized_tasks = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'payment_method_filter' => ['except' => ''],
        'status_filter' => ['except' => '']
    ];

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'mobile_number' => 'nullable|string|max:20',
            'status' => 'boolean',
            'payment_method' => 'required|in:monthly_salary,job_work',
            'monthly_salary' => $this->payment_method === 'monthly_salary' ? 'required|numeric|min:0' : 'nullable',
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

    public function create()
    {
        $this->resetValidation();
        $this->reset(['editingId', 'name', 'mobile_number', 'status', 'monthly_salary', 'authorized_tasks']);
        $this->payment_method = 'monthly_salary';
        $this->status = true;
        
        $this->dispatch('open-modal', 'labor-form-modal');
    }

    public function edit($id)
    {
        $this->resetValidation();
        $labor = Labor::with('tasks')->findOrFail($id);
        
        $this->editingId = $labor->id;
        $this->name = $labor->name;
        $this->mobile_number = $labor->mobile_number;
        $this->status = $labor->status;
        $this->payment_method = $labor->payment_method;
        $this->monthly_salary = $labor->monthly_salary;
        $this->authorized_tasks = $labor->tasks->pluck('id')->toArray();

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
        $query = Labor::with('tasks');

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

        $labors = $query->paginate(10);

        return view('livewire.admin.labor.labor-list', [
            'labors' => $labors,
            'allTasks' => \App\Models\Task::where('status', true)->get(),
        ])->layout('components.admin.layout', ['title' => 'Labor Management']);
    }
}
