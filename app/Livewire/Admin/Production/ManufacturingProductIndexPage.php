<?php

namespace App\Livewire\Admin\Production;

use App\Models\ManufacturingProduct;
 use App\Models\ManufacturingProductCategory;
use App\Models\Task;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class ManufacturingProductIndexPage extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    // Product Modal Properties
    public $productId = null;
    public string $name = '';
    public string $code = '';
    public $standard_labor_rate = 15.00;
    public $manufacturing_product_category_id = '';

    // Task Routing Modal Properties
    public $routingProductId = null;
    public array $routingTasks = [];

    // View Routing Modal Properties
    public $viewRoutingProductId = null;

    public function mount()
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'admin', 'Factory Supervisor']) && !auth()->user()->can('manage_labor')) {
            abort(403, 'Unauthorized access to manufacturing products.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateProductModal(): void
    {
        $this->resetValidation();
        $this->reset(['productId', 'name', 'code', 'manufacturing_product_category_id']);
        $this->standard_labor_rate = 15.00;

        $latestId = ManufacturingProduct::max('id') ?? 0;
        $this->code = 'MP-BED-' . str_pad($latestId + 1, 3, '0', STR_PAD_LEFT);

        $this->dispatch('open-modal', 'product-modal');
    }

    public function editProduct(int $id): void
    {
        $this->resetValidation();
        $product = ManufacturingProduct::findOrFail($id);
        $this->productId = $product->id;
        $this->name = $product->name;
        $this->code = $product->code;
        $this->standard_labor_rate = $product->standard_labor_rate;
        $this->manufacturing_product_category_id = $product->manufacturing_product_category_id ?? '';

        $this->dispatch('open-modal', 'product-modal');
    }

    public function saveProduct(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:manufacturing_products,code,' . $this->productId,
            'manufacturing_product_category_id' => 'nullable|exists:manufacturing_product_categories,id',
        ]);

        $isNewProduct = !$this->productId;

        if ($this->productId) {
            $product = ManufacturingProduct::findOrFail($this->productId);
            $product->update([
                'name' => $this->name,
                'code' => $this->code,
                'manufacturing_product_category_id' => $this->manufacturing_product_category_id ?: null,
            ]);
            $message = "Manufacturing Product {$product->name} updated successfully!";
        } else {
            $product = ManufacturingProduct::create([
                'name' => $this->name,
                'code' => $this->code,
                'manufacturing_product_category_id' => $this->manufacturing_product_category_id ?: null,
            ]);

            $message = "Manufacturing Product {$product->name} created successfully! Please configure its task routing.";
        }

        $this->dispatch('close-modal', 'product-modal');
        $this->dispatch('toast', message: $message, type: 'success');

        if ($isNewProduct) {
            $this->openRoutingModal($product->id);
        }
    }

    public function openViewRoutingModal(int $productId): void
    {
        $this->viewRoutingProductId = $productId;
        $this->dispatch('open-modal', 'view-routing-modal');
    }

    public function openRoutingModal(int $productId): void
    {
        $this->resetValidation();
        $this->routingProductId = $productId;
        $product = ManufacturingProduct::with('tasks')->findOrFail($productId);

        $this->routingTasks = [];
        foreach ($product->tasks as $idx => $task) {
            $this->routingTasks[] = [
                'task_id' => $task->id,
                'sequence_number' => $task->pivot->sequence_number ?? ($idx + 1),
                'standard_labor_rate' => $task->pivot->standard_labor_rate ?? $product->standard_labor_rate ?? 15.00,
                'is_final_step' => (bool) ($task->pivot->is_final_step ?? false),
            ];
        }

        if (empty($this->routingTasks)) {
            $firstTask = Task::first();
            $this->routingTasks[] = [
                'task_id' => $firstTask ? $firstTask->id : '',
                'sequence_number' => 1,
                'standard_labor_rate' => $product->standard_labor_rate ?? 15.00,
                'is_final_step' => true,
            ];
        }

        $this->dispatch('open-modal', 'routing-modal');
    }

    public function addRoutingTaskRow(): void
    {
        $nextSeq = count($this->routingTasks) + 1;
        $allTasks = Task::where('status', true)->get();
        $existingTaskIds = array_column($this->routingTasks, 'task_id');
        $nextTask = $allTasks->reject(fn($t) => in_array($t->id, $existingTaskIds))->first();

        // Automatically set the new last row as the default final step
        foreach ($this->routingTasks as &$row) {
            $row['is_final_step'] = false;
        }
        unset($row);

        $this->routingTasks[] = [
            'task_id' => $nextTask ? $nextTask->id : '',
            'sequence_number' => $nextSeq,
            'standard_labor_rate' => 15.00,
            'is_final_step' => true,
        ];
    }

    public function removeRoutingTaskRow(int $index): void
    {
        if (count($this->routingTasks) > 1) {
            unset($this->routingTasks[$index]);
            $this->routingTasks = array_values($this->routingTasks);

            // Recalculate sequence numbers
            foreach ($this->routingTasks as $idx => &$row) {
                $row['sequence_number'] = $idx + 1;
            }

            // Ensure at least one final step is set if none selected after removal
            $hasFinal = !empty(array_filter($this->routingTasks, fn($r) => !empty($r['is_final_step'])));
            if (!$hasFinal) {
                $lastIdx = count($this->routingTasks) - 1;
                $this->routingTasks[$lastIdx]['is_final_step'] = true;
            }
        }
    }

    public function moveRoutingTaskUp(int $index): void
    {
        if ($index > 0 && isset($this->routingTasks[$index])) {
            $temp = $this->routingTasks[$index - 1];
            $this->routingTasks[$index - 1] = $this->routingTasks[$index];
            $this->routingTasks[$index] = $temp;
            $this->resequenceRoutingTasks();
        }
    }

    public function moveRoutingTaskDown(int $index): void
    {
        if ($index < count($this->routingTasks) - 1 && isset($this->routingTasks[$index])) {
            $temp = $this->routingTasks[$index + 1];
            $this->routingTasks[$index + 1] = $this->routingTasks[$index];
            $this->routingTasks[$index] = $temp;
            $this->resequenceRoutingTasks();
        }
    }

    private function resequenceRoutingTasks(): void
    {
        $this->routingTasks = array_values($this->routingTasks);
        foreach ($this->routingTasks as $idx => &$row) {
            $row['sequence_number'] = $idx + 1;
        }
        unset($row);
    }

    /**
     * Mark a specific routing row as the Final Production Step (radio-like selection).
     * Clears is_final_step on all other rows.
     */
    public function setFinalStep(int $index): void
    {
        foreach ($this->routingTasks as $idx => &$row) {
            $row['is_final_step'] = ($idx === $index);
        }
        unset($row);
    }

    public function saveRouting(): void
    {
        $this->resequenceRoutingTasks();

        $this->validate([
            'routingTasks' => 'required|array|min:1',
            'routingTasks.*.task_id' => 'required|exists:tasks,id',
            'routingTasks.*.sequence_number' => 'required|numeric|min:1',
            'routingTasks.*.standard_labor_rate' => 'required|numeric|min:0',
        ]);

        // SRS Rule: Exactly ONE task must be designated as the Final Production Step.
        $finalStepCount = count(array_filter($this->routingTasks, fn($r) => !empty($r['is_final_step'])));
        if ($finalStepCount !== 1) {
            $this->addError('routingTasks', 'Exactly one task must be designated as the Final Production Step.');
            return;
        }

        // SRS Rule: No duplicate task IDs in the same product routing.
        $taskIds = array_column($this->routingTasks, 'task_id');
        if (count($taskIds) !== count(array_unique($taskIds))) {
            $this->addError('routingTasks', 'Duplicate tasks detected. Each manufacturing stage task may only appear once per product routing.');
            return;
        }

        $product = ManufacturingProduct::findOrFail($this->routingProductId);

        $syncData = [];
        foreach ($this->routingTasks as $row) {
            $syncData[$row['task_id']] = [
                'sequence_number' => (int) $row['sequence_number'],
                'standard_labor_rate' => (float) $row['standard_labor_rate'],
                'is_final_step' => !empty($row['is_final_step']),
            ];
        }

        $product->tasks()->sync($syncData);

        $this->dispatch('close-modal', 'routing-modal');
        $this->dispatch('toast', message: "Routing & piece-rate wages updated for {$product->name}!", type: 'success');
    }

    public function render()
    {
        $query = ManufacturingProduct::with(['tasks', 'category']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%");
            });
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(10);
        $allTasks = Task::where('status', true)->get();
        $allCategories = ManufacturingProductCategory::active()->orderBy('name')->get();
        $routingProduct = $this->routingProductId ? ManufacturingProduct::find($this->routingProductId) : null;
        $viewRoutingProduct = $this->viewRoutingProductId
            ? ManufacturingProduct::with('tasks', 'category')->find($this->viewRoutingProductId)
            : null;

        return view('livewire.admin.production.manufacturing-product-index-page', [
            'products' => $products,
            'allTasks' => $allTasks,
            'allCategories' => $allCategories,
            'routingProduct' => $routingProduct,
            'viewRoutingProduct' => $viewRoutingProduct,
        ])->title('Manufacturing Product Master & Routing Catalog');
    }
}
