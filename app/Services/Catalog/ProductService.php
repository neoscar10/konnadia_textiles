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
    public function list(array $filters = [])
    {
        $query = Product::with(['categories', 'media', 'primaryMedia', 'combinations', 'units']);

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $categoryIds = $this->getCategoryDescendantIds((int)$filters['category_id']);
            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $isActive = $filters['status'] === 'active';
            $query->where('is_active', $isActive);
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'instock') {
                $query->where(function ($q) {
                    $q->where('stock_quantity', '>', 0)
                      ->orWhereHas('combinations', function ($sq) {
                          $sq->where('stock_quantity', '>', 0);
                      });
                });
            } elseif ($filters['stock_status'] === 'outofstock') {
                $query->where(function ($q) {
                    $q->where('stock_quantity', 0)
                      ->whereDoesntHave('combinations', function ($sq) {
                          $sq->where('stock_quantity', '>', 0);
                      });
                });
            }
        }

        return $query->orderBy('id', 'desc')->paginate(10);
    }

    /**
     * Create a new product.
     */
    public function create(array $payload): Product
    {
        return DB::transaction(function () use ($payload) {
            $sku = $this->generateSku();

            $product = Product::create([
                'title' => trim($payload['title']),
                'slug' => Str::slug($payload['title']),
                'sku' => $sku,
                'base_price' => (float)$payload['base_price'],
                'description' => trim($payload['description']),
                'is_active' => isset($payload['is_active']) ? (bool)$payload['is_active'] : true,
                'stock_quantity' => isset($payload['stock_quantity']) ? (int)$payload['stock_quantity'] : 0,
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
                'title' => trim($payload['title']),
                'slug' => Str::slug($payload['title']),
                'base_price' => (float)$payload['base_price'],
                'description' => trim($payload['description']),
                'is_active' => isset($payload['is_active']) ? (bool)$payload['is_active'] : true,
                'stock_quantity' => isset($payload['stock_quantity']) ? (int)$payload['stock_quantity'] : 0,
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
        $latest = Product::orderBy('id', 'desc')->first();
        $nextId = $latest ? ($latest->id + 1) : 1;

        while (true) {
            $sku = 'KT-P-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT);
            if (!Product::where('sku', $sku)->exists()) {
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
