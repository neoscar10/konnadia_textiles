<?php

namespace App\Livewire\Admin\Production;

use App\Models\CustomizedProductionOrder;
use App\Models\ProductionJob;
use App\Models\RawMaterial;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.admin.layout')]
class CustomizedProductionHub extends Component
{
    use WithPagination;

    public string $search = '';

    // Create Modal Properties
    public bool $showCreateModal = false;
    public string $custom_order_id = '';
    public int $target_quantity = 20;
    public string $item_description = '';
    public string $raw_material_id = '';
    public string $width = '108';
    public string $length = '120';
    public string $length_unit = 'Inch (in)';
    public string $notes = '';

    public function mount()
    {
        $this->generateCustomOrderIdPreview();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function generateCustomOrderIdPreview()
    {
        $year = date('Y');
        $maxNum = CustomizedProductionOrder::where('custom_order_id', 'like', "CUST-PROD-{$year}-%")
            ->get()
            ->map(fn($o) => (int) str_replace("CUST-PROD-{$year}-", '', $o->custom_order_id))
            ->max() ?: 0;

        $this->custom_order_id = sprintf("CUST-PROD-%s-%04d", $year, $maxNum + 1);
    }

    public function openCreateModal()
    {
        $this->resetValidation();
        $this->generateCustomOrderIdPreview();
        $this->target_quantity = 20;
        $this->item_description = '';
        
        $firstFabric = RawMaterial::fabricsOnly()->first() ?? RawMaterial::first();
        $this->raw_material_id = $firstFabric ? (string) $firstFabric->id : '';

        $this->width = '108';
        $this->length = '120';
        $this->length_unit = 'Inch (in)';
        $this->notes = '';

        $this->showCreateModal = true;
    }

    public function createCustomOrder()
    {
        $this->validate([
            'item_description' => 'required|string|max:255',
            'target_quantity'  => 'required|integer|min:1',
            'raw_material_id'  => 'required|exists:raw_materials,id',
            'width'            => 'required|numeric|min:0.01',
            'length'           => 'required|numeric|min:0.01',
            'length_unit'      => 'required|string',
        ], [
            'item_description.required' => 'Custom Item Description / Title is required.',
            'raw_material_id.required'  => 'Please select a Fabric / Material.',
            'width.required'           => 'Width is required.',
            'length.required'          => 'Length is required.',
        ]);

        $rawMaterial = RawMaterial::find($this->raw_material_id);

        $order = DB::transaction(function () use ($rawMaterial) {
            $year = date('Y');
            $maxNum = CustomizedProductionOrder::where('custom_order_id', 'like', "CUST-PROD-{$year}-%")
                ->get()
                ->map(fn($o) => (int) str_replace("CUST-PROD-{$year}-", '', $o->custom_order_id))
                ->max() ?: 0;

            $custCode = sprintf("CUST-PROD-%s-%04d", $year, $maxNum + 1);

            // 1. Create underlying ProductionJob for non-standard order
            $latestJobId = ProductionJob::max('id') ?? 0;
            $jobCode = sprintf("JOB-CUST-%s-%04d", $year, $latestJobId + 1);

            $job = ProductionJob::create([
                'job_code'        => $jobCode,
                'target_quantity' => $this->target_quantity,
                'status'          => 'in_progress',
                'notes'           => "Custom Production Order: {$this->item_description} ({$this->width}×{$this->length} {$this->length_unit})",
                'job_date'        => now()->format('Y-m-d'),
                'supervisor_id'   => auth()->id(),
            ]);

            // 2. Create CustomizedProductionOrder
            $customOrder = CustomizedProductionOrder::create([
                'custom_order_id'   => $custCode,
                'item_description'  => trim($this->item_description),
                'raw_material_id'   => $rawMaterial?->id,
                'fabric_name'       => $rawMaterial?->name,
                'width'             => floatval($this->width),
                'length'            => floatval($this->length),
                'length_unit'       => trim($this->length_unit),
                'target_quantity'   => intval($this->target_quantity),
                'status'            => 'in_progress',
                'notes'             => trim($this->notes),
                'production_job_id' => $job->id,
                'created_by'        => auth()->id(),
            ]);

            // 3. Initialize default dynamic task stages on underlying ProductionJob
            $cuttingTask = Task::where('name', 'Cutting')->orWhere('code', 'TSK-001')->first()
                ?? Task::where('name', 'like', '%Cut%')->first()
                ?? Task::firstOrCreate(['name' => 'Cutting', 'code' => 'TSK-001'], ['status' => true]);

            $stitchingTask = Task::where('name', 'Stitching')->orWhere('code', 'TSK-002')->first()
                ?? Task::where('name', 'like', '%Stitch%')->first()
                ?? Task::firstOrCreate(['name' => 'Stitching', 'code' => 'TSK-002'], ['status' => true]);

            // Stage 1: Cutting
            \App\Models\JobStageExecution::create([
                'production_job_id' => $job->id,
                'task_id'          => $cuttingTask->id,
                'sequence_number'   => 1,
                'target_quantity'   => intval($this->target_quantity),
                'status'            => 'in_progress',
                'is_final_step'     => false,
                'started_at'        => now(),
            ]);

            // Stage 2: Stitching (Default Final Stage)
            if ($stitchingTask && $stitchingTask->id !== $cuttingTask->id) {
                \App\Models\JobStageExecution::create([
                    'production_job_id' => $job->id,
                    'task_id'          => $stitchingTask->id,
                    'sequence_number'   => 2,
                    'target_quantity'   => intval($this->target_quantity),
                    'status'            => 'pending',
                    'is_final_step'     => true,
                ]);
            } else {
                // If only 1 task available, mark cutting as final
                DB::table('job_stage_executions')
                    ->where('production_job_id', $job->id)
                    ->where('sequence_number', 1)
                    ->update(['is_final_step' => true]);
            }

            return $customOrder;
        });

        session()->flash('toast', [
            'type'    => 'success',
            'message' => "Customized Production Order {$order->custom_order_id} created successfully! Build dynamic routing below.",
        ]);

        return redirect()->route('admin.production.customized.detail', $order->id);
    }

    public function render()
    {
        $query = CustomizedProductionOrder::with(['rawMaterial', 'productionJob.stageExecutions.task']);

        if (!empty($this->search)) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('custom_order_id', 'like', "%{$term}%")
                  ->orWhere('item_description', 'like', "%{$term}%")
                  ->orWhere('fabric_name', 'like', "%{$term}%");
            });
        }

        $orders = $query->latest('id')->paginate(10);

        // KPI Summary Calculations
        $activeOrdersCount = CustomizedProductionOrder::where('status', 'in_progress')->count();
        $completedRunsCount = CustomizedProductionOrder::where('status', 'completed')->count();
        
        $pcsProducedSum = CustomizedProductionOrder::where('status', 'completed')
            ->sum('target_quantity');

        $allOrders = CustomizedProductionOrder::with('productionJob.stageExecutions')->get();
        $totalTaskCount = 0;
        $orderCount = $allOrders->count();

        foreach ($allOrders as $o) {
            $totalTaskCount += $o->configured_tasks_count;
        }

        $avgCustomTasks = $orderCount > 0 ? round($totalTaskCount / $orderCount, 1) : 0.0;

        $fabricMaterials = RawMaterial::fabricsOnly()->orderBy('name')->get();
        if ($fabricMaterials->isEmpty()) {
            $fabricMaterials = RawMaterial::orderBy('name')->get();
        }

        return view('livewire.admin.production.customized-production-hub', [
            'orders'             => $orders,
            'activeOrdersCount'  => $activeOrdersCount,
            'pcsProducedSum'     => $pcsProducedSum,
            'completedRunsCount' => $completedRunsCount,
            'avgCustomTasks'     => $avgCustomTasks,
            'fabricMaterials'    => $fabricMaterials,
        ])->title('Customized Production Hub');
    }
}
