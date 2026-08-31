<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Category;

$category = Category::firstOrCreate([
    'slug' => 'test-textiles',
], [
    'name' => 'Test Textiles',
    'is_active' => true,
]);

$slugs = [
    'royal-embroidered-silk-dupatta-out-of-stock-test',
    'royal-embroidered-dupatta-out-of-stock'
];

foreach ($slugs as $slug) {
    $product = Product::where('slug', $slug)->first();

    if (!$product) {
        $product = Product::create([
            'title' => 'Royal Embroidered Silk Dupatta (Out of Stock Test)',
            'slug' => $slug,
            'sku' => 'TEST-DUPATTA-OOS-' . strtoupper(substr(md5($slug), 0, 4)),
            'product_type' => 'standard',
            'stock_quantity' => 0,
            'base_price' => 1850.00,
            'is_active' => true,
            'gst_percentage' => 5.0,
            'minimum_order_quantity' => 1,
            'short_description' => 'Premium royal silk dupatta with rich gold embroidery. Currently out of stock for testing stock reminders.',
            'description' => 'This product is specifically configured with 0 stock quantity to test the Notify Me When In Stock reminder UI flow.',
        ]);

        $product->categories()->syncWithoutDetaching([$category->id]);

        ProductUnit::create([
            'product_id' => $product->id,
            'name' => 'Piece',
            'short_code' => 'Pcs',
            'level' => 1,
            'conversion_to_base' => 1,
        ]);

        ProductUnit::create([
            'product_id' => $product->id,
            'name' => 'Box',
            'short_code' => 'Box',
            'level' => 2,
            'conversion_to_base' => 10,
        ]);

        echo "SUCCESS: Created product with slug: {$slug}\n";
    } else {
        $product->update([
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
        echo "SUCCESS: Updated product with slug: {$slug} to stock = 0\n";
    }
}
