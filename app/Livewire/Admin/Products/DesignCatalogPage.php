<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use App\Models\Category;
use App\Services\Catalog\CategoryService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;

#[Layout('components.admin.layout')]
class DesignCatalogPage extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $filterCategory = '';

    #[Url(history: true)]
    public string $filterTag = '';

    protected $queryString = [
        'search'         => ['except' => ''],
        'filterCategory' => ['except' => ''],
        'filterTag'      => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function updatingFilterTag(): void
    {
        $this->resetPage();
    }

    public function shareCatalog(): void
    {
        $params = [];
        if ($this->search !== '') {
            $params['search'] = $this->search;
        }
        if ($this->filterCategory !== '') {
            $params['category'] = $this->filterCategory;
        }
        if ($this->filterTag !== '') {
            $params['selectedTags'] = [$this->filterTag];
        }

        $url = route('customer.products.index', $params);

        $this->dispatch('copy-to-clipboard', url: $url);
        $this->dispatch('toast', message: 'Catalog link copied to clipboard!', type: 'success');
    }

    public function render(CategoryService $categoryService)
    {
        // 1. Fetch Leaf Categories with full breadcrumb paths for the filter
        $leafCategories = $categoryService->getLeafCategories();

        // 2. Query products
        $query = Product::with(['categories', 'primaryMedia', 'tags'])
            ->orderBy('created_at', 'desc');

        if ($this->search) {
            $q = '%' . $this->search . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', $q)
                    ->orWhere('sku', 'like', $q)
                    ->orWhere('description', 'like', $q);
            });
        }

        if ($this->filterCategory) {
            $query->whereHas('categories', function ($sub) {
                $sub->where('categories.id', $this->filterCategory);
            });
        }

        if ($this->filterTag) {
            $query->whereHas('tags', function ($sub) {
                $sub->where('tags.id', $this->filterTag);
            });
        }

        $products = $query->paginate(10);

        $availabilityService = app(\App\Services\Portal\ProductAvailabilityService::class);

        // 3. For each product, build its category path string and compute its stock
        foreach ($products as $product) {
            $paths = [];
            foreach ($product->categories as $category) {
                $paths[] = $categoryService->buildPath($category);
            }
            $product->category_paths = $paths;

            // Compute availability / stock
            $availability = $availabilityService->getProductAvailability($product);
            $product->computed_stock = $availability['available_quantity'];
            $product->stock_status = $availability['status'];
            $product->stock_label = $availability['label'];
        }

        $tagsList = \App\Models\Tag::orderBy('name')->get();

        return view('livewire.admin.products.design-catalog-page', compact('products', 'leafCategories', 'tagsList'));
    }
}
