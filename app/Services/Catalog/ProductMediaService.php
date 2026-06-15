<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariationValue;
use App\Models\ProductVariationValueMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductMediaService
{
    /**
     * Store uploaded file(s) for a product.
     */
    public function storeProductMedia(Product $product, array $uploadedFiles): void
    {
        DB::transaction(function () use ($product, $uploadedFiles) {
            $currentMaxOrder = $product->media()->max('sort_order') ?? 0;
            $hasPrimary = $product->media()->where('is_primary', true)->exists();

            foreach ($uploadedFiles as $index => $file) {
                // If it is a TemporaryUploadedFile or normal UploadedFile
                $path = $file->store('products', 'public');

                ProductMedia::create([
                    'product_id' => $product->id,
                    'file_path' => $path,
                    'file_type' => $this->getFileType($file->getClientMimeType()),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'sort_order' => $currentMaxOrder + $index + 1,
                    'is_primary' => !$hasPrimary && $index === 0,
                ]);
            }
        });
    }

    /**
     * Reorder media gallery for a product.
     */
    public function reorderMedia(Product $product, array $orderedMediaIds): void
    {
        DB::transaction(function () use ($product, $orderedMediaIds) {
            foreach ($orderedMediaIds as $sortOrder => $id) {
                ProductMedia::where('product_id', $product->id)
                    ->where('id', $id)
                    ->update(['sort_order' => $sortOrder]);
            }
        });
    }

    /**
     * Delete media record and delete its storage file.
     */
    public function deleteMedia(ProductMedia $media): void
    {
        DB::transaction(function () use ($media) {
            if (Storage::disk('public')->exists($media->file_path)) {
                Storage::disk('public')->delete($media->file_path);
            }
            
            $product = $media->product;
            $wasPrimary = $media->is_primary;

            $media->delete();

            // If we deleted primary, set another one as primary
            if ($wasPrimary && $product) {
                $next = $product->media()->first();
                if ($next) {
                    $next->update(['is_primary' => true]);
                }
            }
        });
    }

    /**
     * Set a media item as primary cover image.
     */
    public function setPrimary(ProductMedia $media): void
    {
        DB::transaction(function () use ($media) {
            ProductMedia::where('product_id', $media->product_id)
                ->update(['is_primary' => false]);

            $media->update(['is_primary' => true]);
        });
    }

    /**
     * Store uploaded file(s) for a variation value.
     */
    public function storeVariationValueMedia(ProductVariationValue $value, array $uploadedFiles): void
    {
        DB::transaction(function () use ($value, $uploadedFiles) {
            $currentMaxOrder = $value->media()->max('sort_order') ?? 0;

            foreach ($uploadedFiles as $index => $file) {
                $path = $file->store('products/variants', 'public');

                ProductVariationValueMedia::create([
                    'product_variation_value_id' => $value->id,
                    'file_path' => $path,
                    'sort_order' => $currentMaxOrder + $index + 1,
                ]);
            }
        });
    }

    /**
     * Determine file type.
     */
    protected function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        return 'image';
    }
}
