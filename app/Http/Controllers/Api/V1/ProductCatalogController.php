<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProductIndexRequest;
use App\Http\Resources\Api\V1\ProductCardResource;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Models\Product;
use App\Services\Portal\ProductCatalogService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProductCatalogController extends Controller implements HasMiddleware
{
    use ApiResponseTrait;

    protected ProductCatalogService $catalogService;

    public function __construct(ProductCatalogService $catalogService)
    {
        $this->catalogService = $catalogService;
    }

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                $user = $request->user();
                if (!$user || !$user->customer || !$user->customer->is_active) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Customer profile not found or inactive for this account.',
                        'errors' => ['auth' => ['Only active customer accounts can access the product catalog.']]
                    ], 403);
                }
                return $next($request);
            }
        ];
    }

    /**
     * Get list of paginated products.
     */
    public function index(ProductIndexRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $filters = $request->validated();
        if ($request->has('category_slug') && !$request->filled('category_id')) {
            $filters['category'] = $request->input('category_slug');
        } elseif ($request->filled('category_id')) {
            $filters['category'] = $request->input('category_id');
        }

        $paginator = Product::where('is_active', true)
            ->with(['categories', 'media', 'primaryMedia', 'combinations', 'units', 'customerLevelPrices'])
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = trim($filters['search']);
                $q->where(function ($sq) use ($search) {
                    $sq->where('title', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(!empty($filters['category']), function ($q) use ($filters) {
                // We reuse the service descendant categories locator if needed
                $categoryVal = $filters['category'];
                $category = is_numeric($categoryVal) 
                    ? \App\Models\Category::find($categoryVal) 
                    : \App\Models\Category::all()->first(fn($c) => Str::slug($c->name) === $categoryVal);

                if ($category) {
                    $descendants = [$category->id];
                    $children = \App\Models\Category::whereIn('parent_id', $descendants)->pluck('id')->all();
                    while (!empty($children)) {
                        $descendants = array_merge($descendants, $children);
                        $children = \App\Models\Category::whereIn('parent_id', $children)->pluck('id')->all();
                    }
                    $q->whereHas('categories', function ($sq) use ($descendants) {
                        $sq->whereIn('categories.id', $descendants);
                    });
                }
            })
            ->when(!empty($filters['availability']), function ($q) use ($filters) {
                $avail = $filters['availability'];
                if ($avail === 'in_stock') {
                    $q->where(function ($sq) {
                        $sq->where('stock_quantity', '>', 0)
                          ->orWhereHas('combinations', function ($cc) {
                              $cc->where('stock_quantity', '>', 0)->where('is_active', true);
                          });
                    });
                } elseif ($avail === 'out_of_stock') {
                    $q->where(function ($sq) {
                        $sq->where('stock_quantity', 0)
                          ->whereDoesntHave('combinations', function ($cc) {
                              $cc->where('stock_quantity', '>', 0)->where('is_active', true);
                          });
                    });
                }
            })
            ->when(!empty($filters['price_min']), function ($q) use ($filters) {
                $q->where('base_price', '>=', (float)$filters['price_min']);
            })
            ->when(!empty($filters['price_max']), function ($q) use ($filters) {
                $q->where('base_price', '<=', (float)$filters['price_max']);
            });

        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'price_asc':
                $paginator->orderBy('base_price', 'asc');
                break;
            case 'price_desc':
                $paginator->orderBy('base_price', 'desc');
                break;
            case 'name_asc':
                $paginator->orderBy('title', 'asc');
                break;
            case 'availability':
                $paginator->orderBy('stock_quantity', 'desc');
                break;
            case 'newest':
            default:
                $paginator->orderBy('id', 'desc');
                break;
        }

        $perPage = $filters['per_page'] ?? 12;
        $results = $paginator->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully.',
            'data' => ProductCardResource::collection($results),
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem(),
            ]
        ]);
    }

    /**
     * Get detailed info for a single product.
     */
    public function show(Request $request, string $identifier): JsonResponse
    {
        $query = Product::where('is_active', true)
            ->with([
                'categories.parent',
                'media',
                'variationGroups.values.media',
                'combinations',
                'customerLevelPrices',
                'units'
            ]);

        if (is_numeric($identifier)) {
            $product = $query->where('id', $identifier)->first();
        } else {
            $product = $query->where('slug', $identifier)->first();
        }

        if (!$product) {
            return $this->errorResponse('Product not found.', [], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully.',
            'data' => new ProductDetailResource($product)
        ]);
    }

    /**
     * Get available filter tags/categories metadata.
     */
    public function filters(Request $request): JsonResponse
    {
        $metadata = $this->catalogService->getAvailableFilters($request->user());
        
        // Build nested categories tree for clean filter hierarchy
        $categories = \App\Models\Category::with('children')->whereNull('parent_id')->get()->map(function($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => Str::slug($c->name),
                'children' => $c->children->map(fn($child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => Str::slug($child->name),
                    'children' => []
                ])->toArray()
            ];
        })->toArray();

        return $this->successResponse('Product filters retrieved successfully.', [
            'categories' => $categories,
            'availability' => [
                ['value' => 'all', 'label' => 'All'],
                ['value' => 'in_stock', 'label' => 'In Stock'],
                ['value' => 'low_stock', 'label' => 'Low Stock'],
                ['value' => 'out_of_stock', 'label' => 'Out of Stock'],
            ],
            'sort' => [
                ['value' => 'newest', 'label' => 'Newest Arrivals'],
                ['value' => 'price_asc', 'label' => 'Price: Low to High'],
                ['value' => 'price_desc', 'label' => 'Price: High to Low'],
                ['value' => 'name_asc', 'label' => 'Name: A to Z'],
            ],
            'price_range' => [
                'min' => 100,
                'max' => 10000,
                'currency' => 'INR'
            ]
        ]);
    }

    /**
     * Get related products based on shared categories.
     */
    public function related(Request $request, string $identifier): JsonResponse
    {
        if (is_numeric($identifier)) {
            $product = Product::find($identifier);
        } else {
            $product = Product::where('slug', $identifier)->first();
        }

        if (!$product) {
            return $this->errorResponse('Product not found.', [], 404);
        }

        $limit = $request->query('limit', 4);
        $limit = min(12, max(1, (int)$limit));

        $categoryIds = $product->categories->pluck('id')->toArray();
        $related = Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            })
            ->with(['media', 'primaryMedia', 'customerLevelPrices', 'units'])
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Related products retrieved successfully.',
            'data' => ProductCardResource::collection($related)
        ]);
    }
}
