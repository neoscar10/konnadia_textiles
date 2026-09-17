<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SwapProductTypesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_swaps_products_and_categories_types(): void
    {
        // 1. Create Products
        $pMfg1 = Product::create([
            'title' => 'Mfg Bed Sheet',
            'sku' => 'SKU-MFG-001',
            'product_type' => 'manufactured',
            'base_price' => 500.00,
        ]);
        $pMfg2 = Product::create([
            'title' => 'Mfg Pillow Cover',
            'sku' => 'SKU-MFG-002',
            'product_type' => 'manufactured',
            'base_price' => 200.00,
        ]);
        $pRetail1 = Product::create([
            'title' => 'Retail Jeans',
            'sku' => 'SKU-RET-001',
            'product_type' => 'retail',
            'base_price' => 1200.00,
        ]);

        // 2. Create Categories
        $catMfg = Category::create([
            'name' => 'Bedding',
            'slug' => 'bedding',
            'is_leaf' => true,
            'is_active' => true,
            'default_product_config' => [
                'product_type' => 'manufactured',
                'base_price' => 500,
            ],
        ]);

        $catRetail = Category::create([
            'name' => 'Apparel',
            'slug' => 'apparel',
            'is_leaf' => true,
            'is_active' => true,
            'default_product_config' => [
                'product_type' => 'retail',
                'base_price' => 1200,
            ],
        ]);

        // Run the command with --force
        $this->artisan('catalog:swap-product-types', ['--force' => true])
            ->expectsOutputToContain('Successfully swapped 2 manufactured products to retail, 1 retail products to manufactured, and 2 category configurations.')
            ->assertExitCode(0);

        // Verify products swapped
        $this->assertEquals('retail', $pMfg1->fresh()->product_type);
        $this->assertEquals('retail', $pMfg2->fresh()->product_type);
        $this->assertEquals('manufactured', $pRetail1->fresh()->product_type);

        if (Schema::hasColumn('products', 'is_manufactured')) {
            $this->assertFalse((bool) $pMfg1->fresh()->is_manufactured);
            $this->assertFalse((bool) $pMfg2->fresh()->is_manufactured);
            $this->assertTrue((bool) $pRetail1->fresh()->is_manufactured);
        }

        // Verify categories swapped
        $this->assertEquals('retail', $catMfg->fresh()->default_product_config['product_type']);
        $this->assertEquals('manufactured', $catRetail->fresh()->default_product_config['product_type']);
        // Verify other config keys preserved
        $this->assertEquals(500, $catMfg->fresh()->default_product_config['base_price']);
        $this->assertEquals(1200, $catRetail->fresh()->default_product_config['base_price']);
    }

    public function test_it_respects_dry_run_flag(): void
    {
        $pMfg = Product::create([
            'title' => 'Mfg Item',
            'sku' => 'SKU-DRY-001',
            'product_type' => 'manufactured',
            'base_price' => 100.00,
        ]);
        $pRetail = Product::create([
            'title' => 'Retail Item',
            'sku' => 'SKU-DRY-002',
            'product_type' => 'retail',
            'base_price' => 100.00,
        ]);

        $catMfg = Category::create([
            'name' => 'Mfg Cat',
            'slug' => 'mfg-cat',
            'is_leaf' => true,
            'is_active' => true,
            'default_product_config' => [
                'product_type' => 'manufactured',
            ],
        ]);

        $this->artisan('catalog:swap-product-types', ['--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN MODE')
            ->expectsOutputToContain('DRY-RUN completed. Total records that would be swapped: 3')
            ->assertExitCode(0);

        // Verify database was NOT changed
        $this->assertEquals('manufactured', $pMfg->fresh()->product_type);
        $this->assertEquals('retail', $pRetail->fresh()->product_type);
        $this->assertEquals('manufactured', $catMfg->fresh()->default_product_config['product_type']);
    }

    public function test_it_handles_no_records_gracefully(): void
    {
        $this->artisan('catalog:swap-product-types', ['--force' => true])
            ->expectsOutputToContain('No products or category configurations found with "manufactured" or "retail" types. Nothing to swap.')
            ->assertExitCode(0);
    }
}
