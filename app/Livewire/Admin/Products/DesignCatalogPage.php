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

    protected $queryString = [
        'search'         => ['except' => ''],
        'filterCategory' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function render(CategoryService $categoryService)
    {
        // 1. Fetch Leaf Categories with full breadcrumb paths for the filter
        $leafCategories = $categoryService->getLeafCategories();

        // 2. Query products
        $query = Product::with(['categories', 'primaryMedia'])
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

        $products = $query->paginate(10);

        // 3. For each product, build its category path string
        foreach ($products as $product) {
            $paths = [];
            foreach ($product->categories as $category) {
                $paths[] = $categoryService->buildPath($category);
            }
            $product->category_paths = $paths;
        }

        return view('livewire.admin.products.design-catalog-page', compact('products', 'leafCategories'));
    }
}
