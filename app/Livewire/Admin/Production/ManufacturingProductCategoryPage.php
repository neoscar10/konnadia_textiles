<?php

namespace App\Livewire\Admin\Production;

use App\Models\ManufacturingProductCategory;
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
        $this->dispatch('open-modal', 'category-modal');
    }

    public function editCategory(int $id): void
    {
        $this->resetValidation();
        $cat = ManufacturingProductCategory::findOrFail($id);
        $this->categoryId = $cat->id;
        $this->name = $cat->name;
        $this->status = (bool) $cat->status;
        $this->dispatch('open-modal', 'category-modal');
    }

    public function saveCategory(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:manufacturing_product_categories,name,' . $this->categoryId,
            'status' => 'required|boolean',
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
        $cat->delete();
        $this->dispatch('toast', message: "Category \"{$name}\" deleted successfully.", type: 'success');
    }

    public function render()
    {
        $query = ManufacturingProductCategory::withCount('manufacturingProducts');

        if (!empty($this->search)) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        $categories = $query->orderBy('name')->paginate(15);

        return view('livewire.admin.production.manufacturing-product-category-page', [
            'categories' => $categories,
        ])->title('Manufacturing Product Category Master');
    }
}
