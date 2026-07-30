<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreHomeContentSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:banner,banner_slider,image_slider,category_slider,product_slider,image_text_card',
            'title' => 'nullable|string|max:150',
            'subtitle' => 'nullable|string|max:250',
            'is_active' => 'boolean',
            'display_style' => 'nullable|string|max:50',
            'items_per_view' => 'integer|min:1|max:10',
            'display_limit' => 'integer|min:1|max:50',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'settings' => 'nullable|array',
            'items' => 'nullable|array',
        ];
    }
}
