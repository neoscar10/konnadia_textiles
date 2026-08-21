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

    public string $activeTab = 'financials';
    public $filterWorkerId = '';
    public $filterRollId = '';

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

        $jobIds = $this->batch->jobs->pluck('id');

        // Worker analysis
        $batchWorkers = \App\Models\Labor::whereHas('allocations', function ($q) use ($jobIds) {
            $q->whereIn('production_job_id', $jobIds);
        })->get();

        $workerData = null;
        if (!empty($this->filterWorkerId)) {
            $worker = \App\Models\Labor::find($this->filterWorkerId);
            if ($worker) {
                $allocations = \App\Models\JobLaborAllocation::whereIn('production_job_id', $jobIds)
                    ->where('labor_id', $this->filterWorkerId)
                    ->with(['productionJob.task', 'productionJob.manufacturingProduct'])
                    ->get();

                $workerData = [
                    'worker' => $worker,
                    'allocations' => $allocations,
                    'total_earnings' => $allocations->sum('calculated_wage'),
                    'total_pieces' => $allocations->sum('quantity_processed'),
                ];
            }
        }

        // Roll analysis
        $rollIds = \App\Models\JobLaborAllocation::whereIn('production_job_id', $jobIds)
            ->whereNotNull('inventory_bale_roll_id')
            ->pluck('inventory_bale_roll_id')
            ->merge(
                \App\Models\JobMaterialConsumption::whereIn('production_job_id', $jobIds)
                    ->whereNotNull('inventory_bale_roll_id')
                    ->pluck('inventory_bale_roll_id')
            )
            ->unique();
        $batchRolls = \App\Models\InventoryBaleRoll::with('bale')->whereIn('id', $rollIds)->get();

        $rollData = null;
        if (!empty($this->filterRollId)) {
            $roll = \App\Models\InventoryBaleRoll::with('bale')->find($this->filterRollId);
            if ($roll) {
                $rollAllocations = \App\Models\JobLaborAllocation::whereIn('production_job_id', $jobIds)
                    ->where('inventory_bale_roll_id', $this->filterRollId)
                    ->with(['labor', 'productionJob.task'])
                    ->get();

                $rollConsumptions = \App\Models\JobMaterialConsumption::whereIn('production_job_id', $jobIds)
                    ->where('inventory_bale_roll_id', $this->filterRollId)
                    ->with('inventoryBatch.rawMaterial')
                    ->get();

                $rollWastages = \App\Models\JobWastage::whereIn('production_job_id', $jobIds)
                    ->where('inventory_bale_roll_id', $this->filterRollId)
                    ->with(['task', 'manufacturingProduct'])
                    ->get();

                $rollOutputs = \App\Models\JobProductionOutput::whereIn('production_job_id', $jobIds)
                    ->where('inventory_bale_roll_id', $this->filterRollId)
                    ->with('manufacturingProduct')
                    ->get();

                // Fabric unit cost rate estimate from consumptions
                $firstConsumption = $rollConsumptions->first();
                $rate = $firstConsumption ? ($firstConsumption->total_cost / max(1.0, $firstConsumption->quantity_consumed)) : 150.00;
                $rollWastageCost = $rollWastages->sum('quantity_wasted') * $rate;

                $rollData = [
                    'roll' => $roll,
                    'allocations' => $rollAllocations,
                    'consumptions' => $rollConsumptions,
                    'wastages' => $rollWastages,
                    'outputs' => $rollOutputs,
                    'labor_cost' => $rollAllocations->sum('calculated_wage'),
                    'material_cost' => $rollConsumptions->sum('total_cost'),
                    'wastage_cost' => $rollWastageCost,
                    'total_cost' => $rollAllocations->sum('calculated_wage') + $rollConsumptions->sum('total_cost') + $rollWastageCost,
                    'total_produced' => $rollOutputs->sum('quantity_produced'),
                ];
            }
        }

        // Wastage Audit breakdown
        $wastageLog = \App\Models\JobWastage::whereIn('production_job_id', $jobIds)
            ->with(['manufacturingProduct', 'task', 'inventoryBaleRoll.bale'])
            ->get();

        return view('livewire.admin.production.production-batch-ledger', [
            'batch' => $this->batch,
            'costSummary' => $costSummary,
            'batchWorkers' => $batchWorkers,
            'workerData' => $workerData,
            'batchRolls' => $batchRolls,
            'rollData' => $rollData,
            'wastageLog' => $wastageLog,
        ])->title("Production Batch 360 Ledger — {$this->batch->batch_code}");
    }
}
