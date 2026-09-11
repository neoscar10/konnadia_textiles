<?php

namespace App\Services\Catalog;

use Illuminate\Support\Facades\Storage;
use Exception;

class ImageThumbnailService
{
    /**
     * Generate a thumbnail for an image stored in public disk.
     *
     * @param string $originalPath Relative path in public storage disk (e.g. 'products/abc.jpg')
     * @param int $maxWidth Max width/height in pixels
     * @param int $quality JPEG/WebP quality (0-100)
     * @return string|null Relative path of generated thumbnail or null on failure
     */
    public function generateThumbnail(string $originalPath, int $maxWidth = 400, int $quality = 85): ?string
    {
        try {
            $disk = Storage::disk('public');

            if (!$disk->exists($originalPath)) {
                return null;
            }

            $fullPath = $disk->path($originalPath);
            if (!file_exists($fullPath)) {
                return null;
            }

            $imageInfo = @getimagesize($fullPath);
            if (!$imageInfo) {
                return null;
            }

            [$origWidth, $origHeight, $imageType] = $imageInfo;

            if ($origWidth <= 0 || $origHeight <= 0) {
                return null;
            }

            // Calculate scale and target dimensions while retaining aspect ratio
            $scale = min($maxWidth / $origWidth, $maxWidth / $origHeight, 1.0);
            $targetWidth = max(1, (int) round($origWidth * $scale));
            $targetHeight = max(1, (int) round($origHeight * $scale));

            // Load source GD image resource
            $sourceImage = match ($imageType) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($fullPath),
                IMAGETYPE_PNG => @imagecreatefrompng($fullPath),
                IMAGETYPE_WEBP => @imagecreatefromwebp($fullPath),
                IMAGETYPE_GIF => @imagecreatefromgif($fullPath),
                default => null,
            };

            if (!$sourceImage) {
                return null;
            }

            // Create target GD canvas
            $targetCanvas = imagecreatetruecolor($targetWidth, $targetHeight);

            // Handle transparency for PNG / WEBP / GIF
            if (in_array($imageType, [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
                imagealphablending($targetCanvas, false);
                imagesavealpha($targetCanvas, true);
                $transparent = imagecolorallocatealpha($targetCanvas, 255, 255, 255, 127);
                imagefilledrectangle($targetCanvas, 0, 0, $targetWidth, $targetHeight, $transparent);
            }

            // Resample original image into canvas
            imagecopyresampled(
                $targetCanvas,
                $sourceImage,
                0, 0, 0, 0,
                $targetWidth,
                $targetHeight,
                $origWidth,
                $origHeight
            );

            // Determine output filename
            $filename = pathinfo($originalPath, PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'jpg';
            }

            $thumbDirectory = 'products/thumbnails';
            if (!$disk->exists($thumbDirectory)) {
                $disk->makeDirectory($thumbDirectory);
            }

            $thumbFilename = 'thumb_' . $filename . '_' . md5($originalPath) . '.' . $ext;
            $thumbRelativePath = $thumbDirectory . '/' . $thumbFilename;
            $thumbFullPath = $disk->path($thumbRelativePath);

            // Save thumbnail image file
            $saved = match ($ext) {
                'png' => @imagepng($targetCanvas, $thumbFullPath, (int) round((100 - $quality) / 10)),
                'webp' => function_exists('imagewebp') ? @imagewebp($targetCanvas, $thumbFullPath, $quality) : @imagejpeg($targetCanvas, $thumbFullPath, $quality),
                default => @imagejpeg($targetCanvas, $thumbFullPath, $quality),
            };

            // Free GD memory
            imagedestroy($sourceImage);
            imagedestroy($targetCanvas);

            if ($saved && file_exists($thumbFullPath)) {
                return $thumbRelativePath;
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}
