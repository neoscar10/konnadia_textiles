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
    public ?int $editingCategoryId = null;
    public string $categoryName = '';
    public string $categoryFullPath = '';
    public ?int $editingProductId = null;

    // Dynamic row structures
    public array $mfgRows = [];
    public array $pkgRows = [];

    protected $rules = [
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
        $this->editingCategoryId = null;
        $this->categoryName = '';
        $this->categoryFullPath = '';
        $this->editingProductId = null;

        $this->mfgRows = [
            ['manufacturing_product_id' => '', 'quantity' => 1],
        ];

        $this->pkgRows = [
            ['raw_material_id' => '', 'quantity' => 1],
        ];

        $this->resetErrorBag();
    }

    public function configureCategory(int $categoryId, CategoryService $categoryService)
    {
        $this->resetForm();

        $category = Category::findOrFail($categoryId);
        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categoryFullPath = $categoryService->buildPath($category);

        $feProduct = FrontEndProduct::with(['components', 'packagingItems'])
            ->where('category_id', $category->id)
            ->first();

        if ($feProduct) {
            $this->editingProductId = $feProduct->id;
            $this->mfgRows = $feProduct->components->map(fn($c) => [
                'manufacturing_product_id' => $c->manufacturing_product_id,
                'quantity' => $c->quantity,
            ])->toArray();

            $this->pkgRows = $feProduct->packagingItems->map(fn($p) => [
                'raw_material_id' => $p->raw_material_id,
                'quantity' => $p->quantity,
            ])->toArray();
        }

        if (empty($this->mfgRows)) {
            $this->mfgRows = [['manufacturing_product_id' => '', 'quantity' => 1]];
        }

        if (empty($this->pkgRows)) {
            $this->pkgRows = [['raw_material_id' => '', 'quantity' => 1]];
        }

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

    public function saveCategoryConfiguration()
    {
        if (!$this->editingCategoryId) {
            return;
        }

        $this->validate($this->rules);

        $validMfgRows = array_filter($this->mfgRows, fn($r) => !empty($r['manufacturing_product_id']) && intval($r['quantity']) > 0);
        if (empty($validMfgRows)) {
            $this->addError('mfgRows', 'Please add at least one valid manufacturing product requirement.');
            return;
        }

        $validPkgRows = array_filter($this->pkgRows, fn($r) => !empty($r['raw_material_id']) && intval($r['quantity']) > 0);

        \Illuminate\Support\Facades\DB::transaction(function () use ($validMfgRows, $validPkgRows) {
            $sku = "CAT-CFG-" . str_pad((string) $this->editingCategoryId, 4, '0', STR_PAD_LEFT);

            $feProduct = FrontEndProduct::updateOrCreate(
                ['category_id' => $this->editingCategoryId],
                [
                    'name' => trim($this->categoryName),
                    'sku' => $sku,
                    'leaf_category_name' => trim($this->categoryName),
                    'is_active' => true,
                ]
            );

            // Sync components
            $feProduct->components()->delete();
            foreach ($validMfgRows as $mfg) {
                $feProduct->components()->create([
                    'manufacturing_product_id' => intval($mfg['manufacturing_product_id']),
                    'quantity' => intval($mfg['quantity']),
                ]);
            }

            // Sync packaging
            $feProduct->packagingItems()->delete();
            foreach ($validPkgRows as $pkg) {
                $feProduct->packagingItems()->create([
                    'raw_material_id' => intval($pkg['raw_material_id']),
                    'quantity' => intval($pkg['quantity']),
                ]);
            }
        });

        session()->flash('toast', [
            'type' => 'success',
            'message' => "Category assembly configuration for '{$this->categoryName}' saved successfully!",
        ]);

        $this->showModal = false;
        $this->resetForm();
    }

    public function render(CategoryService $categoryService)
    {
        $allLeafCategories = $categoryService->getLeafCategories();

        if (!empty($this->search)) {
            $term = strtolower(trim($this->search));
            $allLeafCategories = $allLeafCategories->filter(function ($cat) use ($term, $categoryService) {
                $path = strtolower($categoryService->buildPath($cat));
                return str_contains(strtolower($cat->name), $term) || str_contains($path, $term);
            });
        }

        // Map configuration details onto categories
        $configs = FrontEndProduct::with(['components.manufacturingProduct', 'packagingItems.rawMaterial'])
            ->whereIn('category_id', $allLeafCategories->pluck('id'))
            ->get()
            ->keyBy('category_id');

        $leafCategoriesMapped = $allLeafCategories->map(function ($cat) use ($configs, $categoryService) {
            $config = $configs->get($cat->id);
            return (object) [
                'id' => $cat->id,
                'name' => $cat->name,
                'full_path' => $categoryService->buildPath($cat),
                'is_configured' => $config !== null && $config->components->count() > 0,
                'config' => $config,
                'components' => $config ? $config->components : collect(),
                'packaging' => $config ? $config->packagingItems : collect(),
            ];
        });

        // Manual pagination for mapped collection
        $page = $this->getPage();
        $perPage = 12;
        $paginatedCategories = new \Illuminate\Pagination\LengthAwarePaginator(
            $leafCategoriesMapped->slice(($page - 1) * $perPage, $perPage)->values(),
            $leafCategoriesMapped->count(),
            $perPage,
            $page,
            ['path' => request()->url()]
        );

        $mfgProducts = ManufacturingProduct::orderBy('name')->get();
        $packagingMaterials = RawMaterial::orderBy('name')->get();

        return view('livewire.admin.production.front-end-product-index-page', [
            'categories' => $paginatedCategories,
            'mfgProducts' => $mfgProducts,
            'packagingMaterials' => $packagingMaterials,
        ])->title('Front-End Products Configuration');
    }
}
