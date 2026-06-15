<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
use App\Services\Catalog\CategoryService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class CategoryIndexPage extends Component
{
    #[Url(as: 'folder', history: true)]
    public ?int $currentCategoryId = null;

    #[Url(history: true)]
    public string $search = '';

    // Modal Control Flags
    public bool $showCategoryModal = false;
    public bool $showDeleteModal = false;

    public ?int $editingCategoryId = null;
    public ?int $deleteCategoryId = null;

    // Form inputs
    public array $form = [
        'name' => '',
        'description' => '',
        'is_active' => true,
    ];

    protected function rules()
    {
        return [
            'form.name' => ['required', 'string', 'max:150'],
            'form.description' => ['nullable', 'string', 'max:500'],
            'form.is_active' => ['boolean'],
        ];
    }

    public function selectCategory(?int $id)
    {
        $this->currentCategoryId = $id;
        $this->search = '';
        $this->resetErrorBag();
    }

    public function navigateUp()
    {
        if ($this->currentCategoryId) {
            $current = Category::find($this->currentCategoryId);
            $this->selectCategory($current ? $current->parent_id : null);
        }
    }

    public function create()
    {
        $this->resetForm();
        $this->dispatch('open-modal', 'add-category');
    }

    public function edit(int $id)
    {
        $this->resetValidation();
        $category = Category::findOrFail($id);
        $this->editingCategoryId = $category->id;
        $this->form = [
            'name' => $category->name,
            'description' => $category->description,
            'is_active' => (bool)$category->is_active,
        ];
        $this->dispatch('open-modal', 'add-category');
    }

    public function save(CategoryService $service)
    {
        $this->validate();

        try {
            if ($this->editingCategoryId) {
                $category = Category::findOrFail($this->editingCategoryId);
                $service->update($category, $this->form);
                $this->dispatch('toast', message: 'Category updated successfully.', type: 'success');
            } else {
                $data = $this->form;
                $data['parent_id'] = $this->currentCategoryId;
                $service->create($data);
                $this->dispatch('toast', message: 'Category created successfully.', type: 'success');
            }

            $this->dispatch('close-modal', 'add-category');
            $this->resetForm();
        } catch (\Exception $e) {
            $this->addError('form.name', $e->getMessage());
        }
    }

    public function confirmDelete(int $id)
    {
        $this->deleteCategoryId = $id;
        $category = Category::findOrFail($id);
        
        $this->dispatch('open-modal', 'delete-category');
    }

    public function delete(CategoryService $service)
    {
        if ($this->deleteCategoryId) {
            try {
                $category = Category::findOrFail($this->deleteCategoryId);
                $service->delete($category);
                $this->dispatch('toast', message: 'Category deleted successfully.', type: 'success');
                $this->dispatch('close-modal', 'delete-category');
                $this->deleteCategoryId = null;
            } catch (\Exception $e) {
                $this->dispatch('toast', message: $e->getMessage(), type: 'error');
                $this->dispatch('close-modal', 'delete-category');
            }
        }
    }

    public function toggleStatus(int $id, CategoryService $service)
    {
        $category = Category::findOrFail($id);
        $service->toggleStatus($category);
        $message = $category->is_active ? 'Category activated successfully.' : 'Category deactivated successfully.';
        $this->dispatch('toast', message: $message, type: 'success');
    }

    private function resetForm()
    {
        $this->resetValidation();
        $this->editingCategoryId = null;
        $this->form = [
            'name' => '',
            'description' => '',
            'is_active' => true,
        ];
    }

    public function render(CategoryService $service)
    {
        $currentCategory = $this->currentCategoryId ? Category::find($this->currentCategoryId) : null;
        
        $tree = $service->getTree();
        $breadcrumbs = $service->getBreadcrumb($currentCategory);
        $openFolderIds = array_column($breadcrumbs, 'id');
        $children = $service->getChildren($this->currentCategoryId, [
            'search' => $this->search
        ]);

        return view('livewire.admin.categories.category-index-page', [
            'currentCategory' => $currentCategory,
            'tree' => $tree,
            'breadcrumbs' => $breadcrumbs,
            'openFolderIds' => $openFolderIds,
            'children' => $children,
        ]);
    }
}
