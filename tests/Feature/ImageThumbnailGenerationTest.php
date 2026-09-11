<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\Catalog\ProductMediaService;
use App\Services\Catalog\ImageThumbnailService;
use App\Services\Portal\ProductCatalogService;
use App\Http\Resources\Api\V1\ProductCardResource;
use App\Http\Resources\Api\V1\AdminProductResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageThumbnailGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_product_media_service_generates_thumbnail_on_image_upload(): void
    {
        $product = Product::factory()->create([
            'title' => 'Test Silk Saree',
            'sku' => 'KT-SAREE-001',
        ]);

        $fakeImage = UploadedFile::fake()->image('saree-highres.jpg', 1200, 1200);

        $service = app(ProductMediaService::class);
        $service->storeProductMedia($product, [$fakeImage]);

        $media = ProductMedia::where('product_id', $product->id)->first();

        $this->assertNotNull($media);
        $this->assertNotNull($media->thumbnail_path);
        Storage::disk('public')->assertExists($media->file_path);
        Storage::disk('public')->assertExists($media->thumbnail_path);
        $this->assertStringContainsString('products/thumbnails/', $media->thumbnail_path);
    }

    public function test_product_media_and_product_accessors_return_correct_urls(): void
    {
        $product = Product::factory()->create();

        $media = ProductMedia::create([
            'product_id' => $product->id,
            'file_path' => 'products/sample.jpg',
            'thumbnail_path' => 'products/thumbnails/thumb_sample.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        Storage::disk('public')->put('products/sample.jpg', 'fake-image-content');
        Storage::disk('public')->put('products/thumbnails/thumb_sample.jpg', 'fake-thumb-content');

        $this->assertStringContainsString('products/thumbnails/thumb_sample.jpg', $media->thumbnail_url);
        $this->assertStringContainsString('products/sample.jpg', $media->image_url);

        $this->assertStringContainsString('products/thumbnails/thumb_sample.jpg', $product->thumbnail_url);
        $this->assertStringContainsString('products/sample.jpg', $product->image_url);
    }

    public function test_artisan_command_generates_missing_thumbnails(): void
    {
        $product = Product::factory()->create();

        // Create dummy image file in fake storage
        $originalPath = 'products/existing-image.jpg';
        $image = imagecreatetruecolor(500, 500);
        ob_start();
        imagejpeg($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($originalPath, $imageData);

        $media = ProductMedia::create([
            'product_id' => $product->id,
            'file_path' => $originalPath,
            'thumbnail_path' => null,
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => strlen($imageData),
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        $this->assertNull($media->thumbnail_path);

        $this->artisan('images:generate-thumbnails')
             ->assertExitCode(0);

        $media->refresh();
        $this->assertNotNull($media->thumbnail_path);
        Storage::disk('public')->assertExists($media->thumbnail_path);
    }

    public function test_deleting_media_removes_both_original_and_thumbnail_files(): void
    {
        $product = Product::factory()->create();

        $originalPath = 'products/to-delete.jpg';
        $thumbPath = 'products/thumbnails/thumb_to-delete.jpg';

        Storage::disk('public')->put($originalPath, 'orig');
        Storage::disk('public')->put($thumbPath, 'thumb');

        $media = ProductMedia::create([
            'product_id' => $product->id,
            'file_path' => $originalPath,
            'thumbnail_path' => $thumbPath,
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 10,
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        $service = app(ProductMediaService::class);
        $service->deleteMedia($media);

        $this->assertDatabaseMissing('product_media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($originalPath);
        Storage::disk('public')->assertMissing($thumbPath);
    }

    public function test_card_resource_and_admin_resource_serve_thumbnail_url(): void
    {
        $product = Product::factory()->create(['title' => 'Silk Dupatta']);

        ProductMedia::create([
            'product_id' => $product->id,
            'file_path' => 'products/dupatta.jpg',
            'thumbnail_path' => 'products/thumbnails/thumb_dupatta.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 200,
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        Storage::disk('public')->put('products/dupatta.jpg', 'img');
        Storage::disk('public')->put('products/thumbnails/thumb_dupatta.jpg', 'thumb');

        $cardData = (new ProductCardResource($product))->toArray(request());
        $this->assertStringContainsString('products/thumbnails/thumb_dupatta.jpg', $cardData['primary_image_url']);

        $adminData = (new AdminProductResource($product))->toArray(request());
        $this->assertStringContainsString('products/thumbnails/thumb_dupatta.jpg', $adminData['primary_image_url']);
    }

    public function test_product_detail_retains_full_resolution_image_url(): void
    {
        $product = Product::factory()->create(['title' => 'Embroidered Kurta', 'is_active' => true]);

        ProductMedia::create([
            'product_id' => $product->id,
            'file_path' => 'products/kurta-hd.jpg',
            'thumbnail_path' => 'products/thumbnails/thumb_kurta.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 500,
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        Storage::disk('public')->put('products/kurta-hd.jpg', 'hd');
        Storage::disk('public')->put('products/thumbnails/thumb_kurta.jpg', 'thumb');

        $catalogService = app(ProductCatalogService::class);
        $detail = $catalogService->formatProductDetail($product, null);

        $this->assertNotEmpty($detail['media']);
        $firstMedia = $detail['media'][0];
        $this->assertStringContainsString('products/kurta-hd.jpg', $firstMedia['url']);
        $this->assertStringContainsString('products/thumbnails/thumb_kurta.jpg', $firstMedia['thumbnail_url']);
    }
}
