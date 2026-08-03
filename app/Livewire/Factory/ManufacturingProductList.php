<?php

namespace App\Livewire\Factory;

use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class ManufacturingProductList extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $categoryFilter = '';

    #[Url(history: true)]
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $product = ManufacturingProduct::findOrFail($id);
        $newStatus = $product->status === 'active' ? 'inactive' : 'active';
        $product->update(['status' => $newStatus]);

        $message = "Product {$product->name} is now " . ucfirst($newStatus) . "!";
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function deleteProduct(int $id): void
    {
        $product = ManufacturingProduct::findOrFail($id);
        
        // Prevent deletion if linked to active production batches, jobs, or allocations
        $hasBatches = \App\Models\ProductionBatch::where('manufacturing_product_id', $product->id)->exists();
        $hasJobs = \App\Models\ProductionJob::where('manufacturing_product_id', $product->id)->exists();

        if ($hasBatches || $hasJobs || $product->allocations()->exists()) {
            $this->dispatch('toast', message: "Cannot delete {$product->name}. It is already associated with production records.", type: 'error');
            return;
        }

        $product->delete();
        $this->dispatch('toast', message: "Product deleted successfully.", type: 'success');
    }

    public function render()
    {
        $query = ManufacturingProduct::with(['tasks', 'category']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%");
            });
        }

        if (!empty($this->categoryFilter)) {
            $query->where('manufacturing_product_category_id', $this->categoryFilter);
        }

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(10);
        $allCategories = ManufacturingProductCategory::active()->orderBy('name')->get();

        return view('livewire.factory.manufacturing-product-list', [
            'products' => $products,
            'allCategories' => $allCategories,
        ])->title('Manufacturing Products');
    }
}
