{{--
    Reusable infinite-scroll CSS ticker for product cards.

    Required variables:
        $sectionTitle   string  — Section heading text
        $products       array   — formatProductCard() shaped items
    Optional:
        $emptyMessage   string  — Shown when $products is empty
        $tickerId       string  — Unique suffix so multiple @keyframes don't clash (default: md5 of title)
--}}
@php
    $tickerId     = $tickerId ?? Str::slug($sectionTitle);
    $emptyMsg     = $emptyMessage ?? 'No products available at the moment.';
    $productCount = count($products);
    // Each card slot = 288px wide + 24px gap = 312px
    // Target scroll speed ≈ 55 px/s
    $oneSetPx     = $productCount * 312;
    $durationS    = max(14, round($oneSetPx / 55));
@endphp

<div>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-base font-extrabold text-[#001229]">{{ $sectionTitle }}</h3>
        @if($productCount > 0)
            <span class="text-xs text-slate-400 font-medium select-none">{{ $productCount }} Products</span>
        @endif
    </div>

    @if($productCount === 0)
        <div class="bg-white border border-outline-variant/20 rounded-xl p-8 text-center text-slate-500 text-sm">
            {{ $emptyMsg }}
        </div>
    @else
        <style>
            @keyframes kt-ticker-{{ $tickerId }} {
                0%   { transform: translateX(0); }
                100% { transform: translateX(-{{ $oneSetPx }}px); }
            }
            .kt-ticker-{{ $tickerId }} {
                animation: kt-ticker-{{ $tickerId }} {{ $durationS }}s linear infinite;
                will-change: transform;
            }
            .kt-ticker-{{ $tickerId }}.paused {
                animation-play-state: paused;
            }
        </style>

        <div
            class="relative overflow-hidden"
            x-data="{ paused: false }"
            @mouseenter="paused = true"
            @mouseleave="paused = false"
        >
            {{-- Left fade edge --}}
            <div class="absolute left-0 top-0 bottom-4 w-16 bg-gradient-to-r from-slate-50 to-transparent z-10 pointer-events-none"></div>
            {{-- Right fade edge --}}
            <div class="absolute right-0 top-0 bottom-4 w-16 bg-gradient-to-l from-slate-50 to-transparent z-10 pointer-events-none"></div>

            {{-- Infinite-scroll track --}}
            <div
                class="kt-ticker-{{ $tickerId }} flex pb-4"
                style="gap: 24px; width: max-content;"
                :class="paused ? 'paused' : ''"
            >
                {{-- Original set --}}
                @foreach($products as $product)
                    <div class="flex-shrink-0" style="width: 288px;">
                        <x-customer.product-card
                            :title="$product['title']"
                            :sku="$product['sku']"
                            :price="$product['price']['customer_price']"
                            :moq="$product['minimum_order_quantity']"
                            :image="$product['primary_image_url']"
                            :inStock="$product['stock']['status'] === 'in_stock'"
                            :url="route('customer.products.show', $product['slug'])"
                            :productId="$product['id']"
                        />
                    </div>
                @endforeach
                {{-- Duplicate set for seamless loop --}}
                @foreach($products as $product)
                    <div class="flex-shrink-0" style="width: 288px;">
                        <x-customer.product-card
                            :title="$product['title']"
                            :sku="$product['sku']"
                            :price="$product['price']['customer_price']"
                            :moq="$product['minimum_order_quantity']"
                            :image="$product['primary_image_url']"
                            :inStock="$product['stock']['status'] === 'in_stock'"
                            :url="route('customer.products.show', $product['slug'])"
                            :productId="$product['id']"
                        />
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
