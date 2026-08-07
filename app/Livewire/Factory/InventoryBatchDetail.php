<?php

namespace App\Livewire\Factory;

use App\Models\InventoryBatch;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class InventoryBatchDetail extends Component
{
    public InventoryBatch $batch;

    public function mount(InventoryBatch $batch)
    {
        // Eager load related data: raw material, unit models, consumptions, stage executions, logs, and linked jobs/products
        $this->batch = $batch->load([
            'rawMaterial.category',
            'rawMaterial.unitGroup',
            'rawMaterial.unitModel',
            'consumptions.job.manufacturingProduct',
            'logs.user',
        ]);
    }

    public function render()
    {
        return view('livewire.factory.inventory-batch-detail');
    }
}
?>
