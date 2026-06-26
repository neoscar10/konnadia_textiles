<?php

namespace App\Livewire\Admin\CustomerLevels;

use App\Models\CustomerLevel;
use App\Services\Customer\CustomerLevelService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Illuminate\Validation\Rule;

#[Layout('components.admin.layout')]
class CustomerLevelIndexPage extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public $search = '';

    #[Url(history: true)]
    public $status = '';

    public $editingId = null;

    public $form = [
        'name' => '',
        'discount_percentage' => 0,
        'default_credit_limit' => 0,
        'sort_order' => 0,
        'description' => '',
        'is_active' => true,
    ];

    public $deleteId = null;

    protected function rules()
    {
        return [
            'form.name' => ['required', 'string', 'max:150', Rule::unique('customer_levels', 'name')->ignore($this->editingId)],
            'form.discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'form.sort_order' => ['nullable', 'integer', 'min:0'],
            'form.description' => ['nullable', 'string', 'max:500'],
            'form.is_active' => ['boolean'],
        ];
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatch('open-modal', 'add-customer-level');
    }

    public function edit(CustomerLevel $level)
    {
        $this->resetValidation();
        $this->editingId = $level->id;
        $this->form = [
            'name' => $level->name,
            'discount_percentage' => $level->discount_percentage,
            'default_credit_limit' => $level->default_credit_limit,
            'sort_order' => $level->sort_order,
            'description' => $level->description,
            'is_active' => $level->is_active,
        ];
        $this->dispatch('open-modal', 'add-customer-level');
    }

    public function save(CustomerLevelService $service)
    {
        $this->validate();

        if ($this->editingId) {
            $level = CustomerLevel::findOrFail($this->editingId);
            $service->update($level, $this->form);
            $this->dispatch('toast', message: 'Customer level updated successfully.', type: 'success');
        } else {
            $service->create($this->form);
            $this->dispatch('toast', message: 'Customer level created successfully.', type: 'success');
        }

        $this->dispatch('close-modal', 'add-customer-level');
        $this->resetForm();
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->dispatch('open-modal', 'delete-customer-level');
    }

    public function delete(CustomerLevelService $service)
    {
        if ($this->deleteId) {
            $level = CustomerLevel::findOrFail($this->deleteId);
            $service->delete($level);
            $this->dispatch('toast', message: 'Customer level deleted successfully.', type: 'success');
            $this->dispatch('close-modal', 'delete-customer-level');
            $this->deleteId = null;
        }
    }

    public function toggleStatus(CustomerLevel $level, CustomerLevelService $service)
    {
        $service->toggleStatus($level);
        $message = $level->is_active ? 'Customer level activated successfully.' : 'Customer level deactivated successfully.';
        $this->dispatch('toast', message: $message, type: 'success');
    }

    private function resetForm()
    {
        $this->resetValidation();
        $this->editingId = null;

        $maxSortOrder = CustomerLevel::max('sort_order');
        $nextSortOrder = $maxSortOrder !== null ? $maxSortOrder + 1 : 1;

        $this->form = [
            'name' => '',
            'discount_percentage' => 0,
            'default_credit_limit' => 0,
            'sort_order' => $nextSortOrder,
            'description' => '',
            'is_active' => true,
        ];
    }

    public function render(CustomerLevelService $service)
    {
        $levels = $service->list([
            'search' => $this->search,
            'status' => $this->status,
        ], 10);

        return view('livewire.admin.customer-levels.customer-level-index-page', [
            'levels' => $levels
        ]);
    }
}
