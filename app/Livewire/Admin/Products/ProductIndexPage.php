<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use App\Models\Category;
use App\Models\CustomerLevel;
use App\Models\ProductMedia;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\ProductVariationService;
use App\Services\Catalog\ProductMediaService;
use App\Services\Catalog\CategoryService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\DB;

#[Layout('components.admin.layout')]
class ProductIndexPage extends Component
{
    use WithPagination, WithFileUploads;

    // Filters and Search
    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterCategory = '';

    #[Url(history: true)]
    public string $filterStatus = '';

    #[Url(history: true)]
    public string $filterStock = '';

    // Wizard State
    public bool $showWizardModal = false;
    public bool $showDeleteModal = false;
    public ?int $selectedProductId = null;
    public ?int $deleteProductId = null;
    public int $currentStep = 1;
    public bool $isEditMode = false;

    // Step 1: Basic Info
    public array $basicInfo = [
        'title'                  => '',
        'sku'                    => '',
        'base_price'             => '',
        'hsn_code'               => '',
        'gst_percentage'         => '',
        'minimum_order_quantity' => 1,
        'description'            => '',
        'is_active'              => true,
        'product_type'           => 'retail',
    ];

    // Step 2: Media
    public $mediaUploads = [];
    public array $existingMedia = [];

    // Step 3: Categories
    public array $selectedCategoryIds = [];

    // Step 4: Variations
    public array $variationGroups = [];
    public ?int $managingGroupIndex = null;
    public ?int $managingValueIndex = null;
    public $valueMediaUploads = [];

    // Step 5: Combinations / Stock
    public $nonVariantStock = ''; // '' = N/A (unlimited), integer = tracked stock
    public string $totalStock = ''; // For variant products: declared total stock across all variants ('' = N/A)
    public array $combinations = [];
    public string $bulkStock = '';
    public string $bulkPrice = '';

    // Step 6: Pricing & Units
    public array $pricingOverrides = []; // [customer_level_id => discount_percentage]
    public array $units = [
        'level1_name' => 'Piece',
        'level1_code' => 'pcs',
        'level2_name' => '',
        'level2_code' => '',
        'level2_conversion' => '',
    ];

    // Markdown preview state
    public bool $isPreviewMode = false;

    // Tags state
    public array $selectedTagIds = [];

    // Category Defaults and navigation state
    public ?int $currentCategoryId = null;
    public ?int $productLockedCategoryId = null;
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

    protected $queryString = [
        'search' => ['except' => ''],
        'filterCategory' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterStock' => ['except' => ''],
    ];

    public function updated($propertyName)
    {
        // Regenerate combinations when variations values change in Step 4
        if (str_starts_with($propertyName, 'variationGroups')) {
            $this->regenerateCombinations();
        }
    }

