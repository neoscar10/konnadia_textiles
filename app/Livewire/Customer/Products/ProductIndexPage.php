<?php

namespace App\Livewire\Customer\Products;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Services\Portal\ProductCatalogService;

#[Layout('components.customer.layout')]
class ProductIndexPage extends Component
{
    use WithPagination;

    public $search = '';
    public $category = '';
    public $availability = 'all';
    public $min_price = 0;
    public $max_price = 5000;
    public $sort = 'newest';

    public $expandedCategories = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'category' => ['except' => ''],
        'availability' => ['except' => 'all'],
        'min_price' => ['except' => 0],
        'max_price' => ['except' => 5000],
        'sort' => ['except' => 'newest'],
    ];

    public function toggleCategory($categoryId)
    {
        if (in_array($categoryId, $this->expandedCategories)) {
            $this->expandedCategories = array_diff($this->expandedCategories, [$categoryId]);
        } else {
            $this->expandedCategories[] = $categoryId;
        }
    }

    public function selectCategory($categoryId)
    {
        $this->category = $categoryId;
        $this->resetPage();
        $this->expandParentIfNeeded($categoryId);
    }

    protected function expandParentIfNeeded($categoryId)
    {
        if ($categoryId) {
            $cat = \App\Models\Category::find($categoryId);
            if ($cat && $cat->parent_id && !in_array($cat->parent_id, $this->expandedCategories)) {
                $this->expandedCategories[] = $cat->parent_id;
            }
        }
    }

    public function updatedCategory($value)
    {
        $this->expandParentIfNeeded($value);
        $this->resetPage();
    }

    public function updating($name)
    {
        if (in_array($name, ['search', 'category', 'availability', 'min_price', 'max_price', 'sort'])) {
            $this->resetPage();
        }
    }

    public function clearFilters()
    {
        $this->reset(['search', 'category', 'availability', 'min_price', 'max_price', 'sort', 'expandedCategories']);
        $this->resetPage();
    }

    public function render(ProductCatalogService $catalogService)
    {
        $this->expandParentIfNeeded($this->category);

        $filters = [
            'search' => $this->search,
            'category' => $this->category,
            'availability' => $this->availability,
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'sort' => $this->sort,
            'per_page' => 12
        ];

        $products = $catalogService->listProductsForCustomer(auth()->user(), $filters);
        $metadata = $catalogService->getAvailableFilters(auth()->user());

        return view('livewire.customer.products.product-index-page', [
            'products' => $products,
            'categoriesList' => $metadata['categories']
        ])->layoutData(['title' => 'Products Catalog']);
    }
}
