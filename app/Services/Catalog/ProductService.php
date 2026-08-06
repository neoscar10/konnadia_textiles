<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductCustomerLevelPrice;
use App\Models\ProductUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * List products with filters.
     */
    public function list(array $filters = [], int $perPage = 10)
    {
        $perPage = (int) ($filters['per_page'] ?? $perPage);
        if ($perPage <= 0) {
            $perPage = 10;
        }

        $query = Product::with(['categories', 'media', 'primaryMedia', 'combinations', 'units', 'tags']);

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $categoryIds = $this->getCategoryDescendantIds((int)$filters['category_id']);
            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        if (!empty($filters['tag_id'])) {
            $tagId = (int) $filters['tag_id'];
            $query->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'all') {
            $statusVal = strtolower((string)$filters['status']);
            if (in_array($statusVal, ['active', '1', 'true'], true)) {
                $query->where('is_active', true);
            } elseif (in_array($statusVal, ['inactive', '0', 'false'], true)) {
                $query->where('is_active', false);
            }
        }

        if (!empty($filters['product_type']) && $filters['product_type'] !== 'all') {
            $query->where('product_type', $filters['product_type']);
        }

        if (!empty($filters['stock_status']) && $filters['stock_status'] !== 'all') {
            $stockStatus = strtolower(str_replace('_', '', $filters['stock_status'])); // instock, lowstock, outofstock
            if ($stockStatus === 'instock') {
                $query->where(function ($q) {
                    $q->where('stock_quantity', '>', 0)
                      ->orWhereHas('combinations', function ($sq) {
                          $sq->where('stock_quantity', '>', 0);
                      });
                });
            } elseif ($stockStatus === 'lowstock') {
                $query->where(function ($q) {
                    $q->where(function ($sq1) {
                        $sq1->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10);
                    })
                    ->orWhereHas('combinations', function ($sq2) {
                        $sq2->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', 10);
                    });
                });
            } elseif ($stockStatus === 'outofstock') {
                $query->where(function ($q) {
                    $q->where(function ($sq1) {
                        $sq1->whereNull('stock_quantity')->orWhere('stock_quantity', '<=', 0);
                    })
                    ->whereDoesntHave('combinations', function ($sq2) {
                        $sq2->where('stock_quantity', '>', 0);
                    });
                });
            }
        }

        // Sorting
        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'price_low':
            case 'price_asc':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_high':
            case 'price_desc':
                $query->orderBy('base_price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'oldest':
                $query->orderBy('id', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('id', 'desc');
                break;
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new product.
     */
    public function create(array $payload): Product
    {
        return DB::transaction(function () use ($payload) {
            $sku = !empty($payload['sku']) ? trim($payload['sku']) : $this->generateSku();

            $product = Product::create([
                'title'          => trim($payload['title']),
                'slug'           => Str::slug($payload['title']),
                'sku'            => $sku,
                'base_price'     => (float) $payload['base_price'],
                'hsn_code'       => isset($payload['hsn_code']) && $payload['hsn_code'] !== '' ? trim($payload['hsn_code']) : null,
                'gst_percentage' => isset($payload['gst_percentage']) && $payload['gst_percentage'] !== '' ? (float) $payload['gst_percentage'] : null,
                'minimum_order_quantity' => isset($payload['minimum_order_quantity']) ? (int) $payload['minimum_order_quantity'] : 1,
                'description'    => trim($payload['description']),
                'is_active'      => isset($payload['is_active']) ? (bool) $payload['is_active'] : true,
                'stock_quantity' => (array_key_exists('stock_quantity', $payload) && $payload['stock_quantity'] !== null && $payload['stock_quantity'] !== '') ? (int)$payload['stock_quantity'] : null,
                'product_type'   => isset($payload['product_type']) ? $payload['product_type'] : 'retail',
            ]);

            if (!empty($payload['category_ids'])) {
                $this->syncCategories($product, $payload['category_ids']);
            }

            if (isset($payload['customer_level_prices'])) {
                $this->syncPricingOverrides($product, $payload['customer_level_prices']);
            }

            if (isset($payload['units'])) {
                $this->syncUnits($product, $payload['units']);
            }

            return $product;
        });
    }

    /**
     * Update an existing product.
     */
    public function update(Product $product, array $payload): Product
    {
        return DB::transaction(function () use ($product, $payload) {
            $product->update([
                'title'          => trim($payload['title']),
                'slug'           => Str::slug($payload['title']),
                'base_price'     => (float) $payload['base_price'],
                'hsn_code'       => isset($payload['hsn_code']) && $payload['hsn_code'] !== '' ? trim($payload['hsn_code']) : null,
                'gst_percentage' => isset($payload['gst_percentage']) && $payload['gst_percentage'] !== '' ? (float) $payload['gst_percentage'] : null,
                'minimum_order_quantity' => isset($payload['minimum_order_quantity']) ? (int) $payload['minimum_order_quantity'] : 1,
                'description'    => trim($payload['description']),
                'is_active'      => isset($payload['is_active']) ? (bool) $payload['is_active'] : true,
                'stock_quantity' => (array_key_exists('stock_quantity', $payload) && $payload['stock_quantity'] !== null && $payload['stock_quantity'] !== '') ? (int)$payload['stock_quantity'] : null,
                'product_type'   => isset($payload['product_type']) ? $payload['product_type'] : 'retail',
            ]);

            if (isset($payload['category_ids'])) {
                $this->syncCategories($product, $payload['category_ids']);
            }

            if (isset($payload['customer_level_prices'])) {
                $this->syncPricingOverrides($product, $payload['customer_level_prices']);
            }

            if (isset($payload['units'])) {
                $this->syncUnits($product, $payload['units']);
            }

            return $product;
        });
    }

    /**
     * Delete a product (soft delete).
     */
    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->delete();
        });
    }

    /**
     * Toggle the active status of a product.
     */
    public function toggleStatus(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $product->is_active = !$product->is_active;
            $product->save();
            return $product;
        });
    }

    /**
     * Generate sequential unique SKU.
     */
    public function generateSku(): string
    {
        $latest = Product::withTrashed()->orderBy('id', 'desc')->first();
        $nextId = $latest ? ($latest->id + 1) : 1;

        while (true) {
            $sku = 'KT-P-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT);
            if (!Product::withTrashed()->where('sku', $sku)->exists()) {
                return $sku;
            }
            $nextId++;
        }
    }

    /**
     * Sync product categories.
     */
    public function syncCategories(Product $product, array $categoryIds): void
    {
        $product->categories()->sync($categoryIds);
    }

    /**
     * Sync customer level pricing overrides.
     */
    public function syncPricingOverrides(Product $product, array $levelPrices): void
    {
        ProductCustomerLevelPrice::where('product_id', $product->id)->delete();

        foreach ($levelPrices as $levelPrice) {
            if (isset($levelPrice['discount_percentage']) && $levelPrice['discount_percentage'] !== '') {
                ProductCustomerLevelPrice::create([
                    'product_id' => $product->id,
                    'customer_level_id' => (int)$levelPrice['customer_level_id'],
                    'discount_percentage' => (float)$levelPrice['discount_percentage'],
                ]);
            }
        }
    }

    /**
     * Sync product unit setup.
     */
    public function syncUnits(Product $product, array $units): void
    {
        ProductUnit::where('product_id', $product->id)->delete();

        // Level 1 Unit
        if (!empty($units['level1_name']) && !empty($units['level1_code'])) {
            ProductUnit::create([
                'product_id' => $product->id,
                'level' => 1,
                'name' => trim($units['level1_name']),
                'short_code' => trim($units['level1_code']),
                'conversion_to_base' => 1.0,
            ]);
        }

        // Level 2 Unit
        if (!empty($units['level2_name']) && !empty($units['level2_code'])) {
            $conversion = isset($units['level2_conversion']) ? (float)$units['level2_conversion'] : 1.0;
            ProductUnit::create([
                'product_id' => $product->id,
                'level' => 2,
                'name' => trim($units['level2_name']),
                'short_code' => trim($units['level2_code']),
                'conversion_to_base' => $conversion,
            ]);
        }
    }

    /**
     * Calculate B2B Selling Price.
     */
    public function calculateSellingPrice(float $basePrice, float $discountPercentage): float
    {
        return $basePrice - ($basePrice * ($discountPercentage / 100));
    }

    /**
     * Helper to get recursive category descendants.
     */
    protected function getCategoryDescendantIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $children = \App\Models\Category::whereIn('parent_id', $ids)->pluck('id')->all();
        while (!empty($children)) {
            $ids = array_merge($ids, $children);
            $children = \App\Models\Category::whereIn('parent_id', $children)->pluck('id')->all();
        }
        return $ids;
    }
}
