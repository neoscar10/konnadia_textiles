<?php

namespace App\Enums;

enum HomeContentType: string
{
    case BANNER = 'banner';
    case CATEGORY_SLIDER = 'category_slider';
    case PRODUCT_SLIDER = 'product_slider';
    case IMAGE_SLIDER = 'image_slider';

    public function label(): string
    {
        return match($this) {
            self::BANNER => 'Promotional Banner',
            self::CATEGORY_SLIDER => 'Category Slider',
            self::PRODUCT_SLIDER => 'Product Slider',
            self::IMAGE_SLIDER => 'Image Slider',
        };
    }
}
