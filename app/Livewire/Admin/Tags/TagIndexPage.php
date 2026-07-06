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

    public ?int $editingId = null;

    public array $form = [
        'name' => '',
    ];

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
        $tag = Tag::findOrFail($id);
        $this->editingId = $tag->id;
        $this->form = [
            'name' => $tag->name,
        ];
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
            $this->dispatch('toast', message: 'Tag updated successfully.', type: 'success');
        } else {
            Tag::create([
                'name' => trim($this->form['name']),
                'slug' => $slug,
            ]);
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
    }

    public function render()
    {
        $query = Tag::query();

        if (!empty($this->search)) {
            $query->where('name', 'like', "%{$this->search}%")
                  ->orWhere('slug', 'like', "%{$this->search}%");
        }

        $tags = $query->orderBy('name')->paginate(10);

        return view('livewire.admin.tags.tag-index-page', [
            'tags' => $tags
        ])->title('Tags Management');
    }
}
