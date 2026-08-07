<?php

namespace App\Livewire\Factory;

use App\Models\RawMaterial;
use App\Models\InventoryBatch;
use App\Models\InventoryBatchLog;
use App\Models\ManufacturingProduct;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class RawMaterialDetail extends Component
{
    use WithPagination;

    public RawMaterial $material;
    public string $activeTab = 'batches'; // 'batches', 'bom', 'logs'
    public string $logSearch = '';

    public function mount(RawMaterial $material)
    {
        $this->material = $material->load([
            'category.unitGroup',
            'unitGroup',
            'unitModel',
        ]);
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function render()
    {
        // 1. Stock Statistics
        $activeBatches = InventoryBatch::where('raw_material_id', $this->material->id)
            ->where('status', 'active')
            ->get();

        $allBatches = InventoryBatch::where('raw_material_id', $this->material->id)->get();

        $totalStockBalance = $activeBatches->sum('balance_quantity');
        $baseStockBalance = $activeBatches->sum('base_current_balance') ?: $totalStockBalance;
        
        $totalInventoryValue = $activeBatches->sum(function ($batch) {
            $rate = floatval($batch->purchase_rate ?: $batch->unit_cost);
            return floatval($batch->balance_quantity) * $rate;
        });

        $totalReceived = $allBatches->sum('quantity_received');
        $totalConsumed = $allBatches->sum('quantity_consumed');

        // 2. Batches list for this material
        $batches = InventoryBatch::where('raw_material_id', $this->material->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'batchesPage');

        // 3. Products using this raw material in BOM
        $bomProducts = ManufacturingProduct::with('category')
            ->whereHas('subsidiaryMaterials', fn($q) => $q->where('raw_materials.id', $this->material->id))
            ->orWhereHas('stitchingMaterials', fn($q) => $q->where('raw_materials.id', $this->material->id))
            ->orWhereHas('packagingMaterials', fn($q) => $q->where('raw_materials.id', $this->material->id))
            ->get();

        // 4. Audit Consumption & Lifecycle Logs across all batches
        $batchIds = $allBatches->pluck('id')->toArray();
        $logsQuery = InventoryBatchLog::with(['batch', 'user'])
            ->whereIn('inventory_batch_id', $batchIds)
            ->orderBy('created_at', 'desc');

        if (!empty($this->logSearch)) {
            $logsQuery->where(function ($q) {
                $q->where('description', 'like', "%{$this->logSearch}%")
                  ->orWhere('action', 'like', "%{$this->logSearch}%");
            });
        }

        $logs = $logsQuery->paginate(15, ['*'], 'logsPage');

        return view('livewire.factory.raw-material-detail', [
            'totalStockBalance' => $totalStockBalance,
            'baseStockBalance' => $baseStockBalance,
            'totalInventoryValue' => $totalInventoryValue,
            'totalReceived' => $totalReceived,
            'totalConsumed' => $totalConsumed,
            'activeBatchesCount' => $activeBatches->count(),
            'totalBatchesCount' => $allBatches->count(),
            'batches' => $batches,
            'bomProducts' => $bomProducts,
            'logs' => $logs,
        ])->title("Raw Material Audit - {$this->material->name}");
    }
}
