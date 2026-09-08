<?php

namespace App\Livewire\Admin\Production;

use App\Models\FactorySupervisor;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Services\Manufacturing\ProductionWorkflowService;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.admin.layout')]
class CreateProductionBatch extends Component
{
    public string $batch_code_preview = '';
    public $manufacturing_product_id = null;
    public $pattern_id = null;
    public int $planned_quantity = 500;
    public string $priority = 'Normal'; // Urgent, Normal, Low
    public string $batch_date = '';
    public $factory_supervisor_id = null;
    public string $remarks = '';

    public function mount()
    {
        // Spatie RBAC Check
        $user = auth()->user();
        $hasRole = false;
        try {
            $hasRole = $user->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor']);
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
            $hasRole = $user->hasAnyRole(['super_admin', 'admin']);
        }

        if (!$hasRole && !$user->can('manage_labor')) {
            abort(403, 'Unauthorized access to initiate production batches.');
        }

        $this->batch_date = now()->format('Y-m-d');

        // Default to first active supervisor if available
        $firstSupervisor = FactorySupervisor::active()->orderBy('name')->first();
        $this->factory_supervisor_id = $firstSupervisor?->id;

        $latestId = ProductionBatch::max('id') ?? 0;
        $this->batch_code_preview = 'PB-' . date('Y') . '-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);

        $firstProduct = ManufacturingProduct::first();
        if ($firstProduct) {
            $this->manufacturing_product_id = $firstProduct->id;
            $this->loadDefaultPattern();
        }
    }

    public function updatedManufacturingProductId()
    {
        $this->loadDefaultPattern();
    }

    protected function loadDefaultPattern()
    {
        if ($this->manufacturing_product_id) {
            $patterns = \App\Models\ManufacturingProductPattern::where('manufacturing_product_id', $this->manufacturing_product_id)->get();
            $defaultPattern = $patterns->firstWhere('is_default', true) ?? $patterns->first();
            $this->pattern_id = $defaultPattern?->id;
        } else {
            $this->pattern_id = null;
        }
    }

    public function saveBatch(ProductionWorkflowService $workflowService)
    {
        $this->validate([
            'manufacturing_product_id'  => 'required|exists:manufacturing_products,id',
            'pattern_id'                => 'nullable|exists:manufacturing_product_patterns,id',
            'planned_quantity'          => 'required|numeric|min:1',
            'priority'                  => 'required|in:Urgent,Normal,Low',
            'batch_date'               => 'required|date',
            'factory_supervisor_id'    => 'required|exists:factory_supervisors,id',
            'remarks'                  => 'nullable|string|max:1000',
        ], [
            'manufacturing_product_id.required'  => 'Please select a manufacturing product.',
            'planned_quantity.min'               => 'Planned quantity must be at least 1 unit.',
            'factory_supervisor_id.required'     => 'Please select a supervisor.',
        ]);

        $response = $workflowService->initiateBatch(
            $this->manufacturing_product_id,
            $this->factory_supervisor_id,
            $this->planned_quantity,
            $this->priority,
            $this->remarks,
            $this->batch_date,
            $this->pattern_id
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
        $allProducts = ManufacturingProduct::with(['tasks', 'patterns'])->get();
        $selectedProduct = ManufacturingProduct::with(['tasks', 'patterns'])->find($this->manufacturing_product_id);
        $availablePatterns = $this->manufacturing_product_id 
            ? \App\Models\ManufacturingProductPattern::with('tasks')->where('manufacturing_product_id', $this->manufacturing_product_id)->get()
            : collect();
        $selectedPattern = $availablePatterns->firstWhere('id', $this->pattern_id);

        $recentBatches = ProductionBatch::with(['manufacturingProduct', 'pattern', 'factorySupervisor', 'childBatches', 'parentBatch'])->latest()->take(10)->get();
        $supervisors = FactorySupervisor::active()->orderBy('name')->get();

        return view('livewire.admin.production.create-production-batch', [
            'allProducts'       => $allProducts,
            'selectedProduct'   => $selectedProduct,
            'availablePatterns' => $availablePatterns,
            'selectedPattern'   => $selectedPattern,
            'recentBatches'     => $recentBatches,
            'supervisors'       => $supervisors,
        ])->title('Create Production Batch');
    }
}
