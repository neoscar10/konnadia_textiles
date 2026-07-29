<?php

namespace App\Livewire\Admin\Production;

use App\Models\Labor;
use App\Models\Task;
use App\Models\ManufacturingProduct;
use App\Models\JobLaborAllocation;
use App\Services\Manufacturing\LaborWageService;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class JobExecutionTerminal extends Component
{
    public int $currentStep = 1;

    public $job_id = 'JOB-2026-001';
    public $production_batch_id = 'PB-2026-00125';
    public $task_id = null;
    public $manufacturing_product_id = null;
    public $total_quantity = 100;

    public array $laborAllocations = [];

    public function mount($job_id = null, $task_id = null, $manufacturing_product_id = null)
    {
        if ($job_id) {
            $this->job_id = $job_id;
        }

        // Default task & product if not provided (fallback for testing/demo)
        $firstTask = Task::where('status', true)->first();
        $this->task_id = $task_id ?? ($firstTask ? $firstTask->id : null);

        $firstProduct = ManufacturingProduct::first();
        $this->manufacturing_product_id = $manufacturing_product_id ?? ($firstProduct ? $firstProduct->id : null);

        // Initialize with one allocation row
        if (empty($this->laborAllocations)) {
            $this->laborAllocations = [
                ['labor_id' => '', 'quantity' => '']
            ];
        }
    }

    public function proceedToAllocation(): void
    {
        $this->validate([
            'job_id' => 'required|string|max:100',
            'production_batch_id' => 'nullable|string|max:100',
            'manufacturing_product_id' => 'required|exists:manufacturing_products,id',
            'task_id' => 'required|exists:tasks,id',
            'total_quantity' => 'required|numeric|min:1',
        ], [
            'manufacturing_product_id.required' => 'Please select a manufacturing product.',
            'task_id.required' => 'Please select a production stage / task.',
            'total_quantity.min' => 'Total job quantity must be at least 1 unit.',
        ]);

        $this->currentStep = 2;
    }

    public function goToStep(int $step): void
    {
        if ($step === 1 || ($step === 2 && $this->manufacturing_product_id && $this->task_id)) {
            $this->currentStep = $step;
        }
    }

    public function addLaborRow(): void
    {
        array_unshift($this->laborAllocations, ['labor_id' => '', 'quantity' => '']);
    }

    public function removeLaborRow(int $index): void
    {
        if (count($this->laborAllocations) > 1) {
            unset($this->laborAllocations[$index]);
            $this->laborAllocations = array_values($this->laborAllocations);
        }
    }

    public function getRemainingQuantityProperty(): int
    {
        $allocatedSum = 0;
        foreach ($this->laborAllocations as $row) {
            $allocatedSum += (int) ($row['quantity'] ?? 0);
        }

        return (int) $this->total_quantity - $allocatedSum;
    }

    public function getAuthorizedLaborsProperty()
    {
        if (!$this->task_id) {
            return Labor::where('status', true)->get();
        }

        return Labor::where('status', true)
            ->whereHas('tasks', function ($q) {
                $q->where('tasks.id', $this->task_id);
            })
            ->get();
    }

    public function submit(LaborWageService $wageService)
    {
        // Custom validation rules
        $this->validate([
            'job_id' => 'required',
            'task_id' => 'required|exists:tasks,id',
            'total_quantity' => 'required|numeric|min:1',
            'laborAllocations' => 'required|array|min:1',
            'laborAllocations.*.labor_id' => 'required|exists:labors,id',
            'laborAllocations.*.quantity' => 'required|numeric|min:1',
        ], [
            'laborAllocations.*.labor_id.required' => 'Please select a laborer.',
            'laborAllocations.*.quantity.required' => 'Quantity is required.',
            'laborAllocations.*.quantity.min' => 'Quantity must be at least 1.',
        ]);

        // Check for duplicate laborer selections
        $selectedLaborIds = array_column($this->laborAllocations, 'labor_id');
        if (count($selectedLaborIds) !== count(array_unique($selectedLaborIds))) {
            $this->addError('laborAllocations', 'Duplicate laborers selected. Each laborer should only be added once.');
            return;
        }

        // Check total allocation sum against total_quantity
        $allocatedSum = array_sum(array_column($this->laborAllocations, 'quantity'));
        if ($allocatedSum > $this->total_quantity) {
            $this->addError('laborAllocations', "Total allocated quantity ({$allocatedSum}) exceeds job total quantity ({$this->total_quantity}).");
            return;
        }

        // Process allocations via LaborWageService
        $response = $wageService->processAllocations(
            $this->laborAllocations,
            $this->job_id,
            $this->manufacturing_product_id,
            $this->task_id,
            $this->production_batch_id
        );

        $responseData = $response->getData(true);

        if (isset($responseData['success']) && $responseData['success']) {
            $this->dispatch('toast', message: 'Labor allocations and wage calculation processed successfully!', type: 'success');
            return redirect()->route('admin.production.tracking-history');
        } else {
            $errorMessage = $responseData['message'] ?? 'Failed to process labor allocations.';
            $this->addError('laborAllocations', $errorMessage);
        }
    }

    public function render()
    {
        $currentTask = Task::find($this->task_id);
        $currentProduct = ManufacturingProduct::find($this->manufacturing_product_id);
        $allTasks = Task::where('status', true)->get();
        $allProducts = ManufacturingProduct::all();

        return view('livewire.admin.production.job-execution-terminal', [
            'authorizedLabors' => $this->authorizedLabors,
            'currentTask' => $currentTask,
            'currentProduct' => $currentProduct,
            'allTasks' => $allTasks,
            'allProducts' => $allProducts,
        ])->title('Job Execution Terminal');
    }
}
