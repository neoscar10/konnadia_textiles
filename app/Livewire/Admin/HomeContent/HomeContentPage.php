<?php

namespace App\Livewire\Admin\HomeContent;

use App\Models\HomeContentSection;
use App\Models\HomeContentItem;
use App\Models\Product;
use App\Models\Category;
use App\Services\Home\AdminHomeContentService;
use App\Services\Home\HomeContentRenderService;
use App\Services\Home\HomeContentMediaService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

#[Layout('components.admin.layout')]
class HomeContentPage extends Component
{
    use WithFileUploads, WithPagination;

    // Component states
    public $activeTab = 'all';
    public $searchSections = '';
    
    // Wizard Modal state
    public $showModal = false;
    public $wizardStep = 1;
    public $editingSectionId = null;

    // Wizard Form fields
    public $sectionType = 'banner';
    public $sectionTitle = '';
    public $sectionSubtitle = '';
    public $sectionIsActive = true;
    public $sectionDisplayStyle = '';
    public $sectionItemsPerView = 4;
    public $sectionDisplayLimit = 10;
    public $sectionStartsAt = '';
    public $sectionEndsAt = '';
    public $visibilityMode = 'live';
    public $sectionSettings = [];

    // Banner Slide item fields
    public $bannerImage = null;
    public $bannerImageAlt = '';
    public $bannerTitle = '';
    public $bannerSubtitle = '';
    public $bannerCtaLabel = 'Shop Now';
    public $bannerLinkType = 'none';
    public $bannerLinkCategoryId = null;
    public $bannerLinkProductId = null;
    public $bannerExternalUrl = '';
    public $bannerExistingImage = null;

    // Category Slider selected IDs
    public $selectedCategoryIds = [];

    // Product Slider Picker fields
    public $productSearch = '';
    public $productCategoryFilter = '';
    public $productStockFilter = '';
    public $selectedProductIds = [];

    // Image Slider items (Dynamic array of slides)
    public $slides = [];

    // Delete section confirmation modal
    public $confirmingDeletionId = null;

    protected $listeners = [
        'refreshOrder' => 'updateOrder',
    ];

    /**
     * Set wizard to first step and open creation modal.
     */
    public function createSection()
    {
        $this->resetWizard();
        $this->editingSectionId = null;
        $this->wizardStep = 1;
        $this->showModal = true;
        $this->dispatch('open-modal', 'home-content-wizard');
    }

    /**
     * Open editing modal and pre-fill data.
     */
    public function editSection($id)
    {
        $this->resetWizard();
        $this->editingSectionId = $id;
        
        $section = HomeContentSection::with('items')->findOrFail($id);
        
        $this->sectionType = $section->type;
        $this->sectionTitle = $section->title ?? '';
        $this->sectionSubtitle = $section->subtitle ?? '';
        $this->sectionIsActive = (bool) $section->is_active;
        $this->sectionDisplayStyle = $section->display_style ?? '';
        $this->sectionItemsPerView = $section->items_per_view ?? 4;
        $this->sectionDisplayLimit = $section->display_limit ?? 10;
        $this->sectionStartsAt = $section->starts_at ? $section->starts_at->format('Y-m-d\TH:i') : '';
        $this->sectionEndsAt = $section->ends_at ? $section->ends_at->format('Y-m-d\TH:i') : '';
        $this->visibilityMode = ($section->starts_at || $section->ends_at) ? 'schedule' : 'live';
        $this->sectionSettings = $section->settings ?? [];

        // Pre-fill type specific items
        if ($section->type === 'banner') {
            $item = $section->items->first();
            if ($item) {
                $this->bannerTitle = $item->title ?? '';
                $this->bannerSubtitle = $item->subtitle ?? '';
                $this->bannerCtaLabel = $item->cta_label ?? 'Shop Now';
                $this->bannerLinkType = $item->link_type ?? 'none';
                $this->bannerLinkCategoryId = $item->link_category_id;
                $this->bannerLinkProductId = $item->link_product_id;
                $this->bannerExternalUrl = $item->external_url ?? '';
                $this->bannerExistingImage = $item->image_path;
            }
        } elseif ($section->type === 'category_slider') {
            $this->selectedCategoryIds = $section->items->pluck('category_id')->filter()->toArray();
        } elseif ($section->type === 'product_slider') {
            $this->selectedProductIds = $section->items->pluck('product_id')->filter()->toArray();
        } elseif ($section->type === 'image_slider') {
            $this->slides = [];
            foreach ($section->items as $item) {
                $this->slides[] = [
                    'id' => $item->id,
                    'title' => $item->title ?? '',
                    'subtitle' => $item->subtitle ?? '',
                    'cta_label' => $item->cta_label ?? 'Shop Now',
                    'image_alt' => $item->image_alt ?? '',
                    'link_type' => $item->link_type ?? 'none',
                    'link_category_id' => $item->link_category_id,
                    'link_product_id' => $item->link_product_id,
                    'external_url' => $item->external_url ?? '',
                    'existing_image' => $item->image_path,
                    'upload' => null, // Temp file upload placeholder
                ];
            }
        }

        $this->wizardStep = 2; // Jump directly to configure step
        $this->showModal = true;
        $this->dispatch('open-modal', 'home-content-wizard');
    }

