<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FinishedGoodsBatch;
use App\Models\FrontEndProduct;
use App\Models\InventoryBatch;
use App\Models\ManufacturingProduct;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\RawMaterial;
use App\Models\User;
use App\Services\Manufacturing\FinishedGoodsConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FrontEndProductsAndFinishedGoodsHubTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Category $category;
    protected ManufacturingProduct $mfgBedsheet;
    protected ManufacturingProduct $mfgPillow;
    protected RawMaterial $pkgPolyBag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@kannodia.test',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Bedding',
            'slug' => 'bedding',
            'is_leaf' => true,
        ]);

        $this->mfgBedsheet = ManufacturingProduct::create([
            'name' => 'ktc king 108×108',
            'code' => 'MP-BEDSHEET-108',
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $this->mfgPillow = ManufacturingProduct::create([
            'name' => 'ktc pillow basic',
            'code' => 'MP-PILLOW-001',
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $rmCategory = \App\Models\RawMaterialCategory::create([
            'name' => 'Packaging',
            'code' => 'CAT-SUB',
            'unit_type' => 'other',
        ]);

        $this->pkgPolyBag = RawMaterial::create([
            'name' => 'Poly Bag 12×16',
            'code' => 'RM-PKG-001',
            'unit' => 'Piece',
            'unit_type' => 'other',
            'raw_material_category_id' => $rmCategory->id,
            'is_active' => true,
        ]);
    }

    public function test_can_create_front_end_product_bundle()
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Admin\Production\FrontEndProductIndexPage::class)
            ->call('configureCategory', $this->category->id)
            ->set('mfgRows', [
                ['manufacturing_product_id' => $this->mfgBedsheet->id, 'quantity' => 1],
                ['manufacturing_product_id' => $this->mfgPillow->id, 'quantity' => 2],
            ])
            ->set('pkgRows', [
                ['raw_material_id' => $this->pkgPolyBag->id, 'quantity' => 1],
            ])
            ->call('saveCategoryConfiguration');

        $this->assertDatabaseHas('front_end_products', [
            'category_id' => $this->category->id,
            'name' => 'Bedding',
        ]);

        $feProduct = FrontEndProduct::where('category_id', $this->category->id)->first();
        $this->assertCount(2, $feProduct->components);
        $this->assertCount(1, $feProduct->packagingItems);
    }

    public function test_stock_availability_check_and_conversion_execution()
    {
        $this->actingAs($this->admin);

        // 1. Create FrontEndProduct for category
        $feProduct = FrontEndProduct::create([
            'name' => 'Bedding',
            'sku' => 'CAT-CFG-' . $this->category->id,
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $feProduct->components()->create([
            'manufacturing_product_id' => $this->mfgBedsheet->id,
            'quantity' => 1,
        ]);
        $feProduct->components()->create([
            'manufacturing_product_id' => $this->mfgPillow->id,
            'quantity' => 2,
        ]);

        $feProduct->packagingItems()->create([
            'raw_material_id' => $this->pkgPolyBag->id,
            'quantity' => 1,
        ]);

        // 2. Create completed production jobs & packaging inventory
        ProductionJob::create([
            'job_code' => 'JOB-BEDSHEET-001',
            'manufacturing_product_id' => $this->mfgBedsheet->id,
            'target_quantity' => 50,
            'converted_quantity' => 0,
            'status' => 'completed',
        ]);

        ProductionJob::create([
            'job_code' => 'JOB-PILLOW-001',
            'manufacturing_product_id' => $this->mfgPillow->id,
            'target_quantity' => 100,
            'converted_quantity' => 0,
            'status' => 'completed',
        ]);

        InventoryBatch::create([
            'raw_material_id' => $this->pkgPolyBag->id,
            'batch_number' => 'LOT-PKG-001',
            'received_quantity' => 200,
            'balance_quantity' => 200,
            'purchase_date' => now(),
            'purchase_rate' => 5.00,
            'status' => 'Active',
        ]);

        // 3. Storefront Product
        Product::create([
            'title' => '5934 Bedding',
            'sku' => 'KT-P-5934',
            'stock_quantity' => 0,
        ]);

        // Test stock availability check via service
        $service = new FinishedGoodsConversionService();
        $stockCheck = $service->checkCategoryStockAvailability($this->category->id, 10);

        $this->assertTrue($stockCheck['canProceed']);
        $this->assertCount(2, $stockCheck['mfgStock']);

        // Test Conversion
        $fgBatch = $service->convertCategoryToFinishedGoods([
            'category_id' => $this->category->id,
            'target_qty' => 10,
            'design_type' => 'new',
            'design_id' => '5934',
        ]);

        $this->assertDatabaseHas('finished_goods_batches', [
            'id' => $fgBatch->id,
            'front_end_product_id' => $feProduct->id,
            'converted_qty' => 10,
            'design_id' => '5934',
        ]);

        // Verify deducts
        $this->assertDatabaseHas('finished_goods_batch_items', [
            'finished_goods_batch_id' => $fgBatch->id,
            'quantity_used' => 10,
        ]);

        $this->assertDatabaseHas('products', [
            'title' => '5934 Bedding',
            'stock_quantity' => 10,
        ]);
    }

    public function test_conversion_hub_livewire_component_renders()
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Admin\Production\FinishedGoodsConversionHub::class)
            ->assertStatus(200)
            ->assertSee('Finished Goods Conversion');
    }
}
