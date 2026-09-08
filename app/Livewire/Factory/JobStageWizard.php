<?php

namespace App\Livewire\Factory;

use App\Models\ProductionJob;
use App\Models\JobStageExecution;
use App\Models\Labor;
use App\Models\JobLaborAllocation;
use App\Models\JobWastage;
use App\Models\JobAlteration;
use App\Models\JobProductionOutput;
use App\Models\ManufacturingProduct;
use App\Services\Manufacturing\ProductionWorkflowService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Exception;

#[Layout('components.admin.layout')]
class JobStageWizard extends Component
{
    public ProductionJob $job;
    public ?JobStageExecution $activeStage = null;
    public int $activeStep = 1;

    // Stage Processing Inputs
    public array $laborRows = [];
    public int $producedQty = 0;
    public float $wastageQty = 0;
    public array $alterationRows = [];
    public string $remarks = '';

    public function mount($id = null, $job = null)
    {
        $jobId = $job ?? $id;
        if ($jobId instanceof ProductionJob) {
            $this->job = $jobId;
        } else {
            $this->job = ProductionJob::with([
                'manufacturingProduct.tasks',
                'pattern.tasks',
                'batch.factorySupervisor',
                'stageExecutions.task',
                'productOutputs',
                'wastages',
                'alterations',
                'allocations',
            ])->findOrFail($jobId);
        }

        $this->job->ensureStageExecutionsExist();
        $this->loadActiveStage();
    }

    public function selectStage(int $executionId)
    {
        $stage = $this->job->stageExecutions->firstWhere('id', $executionId);
        if ($stage) {
            $this->activeStage = $stage;
            $this->activeStep = 1;
            $this->initStageInputs();
        }
    }

    protected function loadActiveStage()
    {
        $this->job->refresh();
        $this->activeStage = $this->job->stageExecutions
            ->where('status', 'in_progress')
            ->sortBy('sequence_number')
            ->first();

        if (!$this->activeStage) {
            $this->activeStage = $this->job->stageExecutions
                ->where('status', 'pending')
                ->sortBy('sequence_number')
                ->first()
                ?? $this->job->stageExecutions->sortByDesc('sequence_number')->first();
        }

        $this->activeStep = 1;
        $this->initStageInputs();
    }

    protected function initStageInputs()
    {
        if ($this->activeStage) {
            $this->producedQty = (int) $this->activeStage->target_quantity;
        }

        $this->laborRows = [];
        $this->addLaborRow();

        $this->alterationRows = [];
        $this->addAlterationRow();
    }

    public function addLaborRow()
    {
        $defaultRate = 10.00;
        $qty = $this->activeStage ? (int) $this->activeStage->target_quantity : 200;

        $this->laborRows[] = [
            'labor_id'       => '',
            'processed_qty'  => $qty,
            'base_rate'      => $defaultRate,
            'bonus_rate'     => 0.00,
        ];
    }

    public function removeLaborRow($index)
    {
        unset($this->laborRows[$index]);
        $this->laborRows = array_values($this->laborRows);
    }

    public function addAlterationRow()
    {
        $this->alterationRows[] = [
            'altered_qty'       => 1,
            'target_product_id' => $this->job->manufacturing_product_id,
        ];
    }

    public function removeAlterationRow($index)
    {
        unset($this->alterationRows[$index]);
        $this->alterationRows = array_values($this->alterationRows);
    }

    public function toggleSkipStage(int $executionId)
    {
        $stage = $this->job->stageExecutions->firstWhere('id', $executionId);
        if (!$stage || $stage->sequence_number === 1) {
            $this->dispatch('toast', message: "Cutting/Step 1 is mandatory and cannot be skipped.", type: 'error');
            return;
        }

        $workflowService = resolve(ProductionWorkflowService::class);
        $workflowService->skipStage($this->job->id, $stage->task_id);

        $this->dispatch('toast', message: "Stage {$stage->task?->name} skipped successfully.", type: 'success');
        $this->loadActiveStage();
    }

