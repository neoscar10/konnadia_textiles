<?php

namespace App\Livewire\Admin\Production;

use App\Models\SpareProduct;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class SpareProductIndexPage extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $designFilter = '';

    public function render()
    {
        $query = SpareProduct::with(['manufacturingProduct', 'productionBatch', 'productionJob']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('design_id', 'like', "%{$this->search}%")
                  ->orWhereHas('manufacturingProduct', fn($mp) => $mp->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
                  ->orWhereHas('productionBatch', fn($pb) => $pb->where('batch_code', 'like', "%{$this->search}%"));
            });
        }

        if (!empty($this->designFilter)) {
            $query->where('design_id', $this->designFilter);
        }

        $spareProducts = $query->latest()->paginate(15);

        $totalSpareRecorded = SpareProduct::sum('quantity');
        $totalSpareUsed = SpareProduct::sum('used_quantity');
        $totalAvailableSpare = max(0, $totalSpareRecorded - $totalSpareUsed);
        $uniqueDesignIdsCount = SpareProduct::distinct('design_id')->count('design_id');
        $availableDesignIds = SpareProduct::distinct('design_id')->pluck('design_id');

        return view('livewire.admin.production.spare-product-index-page', [
            'spareProducts' => $spareProducts,
            'totalSpareRecorded' => $totalSpareRecorded,
            'totalSpareUsed' => $totalSpareUsed,
            'totalAvailableSpare' => $totalAvailableSpare,
            'uniqueDesignIdsCount' => $uniqueDesignIdsCount,
            'availableDesignIds' => $availableDesignIds,
        ])->title('Spare Products Directory');
    }
}
