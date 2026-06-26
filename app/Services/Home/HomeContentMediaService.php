<?php

namespace App\Services\Home;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class HomeContentMediaService
{
    /**
     * Store uploaded file on the public disk.
     */
    public function storeImage(UploadedFile $file, string $subDir = 'banners'): string
    {
        return $file->store("home-content/{$subDir}", 'public');
    }

    /**
     * Delete file from storage.
     */
    public function deleteImage(string $filePath): void
    {
        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
    }

    /**
     * Generate absolute URL from relative path.
     */
    public function getUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return Storage::disk('public')->url($path);
    }
}
