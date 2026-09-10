<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\FrontEndProduct;
use App\Models\ManufacturingProduct;
use App\Models\ProductionJob;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Livewire\Admin\Production\FrontEndProductIndexPage;
use App\Livewire\Admin\Production\FinishedGoodsConversionHub;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeafCategoryConversionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
    }

    /** @test */
    public function frontend_products_page_lists_leaf_categories_and_saves_configuration_without_sku()
    {
        $category = Category::create([
            'name' => 'ROYAL TOUCH 108"',
            'slug' => 'royal-touch-108',
            'is_leaf' => true,
            'is_active' => true,
        ]);

        $mfgProduct = ManufacturingProduct::create([
            'name' => 'King KTC Bedsheet',
            'code' => 'MP-KTC-01',
            'status' => 'active',
        ]);

        $rawCat = RawMaterialCategory::firstOrCreate(
            ['code' => 'CAT-SUB'],
            ['name' => 'Subsidiary & Packaging', 'unit_type' => 'other']
        );

        $pkgMaterial = RawMaterial::create([
            'name' => 'PVC Bag Packaging',
            'code' => 'PKG-PVC-01',
            'raw_material_category_id' => $rawCat->id,
            'unit_type' => 'other',
            'status' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(FrontEndProductIndexPage::class)
            ->assertSee('ROYAL TOUCH 108"')
            ->assertSee('Not Configured')
            ->call('configureCategory', $category->id)
            ->set('mfgRows', [
                ['manufacturing_product_id' => $mfgProduct->id, 'quantity' => 1]
            ])
            ->set('pkgRows', [
                ['raw_material_id' => $pkgMaterial->id, 'quantity' => 1]
            ])
            ->call('saveCategoryConfiguration')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('front_end_products', [
            'category_id' => $category->id,
            'name' => 'ROYAL TOUCH 108"',
        ]);

        $feProduct = FrontEndProduct::where('category_id', $category->id)->first();
        $this->assertCount(1, $feProduct->components);
        $this->assertCount(1, $feProduct->packagingItems);
    }

    /** @test */
    public function finished_goods_conversion_prefills_product_title_with_design_id_and_leaf_category()
    {
        $category = Category::create([
            'name' => 'Royal Touch 108"',
            'slug' => 'royal-touch-108-test',
            'is_leaf' => true,
            'is_active' => true,
        ]);

        $mfgProduct = ManufacturingProduct::create([
            'name' => 'King KTC Bedsheet',
            'code' => 'MP-KTC-02',
            'status' => 'active',
        ]);

        // Create configured FrontEndProduct for this category
        $feProduct = FrontEndProduct::create([
            'category_id' => $category->id,
            'name' => 'Royal Touch 108"',
            'sku' => 'CAT-CFG-' . $category->id,
            'leaf_category_name' => 'Royal Touch 108"',
            'is_active' => true,
        ]);

        $feProduct->components()->create([
            'manufacturing_product_id' => $mfgProduct->id,
            'quantity' => 1,
        ]);

        // Create completed production job with stock
        $job = ProductionJob::create([
            'job_code' => 'JOB-2026-9999',
            'production_batch_id' => 'PB-9999',
            'manufacturing_product_id' => $mfgProduct->id,
            'target_quantity' => 50,
            'status' => 'completed',
            'converted_quantity' => 0,
        ]);

        Livewire::actingAs($this->admin)
            ->test(FinishedGoodsConversionHub::class)
            ->call('openWizardModal')
            ->set('selectedCategoryId', $category->id)
            ->set('produceQty', 10)
            ->set('designType', 'new')
            ->call('goToStep2')
            ->set('designId', '5934')
            ->assertSee('5934 Royal Touch 108"')
            ->assertSee('FG-5934-')
            ->call('confirmAndExecuteConversion');

        $this->assertDatabaseHas('products', [
            'title' => '5934 Royal Touch 108"',
            'stock_quantity' => 10,
        ]);

        $createdProduct = Product::where('title', '5934 Royal Touch 108"')->first();
        $this->assertTrue($createdProduct->categories->contains($category->id));

        $batch = \App\Models\FinishedGoodsBatch::latest()->first();
        $this->assertNotNull($batch);
        $this->assertStringContainsString('FG-5934-', $batch->barcode);

        // Test barcode sticker printing and scanning search
        Livewire::actingAs($this->admin)
            ->test(FinishedGoodsConversionHub::class)
            ->call('openPrintBarcodeModal', $batch->id)
            ->assertSet('showPrintModal', true)
            ->assertSee('Print Barcode Stickers')
            ->assertSee($batch->barcode)
            ->set('barcodeQuery', $batch->barcode)
            ->call('searchBarcodeFromInput')
            ->assertSet('showAuditModal', true)
            ->assertSee('Product Audit Breakdown')
            ->assertSee($batch->barcode);
    }
}
