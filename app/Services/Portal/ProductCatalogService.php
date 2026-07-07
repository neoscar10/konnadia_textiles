<?php

namespace App\Services\Portal;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductCombination;
use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductCatalogService
{
    protected CustomerPricingService $pricingService;
    protected ProductAvailabilityService $availabilityService;
    protected ProductUnitPricingService $unitPricingService;

    public function __construct(
        CustomerPricingService $pricingService,
        ProductAvailabilityService $availabilityService,
        ProductUnitPricingService $unitPricingService
    ) {
        $this->pricingService = $pricingService;
        $this->availabilityService = $availabilityService;
        $this->unitPricingService = $unitPricingService;
    }

    /**
     * List paginated products with filters for a customer.
     */
    public function listProductsForCustomer(?User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Product::where('is_active', true)
            ->with(['categories', 'media', 'primaryMedia', 'combinations', 'units', 'customerLevelPrices', 'tags']);

        // Tags filter
        if (!empty($filters['tags'])) {
            $tags = (array) $filters['tags'];
            $query->whereHas('tags', function ($q) use ($tags) {
                $q->whereIn('tags.id', $tags);
            });
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if (!empty($filters['category'])) {
            $categoryVal = $filters['category'];
            $categoryIds = [];
            
            // Resolve category by ID or slug
            $category = is_numeric($categoryVal) 
                ? Category::find($categoryVal) 
                : Category::all()->first(fn($c) => Str::slug($c->name) === $categoryVal);

            if ($category) {
                // Get subcategory IDs recursively
                $categoryIds = $this->getCategoryDescendantIds($category->id);
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            }
        }

        // Availability filter
        if (!empty($filters['availability'])) {
            $avail = $filters['availability'];
            if ($avail === 'in_stock') {
                $query->where(function ($q) {
                    $q->where('stock_quantity', '>', 0)
                      ->orWhereHas('combinations', function ($sq) {
                          $sq->where('stock_quantity', '>', 0)->where('is_active', true);
                      });
                });
            } elseif ($avail === 'out_of_stock') {
                $query->where(function ($q) {
                    $q->where('stock_quantity', 0)
                      ->whereDoesntHave('combinations', function ($sq) {
                          $sq->where('stock_quantity', '>', 0)->where('is_active', true);
                      });
                });
            }
        }

        // Price range filtering (based on base price)
        if (!empty($filters['min_price'])) {
            $query->where('base_price', '>=', (float)$filters['min_price']);
        }
        if (!empty($filters['max_price']) && (float)$filters['max_price'] < 10000) {
            $query->where('base_price', '<=', (float)$filters['max_price']);
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'price_low':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('base_price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'availability':
                $query->orderBy('stock_quantity', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('id', 'desc');
                break;
        }

        $perPage = $filters['per_page'] ?? 12;
        $paginator = $query->paginate($perPage);

        // Transform collection items
        $paginator->getCollection()->transform(function ($product) use ($user) {
            return $this->formatProductCard($product, $user);
        });

        return $paginator;
    }

    /**
     * Resolve a product and format it for customer detail view.
     */
    public function getProductForCustomer(?User $user, string|int $identifier): array
    {
        $query = Product::where('is_active', true)
            ->with([
                'categories.parent',
                'media',
                'variationGroups.values.media',
                'combinations',
                'customerLevelPrices',
                'units',
                'tags'
            ]);

        if (is_numeric($identifier)) {
            $product = $query->where('id', $identifier)->first();
        } else {
            $product = $query->where('slug', $identifier)->first();
        }

        if (!$product) {
            abort(404, 'Product not found');
        }

        return $this->formatProductDetail($product, $user);
    }

    /**
     * Get list of available categories and filter metadata.
     */
    public function getAvailableFilters(?User $user = null): array
    {
        return [
            'categories' => Category::orderBy('name')->get()->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => Str::slug($c->name),
                'parent_id' => $c->parent_id
            ])->toArray()
        ];
    }

    /**
     * Get products related to the current product.
     */
    public function getRelatedProducts(?User $user, Product $product, int $limit = 4): Collection
    {
        $categoryIds = $product->categories->pluck('id')->toArray();

        $related = Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            })
            ->with(['media', 'primaryMedia', 'customerLevelPrices', 'units'])
            ->limit($limit)
            ->get();

        return $related->map(fn($p) => $this->formatProductCard($p, $user));
    }

    /**
     * Format a product for a standard card display.
     */
    public function formatProductCard(Product $product, ?User $user): array
    {
        $pricing = $this->pricingService->calculateCustomerPrice($product, $user);
        $availability = $this->availabilityService->getProductAvailability($product);
        
        $primaryImage = $product->primaryMedia ? $product->primaryMedia->file_path : null;
        if (!$primaryImage && $product->media->first()) {
            $primaryImage = $product->media->first()->file_path;
        }

        // Format primary image URL
        $primaryImageUrl = $primaryImage 
            ? (str_starts_with($primaryImage, 'http') ? $primaryImage : Storage::url($primaryImage))
            : 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=400';

        $categories = $product->categories->map(fn($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'path' => $this->getCategoryPath($cat)
        ])->toArray();

        // Level 1 Unit
        $baseUnit = $product->units->where('level', 1)->first();
        $unitLabel = $baseUnit ? $baseUnit->name : 'Piece';

        return [
            'id' => $product->id,
            'title' => $product->title,
            'slug' => Str::slug($product->title),
            'sku' => $product->sku,
            'primary_image_url' => $primaryImageUrl,
            'categories' => $categories,
            'price' => [
                'base_price' => $pricing['base_price'],
                'customer_price' => $pricing['customer_price'],
                'discount_percentage' => $pricing['discount_percentage'],
                'currency' => $pricing['currency'],
                'unit_label' => $unitLabel,
            ],
            'stock' => [
                'available_quantity' => $availability['available_quantity'],
                'status' => $availability['status'],
                'label' => $availability['label'],
            ],
            'minimum_order_quantity' => $product->minimum_order_quantity ?? 1,
            'tags' => $product->tags->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
            ])->toArray(),
        ];
    }

    /**
     * Format full details of a product.
     */
    public function formatProductDetail(Product $product, ?User $user): array
    {
        $pricing = $this->pricingService->calculateCustomerPrice($product, $user);
        $availability = $this->availabilityService->getProductAvailability($product);

        $media = $product->media->map(fn($m) => [
            'id' => $m->id,
            'url' => str_starts_with($m->file_path, 'http') ? $m->file_path : Storage::url($m->file_path),
            'is_primary' => (bool)$m->is_primary,
            'sort_order' => $m->sort_order,
        ])->toArray();

        // Fallback for media
        if (empty($media)) {
            $media[] = [
                'id' => 0,
                'url' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=800',
                'is_primary' => true,
                'sort_order' => 0
            ];
        }

        $categories = $product->categories->map(fn($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'parent' => $cat->parent ? [
                'id' => $cat->parent->id,
                'name' => $cat->parent->name
            ] : null,
        ])->toArray();

        // Breadcrumb array
        $breadcrumb = ['Home' => route('customer.dashboard'), 'Products' => route('customer.products.index')];
        if (!empty($categories)) {
            $cat = $product->categories->first();
            if ($cat->parent) {
                $breadcrumb[$cat->parent->name] = route('customer.categories.show', Str::slug($cat->parent->name));
            }
            $breadcrumb[$cat->name] = route('customer.categories.show', Str::slug($cat->name));
        }
        $breadcrumb[$product->title] = '#';

        $variations = $product->variationGroups->map(fn($g) => [
            'id' => $g->id,
            'name' => $g->name,
            'display_type' => $g->display_type,
            'has_images' => (bool)$g->has_images,
            'values' => $g->values->map(fn($v) => [
                'id' => $v->id,
                'value' => $v->value,
                'color_hex' => $v->color_hex,
                'is_default' => (bool)$v->is_default,
                'media' => $v->media->map(fn($vm) => [
                    'url' => str_starts_with($vm->file_path, 'http') ? $vm->file_path : Storage::url($vm->file_path)
                ])->toArray()
            ])->toArray()
        ])->toArray();

        $combinations = $product->combinations->map(fn($c) => [
            'id' => $c->id,
            'sku' => $c->sku,
            'combination_values' => $c->combination_values,
            'price' => $c->price !== null ? (float)$c->price : null,
            'stock_quantity' => (int)$c->stock_quantity,
            'is_active' => (bool)$c->is_active,
        ])->toArray();

        $units = $this->unitPricingService->getAvailableUnits($product, $pricing['customer_price']);
        
        // Convert description markdown to HTML safely
        $descriptionHtml = Str::markdown($product->description ?? '');

        return [
            'id' => $product->id,
            'title' => $product->title,
            'sku' => $product->sku,
            'brand' => 'Kannodia Premium Apparel',
            'description_markdown' => $product->description,
            'description_html' => $descriptionHtml,
            'media' => $media,
            'categories' => $categories,
            'breadcrumb' => $breadcrumb,
            'variations' => $variations,
            'combinations' => $combinations,
            'units' => $units,
            'pricing' => $pricing,
            'availability' => $availability,
            'purchase_defaults' => [
                'minimum_order_quantity' => $product->minimum_order_quantity ?? 1,
                'default_unit_id' => !empty($units) ? $units[0]['id'] : null,
            ],
            'tags' => $product->tags->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
            ])->toArray(),
            'raw_model' => $product // pass model down to retrieve it easily in Livewire
        ];
    }

    /**
     * Resolve selected combination based on variation values selected by the user.
     */
    public function resolveSelectedCombination(Product $product, array $selectedValues): ?ProductCombination
    {
        if (empty($selectedValues)) {
            return null;
        }

        foreach ($product->combinations as $combination) {
            if (!$combination->is_active) {
                continue;
            }

            $match = true;
            foreach ($selectedValues as $groupName => $val) {
                if (!isset($combination->combination_values[$groupName]) || $combination->combination_values[$groupName] !== $val) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                return $combination;
            }
        }

        return null;
    }

    /**
     * Helper to get recursive category descendants.
     */
    protected function getCategoryDescendantIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $children = Category::whereIn('parent_id', $ids)->pluck('id')->all();
        while (!empty($children)) {
            $ids = array_merge($ids, $children);
            $children = Category::whereIn('parent_id', $children)->pluck('id')->all();
        }
        return $ids;
    }

    /**
     * Helper to get a full category breadcrumb path.
     */
    protected function getCategoryPath(Category $category): string
    {
        $path = $category->name;
        $parent = $category->parent;
        while ($parent) {
            $path = $parent->name . ' > ' . $path;
            $parent = $parent->parent;
        }
        return $path;
    }
}
