<?php

namespace App\Livewire\Factory;

use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

#[Layout('components.admin.layout')]
class RawMaterialList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $categoryFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function toggleStatus($id)
    {
        $material = RawMaterial::findOrFail($id);
        $material->update(['is_active' => !$material->is_active]);

        $statusText = $material->is_active ? 'Active' : 'Inactive';
        $this->dispatch('toast', message: "Raw Material [{$material->code}] status set to {$statusText}.", type: 'success');
    }

    public function delete($id)
    {
        $material = RawMaterial::findOrFail($id);

        // Prevent deletion if linked to inventory batches
        if ($material->batches()->count() > 0) {
            $this->dispatch('toast', message: "Cannot delete [{$material->name}] because it has linked inventory batches.", type: 'error');
            return;
        }

        $name = $material->name;
        $material->delete();
        $this->dispatch('toast', message: "Raw Material [{$name}] deleted successfully.", type: 'success');
    }

    #[On('raw-material-saved')]
    public function handleSaved()
    {
        // The list will automatically re-render
    }

    public function render()
    {
        $materials = RawMaterial::with('category')
            ->search($this->search)
            ->when($this->categoryFilter, function ($q) {
                $q->where('raw_material_category_id', $this->categoryFilter);
            })
            ->orderBy('code')
            ->paginate(15);

        $categories = RawMaterialCategory::active()->orderBy('name')->get();

        return view('livewire.factory.raw-material-list', [
            'materials' => $materials,
            'categories' => $categories,
        ])->title('Raw Material Master');
    }
}
