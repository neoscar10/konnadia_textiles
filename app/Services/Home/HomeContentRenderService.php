<?php

namespace App\Services\Home;

use App\Models\HomeContentSection;
use App\Models\HomeContentItem;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Services\Portal\ProductCatalogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomeContentRenderService
{
    protected ProductCatalogService $catalogService;
    protected HomeContentMediaService $mediaService;

    public function __construct(
        ProductCatalogService $catalogService,
        HomeContentMediaService $mediaService
    ) {
        $this->catalogService = $catalogService;
        $this->mediaService = $mediaService;
    }

    /**
     * Get final ordered dynamic content sections for the customer portal.
     */
    public function getHomeContentForCustomer(?User $user = null): array
    {
        // Cache sections briefly (5 mins) to optimize DB hits
        $sections = Cache::remember('home_content_sections_active', 300, function () {
            return HomeContentSection::currentlyVisible()
                ->ordered()
                ->with(['items' => function ($q) {
                    $q->where('is_active', true)->orderBy('sort_order');
                }])
                ->get();
        });

        $formatted = [];
        foreach ($sections as $section) {
            $formattedSection = $this->formatSection($section, $user);
            if (!empty($formattedSection)) {
                $formatted[] = $formattedSection;
            }
        }

        return $formatted;
    }

    /**
     * Format an individual home content section based on type.
     */
    public function formatSection(HomeContentSection $section, ?User $user = null): array
    {
        return match ($section->type) {
            'banner' => $this->formatBanner($section, $user),
            'banner_slider' => $this->formatBannerSlider($section, $user),
            'image_text_card' => $this->formatImageTextCard($section, $user),
            'category_slider' => $this->formatCategorySlider($section, $user),
            'product_slider' => $this->formatProductSlider($section, $user),
            'image_slider' => $this->formatImageSlider($section, $user),
            default => [],
        };
    }

    /**
     * Format banner section.
     */
    public function formatBanner(HomeContentSection $section, ?User $user = null): array
    {
        $items = [];
        foreach ($section->items as $item) {
            $items[] = [
                'id' => $item->id,
                'type' => 'banner',
                'title' => $item->title,
                'subtitle' => $item->subtitle,
                'cta_label' => $item->cta_label,
                'image_url' => $item->image_path ? $this->mediaService->getUrl($item->image_path) : null,
                'image_alt' => $item->image_alt ?? 'Banner',
                'link' => $this->resolveLink($item),
            ];
        }

        if (empty($items)) {
            return [];
        }

        return [
            'id' => $section->id,
            'type' => 'banner',
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'sort_order' => $section->sort_order,
            'display_style' => $section->display_style,
            'items' => $items,
        ];
    }

    /**
     * Format banner slider section.
     */
    public function formatBannerSlider(HomeContentSection $section, ?User $user = null): array
    {
        $items = [];
        foreach ($section->items as $item) {
            $items[] = [
                'id' => $item->id,
                'image_url' => $item->image_path ? $this->mediaService->getUrl($item->image_path) : null,
                'image_alt' => $item->image_alt ?? 'Banner Slide',
                'cta_label' => $item->cta_label,
                'link' => $this->resolveLink($item),
            ];
        }

        if (empty($items)) {
            return [];
        }

        return [
            'id' => $section->id,
            'type' => 'banner_slider',
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'sort_order' => $section->sort_order,
            'display_style' => $section->display_style,
            'items' => $items,
        ];
    }

    /**
     * Format image text card section.
     */
    public function formatImageTextCard(HomeContentSection $section, ?User $user = null): array
    {
        $item = $section->items->first();
        if (!$item) {
            return [];
        }

        return [
            'id' => $section->id,
            'type' => 'image_text_card',
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'sort_order' => $section->sort_order,
            'image_url' => $item->image_path ? $this->mediaService->getUrl($item->image_path) : null,
            'image_alt' => $item->image_alt ?? 'Card Image',
            'markdown' => $item->metadata['markdown'] ?? '',
            'alignment' => $item->metadata['alignment'] ?? 'left',
        ];
    }

    /**
     * Format category slider section.
     */
    public function formatCategorySlider(HomeContentSection $section, ?User $user = null): array
    {
        $categoryIds = $section->items->pluck('category_id')->filter()->toArray();
        if (empty($categoryIds)) {
            return [];
        }

        // Fetch active products in these categories
        $products = Product::where('is_active', true)
            ->whereHas('categories', function($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            })
            ->with(['media', 'primaryMedia', 'customerLevelPrices', 'units'])
            ->take($section->display_limit ?? 10)
            ->get();

        $items = [];
        foreach ($products as $product) {
            // Format card using catalog service
            $formattedCard = $this->catalogService->formatProductCard($product, $user);

            $items[] = [
                'id' => $product->id,
                'type' => 'product',
                'product' => $formattedCard,
                'link' => [
                    'type' => 'product',
                    'url' => route('customer.products.show', ['slug' => $product->slug]),
                    'target' => '_self',
                ],
            ];
        }

        if (empty($items)) {
            return [];
        }

        return [
            'id' => $section->id,
            'type' => 'category_slider',
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'sort_order' => $section->sort_order,
            'items' => $items,
        ];
    }

    /**
     * Format product slider section.
     */
    public function formatProductSlider(HomeContentSection $section, ?User $user = null): array
    {
        $items = [];
        foreach ($section->items as $item) {
            $product = $item->product;
            if (!$product || !$product->is_active) {
                continue;
            }

            // Load relations needed for formatting
            $product->load(['media', 'primaryMedia', 'customerLevelPrices', 'units']);

            // Format card using catalog service
            $formattedCard = $this->catalogService->formatProductCard($product, $user);

            $items[] = [
                'id' => $item->id,
                'type' => 'product',
                'product' => $formattedCard,
                'link' => $this->resolveLink($item),
            ];
        }

        if (empty($items)) {
            return [];
        }

        return [
            'id' => $section->id,
            'type' => 'product_slider',
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'sort_order' => $section->sort_order,
            'display_style' => $section->display_style ?? 'cards',
            'items' => $items,
        ];
    }

    /**
     * Format image slider section.
     */
    public function formatImageSlider(HomeContentSection $section, ?User $user = null): array
    {
        $items = [];
        foreach ($section->items as $item) {
            $items[] = [
                'id' => $item->id,
                'type' => 'image',
                'title' => $item->title,
                'subtitle' => $item->subtitle,
                'cta_label' => $item->cta_label ?? 'Shop Collection',
                'image_url' => $item->image_path ? $this->mediaService->getUrl($item->image_path) : null,
                'image_alt' => $item->image_alt ?? $item->title ?? 'Slide',
                'link' => $this->resolveLink($item),
            ];
        }

        if (empty($items)) {
            return [];
        }

        return [
            'id' => $section->id,
            'type' => 'image_slider',
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'sort_order' => $section->sort_order,
            'display_style' => $section->display_style ?? 'hero',
            'items' => $items,
        ];
    }

    /**
     * Resolve the B2B action link configuration for an item.
     */
    public function resolveLink(HomeContentItem $item): array
    {
        $linkType = $item->link_type ?? 'none';
        
        switch ($linkType) {
            case 'category':
                $category = $item->linkCategory ?? $item->category;
                if ($category) {
                    $slug = Str::slug($category->name);
                    return [
                        'type' => 'category',
                        'category_id' => $category->id,
                        'category_slug' => $slug,
                        'url' => route('customer.products.index', ['category' => $slug]),
                        'target' => '_self',
                    ];
                }
                break;

            case 'product':
                $product = $item->linkProduct ?? $item->product;
                if ($product) {
                    return [
                        'type' => 'product',
                        'product_id' => $product->id,
                        'product_slug' => $product->slug,
                        'url' => route('customer.products.show', ['slug' => $product->slug]),
                        'target' => '_self',
                    ];
                }
                break;

            case 'url':
                if (!empty($item->external_url)) {
                    return [
                        'type' => 'url',
                        'url' => $item->external_url,
                        'target' => '_blank',
                    ];
                }
                break;
        }

        return [
            'type' => 'none',
            'url' => null,
        ];
    }
}
