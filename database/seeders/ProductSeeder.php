<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CustomerLevel;
use App\Models\Product;
use App\Services\Catalog\ProductService;
use App\Services\Catalog\ProductVariationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    protected ProductService $productService;
    protected ProductVariationService $variationService;

    public function __construct(
        ProductService $productService,
        ProductVariationService $variationService
    ) {
        $this->productService = $productService;
        $this->variationService = $variationService;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Find seeded categories
            $mensWear = Category::where('name', "Men's Wear")->first();
            $shirts = Category::where('name', 'Shirts')->first();
            $womensWear = Category::where('name', "Women's Wear")->first();
            $sarees = Category::where('name', 'Sarees')->first();
            $kurtis = Category::where('name', 'Kurtis')->first();
            $kidsWear = Category::where('name', 'Kids Wear')->first();

            // Find customer levels
            $levels = CustomerLevel::active()->get();

            // 1. Product: Premium Cotton Shirt (with variations)
            if (!Product::where('title', 'Premium Cotton Shirt')->exists()) {
                $product = $this->productService->create([
                    'title' => 'Premium Cotton Shirt',
                    'base_price' => 1200.00,
                    'description' => '### Premium Quality Cotton Shirt
* 100% Breathable cotton fabric
* Standard slim-fit cut
* Available in multiple colors and sizes
* Suitable for office and semi-formal wear',
                    'is_active' => true,
                    'category_ids' => array_filter([$mensWear?->id, $shirts?->id]),
                    'units' => [
                        'level1_name' => 'Piece',
                        'level1_code' => 'pcs',
                        'level2_name' => 'Box',
                        'level2_code' => 'box',
                        'level2_conversion' => 12,
                    ],
                ]);

                // Define Variation Groups
                $groups = [
                    [
                        'name' => 'Size',
                        'display_type' => 'text',
                        'values' => [
                            ['value' => 'M', 'is_default' => true],
                            ['value' => 'L', 'is_default' => false],
                            ['value' => 'XL', 'is_default' => false],
                        ]
                    ],
                    [
                        'name' => 'Color',
                        'display_type' => 'color',
                        'values' => [
                            ['value' => 'White', 'color_hex' => '#ffffff', 'is_default' => true],
                            ['value' => 'Blue', 'color_hex' => '#1d4ed8', 'is_default' => false],
                        ]
                    ]
                ];

                $this->variationService->syncVariationGroups($product, $groups);

                // Auto generate combinations
                $combinations = $this->variationService->generateCombinations($groups);
                foreach ($combinations as &$comb) {
                    $comb['stock_quantity'] = rand(10, 100);
                    // Add small price override for Blue XL
                    if ($comb['combination_values']['Size'] === 'XL' && $comb['combination_values']['Color'] === 'Blue') {
                        $comb['price'] = 1300.00;
                    }
                }
                $this->variationService->syncCombinations($product, $combinations);

                // Pricing overrides for wholesale level
                $wholesaleLevel = $levels->where('name', 'Wholesale Distributor')->first();
                if ($wholesaleLevel) {
                    $this->productService->syncPricingOverrides($product, [
                        [
                            'customer_level_id' => $wholesaleLevel->id,
                            'discount_percentage' => 15.00, // custom 15% instead of default
                        ]
                    ]);
                }

                // Recalculate main stock
                $product->update(['stock_quantity' => collect($combinations)->sum('stock_quantity')]);
            }

            // 2. Product: Silk Saree Collection (Non-variant)
            if (!Product::where('title', 'Silk Saree Collection')->exists()) {
                $this->productService->create([
                    'title' => 'Silk Saree Collection',
                    'base_price' => 4500.00,
                    'description' => '### Elegant Traditional Silk Saree
* Pure Banarasi silk blend
* Intricate golden zari border details
* Comes with unstitched blouse piece (80cm)
* Dry clean only',
                    'is_active' => true,
                    'category_ids' => array_filter([$womensWear?->id, $sarees?->id]),
                    'stock_quantity' => 45,
                    'units' => [
                        'level1_name' => 'Piece',
                        'level1_code' => 'pcs',
                    ],
                ]);
            }

            // 3. Product: Printed Kids T-Shirt (Non-variant)
            if (!Product::where('title', 'Printed Kids T-Shirt')->exists()) {
                $this->productService->create([
                    'title' => 'Printed Kids T-Shirt',
                    'base_price' => 350.00,
                    'description' => '### Cute Graphic Printed Kids T-Shirt
* Soft organic cotton blend
* Fun animal character prints
* Machine washable, colorfast dyes',
                    'is_active' => true,
                    'category_ids' => array_filter([$kidsWear?->id]),
                    'stock_quantity' => 120,
                    'units' => [
                        'level1_name' => 'Piece',
                        'level1_code' => 'pcs',
                        'level2_name' => 'Pack',
                        'level2_code' => 'pk',
                        'level2_conversion' => 5,
                    ],
                ]);
            }
        });
    }
}
