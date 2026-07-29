<?php

namespace App\Livewire\Admin\Production;

use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\User;
use App\Services\Manufacturing\ProductionWorkflowService;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class CreateProductionBatch extends Component
{
    public string $batch_code_preview = '';
    public $manufacturing_product_id = null;
    public int $planned_quantity = 500;
    public string $priority = 'Normal'; // Urgent, Normal, Low
    public string $batch_date = '';
    public $supervisor_id = null;
    public string $remarks = '';

    public function mount()
    {
        // Spatie RBAC Check
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor']) && !auth()->user()->can('manage_labor')) {
            abort(403, 'Unauthorized access to initiate production batches.');
        }

        $this->batch_date = now()->format('Y-m-d');
        $this->supervisor_id = auth()->id();

        $latestId = ProductionBatch::max('id') ?? 0;
        $this->batch_code_preview = 'PB-' . date('Y') . '-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);

        $firstProduct = ManufacturingProduct::first();
        if ($firstProduct) {
            $this->manufacturing_product_id = $firstProduct->id;
        }
    }

    public function saveBatch(ProductionWorkflowService $workflowService)
    {
        $this->validate([
            'manufacturing_product_id' => 'required|exists:manufacturing_products,id',
            'planned_quantity' => 'required|numeric|min:1',
            'priority' => 'required|in:Urgent,Normal,Low',
            'batch_date' => 'required|date',
            'supervisor_id' => 'required|exists:users,id',
            'remarks' => 'nullable|string|max:1000',
        ], [
            'manufacturing_product_id.required' => 'Please select a manufacturing product.',
            'planned_quantity.min' => 'Planned quantity must be at least 1 unit.',
        ]);

        $response = $workflowService->initiateBatch(
            $this->manufacturing_product_id,
            $this->supervisor_id,
            $this->planned_quantity,
            $this->priority,
            $this->remarks,
            $this->batch_date
        );

        $responseData = $response->getData(true);

        if (isset($responseData['success']) && $responseData['success']) {
            $batchId = $responseData['data']['batch']['id'] ?? null;
            $batchCode = $responseData['data']['batch']['batch_code'] ?? $this->batch_code_preview;
            $this->dispatch('toast', message: "Production Batch {$batchCode} & First Job initiated successfully!", type: 'success');
            
            if ($batchId) {
                return redirect()->route('admin.production.batches.ledger', $batchId);
            }
            return redirect()->route('admin.production.workbench');
        } else {
            $errorMessage = $responseData['message'] ?? 'Failed to initiate production batch.';
            $this->addError('manufacturing_product_id', $errorMessage);
        }
    }

    public function render()
    {
        $allProducts = ManufacturingProduct::with('tasks')->get();
        $selectedProduct = ManufacturingProduct::with('tasks')->find($this->manufacturing_product_id);
        $recentBatches = ProductionBatch::with(['manufacturingProduct', 'supervisor', 'childBatches', 'parentBatch'])->latest()->take(10)->get();

        $supervisors = User::role(['super_admin', 'admin', 'Factory Supervisor'])->get();
        if ($supervisors->isEmpty()) {
            $supervisors = User::all();
        }

        return view('livewire.admin.production.create-production-batch', [
            'allProducts' => $allProducts,
            'selectedProduct' => $selectedProduct,
            'recentBatches' => $recentBatches,
            'supervisors' => $supervisors,
        ])->title('Create Production Batch');
    }
}
