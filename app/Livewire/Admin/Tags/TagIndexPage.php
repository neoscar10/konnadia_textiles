<?php

namespace App\Livewire\Admin\Tags;

use App\Models\Tag;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Illuminate\Validation\Rule;

#[Layout('components.admin.layout')]
class TagIndexPage extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public string $categorySearch = '';

    public ?int $editingId = null;

    public array $form = [
        'name' => '',
    ];

    public array $selectedCategoryIds = [];

    public ?int $deleteId = null;

    protected function rules(): array
    {
        return [
            'form.name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tags', 'name')->ignore($this->editingId)
            ],
            'selectedCategoryIds' => ['required', 'array', 'min:1'],
            'selectedCategoryIds.*' => ['exists:categories,id'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'selectedCategoryIds' => 'categories',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->dispatch('open-modal', 'add-tag');
    }

    public function edit(int $id): void
    {
        $this->resetValidation();
        $tag = Tag::with('categories')->findOrFail($id);
        $this->editingId = $tag->id;
        $this->form = [
            'name' => $tag->name,
        ];
        $this->selectedCategoryIds = $tag->categories->pluck('id')->toArray();
        $this->dispatch('open-modal', 'add-tag');
    }

    public function save(): void
    {
        $this->validate();

        $slug = \Illuminate\Support\Str::slug($this->form['name']);

        if ($this->editingId) {
            $tag = Tag::findOrFail($this->editingId);
            $tag->update([
                'name' => trim($this->form['name']),
                'slug' => $slug,
            ]);
            $tag->categories()->sync($this->selectedCategoryIds);
            $this->dispatch('toast', message: 'Tag updated successfully.', type: 'success');
        } else {
            $tag = Tag::create([
                'name' => trim($this->form['name']),
                'slug' => $slug,
            ]);
            $tag->categories()->sync($this->selectedCategoryIds);
            $this->dispatch('toast', message: 'Tag created successfully.', type: 'success');
        }

        $this->dispatch('close-modal', 'add-tag');
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->dispatch('open-modal', 'delete-tag');
    }

    public function delete(): void
    {
        if ($this->deleteId) {
            $tag = Tag::findOrFail($this->deleteId);
            $tag->products()->detach(); // detach from all products first
            $tag->categories()->detach(); // detach from categories
            $tag->delete();

            $this->dispatch('toast', message: 'Tag deleted successfully.', type: 'success');
            $this->dispatch('close-modal', 'delete-tag');
            $this->deleteId = null;
        }
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = [
            'name' => '',
        ];
        $this->selectedCategoryIds = [];
        $this->categorySearch = '';
    }

    public function render()
    {
        $query = Tag::query();

        if (!empty($this->search)) {
            $query->where('name', 'like', "%{$this->search}%")
                  ->orWhere('slug', 'like', "%{$this->search}%");
        }

        $tags = $query->with('categories')->orderBy('name')->paginate(10);
        $leafCategories = app(\App\Services\Catalog\CategoryService::class)->getLeafCategories();

        if (!empty($this->categorySearch)) {
            $searchTerm = strtolower(trim($this->categorySearch));
            $leafCategories = $leafCategories->filter(function ($leaf) use ($searchTerm) {
                return str_contains(strtolower($leaf->name), $searchTerm) || 
                       str_contains(strtolower($leaf->full_path), $searchTerm);
            });
        }

        return view('livewire.admin.tags.tag-index-page', [
            'tags' => $tags,
            'leafCategories' => $leafCategories,
        ])->title('Tags Management');
    }
}
