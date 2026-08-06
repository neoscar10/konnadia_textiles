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
        // eager load related data: raw material, consumptions, logs, and the jobs/products linked
        $this->batch = $batch->load([
            'rawMaterial.category',
            'consumptions.job',
            'logs.user',
        ]);
    }

    public function render()
    {
        return view('livewire.factory.inventory-batch-detail');
    }
}
?>