    /**
     * Add slide to image slider dynamically.
     */
    public function addSlide()
    {
        $this->slides[] = [
            'id' => null,
            'title' => '',
            'subtitle' => '',
            'cta_label' => 'Shop Now',
            'image_alt' => '',
            'link_type' => 'none',
            'link_category_id' => null,
            'link_product_id' => null,
            'external_url' => '',
            'existing_image' => null,
            'upload' => null,
        ];
    }

    /**
     * Remove slide from image slider.
     */
    public function removeSlide($index)
    {
        unset($this->slides[$index]);
        $this->slides = array_values($this->slides);
    }

    /**
     * Move slide position in image slider.
     */
    public function moveSlide($index, $direction)
    {
        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if ($target >= 0 && $target < count($this->slides)) {
            $temp = $this->slides[$index];
            $this->slides[$index] = $this->slides[$target];
            $this->slides[$target] = $temp;
        }
    }

    /**
     * Toggle item selection in category picker.
     */
    public function toggleCategorySelection($id)
    {
        if (in_array($id, $this->selectedCategoryIds)) {
            $this->selectedCategoryIds = array_diff($this->selectedCategoryIds, [$id]);
        } else {
            $this->selectedCategoryIds[] = $id;
        }
    }

    /**
     * Toggle product selection in product picker.
     */
    public function toggleProductSelection($id)
    {
        if (in_array($id, $this->selectedProductIds)) {
            $this->selectedProductIds = array_diff($this->selectedProductIds, [$id]);
        } else {
            $this->selectedProductIds[] = $id;
        }
    }

    /**
     * Move section sorting order.
     */
    public function updateOrder($orderedIds, AdminHomeContentService $adminService)
    {
        $adminService->reorderSections($orderedIds);
        $this->dispatch('toast', type: 'success', message: 'Home content order updated successfully.');
    }

    /**
     * Toggle status.
     */
    public function toggleStatus($id, AdminHomeContentService $adminService)
    {
        $section = HomeContentSection::findOrFail($id);
        $adminService->toggleSectionStatus($section);
        
        $message = $section->is_active ? 'Home content section deactivated successfully.' : 'Home content section activated successfully.';
        $this->dispatch('toast', type: 'success', message: $message);
    }

    /**
     * Ask for delete confirmation.
     */
    public function confirmDelete($id)
    {
        $this->confirmingDeletionId = $id;
        $this->dispatch('open-modal', 'delete-section-modal');
    }

    /**
     * Execute deletion.
     */
    public function deleteSection(AdminHomeContentService $adminService)
    {
        if ($this->confirmingDeletionId) {
            $section = HomeContentSection::findOrFail($this->confirmingDeletionId);
            $adminService->deleteSection($section);
            
            $this->confirmingDeletionId = null;
            $this->dispatch('close-modal', 'delete-section-modal');
            $this->dispatch('toast', type: 'success', message: 'Home content section deleted successfully.');
        }
    }

    /**
     * Step Validation and Navigation.
     */
    public function nextStep()
    {
        $this->validateStep();
        $this->wizardStep++;
    }

    public function prevStep()
    {
        $this->wizardStep--;
    }

