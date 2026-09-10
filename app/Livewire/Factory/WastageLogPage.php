<?php

namespace App\Livewire\Factory;

use App\Models\JobWastage;
use App\Models\ManufacturingProduct;
use App\Models\ProductionJob;
use App\Models\ProductionBatch;
use App\Models\Task;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.admin.layout')]
class WastageLogPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedTask = '';
    public string $selectedWastageType = '';

    public function mount()
    {
        $this->ensureSampleDataExists();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedTask()
    {
        $this->resetPage();
    }

    public function updatingSelectedWastageType()
    {
        $this->resetPage();
    }

    public function ensureSampleDataExists()
    {
        if (JobWastage::count() === 0) {
            $mfgProduct1 = ManufacturingProduct::firstOrCreate(
                ['name' => 'KTC Bed Sheet 2-Side'],
                ['code' => 'MP-KTC-001', 'status' => 'active']
            );

            $mfgProduct2 = ManufacturingProduct::firstOrCreate(
                ['name' => 'Premium Pillow Case'],
                ['code' => 'MP-PILLOW-002', 'status' => 'active']
            );

            $taskIroning = Task::firstOrCreate(
                ['name' => 'Ironing & Folding'],
                ['code' => 'TSK-IRN', 'status' => true]
            );

            $taskStitching = Task::firstOrCreate(
                ['name' => 'Stitching'],
                ['code' => 'TSK-STITCH', 'status' => true]
            );

            $batch1 = ProductionBatch::firstOrCreate(
                ['batch_code' => 'PB-2026-0019'],
                [
                    'manufacturing_product_id' => $mfgProduct1->id,
                    'planned_quantity' => 125,
                    'status' => 'Completed',
                ]
            );

            $batch2 = ProductionBatch::firstOrCreate(
                ['batch_code' => 'PB-2026-0015-A1'],
                [
                    'manufacturing_product_id' => $mfgProduct2->id,
                    'planned_quantity' => 65,
                    'status' => 'Completed',
                ]
            );

            $job1 = ProductionJob::firstOrCreate(
                ['job_code' => 'JOB-2026-0019'],
                [
                    'production_batch_id' => $batch1->batch_code,
                    'production_batch_db_id' => $batch1->id,
                    'manufacturing_product_id' => $mfgProduct1->id,
                    'target_quantity' => 125,
                    'status' => 'completed',
                ]
            );

            $job2 = ProductionJob::firstOrCreate(
                ['job_code' => 'JOB-2026-0015'],
                [
                    'production_batch_id' => $batch2->batch_code,
                    'production_batch_db_id' => $batch2->id,
                    'manufacturing_product_id' => $mfgProduct2->id,
                    'target_quantity' => 65,
                    'status' => 'completed',
                ]
            );

            JobWastage::create([
                'job_code' => $job1->job_code,
                'production_job_id' => $job1->id,
                'manufacturing_product_id' => $mfgProduct1->id,
                'task_id' => $taskIroning->id,
                'wastage_type' => 'scrap',
                'quantity_wasted' => 4.00,
                'reason' => 'Unaccounted scrap during final batch completion',
                'created_at' => now()->subDays(15),
            ]);

            JobWastage::create([
                'job_code' => $job2->job_code,
                'production_job_id' => $job2->id,
                'manufacturing_product_id' => $mfgProduct2->id,
                'task_id' => $taskStitching->id,
                'wastage_type' => 'damage',
                'quantity_wasted' => 2.00,
                'reason' => 'Edge tear defect non-alterable',
                'created_at' => now()->subDays(12),
            ]);
        }
    }

    public function render()
    {
        $query = JobWastage::with([
            'productionJob.batch',
            'productionJob.pattern',
            'manufacturingProduct.patterns',
            'pattern',
            'task',
            'inventoryBaleRoll'
        ])->where('quantity_wasted', '>', 0);

        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('job_code', 'like', $term)
                  ->orWhere('reason', 'like', $term)
                  ->orWhere('wastage_type', 'like', $term)
                  ->orWhereHas('manufacturingProduct', fn($m) => $m->where('name', 'like', $term)->orWhere('code', 'like', $term))
                  ->orWhereHas('pattern', fn($p) => $p->where('name', 'like', $term))
                  ->orWhereHas('productionJob', fn($j) => $j->where('job_code', 'like', $term)->orWhere('production_batch_id', 'like', $term))
                  ->orWhereHas('productionJob.pattern', fn($jp) => $jp->where('name', 'like', $term))
                  ->orWhereHas('productionJob.batch', fn($b) => $b->where('batch_code', 'like', $term))
                  ->orWhereHas('task', fn($t) => $t->where('name', 'like', $term));
            });
        }

        if (!empty($this->selectedTask)) {
            $query->where('task_id', $this->selectedTask);
        }

        if (!empty($this->selectedWastageType)) {
            if (in_array(strtolower($this->selectedWastageType), ['damage', 'damaged'])) {
                $query->whereIn('wastage_type', ['damage', 'damaged']);
            } else {
                $query->where('wastage_type', $this->selectedWastageType);
            }
        }

        $wastages = $query->orderBy('created_at', 'desc')->paginate(10);

        // Compute summary KPIs for actual wastes (> 0)
        $validWastages = JobWastage::where('quantity_wasted', '>', 0);
        $totalWastageQty = (float) $validWastages->sum('quantity_wasted');
        $lossIncidentsCount = $validWastages->count();
        
        $impactedJobIds = (clone $validWastages)->whereNotNull('production_job_id')->pluck('production_job_id')->unique();
        $impactedBatchCount = ProductionJob::whereIn('id', $impactedJobIds)->whereNotNull('production_batch_db_id')->pluck('production_batch_db_id')->unique()->count();
        if ($impactedBatchCount === 0) {
            $impactedBatchCount = $impactedJobIds->count();
        }

        $totalTargetQty = ProductionJob::whereIn('id', $impactedJobIds)->sum('target_quantity');
        $avgLossRate = $totalTargetQty > 0 ? round(($totalWastageQty / $totalTargetQty) * 100, 1) : 0.0;

        $tasks = Task::where('status', true)->orderBy('name')->get();

        return view('livewire.factory.wastage-log-page', [
            'wastages' => $wastages,
            'totalWastageQty' => $totalWastageQty,
            'lossIncidentsCount' => $lossIncidentsCount,
            'impactedBatchCount' => $impactedBatchCount,
            'avgLossRate' => $avgLossRate,
            'tasks' => $tasks,
        ])->title('Wastage & Scrap Log');
    }
}
