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
            'items_per_view' => 'nullable|integer|min:1|max:10',
            'display_limit' => 'nullable|integer|min:1|max:50',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'settings' => 'nullable|array',
            'items' => 'nullable|array',
            'items.*.item_type' => 'nullable|string',
            'items.*.title' => 'nullable|string|max:150',
            'items.*.subtitle' => 'nullable|string|max:250',
            'items.*.cta_label' => 'nullable|string|max:50',
            'items.*.image_path' => 'nullable|string|max:500',
            'items.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:15360',
            'items.*.image_alt' => 'nullable|string|max:255',
            'items.*.link_type' => 'nullable|string|in:none,category,product,url',
            'items.*.link_category_id' => 'nullable|integer|exists:categories,id',
            'items.*.link_product_id' => 'nullable|integer|exists:products,id',
            'items.*.external_url' => 'nullable|string|url|max:500',
            'items.*.category_id' => 'nullable|integer|exists:categories,id',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.metadata' => 'nullable|array',
            'items.*.metadata.markdown' => 'nullable|string',
            'items.*.metadata.alignment' => 'nullable|string|in:left,right',
        ];
    }
}