    public function updatedSelectedCategoryIds()
    {
        $this->selectedCategoryIds = collect($this->selectedCategoryIds)
            ->map(fn($id) => (int)$id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function updatedMediaUploads()
    {
        $this->validate([
            'mediaUploads.*' => 'image|max:4096',
        ]);

        $productService = app(ProductService::class);
        $mediaService = app(ProductMediaService::class);

        try {
            DB::transaction(function () use ($productService, $mediaService) {
                if (!$this->selectedProductId) {
                    // Automatically run step 1 validation
                    $this->validateStep(1);

                    $catId = $this->currentCategoryId ?: collect($this->selectedCategoryIds)->first();
                    $category = $catId ? Category::find($catId) : null;
                    $defaults = $category ? ($category->default_product_config ?? []) : [];

                    $payload = $this->basicInfo;
                    $payload['base_price'] = isset($defaults['base_price']) && $defaults['base_price'] !== '' ? (float)$defaults['base_price'] : 0.00;
                    $payload['category_ids'] = $this->selectedCategoryIds;
                    $payload['customer_level_prices'] = [];
                    $payload['units'] = $this->units;
                    $payload['stock_quantity'] = null; // Stock is optional; configured in Step 5

                    $product = $productService->create($payload);
                    $this->selectedProductId = $product->id;
                } else {
                    $product = Product::findOrFail($this->selectedProductId);
                }

                if (!empty($this->mediaUploads)) {
                    $mediaService->storeProductMedia($product, $this->mediaUploads);
                    $this->mediaUploads = [];
                }

                // Refresh existing media list
                $this->existingMedia = [];
                $product->load('media');
                foreach ($product->media as $m) {
                    $this->existingMedia[] = [
                        'id' => $m->id,
                        'file_path' => $m->file_path,
                        'is_primary' => (bool)$m->is_primary,
                    ];
                }
            });

            $this->dispatch('toast', message: 'Images uploaded and saved automatically.', type: 'success');
        } catch (\Exception $e) {
            $this->addError('mediaUploads', $e->getMessage());
        }
    }

    public function selectStep(int $step)
    {
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
            return;
        }

        // Validate steps sequentially
        for ($s = $this->currentStep; $s < $step; $s++) {
            if (!$this->validateStep($s)) {
                return;
            }
        }

        $this->currentStep = $step;

        // Auto generate/preserve combinations if moving to Step 5
        if ($this->currentStep === 5) {
            $this->regenerateCombinations();
        }
    }

    public function nextStep()
    {
        if ($this->validateStep($this->currentStep)) {
            $this->currentStep++;
            if ($this->currentStep === 5) {
                $this->regenerateCombinations();
            }
        }
    }

    public function prevStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function hasStockMismatch(): bool
    {
        if (empty($this->variationGroups)) {
            return false;
        }

        if ($this->totalStock === '') {
            return false;
        }

        $definedTotal = (int) $this->totalStock;
        $combinationSum = collect($this->combinations)->sum(fn($c) =>
            isset($c['stock_quantity']) && $c['stock_quantity'] !== '' ? (int)$c['stock_quantity'] : 0
        );
        $anySet = collect($this->combinations)->contains(fn($c) =>
            isset($c['stock_quantity']) && $c['stock_quantity'] !== ''
        );

        return $anySet && $combinationSum !== $definedTotal;
    }

    protected function validateStep(int $step): bool
    {
         if ($step === 1) {
            $rules = [
                'basicInfo.title'          => ['required', 'string', 'max:200'],
                'basicInfo.hsn_code'       => ['nullable', 'string', 'max:20'],
                'basicInfo.gst_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
                'basicInfo.minimum_order_quantity' => ['required', 'integer', 'min:1'],
                'basicInfo.description'    => ['required', 'string'],
                'basicInfo.product_type'   => ['required', 'string', 'in:retail,manufactured'],
            ];

            $this->validate($rules);
        } elseif ($step === 3) {
            if (empty($this->selectedCategoryIds)) {
                $this->addError('selectedCategoryIds', 'At least one category is required.');
                return false;
            }
        } elseif ($step === 4) {
            foreach ($this->variationGroups as $gIndex => $group) {
                if (empty($group['name'])) {
                    $this->addError("variationGroups.{$gIndex}.name", 'Variation group name is required.');
                    return false;
                }
                if (empty($group['values'])) {
                    $this->addError("variationGroups.{$gIndex}.values", 'At least one value is required.');
                    return false;
                }
                foreach ($group['values'] as $vIndex => $val) {
                    if (empty($val['value'])) {
                        $this->addError("variationGroups.{$gIndex}.values.{$vIndex}.value", 'Value name is required.');
                        return false;
                    }
                }
            }
        } elseif ($step === 5) {
            // Both product types support optional stock. Empty = N/A (unlimited).
            if (empty($this->variationGroups)) {
                // No variants: optional total stock field
                $this->validate([
                    'nonVariantStock' => ['nullable', 'integer', 'min:0'],
                ]);
            } else {
                // Has variants: optional totalStock + optional per-combination stocks
                $this->validate([
                    'totalStock'                      => ['nullable', 'integer', 'min:0'],
                    'combinations.*.stock_quantity'   => ['nullable', 'integer', 'min:0'],
                    'combinations.*.price'            => ['nullable', 'numeric', 'min:0'],
                ]);

                // If totalStock is defined, combination stocks (when set) must sum to it
                if ($this->hasStockMismatch()) {
                    $combinationSum = collect($this->combinations)->sum(fn($c) =>
                        isset($c['stock_quantity']) && $c['stock_quantity'] !== '' ? (int)$c['stock_quantity'] : 0
                    );
                    $this->addError('totalStock', "Combination stocks sum to {$combinationSum} but total stock is {$this->totalStock}. They must match.");
                    return false;
                }
            }
        } elseif ($step === 6) {
            $rules = [
                'units.level1_name' => ['required', 'string', 'max:50'],
                'units.level1_code' => ['required', 'string', 'max:20'],
            ];

            if (!empty($this->units['level2_name']) || !empty($this->units['level2_code'])) {
                $rules['units.level2_name'] = ['required', 'string', 'max:50'];
                $rules['units.level2_code'] = ['required', 'string', 'max:20'];
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

    public function openSelectLeafForDefaults(): void
    {
        $this->resetValidation();
        $this->currentCategoryId = null;
        $this->dispatch('open-modal', 'select-leaf-for-defaults');
    }

    public function selectLeafForDefaults(int $categoryId): void
    {
        $this->currentCategoryId = $categoryId;
        $this->dispatch('close-modal', 'select-leaf-for-defaults');
        $this->openCategoryDefaults();
    }

    public function openCategoryDefaults(): void
    {
        $this->resetValidation();
        if (!$this->currentCategoryId) return;

        $category = Category::findOrFail($this->currentCategoryId);
        $defaults = $category->default_product_config;

        if (!empty($defaults)) {
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

    public function openSelectLeafForAddProduct(): void
    {
        $this->resetValidation();
        $this->dispatch('open-modal', 'select-leaf-for-add-product');
    }

    public function selectLeafForAddProduct(int $categoryId): void
    {
        $this->currentCategoryId = $categoryId;
        $this->productLockedCategoryId = $categoryId;
        $this->dispatch('close-modal', 'select-leaf-for-add-product');
        $this->create();
    }

    public function create(): void
    {
        $this->resetWizard();
        $this->isEditMode = false;
        $this->selectedCategoryIds = $this->currentCategoryId ? [$this->currentCategoryId] : [];
        $this->productLockedCategoryId = $this->currentCategoryId;

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

        $this->dispatch('open-modal', 'add-product');
    }

    public function edit(int $id): void
    {
        $this->resetWizard();
        $this->isEditMode = true;

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
        $this->currentCategoryId = $product->categories->first()?->id;
        $this->productLockedCategoryId = $this->currentCategoryId;

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

        $this->dispatch('open-modal', 'add-product');
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
            $this->dispatch('close-modal', 'add-product');
            $this->resetWizard();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->addError('basicInfo.title', $e->getMessage());
        }
    }



    public function confirmDelete(int $id)
    {
        $this->deleteProductId = $id;
        $this->dispatch('open-modal', 'delete-product');
    }

    // Close delete modal and reset state
    public function closeDeleteModal()
    {
        $this->deleteProductId = null;
        $this->dispatch('close-modal', 'delete-product');
    }

    public function delete(ProductService $service)
    {
        if ($this->deleteProductId) {
            $product = Product::findOrFail($this->deleteProductId);
            $service->delete($product);
            $this->dispatch('toast', message: 'Product deleted successfully.', type: 'success');
            $this->dispatch('close-modal', 'delete-product');
            $this->deleteProductId = null;
        }
    }

    public function toggleStatus(int $id, ProductService $service)
    {
        $product = Product::findOrFail($id);
        $service->toggleStatus($product);
        $message = $product->is_active ? 'Product activated successfully.' : 'Product deactivated successfully.';
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function removeCategory($id)
    {
        $this->selectedCategoryIds = array_values(array_diff($this->selectedCategoryIds, [(int)$id, (string)$id]));
    }

    public function toggleTag(int $tagId): void
    {
        if (in_array($tagId, $this->selectedTagIds)) {
            $this->selectedTagIds = array_diff($this->selectedTagIds, [$tagId]);
        } else {
            $this->selectedTagIds[] = $tagId;
        }
    }

    public function setVariationDefault(int $gIndex, int $vIndex)
    {
        foreach ($this->variationGroups[$gIndex]['values'] as $i => $val) {
            $this->variationGroups[$gIndex]['values'][$i]['is_default'] = ($i === $vIndex);
        }
    }

    // Variations Manager Helpers
    public function addVariationGroup()
    {
        $this->variationGroups[] = [
            'name' => '',
            'display_type' => 'text',
            'has_images' => false,
            'values' => [
                ['value' => '', 'color_hex' => '', 'is_default' => true, 'media' => []]
            ]
        ];
    }

    public function removeVariationGroup(int $gIndex)
    {
        unset($this->variationGroups[$gIndex]);
        $this->variationGroups = array_values($this->variationGroups);
        $this->regenerateCombinations();
    }

    public function addVariationValue(int $gIndex)
    {
        $isFirst = empty($this->variationGroups[$gIndex]['values']);
        $this->variationGroups[$gIndex]['values'][] = [
            'value' => '',
            'color_hex' => '',
            'is_default' => $isFirst,
            'media' => []
        ];
    }

    public function removeVariationValue(int $gIndex, int $vIndex)
    {
        unset($this->variationGroups[$gIndex]['values'][$vIndex]);
        $this->variationGroups[$gIndex]['values'] = array_values($this->variationGroups[$gIndex]['values']);
        
        if (!empty($this->variationGroups[$gIndex]['values'])) {
            $hasDefault = collect($this->variationGroups[$gIndex]['values'])->contains('is_default', true);
            if (!$hasDefault) {
                $this->variationGroups[$gIndex]['values'][0]['is_default'] = true;
            }
        }
        $this->regenerateCombinations();
    }

    public function manageValueMedia(int $gIndex, int $vIndex)
    {
        $this->managingGroupIndex = $gIndex;
        $this->managingValueIndex = $vIndex;
        $this->valueMediaUploads = [];
        $this->dispatch('open-modal', 'manage-value-media');
    }

    public function toggleValueProductMedia(string $path)
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

    public function uploadValueMedia()
    {
        $this->validate([
            'valueMediaUploads.*' => 'image|max:4096',
        ]);

        if ($this->managingGroupIndex === null || $this->managingValueIndex === null) return;

        foreach ($this->valueMediaUploads as $file) {
            $path = $file->store('products', 'public');
            $this->variationGroups[$this->managingGroupIndex]['values'][$this->managingValueIndex]['media'][] = $path;
        }

        $this->valueMediaUploads = [];
    }

    protected function regenerateCombinations()
    {
        $varService = app(ProductVariationService::class);
        $newCombs = $varService->generateCombinations($this->variationGroups);

        if ($this->selectedProductId) {
            $product = Product::find($this->selectedProductId);
            if ($product) {
                $newCombs = $varService->preserveExistingCombinationData($product, $newCombs);
            }
        }

        // Map price and stock formatting nicely for binding
        foreach ($newCombs as &$c) {
            if (isset($c['price'])) {
                $c['price'] = $c['price'] === null ? '' : (float)$c['price'];
            }
            if (isset($c['stock_quantity'])) {
                $c['stock_quantity'] = ($c['stock_quantity'] === 0 || $c['stock_quantity'] === '') ? '' : (int)$c['stock_quantity'];
            }
        }

        $this->combinations = $newCombs;
    }

    public function applyBulkStock()
    {
        if ($this->bulkStock !== '') {
            foreach ($this->combinations as &$comb) {
                $comb['stock_quantity'] = (int)$this->bulkStock;
            }
            $this->bulkStock = '';
        }
    }

    public function applyBulkPrice()
    {
        if ($this->bulkPrice !== '') {
            foreach ($this->combinations as &$comb) {
                $comb['price'] = (float)$this->bulkPrice;
            }
            $this->bulkPrice = '';
        }
    }

    // Media Manager Helpers
    public function removeExistingMedia(int $mediaId, ProductMediaService $service)
    {
        $media = ProductMedia::findOrFail($mediaId);
        $service->deleteMedia($media);

        // Reload existingMedia to ensure matches database re-indexing and new cover
        $this->existingMedia = [];
        if ($this->selectedProductId) {
            $product = Product::with('media')->find($this->selectedProductId);
            if ($product) {
                foreach ($product->media as $m) {
                    $this->existingMedia[] = [
                        'id' => $m->id,
                        'file_path' => $m->file_path,
                        'is_primary' => (bool)$m->is_primary,
                    ];
                }
            }
        }

        $this->dispatch('toast', message: 'Media removed successfully.', type: 'success');
    }

    public function setPrimaryMedia(int $mediaId, ProductMediaService $service)
    {
        $media = ProductMedia::findOrFail($mediaId);

        // Find it in existingMedia and move it to index 0
        $foundIndex = null;
        foreach ($this->existingMedia as $index => $m) {
            if ($m['id'] === $mediaId) {
                $foundIndex = $index;
                break;
            }
        }

        if ($foundIndex !== null) {
            $item = $this->existingMedia[$foundIndex];
            array_splice($this->existingMedia, $foundIndex, 1);
            array_splice($this->existingMedia, 0, 0, [$item]);
        }

        // Save order and let ProductMediaService handle setPrimary during reorderMedia
        if ($this->selectedProductId) {
            $orderedIds = collect($this->existingMedia)->pluck('id')->all();
            $product = Product::find($this->selectedProductId);
            if ($product) {
                $service->reorderMedia($product, $orderedIds);
            }
        }

        // Reload existingMedia to ensure local array matches DB order and primary flags
        $this->existingMedia = [];
        if ($this->selectedProductId) {
            $product = Product::with('media')->find($this->selectedProductId);
            if ($product) {
                foreach ($product->media as $m) {
                    $this->existingMedia[] = [
                        'id' => $m->id,
                        'file_path' => $m->file_path,
                        'is_primary' => (bool)$m->is_primary,
                    ];
                }
            }
        }

        $this->dispatch('toast', message: 'Cover image updated and moved to first position.', type: 'success');
    }

    public function deleteUploadedFile(int $index)
    {
        // Use array_splice to remove the item and automatically reindex
        if (isset($this->mediaUploads[$index])) {
            array_splice($this->mediaUploads, $index, 1);
        }
    }

    public function reorderExistingMediaInArray(int $from, int $to)
    {
        $item = $this->existingMedia[$from];
        array_splice($this->existingMedia, $from, 1);
        array_splice($this->existingMedia, $to, 0, [$item]);

        if ($this->selectedProductId) {
            $orderedIds = collect($this->existingMedia)->pluck('id')->all();
            $service = app(ProductMediaService::class);
            $product = Product::find($this->selectedProductId);
            if ($product) {
                $service->reorderMedia($product, $orderedIds);

                // Reload local array to capture correct primary flags
                $this->existingMedia = [];
                $product->load('media');
                foreach ($product->media as $m) {
                    $this->existingMedia[] = [
                        'id' => $m->id,
                        'file_path' => $m->file_path,
                        'is_primary' => (bool)$m->is_primary,
                    ];
                }
            }
        }
    }

    public function reorderUploadedMedia(int $from, int $to)
    {
        $item = $this->mediaUploads[$from];
        array_splice($this->mediaUploads, $from, 1);
        array_splice($this->mediaUploads, $to, 0, [$item]);
    }

    protected function resetWizard()
    {
        $this->currentStep = 1;
        $this->selectedProductId = null;
        $this->isEditMode = false;
        $this->basicInfo = [
            'title'          => '',
            'base_price'     => '',
            'hsn_code'       => '',
            'gst_percentage' => '',
            'minimum_order_quantity' => 1,
            'description'    => '',
            'is_active'      => true,
            'product_type'   => 'retail',
        ];
        $this->mediaUploads = [];
        $this->existingMedia = [];
        $this->selectedCategoryIds = [];
        $this->selectedTagIds = [];
        $this->variationGroups = [];
        $this->combinations = [];
        $this->nonVariantStock = '';
        $this->totalStock = '';
        $this->pricingOverrides = [];
        $this->units = [
            'level1_name' => 'Piece',
            'level1_code' => 'pcs',
            'level2_name' => '',
            'level2_code' => '',
            'level2_conversion' => '',
        ];
        $this->bulkStock = '';
        $this->bulkPrice = '';
        $this->managingGroupIndex = null;
        $this->managingValueIndex = null;
        $this->valueMediaUploads = [];
        $this->resetErrorBag();
    }

    public function render(ProductService $productService)
    {
        $products = $productService->list([
            'search' => $this->search,
            'category_id' => $this->filterCategory,
            'status' => $this->filterStatus,
            'stock_status' => $this->filterStock,
        ]);

        $categories = Category::ordered()->get();
        $customerLevels = CustomerLevel::active()->ordered()->get();

        // Load pricing structures inside the pricing matrix step
        foreach ($customerLevels as $level) {
            if (!isset($this->pricingOverrides[$level->id])) {
                $this->pricingOverrides[$level->id] = '';
            }
        }

        $categoryService = app(CategoryService::class);
        $leafCategories = $categoryService->getLeafCategories();
        $availableTags = \App\Models\Tag::orderBy('name')->get();

        return view('livewire.admin.products.product-index-page', [
            'products' => $products,
            'categories' => $categories,
            'customerLevels' => $customerLevels,
            'leafCategories' => $leafCategories,
            'availableTags' => $availableTags,
        ]);
    }
}
