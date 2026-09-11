<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Storage;

class ProductMedia extends Model
{
    protected $table = 'product_media';

    protected $fillable = [
        'product_id',
        'file_path',
        'thumbnail_path',
        'file_type',
        'mime_type',
        'size',
        'sort_order',
        'is_primary',
        'alt_text',
    ];

    protected $casts = [
        'size' => 'integer',
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
    ];

    protected $appends = [
        'thumbnail_url',
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->file_path)) {
            return url('/images/product-placeholder.svg');
        }

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        return url(Storage::url($this->file_path));
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!empty($this->thumbnail_path)) {
            if (str_starts_with($this->thumbnail_path, 'http://') || str_starts_with($this->thumbnail_path, 'https://')) {
                return $this->thumbnail_path;
            }

            if (Storage::disk('public')->exists($this->thumbnail_path)) {
                return url(Storage::url($this->thumbnail_path));
            }
        }

        return $this->image_url;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
