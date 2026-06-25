<div>
    <!-- Page title -->
    <x-customer.page-title 
        title="Products Catalog" 
        subtitle="Browse our current in-stock and upcoming textile fabric selections."
        :breadcrumbs="['Home' => route('customer.dashboard'), 'Products' => '#']"
    />

    <!-- Mobile horizontal scrolling filter chips -->
    <div class="lg:hidden flex items-center gap-2 overflow-x-auto hide-scrollbar mb-2 pb-1">
        <button wire:click="selectCategory('')" class="px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap {{ empty($category) ? 'bg-[#001229] text-white' : 'bg-white border border-outline-variant/30 text-slate-700' }}">All Items</button>
        @foreach($categoriesList as $cat)
            @if(!$cat['parent_id'])
                @php
                    $isTopActive = (string)$category === (string)$cat['id'] || (collect($categoriesList)->firstWhere('id', $category)['parent_id'] ?? null) == $cat['id'];
                @endphp
                <button wire:click="selectCategory('{{ $cat['id'] }}')" class="px-4 py-1.5 rounded-full text-xs font-medium whitespace-nowrap {{ $isTopActive ? 'bg-[#001229] text-white' : 'bg-white border border-outline-variant/30 text-slate-700' }}">
                    {{ $cat['name'] }}
                </button>
            @endif
        @endforeach
    </div>

    <!-- Mobile subcategory scrolling chips -->
    @if($category)
        @php
            $currentCat = collect($categoriesList)->firstWhere('id', $category);
            $parentId = $currentCat['parent_id'] ?? $currentCat['id'];
            $subCategories = collect($categoriesList)->where('parent_id', $parentId);
        @endphp
        @if($subCategories->isNotEmpty())
            <div class="lg:hidden flex items-center gap-2 overflow-x-auto hide-scrollbar mb-4 pb-2">
                <span class="text-[10px] uppercase font-bold text-slate-400 pl-1 mr-1">Sub:</span>
                @php
                    $parentCat = collect($categoriesList)->firstWhere('id', $parentId);
                @endphp
                <button wire:click="selectCategory('{{ $parentId }}')" class="px-3 py-1 rounded-full text-[11px] font-medium whitespace-nowrap {{ (string)$category === (string)$parentId ? 'bg-[#001229] text-white' : 'bg-slate-100 text-slate-600' }}">
                    All {{ $parentCat['name'] }}
                </button>
                @foreach($subCategories as $sub)
                    <button wire:click="selectCategory('{{ $sub['id'] }}')" class="px-3 py-1 rounded-full text-[11px] font-medium whitespace-nowrap {{ (string)$category === (string)$sub['id'] ? 'bg-[#001229] text-white' : 'bg-slate-100 text-slate-600' }}">
                        {{ $sub['name'] }}
                    </button>
                @endforeach
            </div>
        @endif
    @endif

    <!-- Main catalog layout: Sidebar filters + Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Desktop Left Sidebar Filters -->
        <aside class="hidden lg:block lg:col-span-1 sticky top-6 max-h-[calc(100vh-3rem)] overflow-y-auto pr-2 space-y-6">
            <x-customer.card bodyClass="p-5 space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between pb-3 border-b border-outline-variant/20">
                    <span class="font-bold text-[#001229] text-sm">Filters</span>
                    <button wire:click="clearFilters" class="text-xs text-gold font-bold hover:underline">Clear All</button>
                </div>

                <!-- Categories -->
                <div>
                    <h5 class="text-xs font-bold text-[#001229] uppercase tracking-wider mb-3">Categories</h5>
                    <div class="space-y-1">
                        <!-- All Categories -->
                        <div class="flex items-center justify-between py-1.5 px-2 rounded-lg hover:bg-slate-50 transition-colors {{ $category === '' ? 'bg-slate-50 font-bold' : '' }}">
                            <button wire:click="selectCategory('')" class="flex-1 text-left text-sm text-slate-700 hover:text-slate-900 cursor-pointer">
                                All Categories
                            </button>
                            @if($category === '')
                                <span class="material-symbols-outlined text-xs text-gold">check</span>
                            @endif
                        </div>

                        @foreach($categoriesList as $cat)
                            @if(!$cat['parent_id'])
                                @include('partials.customer.category-tree-item', ['cat' => $cat, 'level' => 0])
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Availability filter -->
                <div>
                    <h5 class="text-xs font-bold text-[#001229] uppercase tracking-wider mb-3">Availability</h5>
                    <div class="space-y-2.5">
                        <label class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 cursor-pointer">
                            <input type="radio" wire:model.live="availability" value="all" class="rounded-full border-outline-variant text-[#001229] focus:ring-gold focus:ring-offset-0">
                            <span>All Products</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 cursor-pointer">
                            <input type="radio" wire:model.live="availability" value="in_stock" class="rounded-full border-outline-variant text-[#001229] focus:ring-gold focus:ring-offset-0">
                            <span>In Stock Only</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 cursor-pointer">
                            <input type="radio" wire:model.live="availability" value="out_of_stock" class="rounded-full border-outline-variant text-[#001229] focus:ring-gold focus:ring-offset-0">
                            <span>Out of Stock Only</span>
                        </label>
                    </div>
                </div>

                <!-- Price Range -->
                <div>
                    <h5 class="text-xs font-bold text-[#001229] uppercase tracking-wider mb-3">Max Price: ₹{{ number_format($max_price) }}</h5>
                    <div class="space-y-2">
                        <input type="range" min="100" max="10000" step="100" wire:model.live.debounce.150ms="max_price" class="w-full accent-gold bg-slate-100 rounded-lg appearance-none h-1.5 cursor-pointer">
                        <div class="flex justify-between text-xs text-slate-500 font-bold">
                            <span>₹100</span>
                            <span>₹10,000</span>
                        </div>
                    </div>
                </div>
            </x-customer.card>
        </aside>

        <!-- Right Side: Search bar, sorting & Products Grid -->
        <div class="lg:col-span-3 space-y-6">
            <!-- Search & Sorting Header -->
            <div class="bg-white border border-outline-variant/30 rounded-xl shadow-ambient p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <!-- Search Input -->
                <div class="relative flex-1 max-w-md">
                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <span class="material-symbols-outlined text-xl">search</span>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search catalog..." class="w-full bg-slate-50 text-[#001229] placeholder-slate-400 pl-10 pr-4 py-2 rounded-lg text-sm border border-outline-variant/20 focus:outline-none focus:ring-2 focus:ring-gold">
                </div>

                <!-- Sorting & Items count -->
                <div class="flex items-center justify-between md:justify-end gap-4">
                    <span class="text-xs text-slate-500 font-medium whitespace-nowrap">Showing {{ $products->count() }} products</span>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500 font-medium">Sort:</span>
                        <select wire:model.live="sort" class="bg-slate-50 text-slate-700 text-xs font-bold border border-outline-variant/30 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-gold">
                            <option value="newest">Newest Arrivals</option>
                            <option value="price_low">Price: Low to High</option>
                            <option value="price_high">Price: High to Low</option>
                            <option value="name_asc">Name: A to Z</option>
                            <option value="availability">Availability</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Products Grid (3 columns on desktop, 1 on mobile) -->
            @if($products->isEmpty())
                <div class="py-12">
                    <x-customer.empty-state 
                        icon="search_off"
                        title="No products match your filters"
                        description="Try adjusting your search query, selecting another category, or clearing the active filters."
                        actionText="Clear Filters"
                        actionUrl="javascript:void(0);"
                        wire:click="clearFilters"
                    />
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($products as $prod)
                        <x-customer.product-card 
                            :title="$prod['title']" 
                            :sku="$prod['sku']" 
                            :price="$prod['price']['customer_price']" 
                            :moq="$prod['minimum_order_quantity']" 
                            :image="$prod['primary_image_url']" 
                            :inStock="$prod['stock']['status'] !== 'out_of_stock'"
                            :url="route('customer.products.show', $prod['slug'])"
                            :productId="$prod['id']"
                        />
                    @endforeach
                </div>

                <!-- Pagination Visual -->
                <div class="pt-6">
                    {{ $products->links() }}
                </div>
            @endif
        </div>

    </div>

    <!-- Variant Selection Modal Overlay -->
    @if($showQuickAddModal && $quickAddProduct)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-4 z-50">
            <div class="bg-white border border-outline-variant/30 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest select-none">{{ $quickAddProduct->brand }}</span>
                        <h3 class="text-base font-extrabold text-[#001229]">{{ $quickAddProduct->title }}</h3>
                    </div>
                    <button type="button" wire:click="$set('showQuickAddModal', false)" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-6 overflow-y-auto max-h-[70vh]">
                    <!-- Price and stock status -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-outline-variant/10 flex justify-between items-center">
                        <div>
                            <span class="text-[10px] text-slate-400 font-semibold uppercase block select-none">Price per piece</span>
                            <div class="flex items-baseline gap-2">
                                <span class="text-xl font-black text-[#001229]">₹{{ number_format($quickAddPricePerPiece, 2) }}</span>
                                @if($quickAddDiscountPercentage > 0)
                                    <span class="text-xs text-slate-400 line-through">₹{{ number_format($quickAddEffectiveBasePrice, 2) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $quickAddStockStatus === 'out_of_stock' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/50' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/50' }}">
                                {{ $quickAddStockLabel }}
                            </span>
                        </div>
                    </div>

                    <!-- Dynamic Variation Selectors -->
                    @foreach($quickAddVariations as $group)
                        <div class="space-y-2">
                            <h4 class="text-xs font-bold text-[#001229] uppercase tracking-wider">Select {{ $group['name'] }}</h4>
                            <div class="flex flex-wrap items-center gap-2">
                                @foreach($group['values'] as $val)
                                    @if($group['display_type'] === 'color')
                                        <button type="button" 
                                                wire:click="selectQuickAddVariationValue('{{ $group['name'] }}', '{{ $val['value'] }}')" 
                                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold border transition-all" 
                                                :class="'{{ $quickAddSelectedValues[$group['name']] ?? '' }}' === '{{ $val['value'] }}' ? 'border-[#001229] ring-2 ring-gold/20' : 'border-outline-variant/30 bg-slate-50/55 hover:border-slate-400'">
                                            <span class="w-4 h-4 rounded-full border border-slate-200" style="background-color: {{ $val['color_hex'] ?? '#ccc' }}"></span>
                                            <span>{{ $val['value'] }}</span>
                                        </button>
                                    @else
                                        <button type="button" 
                                                wire:click="selectQuickAddVariationValue('{{ $group['name'] }}', '{{ $val['value'] }}')" 
                                                class="px-3 py-2 rounded-lg text-xs font-bold border transition-all shadow-sm" 
                                                :class="'{{ $quickAddSelectedValues[$group['name']] ?? '' }}' === '{{ $val['value'] }}' ? 'bg-[#001229] text-white border-[#001229]' : 'bg-white border-outline-variant/40 text-[#001229] hover:border-slate-400'">
                                            {{ $val['value'] }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <!-- Unit Pricing & Conversion Info -->
                    @php
                        $lvl1 = collect($quickAddUnits)->firstWhere('level', 1);
                        $lvl2 = collect($quickAddUnits)->firstWhere('level', 2);
                    @endphp
                    @if($lvl1)
                        <div class="bg-slate-50 border border-outline-variant/10 rounded-xl p-4.5 space-y-2.5">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block select-none">Unit Pricing Details</span>
                            <div class="flex flex-col gap-2 text-xs text-slate-600 font-medium">
                                <div class="flex justify-between items-center">
                                    <span>Base Unit Price ({{ $lvl1['name'] }}):</span>
                                    <span class="font-bold text-slate-900">₹{{ number_format($lvl1['price'], 2) }}</span>
                                </div>
                                @if($quickAddHasLvl2Unit && $lvl2)
                                    <div class="flex justify-between items-center">
                                        <span>Bulk Unit Price ({{ $lvl2['name'] }}):</span>
                                        <span class="font-bold text-slate-900">₹{{ number_format($lvl2['price'], 2) }}</span>
                                    </div>
                                    <div class="text-[11px] text-[#001229] bg-white border border-outline-variant/20 rounded-lg px-3 py-2 font-semibold select-none flex items-center gap-1.5 shadow-sm mt-1">
                                        <span class="material-symbols-outlined text-sm text-gold select-none">info</span>
                                        <span>Relationship: 1 {{ $lvl2['name'] }} = {{ (int)$lvl2['conversion_to_base'] }} {{ $lvl1['name'] }}s</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Quantity Inputs: Dual-unit (Boxes + Pieces simultaneously) --}}
                    <div class="space-y-4">
                        <h5 class="text-sm font-bold text-slate-700">Order Quantity</h5>

                        @php
                            $lvl1 = collect($quickAddUnits)->firstWhere('level', 1);
                            $lvl2 = collect($quickAddUnits)->firstWhere('level', 2);
                            $lvl1Name = $lvl1 ? strtoupper($lvl1['name']) : 'PIECES';
                            $lvl2Name = $lvl2 ? strtoupper($lvl2['name']) : null;
                            $conversionRate = $lvl2 ? (int)$lvl2['conversion_to_base'] : 1;
                        @endphp

                        <div class="flex items-start gap-3">
                            @if($quickAddHasLvl2Unit && $lvl2)
                                {{-- lvl2 (e.g. Boxes) --}}
                                <div class="flex-1">
                                    <label class="text-xs text-slate-400 font-bold uppercase block mb-1">{{ $lvl2Name }}</label>
                                    <div class="flex items-center border border-outline-variant/30 rounded-lg bg-slate-50 p-2">
                                        <input type="number"
                                               wire:model.live.debounce.300ms="quickAddQtyLvl2"
                                               min="0"
                                               class="w-full text-center bg-transparent border-none focus:outline-none focus:ring-0 text-sm font-extrabold text-[#001229] p-0.5"
                                               placeholder="0">
                                    </div>
                                    <span class="text-[10px] text-slate-400 mt-0.5 block text-center">1 {{ ucfirst($lvl2['name']) }} = {{ $conversionRate }} {{ ucfirst($lvl1['name'] ?? 'Pcs') }}s</span>
                                </div>

                                <div class="flex items-end pb-6 pt-5 text-slate-300 font-bold text-sm select-none">+</div>
                            @endif

                            {{-- lvl1 (e.g. Pieces) --}}
                            <div class="flex-1">
                                <label class="text-xs text-slate-400 font-bold uppercase block mb-1">{{ $lvl1Name }}</label>
                                <div class="flex items-center border border-outline-variant/30 rounded-lg bg-slate-50 p-2">
                                    <input type="number"
                                           wire:model.live.debounce.300ms="quickAddQtyLvl1"
                                           min="0"
                                           class="w-full text-center bg-transparent border-none focus:outline-none focus:ring-0 text-sm font-extrabold text-[#001229] p-0.5"
                                           placeholder="0">
                                </div>
                            </div>
                        </div>

                        {{-- Summary row --}}
                        <div class="flex justify-between items-center text-[10px] text-slate-400 font-semibold select-none">
                            @php
                                $totalPiecesDisplay = ($quickAddQtyLvl2 * $conversionRate) + $quickAddQtyLvl1;
                            @endphp
                            @if($quickAddHasLvl2Unit)
                                <span>Total: {{ $totalPiecesDisplay }} Pieces</span>
                            @else
                                <span></span>
                            @endif
                            <span>MOQ: {{ $quickAddMoq }} Pieces</span>
                        </div>
                    </div>

                    <!-- Pricing Summary -->
                    <div class="border-t border-slate-100 pt-4 space-y-2">
                        <div class="flex justify-between text-xs text-slate-500 font-medium">
                            <span>Subtotal</span>
                            <span class="font-bold text-slate-800">₹{{ number_format($quickAddSubtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-500 font-medium">
                            <span>GST</span>
                            <span class="font-bold text-slate-800">₹{{ number_format($quickAddGstAmount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-[#001229] font-extrabold pt-2 border-t border-dashed border-slate-200">
                            <span>Estimated Total</span>
                            <span class="text-[#001229]">₹{{ number_format($quickAddTotal, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50">
                    <button type="button" wire:click="$set('showQuickAddModal', false)" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-700 border border-outline-variant/30 hover:bg-slate-50 transition-colors bg-white shadow-xs">Cancel</button>
                    <button type="button" 
                            wire:click="addVariantToCart"
                            @if(!$quickAddIsPurchasable) disabled @endif
                            class="flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-white transition-colors shadow-sm {{ $quickAddIsPurchasable ? 'bg-[#001229] hover:bg-slate-800' : 'bg-slate-300 cursor-not-allowed' }}">
                        <span class="material-symbols-outlined text-sm">shopping_cart</span> Add to Cart
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
