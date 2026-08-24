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
    public ?ProductionBatch $batch = null;

    #[Url]
    public $selectedJobId = '';

    public string $activeTab = 'financials';
    public $filterWorkerId = '';
    public $filterRollId = '';

    public function mount($id, ProductionCostingService $costingService)
    {
        $withRelations = [
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
        ];

        // Try to find by numeric DB id or string batch_code
        $batch = is_numeric($id)
            ? ProductionBatch::where('id', $id)->orWhere('batch_code', $id)->first()
            : ProductionBatch::where('batch_code', $id)->orWhere('id', $id)->first();

        // Auto-create batch record for legacy jobs that pre-date the ledger feature
        if (!$batch) {
            $firstJob = \App\Models\ProductionJob::where('production_batch_id', $id)
                ->orWhere('job_code', $id)
                ->first();

            // Fallback supervisor_id: use job's supervisor, else any admin user
            // This ensures compatibility even if the DB migration hasn't been run yet
            $supervisorId = $firstJob?->supervisor_id
                ?? \App\Models\User::orderBy('id')->value('id');

            $batch = ProductionBatch::create([
                'batch_code'               => is_numeric($id) ? 'PB-' . date('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT) : $id,
                'manufacturing_product_id' => $firstJob?->manufacturing_product_id,
                'supervisor_id'            => $supervisorId,
                'planned_quantity'         => $firstJob?->target_quantity ?? 10,
                'status'                   => 'In Progress',
            ]);

            // Link any orphaned jobs to the new batch DB record
            \App\Models\ProductionJob::where('production_batch_id', $batch->batch_code)
                ->update(['production_batch_db_id' => $batch->id]);
        } else {
            \App\Models\ProductionJob::where('production_batch_id', $batch->batch_code)
                ->whereNull('production_batch_db_id')
                ->update(['production_batch_db_id' => $batch->id]);
        }

        $batch->load($withRelations);
        $this->batch = $batch;

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

        // job_labor_allocations uses job_id (string job_code), other tables use production_job_id (integer)
        $jobIds       = $this->batch->jobs->pluck('id');        // integer IDs for most tables
        $jobCodes     = $this->batch->jobs->pluck('job_code');   // string codes for labor allocations

        // Worker analysis — labor allocations are keyed by job_code via job_id
        $batchWorkers = \App\Models\Labor::whereHas('allocations', function ($q) use ($jobCodes) {
            $q->whereIn('job_id', $jobCodes);
        })->get();

        $workerData = null;
        if (!empty($this->filterWorkerId)) {
            $worker = \App\Models\Labor::find($this->filterWorkerId);
            if ($worker) {
                $allocations = \App\Models\JobLaborAllocation::whereIn('job_id', $jobCodes)
                    ->where('labor_id', $this->filterWorkerId)
                    ->with(['productionJob.task', 'productionJob.manufacturingProduct'])
                    ->get();

                $totalEquivalentValue = 0.0;
                foreach ($allocations as $alloc) {
                    $rate = (float) $alloc->piece_rate;
                    if ($rate <= 0 && $alloc->productionJob?->manufacturingProduct) {
                        $rate = (float) $alloc->productionJob->manufacturingProduct->getStandardLaborRateForTask($alloc->task_id);
                    }
                    $alloc->equivalent_rate = $rate;
                    $alloc->equivalent_value = round((float)$alloc->quantity_processed * $rate, 2);
                    $totalEquivalentValue += $alloc->equivalent_value;
                }

                $workerData = [
                    'worker'                 => $worker,
                    'allocations'            => $allocations,
                    'total_earnings'         => $allocations->sum('calculated_wage'),
                    'total_pieces'           => $allocations->sum('quantity_processed'),
                    'total_equivalent_value' => $totalEquivalentValue,
                ];
            }
        }

        // Roll analysis — labor side uses job_id (codes), other tables use production_job_id (ids)
        $rollIds = \App\Models\JobLaborAllocation::whereIn('job_id', $jobCodes)
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
                $rollAllocations = \App\Models\JobLaborAllocation::whereIn('job_id', $jobCodes)
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
                    'roll'          => $roll,
                    'allocations'   => $rollAllocations,
                    'consumptions'  => $rollConsumptions,
                    'wastages'      => $rollWastages,
                    'outputs'       => $rollOutputs,
                    'labor_cost'    => $rollAllocations->sum('calculated_wage'),
                    'material_cost' => $rollConsumptions->sum('total_cost'),
                    'wastage_cost'  => $rollWastageCost,
                    'total_cost'    => $rollAllocations->sum('calculated_wage') + $rollConsumptions->sum('total_cost') + $rollWastageCost,
                    'total_produced'=> $rollOutputs->sum('quantity_produced'),
                ];
            }
        }

        // Summary list for ALL rolls used in this batch
        $allRollsSummary = [];
        foreach ($batchRolls as $roll) {
            $rConsumptions = \App\Models\JobMaterialConsumption::whereIn('production_job_id', $jobIds)
                ->where('inventory_bale_roll_id', $roll->id)
                ->get();
            $rWastages = \App\Models\JobWastage::whereIn('production_job_id', $jobIds)
                ->where('inventory_bale_roll_id', $roll->id)
                ->get();
            $rOutputs = \App\Models\JobProductionOutput::whereIn('production_job_id', $jobIds)
                ->where('inventory_bale_roll_id', $roll->id)
                ->get();
            $rAllocations = \App\Models\JobLaborAllocation::whereIn('job_id', $jobCodes)
                ->where('inventory_bale_roll_id', $roll->id)
                ->get();

            $rate = $roll->bale?->unit_cost ?? ($rConsumptions->first()?->unit_cost ?? 150.00);
            $wastedQty = (float) $rWastages->sum('quantity_wasted');
            $wastageCost = round($wastedQty * (float)$rate, 2);
            $matCost = (float) $rConsumptions->sum('total_cost');
            $laborCost = (float) $rAllocations->sum('calculated_wage');

            $allRollsSummary[] = [
                'roll_id'         => $roll->id,
                'bale_number'     => $roll->bale?->bale_number ?? 'N/A',
                'roll_number'     => $roll->roll_number,
                'consumed_qty'    => (float) $rConsumptions->sum('quantity_consumed'),
                'produced_qty'    => (int) $rOutputs->sum('quantity_produced'),
                'wasted_qty'      => $wastedQty,
                'material_cost'   => $matCost,
                'labor_cost'      => $laborCost,
                'wastage_cost'    => $wastageCost,
                'total_roll_cost' => $matCost + $laborCost + $wastageCost,
                'total_cost'      => $matCost + $laborCost + $wastageCost,
            ];
        }

        // Wastage Audit breakdown
        $wastageLog = \App\Models\JobWastage::whereIn('production_job_id', $jobIds)
            ->with(['manufacturingProduct', 'task', 'inventoryBaleRoll.bale'])
            ->get();

        return view('livewire.admin.production.production-batch-ledger', [
            'batch'           => $this->batch,
            'costSummary'     => $costSummary,
            'batchWorkers'    => $batchWorkers,
            'workerData'      => $workerData,
            'batchRolls'      => $batchRolls,
            'rollData'        => $rollData,
            'allRollsSummary' => $allRollsSummary,
            'wastageLog'      => $wastageLog,
        ])->title("Production Batch 360 Ledger — {$this->batch->batch_code}");
    }
}
