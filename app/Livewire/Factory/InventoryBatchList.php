<?php

namespace App\Livewire\Factory;

use App\Models\InventoryBatch;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class InventoryBatchList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $materialFilter = '';
    public string $categoryFilter = '';
    public string $statusFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'materialFilter' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingMaterialFilter()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $batches = InventoryBatch::with(['rawMaterial.category'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('batch_number', 'like', "%{$this->search}%")
                        ->orWhere('supplier_name', 'like', "%{$this->search}%")
                        ->orWhere('invoice_number', 'like', "%{$this->search}%");
                });
            })
            ->when($this->materialFilter, function ($q) {
                $q->where('raw_material_id', $this->materialFilter);
            })
            ->when($this->categoryFilter, function ($q) {
                $q->whereHas('rawMaterial', function ($query) {
                    $query->where('raw_material_category_id', $this->categoryFilter);
                });
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->orderBy('batch_number', 'desc')
            ->paginate(15);

        // Fetch active filter lists
        $materials = RawMaterial::active()->orderBy('name')->get();
        $categories = RawMaterialCategory::active()->orderBy('name')->get();

        // Calculate Summary stats
        $totalBatches = InventoryBatch::count();
        $activeBatches = InventoryBatch::where('status', 'active')->count();
        $depletedBatches = InventoryBatch::where('status', 'depleted')->count();
        $totalInventoryValue = InventoryBatch::where('status', 'active')->sum('total_amount');

        return view('livewire.factory.inventory-batch-list', [
            'batches' => $batches,
            'materials' => $materials,
            'categories' => $categories,
            'totalBatches' => $totalBatches,
            'activeBatches' => $activeBatches,
            'depletedBatches' => $depletedBatches,
            'totalInventoryValue' => $totalInventoryValue,
        ])->title('Inventory Batches');
    }
}
