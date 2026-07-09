<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
use App\Models\Product;
use App\Models\CustomerLevel;
use App\Models\ProductMedia;
use App\Services\Catalog\CategoryService;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\ProductVariationService;
use App\Services\Catalog\ProductMediaService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;

#[Layout('components.admin.layout')]
class CategoryIndexPage extends Component
{
    use WithPagination, WithFileUploads;

    // ─────────────────────────────────────────────────────────────────────────
    // CATEGORY NAVIGATION
    // ─────────────────────────────────────────────────────────────────────────

    #[Url(as: 'folder', history: true)]
    public ?int $currentCategoryId = null;

    #[Url(history: true)]
    public string $search = '';

    // ─────────────────────────────────────────────────────────────────────────
    // CATEGORY CRUD STATE
    // ─────────────────────────────────────────────────────────────────────────

    public ?int $editingCategoryId = null;
    public ?int $deleteCategoryId  = null;

    public array $form = [
        'name'        => '',
        'description' => '',
        'is_active'   => true,
        'is_leaf'     => false,
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // LEAF SAFETY MODAL STATE
    // ─────────────────────────────────────────────────────────────────────────

    public string $leafSafetyAction       = '';   // 'delete'
    public ?int   $leafSafetyCategoryId   = null;
    public int    $leafSafetyProductCount = 0;
    public bool   $showDeleteAllConfirm   = false;
    public ?int   $moveToTargetCategoryId = null;

    // ─────────────────────────────────────────────────────────────────────────
    // PRODUCT LISTING STATE (leaf view)
    // ─────────────────────────────────────────────────────────────────────────

    public string $productSearch       = '';
    public string $productFilterStatus = '';
    public string $productFilterStock  = '';

    // ─────────────────────────────────────────────────────────────────────────
    // PRODUCT WIZARD STATE (mirrors ProductIndexPage)
    // ─────────────────────────────────────────────────────────────────────────

    public ?int  $productLockedCategoryId = null; // When set, category step is locked
    public bool  $showWizardModal         = false;
    public ?int  $selectedProductId       = null;
    public ?int  $deleteProductId         = null;
    public int   $currentStep             = 1;
    public bool  $isEditMode              = false;
    public bool  $isPreviewMode           = false;
    public array $selectedTagIds          = [];

    // Step 1: Basic Info
    public array $basicInfo = [
        'title'                  => '',
        'base_price'             => '',
        'hsn_code'               => '',
        'gst_percentage'         => '',
        'minimum_order_quantity' => 1,
        'description'            => '',
        'is_active'              => true,
        'product_type'           => 'retail',
    ];

    // Step 2: Media
    public        $mediaUploads  = [];
    public array  $existingMedia = [];

    // Step 3: Categories (locked in category context)
    public array $selectedCategoryIds = [];

    // Step 4 Variations (commented out in wizard UI – kept for data completeness)
    public array $variationGroups    = [];
    public ?int  $managingGroupIndex = null;
    public ?int  $managingValueIndex = null;
    public       $valueMediaUploads  = [];

    // Stock
    public        $nonVariantStock = '';
    public string $totalStock      = '';
    public array  $combinations    = [];
    public string $bulkStock       = '';
    public string $bulkPrice       = '';

    // Step 4 (now): Pricing & Units
    public array $pricingOverrides = [];
    public array $units = [
        'level1_name'       => 'Piece',
        'level1_code'       => 'pcs',
        'level2_name'       => '',
        'level2_code'       => '',
        'level2_conversion' => '',
    ];

    // Category Defaults
    public array $categoryDefaults = [
        'hsn_code' => '',
        'gst_percentage' => '',
        'minimum_order_quantity' => 1,
        'product_type' => 'retail',
        'base_price' => '',
        'pricingOverrides' => [],
        'units' => [
            'level1_name' => 'Piece',
            'level1_code' => 'pcs',
            'level2_name' => '',
            'level2_code' => '',
            'level2_conversion' => '',
        ],
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // LIVEWIRE LIFECYCLE HOOKS
    // ─────────────────────────────────────────────────────────────────────────

    public function updated($propertyName): void
    {
        if (str_starts_with($propertyName, 'variationGroups')) {
            $this->regenerateCombinations();
        }
    }

    public function updatedSelectedCategoryIds(): void
    {
        $this->selectedCategoryIds = collect($this->selectedCategoryIds)
            ->map(fn($id) => (int)$id)->filter()->unique()->values()->all();
    }

    public function updatedMediaUploads(): void
    {
        $this->validate(['mediaUploads.*' => 'image|max:4096']);

        $productService = app(ProductService::class);
        $mediaService   = app(ProductMediaService::class);

        try {
            DB::transaction(function () use ($productService, $mediaService) {
                if (!$this->selectedProductId) {
                    $this->validateStep(1);

                    $catId = $this->currentCategoryId ?: collect($this->selectedCategoryIds)->first();
                    $category = $catId ? Category::find($catId) : null;
                    $defaults = $category ? ($category->default_product_config ?? []) : [];

                    $payload                          = $this->basicInfo;
                    $payload['base_price']            = isset($defaults['base_price']) && $defaults['base_price'] !== '' ? (float)$defaults['base_price'] : 0.00;
                    $payload['category_ids']          = $this->getEffectiveCategoryIds();
                    $payload['customer_level_prices'] = [];
                    $payload['units']                 = $this->units;
                    $payload['stock_quantity']         = null;
                    $product                          = $productService->create($payload);
                    $this->selectedProductId          = $product->id;
                } else {
                    $product = Product::findOrFail($this->selectedProductId);
                }

                if (!empty($this->mediaUploads)) {
                    $mediaService->storeProductMedia($product, $this->mediaUploads);
                    $this->mediaUploads = [];
                }

                $this->existingMedia = [];
                $product->load('media');
                foreach ($product->media as $m) {
                    $this->existingMedia[] = [
                        'id'         => $m->id,
                        'file_path'  => $m->file_path,
                        'is_primary' => (bool)$m->is_primary,
                    ];
                }
            });

            $this->dispatch('toast', message: 'Images uploaded and saved automatically.', type: 'success');
        } catch (\Exception $e) {
            $this->addError('mediaUploads', $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CATEGORY NAVIGATION
    // ─────────────────────────────────────────────────────────────────────────

    public function selectCategory(?int $id): void
    {
        $this->currentCategoryId    = $id;
        $this->search               = '';
        $this->productSearch        = '';
        $this->productFilterStatus  = '';
        $this->productFilterStock   = '';
        $this->resetPage('productsPage');
        $this->resetErrorBag();
    }

    public function navigateUp(): void
    {
        if ($this->currentCategoryId) {
            $current = Category::find($this->currentCategoryId);
            $this->selectCategory($current ? $current->parent_id : null);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CATEGORY CRUD
    // ─────────────────────────────────────────────────────────────────────────

    public function createCategory(): void
    {
        $this->resetCategoryForm();
        $this->dispatch('open-modal', 'add-category');
    }

    public function editCategory(int $id): void
    {
        $this->resetValidation();
        $category = Category::findOrFail($id);
        $this->editingCategoryId = $category->id;
        $this->form = [
            'name'        => $category->name,
            'description' => $category->description ?? '',
            'is_active'   => (bool)$category->is_active,
            'is_leaf'     => (bool)$category->is_leaf,
        ];
        $this->dispatch('open-modal', 'add-category');
    }

    public function saveCategory(CategoryService $service): void
    {
        $this->validate([
            'form.name'        => ['required', 'string', 'max:150'],
            'form.description' => ['nullable', 'string', 'max:500'],
            'form.is_active'   => ['boolean'],
            'form.is_leaf'     => ['boolean'],
        ]);

        try {
            if ($this->editingCategoryId) {
                $category = Category::findOrFail($this->editingCategoryId);

                // Safety: cannot un-leaf if products exist
                if ($category->is_leaf && !(bool)$this->form['is_leaf']) {
                    $count = $category->products()->count();
                    if ($count > 0) {
                        $this->addError('form.is_leaf', "Cannot convert to folder: {$count} product(s) exist. Move or delete them first.");
                        return;
                    }
                }

                $service->update($category, $this->form);
                $this->dispatch('toast', message: 'Category updated successfully.', type: 'success');
            } else {
                $data              = $this->form;
                $data['parent_id'] = $this->currentCategoryId;
                $service->create($data);
                $this->dispatch('toast', message: 'Category created successfully.', type: 'success');
            }

            $this->dispatch('close-modal', 'add-category');
            $this->resetCategoryForm();
        } catch (\Exception $e) {
            $this->addError('form.name', $e->getMessage());
        }
    }

    public function confirmDeleteCategory(int $id): void
    {
        $category = Category::findOrFail($id);

        // Leaf with products → open safety modal first
        if ($category->is_leaf) {
            $count = $category->products()->count();
            if ($count > 0) {
                $this->leafSafetyAction       = 'delete';
                $this->leafSafetyCategoryId   = $id;
                $this->leafSafetyProductCount = $count;
                $this->moveToTargetCategoryId = null;
                $this->showDeleteAllConfirm   = false;
                $this->dispatch('open-modal', 'leaf-safety');
                return;
            }
        }

        $this->deleteCategoryId = $id;
        $this->dispatch('open-modal', 'delete-category');
    }

    public function deleteCategory(CategoryService $service): void
    {
        if ($this->deleteCategoryId) {
            try {
                $category = Category::findOrFail($this->deleteCategoryId);
                $parentId = $category->parent_id;
                $service->delete($category);

                $this->dispatch('toast', message: 'Category deleted.', type: 'success');
                $this->dispatch('close-modal', 'delete-category');

                if ($this->currentCategoryId === $this->deleteCategoryId) {
                    $this->selectCategory($parentId);
                }
                $this->deleteCategoryId = null;
            } catch (\Exception $e) {
                $this->dispatch('toast', message: $e->getMessage(), type: 'error');
                $this->dispatch('close-modal', 'delete-category');
            }
        }
    }

    public function toggleCategoryStatus(int $id, CategoryService $service): void
    {
        $category = Category::findOrFail($id);
        $service->toggleStatus($category);
        $message = $category->is_active ? 'Category activated.' : 'Category deactivated.';
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function openCategoryDefaults(): void
    {
        $this->resetValidation();
        if (!$this->currentCategoryId) return;

        $category = Category::findOrFail($this->currentCategoryId);
        $defaults = $category->default_product_config;

        if (!empty($defaults)) {
            // Merge defaults in case new levels were added
            $customerLevels = CustomerLevel::active()->ordered()->get();
            $pricing = $defaults['pricingOverrides'] ?? [];
            foreach ($customerLevels as $lvl) {
                if (!isset($pricing[$lvl->id])) {
                    $pricing[$lvl->id] = '';
                }
            }
            $defaults['pricingOverrides'] = $pricing;
            if (!isset($defaults['base_price'])) {
                $defaults['base_price'] = '';
            }
            $this->categoryDefaults = $defaults;
        } else {
            $customerLevels = CustomerLevel::active()->ordered()->get();
            $pricing = [];
            foreach ($customerLevels as $lvl) {
                $pricing[$lvl->id] = '';
            }

            $this->categoryDefaults = [
                'hsn_code' => '',
                'gst_percentage' => '',
                'minimum_order_quantity' => 1,
                'product_type' => 'retail',
                'base_price' => '',
                'pricingOverrides' => $pricing,
                'units' => [
                    'level1_name' => 'Piece',
                    'level1_code' => 'pcs',
                    'level2_name' => '',
                    'level2_code' => '',
                    'level2_conversion' => '',
                ],
            ];
        }

        $this->dispatch('open-modal', 'category-defaults');
    }

    public function saveCategoryDefaults(): void
    {
        $this->validate([
            'categoryDefaults.base_price' => ['required', 'numeric', 'min:0'],
            'categoryDefaults.gst_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'categoryDefaults.minimum_order_quantity' => ['required', 'integer', 'min:1'],
            'categoryDefaults.product_type' => ['required', 'string', 'in:retail,manufactured'],
            'categoryDefaults.units.level1_name' => ['required', 'string', 'max:50'],
            'categoryDefaults.units.level1_code' => ['required', 'string', 'max:20'],
        ]);

        if ($this->currentCategoryId) {
            $category = Category::findOrFail($this->currentCategoryId);
            $category->update([
                'default_product_config' => $this->categoryDefaults,
            ]);

            $this->dispatch('toast', message: 'Category default configuration saved.', type: 'success');
            $this->dispatch('close-modal', 'category-defaults');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LEAF SAFETY ACTIONS
    // ─────────────────────────────────────────────────────────────────────────

    public function deleteAllCategoryProducts(): void
    {
        if (!$this->leafSafetyCategoryId) return;

        try {
            DB::transaction(function () {
                $category   = Category::findOrFail($this->leafSafetyCategoryId);
                $productIds = $category->products()->pluck('products.id')->toArray();

                foreach ($productIds as $pid) {
                    $product = Product::find($pid);
                    if (!$product) continue;
                    $product->categories()->detach($this->leafSafetyCategoryId);
                    // If product now has no other categories, fully delete it
                    if ($product->categories()->count() === 0) {
                        $product->delete();
                    }
                }
            });

            $this->dispatch('toast', message: 'All products removed from category.', type: 'success');
            $this->showDeleteAllConfirm = false;
            $this->dispatch('close-modal', 'leaf-safety');

            // Proceed with the original blocked action
            if ($this->leafSafetyAction === 'delete') {
                $this->deleteCategoryId = $this->leafSafetyCategoryId;
                $this->deleteCategory(app(CategoryService::class));
            }

            $this->leafSafetyCategoryId = null;
            $this->leafSafetyAction     = '';
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function moveProductsToCategory(CategoryService $service): void
    {
        if (!$this->leafSafetyCategoryId || !$this->moveToTargetCategoryId) {
            $this->addError('moveToTargetCategoryId', 'Please select a target category.');
            return;
        }

        try {
            $service->moveProductsToCategory($this->leafSafetyCategoryId, $this->moveToTargetCategoryId);

            $this->dispatch('toast', message: 'Products moved successfully.', type: 'success');
            $this->dispatch('close-modal', 'leaf-safety');

            if ($this->leafSafetyAction === 'delete') {
                $this->deleteCategoryId = $this->leafSafetyCategoryId;
                $this->deleteCategory(app(CategoryService::class));
            }

            $this->leafSafetyCategoryId   = null;
            $this->leafSafetyAction       = '';
            $this->moveToTargetCategoryId = null;
        } catch (\Exception $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRODUCT WIZARD — helpers
    // ─────────────────────────────────────────────────────────────────────────

    protected function getEffectiveCategoryIds(): array
    {
        $ids = $this->selectedCategoryIds;
        if ($this->productLockedCategoryId && !in_array($this->productLockedCategoryId, $ids)) {
            $ids[] = $this->productLockedCategoryId;
        }
        return array_unique(array_filter($ids));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRODUCT WIZARD — CRUD (same method names as ProductIndexPage for partial reuse)
    // ─────────────────────────────────────────────────────────────────────────

    public function create(): void
    {
        $this->resetWizard();
        $this->isEditMode              = false;
        $this->productLockedCategoryId = $this->currentCategoryId;
        $this->selectedCategoryIds     = $this->currentCategoryId ? [$this->currentCategoryId] : [];

        if ($this->currentCategoryId) {
            $category = Category::find($this->currentCategoryId);
            if ($category && !empty($category->default_product_config['units'])) {
                $catUnits = $category->default_product_config['units'];
                $this->units = [
                    'level1_name'       => $catUnits['level1_name'] ?? 'Piece',
                    'level1_code'       => $catUnits['level1_code'] ?? 'pcs',
                    'level2_name'       => $catUnits['level2_name'] ?? '',
                    'level2_code'       => $catUnits['level2_code'] ?? '',
                    'level2_conversion' => $catUnits['level2_conversion'] ?? '',
                ];
            }
        }

        $this->dispatch('open-modal', 'cat-add-product');
    }

    public function edit(int $id): void
    {
        $this->resetWizard();
        $this->isEditMode              = true;
        $this->productLockedCategoryId = $this->currentCategoryId;

        $product = Product::with([
            'categories', 'media', 'variationGroups.values.media',
            'combinations', 'customerLevelPrices', 'units', 'tags',
        ])->findOrFail($id);

        $this->selectedProductId = $product->id;
        $this->selectedTagIds = $product->tags->pluck('id')->toArray();
        $this->basicInfo = [
            'title'                  => $product->title,
            'base_price'             => $product->base_price,
            'hsn_code'               => $product->hsn_code ?? '',
            'gst_percentage'         => $product->gst_percentage !== null ? (string)$product->gst_percentage : '',
            'minimum_order_quantity' => $product->minimum_order_quantity ?? 1,
            'description'            => $product->description ?? '',
            'is_active'              => (bool)$product->is_active,
            'product_type'           => $product->product_type ?? 'retail',
        ];

        foreach ($product->media as $m) {
            $this->existingMedia[] = [
                'id'         => $m->id,
                'file_path'  => $m->file_path,
                'is_primary' => (bool)$m->is_primary,
            ];
        }

        $this->selectedCategoryIds = $product->categories->pluck('id')->map(fn($id) => (int)$id)->all();

        if (empty($this->variationGroups)) {
            $this->nonVariantStock = $product->stock_quantity === null ? '' : $product->stock_quantity;
        }

        foreach ($product->customerLevelPrices as $price) {
            $this->pricingOverrides[$price->customer_level_id] = $price->discount_percentage;
        }

        $lvl1 = $product->units->where('level', 1)->first();
        $lvl2 = $product->units->where('level', 2)->first();
        $this->units = [
            'level1_name'       => $lvl1 ? $lvl1->name : 'Piece',
            'level1_code'       => $lvl1 ? $lvl1->short_code : 'pcs',
            'level2_name'       => $lvl2 ? $lvl2->name : '',
            'level2_code'       => $lvl2 ? $lvl2->short_code : '',
            'level2_conversion' => $lvl2 ? (float)$lvl2->conversion_to_base : '',
        ];

        $this->dispatch('open-modal', 'cat-add-product');
    }

    public function selectStep(int $step): void
    {
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
            return;
        }
        for ($s = $this->currentStep; $s < $step; $s++) {
            if (!$this->validateStep($s)) return;
        }
        $this->currentStep = $step;
    }

    public function nextStep(): void
    {
        if ($this->validateStep($this->currentStep)) {
            $this->currentStep++;
        }
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function hasStockMismatch(): bool
    {
        return false; // Variations are hidden in this version
    }

    protected function validateStep(int $step): bool
    {
        if ($step === 1) {
            $this->validate([
                'basicInfo.title'                  => ['required', 'string', 'max:200'],
                'basicInfo.hsn_code'               => ['nullable', 'string', 'max:20'],
                'basicInfo.gst_percentage'         => ['required', 'numeric', 'min:0', 'max:100'],
                'basicInfo.minimum_order_quantity' => ['required', 'integer', 'min:1'],
                'basicInfo.description'            => ['required', 'string'],
                'basicInfo.product_type'           => ['required', 'string', 'in:retail,manufactured'],
            ]);
        } elseif ($step === 3) {
            $ids = $this->getEffectiveCategoryIds();
            if (empty($ids)) {
                $this->addError('selectedCategoryIds', 'At least one category is required.');
                return false;
            }
        } elseif ($step === 4) {
            $rules = [
                'units.level1_name' => ['required', 'string', 'max:50'],
                'units.level1_code' => ['required', 'string', 'max:20'],
            ];
            if (!empty($this->units['level2_name']) || !empty($this->units['level2_code'])) {
                $rules['units.level2_name']       = ['required', 'string', 'max:50'];
                $rules['units.level2_code']       = ['required', 'string', 'max:20'];
                $rules['units.level2_conversion'] = ['required', 'numeric', 'min:0.0001'];
            }
            foreach ($this->pricingOverrides as $levelId => $disc) {
                if ($disc !== '') {
                    $rules["pricingOverrides.{$levelId}"] = ['numeric', 'between:-100,100'];
                }
            }
            $this->validate($rules);
        }
        return true;
    }

    protected function validateUntilStep(int $maxStep): bool
    {
        for ($s = 1; $s <= $maxStep; $s++) {
            try {
                if (!$this->validateStep($s)) {
                    $this->currentStep = $s;
                    return false;
                }
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->currentStep = $s;
                throw $e;
            }
        }
        return true;
    }

    public function saveCurrentStep(
        ProductService $productService,
        ProductVariationService $varService,
        ProductMediaService $mediaService
    ): void {
        if (!$this->validateStep($this->currentStep)) return;
        if (!$this->validateUntilStep($this->currentStep - 1)) return;
        $this->save($productService, $varService, $mediaService);
    }

    protected function validateSimplifiedProduct(): void
    {
        $rules = [
            'basicInfo.title'       => ['required', 'string', 'max:200'],
            'basicInfo.description' => ['required', 'string'],
            'nonVariantStock'       => ['nullable', 'integer', 'min:0'],
            'units.level1_name'     => ['required', 'string', 'max:50'],
            'units.level1_code'     => ['required', 'string', 'max:20'],
            'selectedCategoryIds'   => ['required', 'array', 'min:1'],
        ];

        if (!empty($this->units['level2_name']) || !empty($this->units['level2_code'])) {
            $rules['units.level2_name'] = ['required', 'string', 'max:50'];
            $rules['units.level2_code'] = ['required', 'string', 'max:20'];
            $rules['units.level2_conversion'] = ['required', 'numeric', 'min:0.0001'];
        }

        $this->validate($rules);
    }

    public function save(
        ProductService $productService,
        ProductVariationService $varService,
        ProductMediaService $mediaService
    ): void {
        $this->validateSimplifiedProduct();

        try {
            DB::transaction(function () use ($productService, $varService, $mediaService) {
                // Get defaults from current category
                $category = Category::findOrFail($this->currentCategoryId ?: collect($this->selectedCategoryIds)->first());
                $defaults = $category->default_product_config ?? [];

                $payload = [
                    'title'                  => trim($this->basicInfo['title']),
                    'base_price'             => isset($defaults['base_price']) && $defaults['base_price'] !== '' ? (float) $defaults['base_price'] : 0.00,
                    'description'            => trim($this->basicInfo['description']),
                    'is_active'              => isset($this->basicInfo['is_active']) ? (bool) $this->basicInfo['is_active'] : true,
                    'category_ids'           => [$category->id],
                    // Merged defaults
                    'hsn_code'               => isset($defaults['hsn_code']) && $defaults['hsn_code'] !== '' ? trim($defaults['hsn_code']) : null,
                    'gst_percentage'         => isset($defaults['gst_percentage']) && $defaults['gst_percentage'] !== '' ? (float) $defaults['gst_percentage'] : null,
                    'minimum_order_quantity' => isset($defaults['minimum_order_quantity']) ? (int) $defaults['minimum_order_quantity'] : 1,
                    'product_type'           => isset($defaults['product_type']) ? $defaults['product_type'] : 'retail',
                    'stock_quantity'         => $this->nonVariantStock !== '' ? (int)$this->nonVariantStock : null,
                ];

                // Build level prices from defaults or form overrides
                $levelPrices = [];
                $hasFormOverrides = collect($this->pricingOverrides)->contains(fn($disc) => $disc !== '');
                $pricingOverrides = $hasFormOverrides ? $this->pricingOverrides : ($defaults['pricingOverrides'] ?? []);
                foreach ($pricingOverrides as $levelId => $disc) {
                    if ($disc !== '') {
                        $levelPrices[] = [
                            'customer_level_id' => $levelId,
                            'discount_percentage' => $disc,
                        ];
                    }
                }
                $payload['customer_level_prices'] = $levelPrices;
                $payload['units']                 = $this->units;

                if ($this->selectedProductId) {
                    $product = Product::findOrFail($this->selectedProductId);
                    $productService->update($product, $payload);
                } else {
                    $product                 = $productService->create($payload);
                    $this->selectedProductId = $product->id;
                }

                $product->tags()->sync($this->selectedTagIds);

                // Variations & Combinations Sync
                if (!empty($this->variationGroups)) {
                    $varService->syncVariationGroups($product, $this->variationGroups);
                    $varService->syncCombinations($product, $this->combinations);
                    
                    // Sum up variant stocks into product main stock_quantity
                    $totalStock = collect($this->combinations)->sum(fn($c) => (isset($c['stock_quantity']) && $c['stock_quantity'] !== '') ? (int)$c['stock_quantity'] : 0);
                    $product->update(['stock_quantity' => $totalStock]);
                } else {
                    $product->variationGroups()->delete();
                    $product->combinations()->delete();
                }

                if (!empty($this->mediaUploads)) {
                    $mediaService->storeProductMedia($product, $this->mediaUploads);
                    $this->mediaUploads = [];
                }
            });

            $msg = $this->isEditMode ? 'Product updated successfully.' : 'Product created successfully.';
            $this->dispatch('toast', message: $msg, type: 'success');
            $this->dispatch('close-modal', 'cat-add-product');
            $this->resetWizard();
        } catch (\Exception $e) {
            $this->addError('basicInfo.title', $e->getMessage());
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteProductId = $id;
        $this->dispatch('open-modal', 'cat-delete-product');
    }

    public function closeDeleteModal(): void
    {
        $this->deleteProductId = null;
        $this->dispatch('close-modal', 'cat-delete-product');
    }

    public function delete(ProductService $service): void
    {
        if ($this->deleteProductId) {
            $product = Product::findOrFail($this->deleteProductId);

            // Detach from this category; fully delete if it has no other categories
            if ($this->currentCategoryId) {
                $product->categories()->detach($this->currentCategoryId);
            }
            if ($product->fresh()->categories()->count() === 0) {
                $service->delete($product);
            }

            $this->dispatch('toast', message: 'Product removed from this category.', type: 'success');
            $this->dispatch('close-modal', 'cat-delete-product');
            $this->deleteProductId = null;
        }
    }

    public function toggleStatus(int $id, ProductService $service): void
    {
        $product = Product::findOrFail($id);
        $service->toggleStatus($product);
        $message = $product->fresh()->is_active ? 'Product activated.' : 'Product deactivated.';
        $this->dispatch('toast', message: $message, type: 'success');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRODUCT WIZARD — MEDIA HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    public function removeExistingMedia(int $mediaId, ProductMediaService $service): void
    {
        $media = ProductMedia::findOrFail($mediaId);
        $service->deleteMedia($media);
        $this->existingMedia = [];
        if ($this->selectedProductId) {
            $product = Product::with('media')->find($this->selectedProductId);
            if ($product) {
                foreach ($product->media as $m) {
                    $this->existingMedia[] = ['id' => $m->id, 'file_path' => $m->file_path, 'is_primary' => (bool)$m->is_primary];
                }
            }
        }
        $this->dispatch('toast', message: 'Media removed successfully.', type: 'success');
    }

    public function setPrimaryMedia(int $mediaId, ProductMediaService $service): void
    {
        $foundIndex = null;
        foreach ($this->existingMedia as $index => $m) {
            if ($m['id'] === $mediaId) { $foundIndex = $index; break; }
        }
        if ($foundIndex !== null) {
            $item = $this->existingMedia[$foundIndex];
            array_splice($this->existingMedia, $foundIndex, 1);
            array_splice($this->existingMedia, 0, 0, [$item]);
        }
        if ($this->selectedProductId) {
            $orderedIds = collect($this->existingMedia)->pluck('id')->all();
            $product    = Product::find($this->selectedProductId);
            if ($product) $service->reorderMedia($product, $orderedIds);
        }
        $this->existingMedia = [];
        if ($this->selectedProductId) {
            $product = Product::with('media')->find($this->selectedProductId);
            if ($product) {
                foreach ($product->media as $m) {
                    $this->existingMedia[] = ['id' => $m->id, 'file_path' => $m->file_path, 'is_primary' => (bool)$m->is_primary];
                }
            }
        }
        $this->dispatch('toast', message: 'Cover image updated and moved to first position.', type: 'success');
    }

    public function deleteUploadedFile(int $index): void
    {
        if (isset($this->mediaUploads[$index])) {
            array_splice($this->mediaUploads, $index, 1);
        }
    }

    public function reorderExistingMediaInArray(int $from, int $to): void
    {
        $item = $this->existingMedia[$from];
        array_splice($this->existingMedia, $from, 1);
        array_splice($this->existingMedia, $to, 0, [$item]);
        if ($this->selectedProductId) {
            $orderedIds = collect($this->existingMedia)->pluck('id')->all();
            $service    = app(ProductMediaService::class);
            $product    = Product::find($this->selectedProductId);
            if ($product) {
                $service->reorderMedia($product, $orderedIds);
                $this->existingMedia = [];
                $product->load('media');
                foreach ($product->media as $m) {
                    $this->existingMedia[] = ['id' => $m->id, 'file_path' => $m->file_path, 'is_primary' => (bool)$m->is_primary];
                }
            }
        }
    }

    public function reorderUploadedMedia(int $from, int $to): void
    {
        $item = $this->mediaUploads[$from];
        array_splice($this->mediaUploads, $from, 1);
        array_splice($this->mediaUploads, $to, 0, [$item]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRODUCT WIZARD — CATEGORY HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    public function removeCategory($id): void
    {
        // Cannot remove the locked category
        if ($this->productLockedCategoryId && (int)$id === $this->productLockedCategoryId) {
            return;
        }
        $this->selectedCategoryIds = array_values(
            array_diff($this->selectedCategoryIds, [(int)$id, (string)$id])
        );
    }

    public function toggleTag(int $tagId): void
    {
        if (in_array($tagId, $this->selectedTagIds)) {
            $this->selectedTagIds = array_diff($this->selectedTagIds, [$tagId]);
        } else {
            $this->selectedTagIds[] = $tagId;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRODUCT WIZARD — VARIATION HELPERS (kept for data integrity, hidden in UI)
    // ─────────────────────────────────────────────────────────────────────────

    public function setVariationDefault(int $gIndex, int $vIndex): void
    {
        foreach ($this->variationGroups[$gIndex]['values'] as $i => $val) {
            $this->variationGroups[$gIndex]['values'][$i]['is_default'] = ($i === $vIndex);
        }
    }

    public function addVariationGroup(): void
    {
        $this->variationGroups[] = [
            'name'         => '',
            'display_type' => 'text',
            'has_images'   => false,
            'values'       => [['value' => '', 'color_hex' => '', 'is_default' => true, 'media' => []]],
        ];
    }

    public function removeVariationGroup(int $gIndex): void
    {
        unset($this->variationGroups[$gIndex]);
        $this->variationGroups = array_values($this->variationGroups);
        $this->regenerateCombinations();
    }

    public function addVariationValue(int $gIndex): void
    {
        $isFirst = empty($this->variationGroups[$gIndex]['values']);
        $this->variationGroups[$gIndex]['values'][] = [
            'value' => '', 'color_hex' => '', 'is_default' => $isFirst, 'media' => [],
        ];
    }

    public function removeVariationValue(int $gIndex, int $vIndex): void
    {
        unset($this->variationGroups[$gIndex]['values'][$vIndex]);
        $this->variationGroups[$gIndex]['values'] = array_values($this->variationGroups[$gIndex]['values']);
        if (!empty($this->variationGroups[$gIndex]['values'])) {
            $hasDefault = collect($this->variationGroups[$gIndex]['values'])->contains('is_default', true);
            if (!$hasDefault) $this->variationGroups[$gIndex]['values'][0]['is_default'] = true;
        }
        $this->regenerateCombinations();
    }

    public function manageValueMedia(int $gIndex, int $vIndex): void
    {
        $this->managingGroupIndex = $gIndex;
        $this->managingValueIndex = $vIndex;
        $this->valueMediaUploads  = [];
        $this->dispatch('open-modal', 'cat-manage-value-media');
    }

    public function toggleValueProductMedia(string $path): void
    {
        if ($this->managingGroupIndex === null || $this->managingValueIndex === null) return;
        $currentMedia = $this->variationGroups[$this->managingGroupIndex]['values'][$this->managingValueIndex]['media'] ?? [];
        if (in_array($path, $currentMedia)) {
            $currentMedia = array_values(array_diff($currentMedia, [$path]));
        } else {
            $currentMedia[] = $path;
        }
        $this->variationGroups[$this->managingGroupIndex]['values'][$this->managingValueIndex]['media'] = $currentMedia;
    }

    public function uploadValueMedia(): void
    {
        $this->validate(['valueMediaUploads.*' => 'image|max:4096']);
        if ($this->managingGroupIndex === null || $this->managingValueIndex === null) return;
        foreach ($this->valueMediaUploads as $file) {
            $path = $file->store('products', 'public');
            $this->variationGroups[$this->managingGroupIndex]['values'][$this->managingValueIndex]['media'][] = $path;
        }
        $this->valueMediaUploads = [];
    }

    protected function regenerateCombinations(): void
    {
        $varService = app(ProductVariationService::class);
        $newCombs   = $varService->generateCombinations($this->variationGroups);
        if ($this->selectedProductId) {
            $product = Product::find($this->selectedProductId);
            if ($product) $newCombs = $varService->preserveExistingCombinationData($product, $newCombs);
        }
        foreach ($newCombs as &$c) {
            if (isset($c['price'])) $c['price'] = $c['price'] === null ? '' : (float)$c['price'];
            if (isset($c['stock_quantity'])) {
                $c['stock_quantity'] = ($c['stock_quantity'] === 0 || $c['stock_quantity'] === '') ? '' : (int)$c['stock_quantity'];
            }
        }
        $this->combinations = $newCombs;
    }

    public function applyBulkStock(): void
    {
        if ($this->bulkStock !== '') {
            foreach ($this->combinations as &$comb) $comb['stock_quantity'] = (int)$this->bulkStock;
            $this->bulkStock = '';
        }
    }

    public function applyBulkPrice(): void
    {
        if ($this->bulkPrice !== '') {
            foreach ($this->combinations as &$comb) $comb['price'] = (float)$this->bulkPrice;
            $this->bulkPrice = '';
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function resetCategoryForm(): void
    {
        $this->resetValidation();
        $this->editingCategoryId = null;
        $this->form = ['name' => '', 'description' => '', 'is_active' => true, 'is_leaf' => false];
    }

    protected function resetWizard(): void
    {
        $this->currentStep             = 1;
        $this->selectedProductId       = null;
        $this->isEditMode              = false;
        $this->productLockedCategoryId = null;
        $this->showWizardModal         = false;
        $this->isPreviewMode           = false;
        $this->basicInfo = [
            'title' => '', 'base_price' => '', 'hsn_code' => '', 'gst_percentage' => '',
            'minimum_order_quantity' => 1, 'description' => '', 'is_active' => true, 'product_type' => 'retail',
        ];
        $this->mediaUploads       = [];
        $this->existingMedia      = [];
        $this->selectedCategoryIds = [];
        $this->selectedTagIds      = [];
        $this->variationGroups    = [];
        $this->combinations       = [];
        $this->nonVariantStock    = '';
        $this->totalStock         = '';
        $this->pricingOverrides   = [];
        $this->units = [
            'level1_name' => 'Piece', 'level1_code' => 'pcs',
            'level2_name' => '', 'level2_code' => '', 'level2_conversion' => '',
        ];
        $this->bulkStock          = '';
        $this->bulkPrice          = '';
        $this->managingGroupIndex = null;
        $this->managingValueIndex = null;
        $this->valueMediaUploads  = [];
        $this->resetErrorBag();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RENDER
    // ─────────────────────────────────────────────────────────────────────────

    public function render(CategoryService $categoryService)
    {
        $currentCategory = $this->currentCategoryId ? Category::find($this->currentCategoryId) : null;
        $isLeafMode      = $currentCategory && $currentCategory->is_leaf;

        $tree          = $categoryService->getTree();
        $breadcrumbs   = $categoryService->getBreadcrumb($currentCategory);
        $openFolderIds = array_column($breadcrumbs, 'id');

        // Always loaded — wizard needs them regardless of current mode
        $categories     = Category::with('children')->ordered()->get();
        $customerLevels = CustomerLevel::active()->ordered()->get();
        $leafCategories = $categoryService->getLeafCategories(); // For move-products modal

        foreach ($customerLevels as $level) {
            if (!isset($this->pricingOverrides[$level->id])) {
                $this->pricingOverrides[$level->id] = '';
            }
        }

        $children = collect();
        $products  = null;

        if ($isLeafMode) {
            $query = $currentCategory->products()
                ->with(['primaryMedia', 'combinations'])
                ->select('products.*');

            if ($this->productSearch) {
                $query->where(function ($q) {
                    $q->where('products.title', 'like', "%{$this->productSearch}%")
                      ->orWhere('products.sku', 'like', "%{$this->productSearch}%");
                });
            }
            if ($this->productFilterStatus === 'active') {
                $query->where('products.is_active', true);
            } elseif ($this->productFilterStatus === 'inactive') {
                $query->where('products.is_active', false);
            }
            if ($this->productFilterStock === 'instock') {
                $query->where('products.stock_quantity', '>', 0);
            } elseif ($this->productFilterStock === 'outofstock') {
                $query->where(function ($q) {
                    $q->whereNull('products.stock_quantity')->orWhere('products.stock_quantity', 0);
                });
            }

            $products = $query->paginate(15, ['products.*'], 'productsPage');
        } else {
            $children = $categoryService->getChildren($this->currentCategoryId, ['search' => $this->search]);
        }

        $availableTags = \App\Models\Tag::orderBy('name')->get();

        return view('livewire.admin.categories.category-index-page', compact(
            'currentCategory', 'isLeafMode', 'tree', 'breadcrumbs', 'openFolderIds',
            'children', 'products', 'customerLevels', 'categories', 'leafCategories', 'availableTags'
        ));
    }
}
