<?php

namespace App\Livewire\Factory;

use App\Models\ProductionJob;
use App\Models\JobStageExecution;
use App\Models\Labor;
use App\Models\JobLaborAllocation;
use App\Models\JobWastage;
use App\Models\JobAlteration;
use App\Models\JobProductionOutput;
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

    // Stage Processing Inputs
    public array $laborRows = [];
    public float $wastageQty = 0;
    public int $alterationQty = 0;
    public int $producedQty = 0;
    public string $remarks = '';

    public function mount($job)
    {
        $this->job = ProductionJob::with([
            'manufacturingProduct.tasks',
            'batch',
            'stageExecutions.task',
            'productOutputs',
            'wastages',
            'alterations',
            'allocations',
        ])->findOrFail($job);

        $this->job->ensureStageExecutionsExist();
        $this->loadActiveStage();
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
                ->first();
        }

        if ($this->activeStage) {
            $this->producedQty = (int) $this->activeStage->target_quantity;
        }

        $this->laborRows = [];
        $this->addLaborRow();
    }

    public function addLaborRow()
    {
        $this->laborRows[] = [
            'labor_id' => '',
            'rate' => 50.00,
            'processed_qty' => $this->activeStage ? (int) $this->activeStage->target_quantity : 1,
        ];
    }

    public function removeLaborRow($index)
    {
        unset($this->laborRows[$index]);
        $this->laborRows = array_values($this->laborRows);
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

            // 1. Record Labor Allocations for this stage
            foreach ($this->laborRows as $lRow) {
                if (!empty($lRow['labor_id'])) {
                    $rate = floatval($lRow['rate'] ?? 0);
                    $processed = floatval($lRow['processed_qty'] ?? 0);
                    $wage = round($rate * $processed, 2);

                    JobLaborAllocation::create([
                        'job_id' => $this->job->job_code,
                        'production_batch_id' => $this->job->batch?->batch_code ?? 'BATCH',
                        'labor_id' => $lRow['labor_id'],
                        'task_id' => $taskId,
                        'rate_type' => 'piece_rate',
                        'rate_applied' => $rate,
                        'quantity_processed' => $processed,
                        'calculated_wage' => $wage,
                        'status' => 'approved',
                    ]);
                }
            }

            // 2. Record Wastage if specified
            if ($this->wastageQty > 0) {
                JobWastage::create([
                    'production_job_id' => $this->job->id,
                    'task_id' => $taskId,
                    'quantity_wasted' => $this->wastageQty,
                    'reason' => $this->remarks ?: "Stage Wastage Recorded",
                ]);
            }

            // 3. Record Alterations if specified
            if ($this->alterationQty > 0) {
                JobAlteration::create([
                    'production_job_id' => $this->job->id,
                    'source_product_id' => $this->job->manufacturing_product_id,
                    'source_quantity' => $this->alterationQty,
                    'reason' => 'Alteration required during stage execution',
                    'status' => 'pending',
                ]);
            }

            // 4. Record Product Output for this stage
            if ($this->producedQty > 0) {
                JobProductionOutput::create([
                    'production_job_id' => $this->job->id,
                    'task_id' => $taskId,
                    'quantity_produced' => $this->producedQty,
                ]);
            }

            // 5. Complete stage via Workflow Service
            $workflowService = resolve(ProductionWorkflowService::class);
            $workflowService->completeJob($this->job->id, $taskId);
        });

        $this->dispatch('toast', message: "Stage {$this->activeStage->task?->name} completed successfully!", type: 'success');
        $this->loadActiveStage();
    }

    public function render()
    {
        $labors = Labor::active()->orderBy('name')->get();
        $stageExecutions = $this->job->stageExecutions()->with('task')->orderBy('sequence_number')->get();

        return view('livewire.factory.job-stage-wizard', [
            'labors' => $labors,
            'stageExecutions' => $stageExecutions,
        ])->title("Job {$this->job->job_code} — Stage Execution Wizard");
    }
}
