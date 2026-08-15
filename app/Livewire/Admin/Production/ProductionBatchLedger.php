<?php

namespace App\Livewire\Admin\Production;

use App\Models\ProductionBatch;
use App\Services\Manufacturing\ProductionBatchService;
use App\Services\Manufacturing\ProductionCostingService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class ProductionBatchLedger extends Component
{
    public ProductionBatch $batch;

    #[Url]
    public $selectedJobId = '';

    public function mount($id, ProductionCostingService $costingService)
    {
        $this->batch = ProductionBatch::with([
            'manufacturingProduct.tasks',
            'supervisor',
            'parentBatch',
            'childBatches.manufacturingProduct',
            'jobs.task',
            'jobs.manufacturingProduct',
            'jobs.supervisor',
            'jobs.allocations.labor',
            'jobs.materialConsumptions.inventoryBatch.rawMaterial.category',
            'jobs.productOutputs.manufacturingProduct',
            'jobs.wastages.manufacturingProduct',
            'jobs.alterations.sourceProduct',
            'jobs.alterations.targetProduct',
            'jobs.alterations.childBatch',
        ])->findOrFail($id);

        // Cache latest cost metrics into DB columns
        $costingService->cacheBatchCostSummary($this->batch->id);
    }

    public function evaluateBatchCompletion(ProductionBatchService $batchService, ProductionCostingService $costingService)
    {
        $costingService->cacheBatchCostSummary($this->batch->id);
        $response = $batchService->checkAndCompleteBatch($this->batch->id);
        $data = $response->getData(true);

        if (isset($data['success']) && $data['success']) {
            $this->batch->refresh();
            $this->dispatch('toast', message: "Batch status and financial costs evaluated. Status: {$this->batch->status}", type: 'success');
        }
    }

    public function render(ProductionCostingService $costingService)
    {
        if (!empty($this->selectedJobId)) {
            $costSummary = $costingService->getJobCostSummary((int)$this->selectedJobId);
        } else {
            $costSummary = $costingService->getBatchCostSummary($this->batch->id);
        }

        return view('livewire.admin.production.production-batch-ledger', [
            'batch' => $this->batch,
            'costSummary' => $costSummary,
        ])->title("Production Batch 360 Ledger — {$this->batch->batch_code}");
    }
}