    protected function validateStep()
    {
        if ($this->wizardStep === 2) {
            $this->validate([
                'sectionTitle' => ['nullable', 'string', 'max:150'],
                'sectionSubtitle' => ['nullable', 'string', 'max:250'],
                'sectionStartsAt' => ['nullable', 'date'],
                'sectionEndsAt' => ['nullable', 'date', 'after_or_equal:sectionStartsAt'],
            ]);
        } elseif ($this->wizardStep === 3) {
            if ($this->sectionType === 'banner') {
                $rules = [
                    'bannerTitle' => ['nullable', 'string', 'max:150'],
                    'bannerSubtitle' => ['nullable', 'string', 'max:250'],
                    'bannerCtaLabel' => ['nullable', 'string', 'max:50'],
                    'bannerLinkType' => ['required', 'in:none,category,product,url'],
                    'bannerLinkCategoryId' => ['required_if:bannerLinkType,category', 'nullable', 'exists:categories,id'],
                    'bannerLinkProductId' => ['required_if:bannerLinkType,product', 'nullable', 'exists:products,id'],
                    'bannerExternalUrl' => ['required_if:bannerLinkType,url', 'nullable', 'url', 'max:500'],
                ];
                if (!$this->bannerExistingImage) {
                    $rules['bannerImage'] = ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
                } else {
                    $rules['bannerImage'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
                }
                $this->validate($rules);
            } elseif ($this->sectionType === 'category_slider') {
                if (empty($this->selectedCategoryIds)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'selectedCategoryIds' => 'Please select at least one category.',
                    ]);
                }
            } elseif ($this->sectionType === 'product_slider') {
                if (empty($this->selectedProductIds)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'selectedProductIds' => 'Please select at least one product.',
                    ]);
                }
            } elseif ($this->sectionType === 'image_slider') {
                if (empty($this->slides)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'slides' => 'Please add at least one slide.',
                    ]);
                }
                foreach ($this->slides as $index => $slide) {
                    $rules = [
                        "slides.{$index}.title" => ['nullable', 'string', 'max:150'],
                        "slides.{$index}.subtitle" => ['nullable', 'string', 'max:250'],
                        "slides.{$index}.cta_label" => ['nullable', 'string', 'max:50'],
                        "slides.{$index}.link_type" => ['required', 'in:none,category,product,url'],
                        "slides.{$index}.link_category_id" => ["required_if:slides.{$index}.link_type,category", 'nullable', 'exists:categories,id'],
                        "slides.{$index}.link_product_id" => ["required_if:slides.{$index}.link_type,product", 'nullable', 'exists:products,id'],
                        "slides.{$index}.external_url" => ["required_if:slides.{$index}.link_type,url", 'nullable', 'url', 'max:500'],
                    ];
                    if (empty($slide['existing_image'])) {
                        $rules["slides.{$index}.upload"] = ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
                    } else {
                        $rules["slides.{$index}.upload"] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
                    }
                    $this->validate($rules, [
                        "slides.{$index}.upload.required" => "An image file is required for slide #".($index + 1),
                    ]);
                }
            }
        }
    }

    /**
     * Save/Create Section.
     */
    public function saveSection(AdminHomeContentService $adminService, HomeContentMediaService $mediaService)
    {
        $this->validateStep();

        $sectionData = [
            'type' => $this->sectionType,
            'title' => $this->sectionTitle,
            'subtitle' => $this->sectionSubtitle,
            'is_active' => $this->sectionIsActive,
            'display_style' => $this->sectionDisplayStyle ?: null,
            'items_per_view' => $this->sectionItemsPerView,
            'display_limit' => $this->sectionDisplayLimit,
            'starts_at' => $this->visibilityMode === 'schedule' ? ($this->sectionStartsAt ?: null) : null,
            'ends_at' => $this->visibilityMode === 'schedule' ? ($this->sectionEndsAt ?: null) : null,
            'settings' => $this->sectionSettings,
            'items' => [],
        ];

        // Format items payloads
        if ($this->sectionType === 'banner') {
            $imagePath = $this->bannerExistingImage;
            if ($this->bannerImage) {
                $imagePath = $mediaService->storeImage($this->bannerImage, 'banners');
            }
            $sectionData['items'][] = [
                'item_type' => 'banner',
                'title' => $this->bannerTitle ?: null,
                'subtitle' => $this->bannerSubtitle ?: null,
                'cta_label' => $this->bannerCtaLabel ?: null,
                'image_path' => $imagePath,
                'image_alt' => $this->bannerImageAlt ?: null,
                'link_type' => $this->bannerLinkType,
                'link_category_id' => $this->bannerLinkCategoryId,
                'link_product_id' => $this->bannerLinkProductId,
                'external_url' => $this->bannerExternalUrl ?: null,
            ];
        } elseif ($this->sectionType === 'category_slider') {
            foreach ($this->selectedCategoryIds as $catId) {
                $sectionData['items'][] = [
                    'item_type' => 'category',
                    'category_id' => $catId,
                    'link_type' => 'category',
                ];
            }
        } elseif ($this->sectionType === 'product_slider') {
            foreach ($this->selectedProductIds as $prodId) {
                $sectionData['items'][] = [
                    'item_type' => 'product',
                    'product_id' => $prodId,
                    'link_type' => 'product',
                ];
            }
        } elseif ($this->sectionType === 'image_slider') {
            foreach ($this->slides as $index => $slide) {
                $imagePath = $slide['existing_image'];
                if ($slide['upload']) {
                    $imagePath = $mediaService->storeImage($slide['upload'], 'slides');
                }
                $sectionData['items'][] = [
                    'item_type' => 'image',
                    'title' => $slide['title'] ?: null,
                    'subtitle' => $slide['subtitle'] ?: null,
                    'cta_label' => $slide['cta_label'] ?: null,
                    'image_path' => $imagePath,
                    'image_alt' => $slide['image_alt'] ?: null,
                    'link_type' => $slide['link_type'],
                    'link_category_id' => $slide['link_category_id'],
                    'link_product_id' => $slide['link_product_id'],
                    'external_url' => $slide['external_url'] ?: null,
                ];
            }
        }

        if ($this->editingSectionId) {
            $section = HomeContentSection::findOrFail($this->editingSectionId);
            $adminService->updateSection($section, $sectionData);
            $this->dispatch('toast', type: 'success', message: 'Home content section updated successfully.');
        } else {
            $adminService->createSection($sectionData);
            $this->dispatch('toast', type: 'success', message: 'Home content section created successfully.');
        }

        $this->showModal = false;
        $this->dispatch('close-modal', 'home-content-wizard');
        $this->resetWizard();
    }

    protected function resetWizard()
    {
        $this->reset([
            'wizardStep',
            'sectionType',
            'sectionTitle',
            'sectionSubtitle',
            'sectionIsActive',
            'sectionDisplayStyle',
            'sectionItemsPerView',
            'sectionDisplayLimit',
            'sectionStartsAt',
            'sectionEndsAt',
            'visibilityMode',
            'sectionSettings',
            
            'bannerImage',
            'bannerImageAlt',
            'bannerTitle',
            'bannerSubtitle',
            'bannerCtaLabel',
            'bannerLinkType',
            'bannerLinkCategoryId',
            'bannerLinkProductId',
            'bannerExternalUrl',
            'bannerExistingImage',
            
            'selectedCategoryIds',
            
            'productSearch',
            'productCategoryFilter',
            'productStockFilter',
            'selectedProductIds',
            
            'slides',
        ]);
        $this->resetValidation();
        $this->slides = [];
        $this->visibilityMode = 'live';
    }

    public function render()
    {
        // Query home sections for index
        $query = HomeContentSection::ordered()->withCount('items');

        if ($this->searchSections) {
            $query->where('title', 'like', "%{$this->searchSections}%");
        }

        if ($this->activeTab === 'active') {
            $query->active();
        } elseif ($this->activeTab === 'inactive') {
            $query->where('is_active', false);
        }

        $sections = $query->paginate(10);

        // Category options
        $categoriesList = Category::orderBy('name')->get();

        // Product Picker listing with filters and pagination
        $productQuery = Product::where('is_active', true)->with('primaryMedia');
        if ($this->productSearch) {
            $productQuery->where(function ($q) {
                $q->where('title', 'like', "%{$this->productSearch}%")
                  ->orWhere('sku', 'like', "%{$this->productSearch}%");
            });
        }
        if ($this->productCategoryFilter) {
            $productQuery->whereHas('categories', function ($q) {
                $q->where('categories.id', $this->productCategoryFilter);
            });
        }
        if ($this->productStockFilter === 'in_stock') {
            $productQuery->where('stock_quantity', '>', 0);
        } elseif ($this->productStockFilter === 'out_of_stock') {
            $productQuery->where('stock_quantity', 0);
        }

        $productsList = $productQuery->paginate(6, ['*'], 'productsPage');

        return view('livewire.admin.home-content.home-content-page', [
            'sections' => $sections,
            'categoriesList' => $categoriesList,
            'productsList' => $productsList,
        ])->layoutData(['title' => 'Home Content CMS']);
    }
}
