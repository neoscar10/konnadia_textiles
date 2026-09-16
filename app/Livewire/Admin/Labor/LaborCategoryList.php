<?php

namespace App\Livewire\Admin\Labor;

use App\Models\LaborCategory;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class LaborCategoryList extends Component
{
    use WithPagination;

    public string $search = '';

    // Modal Form State
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $code = '';
    public ?string $description = '';
    public bool $status = true;

    // Delete Modal State
    public ?int $deleteId = null;
    public string $deletingCategoryName = '';

    protected $queryString = ['search' => ['except' => '']];

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:labor_categories,name,' . $this->editingId,
            'code' => 'nullable|string|max:50|unique:labor_categories,code,' . $this->editingId,
            'description' => 'nullable|string',
            'status' => 'required|boolean',
        ];
    }

    protected function messages()
    {
        return [
            'name.required' => 'Labour Category Name is required.',
            'name.unique' => 'A Labour Category with this name already exists.',
            'code.unique' => 'A Labour Category with this code already exists.',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetModal();
        $this->showModal = true;
    }

    public function edit(int $id)
    {
        $this->resetModal();
        $category = LaborCategory::findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description;
        $this->status = (bool) $category->status;

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetModal();
    }

    public function resetModal()
    {
        $this->reset(['editingId', 'name', 'code', 'description', 'status']);
        $this->status = true;
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
        ];

        if (!empty($this->code)) {
            $data['code'] = $this->code;
        }

        if ($this->editingId) {
            $category = LaborCategory::findOrFail($this->editingId);
            $category->update($data);
            $message = "Labour Category [{$category->name}] updated successfully.";
        } else {
            $category = LaborCategory::create($data);
            $message = "Labour Category [{$category->name}] created successfully.";
        }

        $this->dispatch('toast', message: $message, type: 'success');
        $this->closeModal();
    }

    public function toggleStatus(int $id)
    {
        $category = LaborCategory::findOrFail($id);
        $category->update(['status' => !$category->status]);

        $statusText = $category->status ? 'Active' : 'Inactive';
        $this->dispatch('toast', message: "Labour Category [{$category->name}] status set to {$statusText}.", type: 'success');
    }

    public function confirmDelete(int $id)
    {
        $category = LaborCategory::findOrFail($id);

        $taskCount = $category->tasks()->count();
        $laborCount = $category->labors()->count();

        if ($taskCount > 0 || $laborCount > 0) {
            $msg = "Cannot delete category [{$category->name}] because it is currently assigned to ";
            $parts = [];
            if ($taskCount > 0) $parts[] = "{$taskCount} task(s)";
            if ($laborCount > 0) $parts[] = "{$laborCount} worker(s)";
            $msg .= implode(' and ', $parts) . '.';

            $this->dispatch('toast', message: $msg, type: 'error');
            return;
        }

        $this->deleteId = $id;
        $this->deletingCategoryName = $category->name;
        $this->dispatch('open-modal', 'delete-category-modal');
    }

    public function delete()
    {
        if ($this->deleteId) {
            $category = LaborCategory::findOrFail($this->deleteId);

            if ($category->tasks()->count() > 0 || $category->labors()->count() > 0) {
                $this->dispatch('toast', message: "Cannot delete category [{$category->name}] because it is assigned to existing tasks or workers.", type: 'error');
                $this->dispatch('close-modal', 'delete-category-modal');
                $this->deleteId = null;
                return;
            }

            $category->delete();
            $this->dispatch('toast', message: "Labour Category [{$category->name}] deleted successfully.", type: 'success');
            $this->dispatch('close-modal', 'delete-category-modal');
            $this->deleteId = null;
        }
    }

    public function render()
    {
        $categories = LaborCategory::withCount(['tasks', 'labors'])
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.labor.labor-category-list', [
            'categories' => $categories,
        ])->title('Labour Categories');
    }
}
