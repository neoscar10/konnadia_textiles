<?php

namespace App\Livewire\Admin\Production;

use App\Models\Category;
use App\Models\FrontEndProduct;
use App\Models\ManufacturingProduct;
use App\Models\RawMaterial;
use App\Services\Catalog\CategoryService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.admin.layout')]
class FrontEndProductIndexPage extends Component
{
    use WithPagination;

    public string $search = '';

    // Modal state
    public bool $showModal = false;
    public ?int $editingProductId = null;

    public string $name = '';
    public string $sku = '';
    public ?int $category_id = null;
    public string $leaf_category_name = '';
    public bool $is_active = true;
    public string $description = '';

    // Dynamic row structures
    public array $mfgRows = [];
    public array $pkgRows = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'sku' => 'required|string|max:100',
        'mfgRows' => 'required|array|min:1',
        'mfgRows.*.manufacturing_product_id' => 'required|integer|exists:manufacturing_products,id',
        'mfgRows.*.quantity' => 'required|integer|min:1',
    ];

    public function mount()
    {
        $this->resetForm();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function resetForm()
    {
        $this->editingProductId = null;
        $this->name = '';
        $this->sku = '';
        $this->category_id = null;
        $this->leaf_category_name = '';
        $this->is_active = true;
        $this->description = '';

        $this->mfgRows = [
            ['manufacturing_product_id' => '', 'quantity' => 1],
        ];

        $this->pkgRows = [
            ['raw_material_id' => '', 'quantity' => 1],
        ];

        $this->resetErrorBag();
    }

    public function openCreateModal()
    {
        $this->resetForm();

        // Generate default unique SKU if empty
        $nextNum = FrontEndProduct::withTrashed()->count() + 52;
        $this->sku = "KT-P-" . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);

        $this->showModal = true;
    }

    public function addMfgRow()
    {
        $this->mfgRows[] = [
            'manufacturing_product_id' => '',
            'quantity' => 1,
        ];
    }

    public function removeMfgRow(int $index)
    {
        unset($this->mfgRows[$index]);
        $this->mfgRows = array_values($this->mfgRows);
    }

    public function addPkgRow()
    {
        $this->pkgRows[] = [
            'raw_material_id' => '',
            'quantity' => 1,
        ];
    }

    public function removePkgRow(int $index)
    {
        unset($this->pkgRows[$index]);
        $this->pkgRows = array_values($this->pkgRows);
    }

    public function editProduct(int $id)
    {
        $product = FrontEndProduct::with(['components', 'packagingItems'])->findOrFail($id);

        $this->editingProductId = $product->id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->category_id = $product->category_id;
        $this->leaf_category_name = $product->leaf_category_name ?? '';
        $this->is_active = (bool) $product->is_active;
        $this->description = $product->description ?? '';

        $this->mfgRows = $product->components->map(fn($c) => [
            'manufacturing_product_id' => $c->manufacturing_product_id,
            'quantity' => $c->quantity,
        ])->toArray();

        if (empty($this->mfgRows)) {
            $this->mfgRows = [['manufacturing_product_id' => '', 'quantity' => 1]];
        }

        $this->pkgRows = $product->packagingItems->map(fn($p) => [
            'raw_material_id' => $p->raw_material_id,
            'quantity' => $p->quantity,
        ])->toArray();

        if (empty($this->pkgRows)) {
            $this->pkgRows = [['raw_material_id' => '', 'quantity' => 1]];
        }

        $this->showModal = true;
    }

    public function saveProduct()
    {
        $rules = $this->rules;
        if ($this->editingProductId) {
            $rules['sku'] = 'required|string|max:100|unique:front_end_products,sku,' . $this->editingProductId;
        } else {
            $rules['sku'] = 'required|string|max:100|unique:front_end_products,sku';
        }

        $this->validate($rules);

        // Filter valid rows
        $validMfgRows = array_filter($this->mfgRows, fn($r) => !empty($r['manufacturing_product_id']) && intval($r['quantity']) > 0);
        if (empty($validMfgRows)) {
            $this->addError('mfgRows', 'Please add at least one valid manufacturing product.');
            return;
        }

        $validPkgRows = array_filter($this->pkgRows, fn($r) => !empty($r['raw_material_id']) && intval($r['quantity']) > 0);

        \Illuminate\Support\Facades\DB::transaction(function () use ($validMfgRows, $validPkgRows) {
            $product = FrontEndProduct::updateOrCreate(
                ['id' => $this->editingProductId],
                [
                    'name' => trim($this->name),
                    'sku' => trim($this->sku),
                    'category_id' => $this->category_id ?: null,
                    'leaf_category_name' => trim($this->leaf_category_name) ?: null,
                    'is_active' => $this->is_active,
                    'description' => trim($this->description) ?: null,
                ]
            );

            // Sync components
            $product->components()->delete();
            foreach ($validMfgRows as $mfg) {
                $product->components()->create([
                    'manufacturing_product_id' => intval($mfg['manufacturing_product_id']),
                    'quantity' => intval($mfg['quantity']),
                ]);
            }

            // Sync packaging
            $product->packagingItems()->delete();
            foreach ($validPkgRows as $pkg) {
                $product->packagingItems()->create([
                    'raw_material_id' => intval($pkg['raw_material_id']),
                    'quantity' => intval($pkg['quantity']),
                ]);
            }
        });

        session()->flash('toast', [
            'type' => 'success',
            'message' => $this->editingProductId ? "Front-End Product updated successfully!" : "Front-End Product created successfully!",
        ]);

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $id)
    {
        $product = FrontEndProduct::findOrFail($id);
        $product->update(['is_active' => !$product->is_active]);

        session()->flash('toast', [
            'type' => 'success',
            'message' => "Front-End Product status updated.",
        ]);
    }

    public function render(CategoryService $categoryService)
    {
        $productsQuery = FrontEndProduct::with(['category.parent.parent', 'components.manufacturingProduct', 'packagingItems.rawMaterial'])
            ->when($this->search, function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('sku', 'like', "%{$this->search}%")
                  ->orWhere('leaf_category_name', 'like', "%{$this->search}%");
            })
            ->latest();

        $frontendProducts = $productsQuery->paginate(12);

        $mfgProducts = ManufacturingProduct::orderBy('name')->get();

        // Packaging raw materials (category subsidiary or all materials)
        $packagingMaterials = RawMaterial::orderBy('name')->get();

        // Load leaf categories using CategoryService (same as admin/products page)
        $categories = $categoryService->getLeafCategories();

        return view('livewire.admin.production.front-end-product-index-page', [
            'frontendProducts' => $frontendProducts,
            'mfgProducts' => $mfgProducts,
            'packagingMaterials' => $packagingMaterials,
            'categories' => $categories,
        ])->title('Front-End Products');
    }
}