    public function completeActiveStage()
    {
        if (!$this->activeStage) {
            $this->dispatch('toast', message: "No active stage available to complete.", type: 'error');
            return;
        }

        if ($this->activeStage->status === 'completed') {
            $this->dispatch('toast', message: "Stage is already completed.", type: 'error');
            return;
        }

        DB::transaction(function () {
            $taskId = $this->activeStage->task_id;

            // 1. Record Labor Allocations with Base Rate + Bonus Rate
            foreach ($this->laborRows as $lRow) {
                if (!empty($lRow['labor_id'])) {
                    $baseRate  = floatval($lRow['base_rate'] ?? 0);
                    $bonusRate = floatval($lRow['bonus_rate'] ?? 0);
                    $processed = intval($lRow['processed_qty'] ?? 0);
                    $effective = $baseRate + $bonusRate;
                    $wage      = round($effective * $processed, 2);

                    JobLaborAllocation::create([
                        'job_id'              => $this->job->job_code,
                        'production_batch_id' => $this->job->batch?->batch_code ?? 'BATCH',
                        'labor_id'            => $lRow['labor_id'],
                        'task_id'             => $taskId,
                        'rate_type'           => 'piece_rate',
                        'base_rate'           => $baseRate,
                        'bonus_rate'          => $bonusRate,
                        'rate_applied'        => $effective,
                        'quantity_processed'  => $processed,
                        'calculated_wage'     => $wage,
                        'status'              => 'approved',
                    ]);
                }
            }

            // 2. Record Product Output for this stage
            if ($this->producedQty > 0) {
                JobProductionOutput::create([
                    'production_job_id' => $this->job->id,
                    'task_id'           => $taskId,
                    'quantity_produced' => $this->producedQty,
                ]);
            }

            // 3. Final Step Reconciliation: Record Wastage and Alteration Mapping if on Final Step
            $isFinalStep = $this->isFinalStage($this->activeStage);
            if ($isFinalStep) {
                if ($this->wastageQty > 0) {
                    JobWastage::create([
                        'production_job_id' => $this->job->id,
                        'task_id'           => $taskId,
                        'quantity_wasted'   => $this->wastageQty,
                        'reason'            => $this->remarks ?: "Final Task Reconciliation Wastage",
                    ]);
                }

                foreach ($this->alterationRows as $altRow) {
                    $altQty    = intval($altRow['altered_qty'] ?? 0);
                    $targetPId = $altRow['target_product_id'] ?? null;
                    if ($altQty > 0 && $targetPId) {
                        JobAlteration::create([
                            'job_code'          => $this->job->job_code,
                            'production_job_id' => $this->job->id,
                            'source_product_id' => $this->job->manufacturing_product_id ?? $targetPId,
                            'source_quantity'   => $altQty,
                            'target_product_id' => $targetPId,
                            'target_quantity'   => $altQty,
                            'status'            => 'pending',
                        ]);
                    }
                }
            }

            // 4. Advance Workflow Service
            $workflowService = resolve(ProductionWorkflowService::class);
            $workflowService->completeJob($this->job->id, $taskId);
        });

        $this->dispatch('toast', message: "Stage {$this->activeStage->task?->name} completed successfully!", type: 'success');
        $this->loadActiveStage();
    }

    public function isFinalStage(JobStageExecution $stage): bool
    {
        $maxSeq = $this->job->stageExecutions->max('sequence_number');
        return $stage->sequence_number === $maxSeq;
    }

    public function render()
    {
        $labors = Labor::active()->orderBy('name')->get();
        $allProducts = ManufacturingProduct::orderBy('name')->get();
        $stageExecutions = $this->job->stageExecutions()->with('task')->orderBy('sequence_number')->get();

        return view('livewire.factory.job-stage-wizard', [
            'labors'          => $labors,
            'allProducts'     => $allProducts,
            'stageExecutions' => $stageExecutions,
        ])->title("Job {$this->job->job_code} — Work Order Terminal");
    }
}
