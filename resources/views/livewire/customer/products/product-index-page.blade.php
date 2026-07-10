<div x-data="{ mobileFiltersOpen: false }">
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
            $parentId = $currentCat ? ($currentCat['parent_id'] ?? $currentCat['id']) : null;
            $subCategories = $parentId ? collect($categoriesList)->where('parent_id', $parentId) : collect();
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

                <!-- Tags Filter -->
                <div>
                    <h5 class="text-xs font-bold text-[#001229] uppercase tracking-wider mb-3">Filter by Tags</h5>
                    <div class="flex flex-wrap gap-1.5 select-none">
                        @forelse($tagsList as $tag)
                            @php $isSel = in_array($tag->id, $selectedTags); @endphp
                            <button type="button" wire:click="toggleTagFilter({{ $tag->id }})" 
                                    class="px-3 py-1 rounded-full text-xs font-medium border transition-all flex items-center gap-1 cursor-pointer
                                    {{ $isSel 
                                        ? 'bg-gold/15 text-gold border-gold font-semibold' 
                                        : 'bg-slate-50 border-[#cbd5e1] text-slate-600 hover:border-slate-400' }}">
                                @if($isSel)
                                    <span class="material-symbols-outlined text-[12px] font-bold">close</span>
                                @endif
                                <span>{{ $tag->name }}</span>
                            </button>
                        @empty
                            <p class="text-xs text-slate-400 italic">No tags available.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Price Range -->
                @auth
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
                @endauth
            </x-customer.card>
        </aside>

        <!-- Right Side: Search bar, sorting & Products Grid -->
        <div class="lg:col-span-3 space-y-6">
            <!-- Search & Sorting Header -->
            <div class="bg-white border border-outline-variant/30 rounded-xl shadow-ambient p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <!-- Search Input & Mobile Filters Trigger -->
                <div class="flex items-center gap-2 w-full md:max-w-md">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                            <span class="material-symbols-outlined text-xl">search</span>
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search catalog..." class="w-full bg-slate-50 text-[#001229] placeholder-slate-400 pl-10 pr-4 py-2 rounded-lg text-sm border border-outline-variant/20 focus:outline-none focus:ring-2 focus:ring-gold">
                    </div>
                    <!-- Mobile Filters Trigger Button -->
                    <button type="button" @click="mobileFiltersOpen = true" class="lg:hidden flex items-center gap-1.5 px-3.5 py-2 bg-white border border-outline-variant/30 rounded-lg text-sm text-slate-700 hover:bg-slate-50 cursor-pointer h-[38px] select-none whitespace-nowrap">
                        <span class="material-symbols-outlined text-lg">filter_list</span>
                        <span>Filters</span>
                        @if($activeFiltersCount > 0)
                            <span class="w-5 h-5 bg-[#001229] text-white text-[10px] font-bold rounded-full flex items-center justify-center">{{ $activeFiltersCount }}</span>
                        @endif
                    </button>
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

            <!-- Price Warning Notice Banner -->
            <div class="bg-amber-50 border border-amber-200/60 rounded-xl p-3.5 flex items-start gap-2.5 text-amber-800 shadow-sm select-none">
                <span class="material-symbols-outlined text-amber-500 text-lg mt-0.5 select-none">info</span>
                <div class="text-xs font-semibold leading-relaxed">
                    Prices are subject to change. Please contact the admin for final price confirmation.
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

                    {{-- Quantity Inputs: Dual-unit selection queue --}}
                    <div class="space-y-4">
                        <h5 class="text-sm font-bold text-slate-700">Order Quantity by Unit</h5>

                        @if($quickAddHasLvl2Unit)
                            @php
                                $lvl1 = collect($quickAddUnits)->firstWhere('level', 1);
                                $lvl2 = collect($quickAddUnits)->firstWhere('level', 2);
                            @endphp
                            @if($lvl1 && $lvl2)
                                <div class="p-3 bg-secondary/10 border border-secondary/20 rounded-xl flex items-start gap-2 mb-2 select-none">
                                    <span class="material-symbols-outlined text-secondary text-base select-none mt-0.5">info</span>
                                    <div class="text-[11px] font-semibold text-secondary">
                                        Only <strong>{{ $lvl2['name'] }}</strong> purchases are allowed for this product.<br>
                                        Relation: 1 {{ $lvl2['name'] }} = {{ (int)$lvl2['conversion_to_base'] }} {{ $lvl1['name'] }}s.
                                    </div>
                                </div>
                            @endif
                        @endif

                        @foreach($quickAddUnits as $u)
                            @if(!$quickAddHasLvl2Unit || $u['level'] === 2)
                                <div class="space-y-1 bg-slate-50 p-2.5 rounded-lg border border-outline-variant/20">
                                    <div class="flex justify-between items-center">
                                        <label class="text-xs text-slate-500 font-bold uppercase block">Buy in {{ $u['name'] }}s</label>
                                        @if($u['level'] === 2)
                                            <span class="text-[10px] text-slate-400 block">1 {{ ucfirst($u['name']) }} = {{ (int)$u['conversion_to_base'] }} Pieces</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center justify-between border border-outline-variant/30 rounded-lg bg-white p-1 flex-1">
                                            <button type="button" wire:click="decrementQuickAddUnitQuantity({{ $u['id'] }})" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-all focus:outline-none">
                                                <span class="material-symbols-outlined text-lg">remove</span>
                                            </button>
                                            <input type="number" wire:model.live="quickAddUnitQuantities.{{ $u['id'] }}" min="0" class="w-12 text-center bg-transparent border-none focus:outline-none focus:ring-0 text-base font-extrabold text-[#001229] py-1">
                                            <button type="button" wire:click="incrementQuickAddUnitQuantity({{ $u['id'] }})" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-all focus:outline-none">
                                                <span class="material-symbols-outlined text-lg">add</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        <!-- Queued Items List -->
                        @if(!empty($quickAddQueuedItems))
                            <style>
                                @keyframes bulge-pulse {
                                    0%, 100% { transform: scale(1); }
                                    50% { transform: scale(1.04); }
                                }
                                .animate-bulge {
                                    display: inline-block;
                                    animation: bulge-pulse 2.5s ease-in-out infinite;
                                    transform-origin: left center;
                                }
                            </style>
                            <div class="space-y-2 bg-[#f8fafc] border border-slate-200 rounded-lg p-3">
                                <span class="text-xs font-extrabold text-gold uppercase tracking-wider block border-b border-slate-200 pb-1.5 mb-1.5 select-none animate-bulge">Current Selection:</span>
                                <div class="space-y-1.5">
                                    @foreach($quickAddQueuedItems as $item)
                                        <div class="flex items-center justify-between bg-white border border-outline-variant/20 rounded-md px-2.5 py-1.5 text-xs text-slate-700">
                                            <span class="font-semibold">{{ $item['quantity'] }} {{ $item['unit_name'] }}(s)</span>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[10px] text-slate-400">({{ $item['quantity'] * $item['conversion_to_base'] }} pcs)</span>
                                                <button type="button" wire:click="removeQuickAddUnitFromQueue({{ $item['unit_id'] }})" class="text-rose-500 hover:text-rose-700 focus:outline-none" title="Remove">
                                                    <span class="material-symbols-outlined text-[16px] font-bold">close</span>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-between items-center text-[10px] text-slate-400 font-semibold select-none">
                            @php
                                $totalPiecesDisplay = collect($quickAddQueuedItems)->sum(fn($i) => $i['quantity'] * $i['conversion_to_base']);
                                $purchasableUnit = $quickAddHasLvl2Unit 
                                    ? collect($quickAddUnits)->firstWhere('level', 2) 
                                    : collect($quickAddUnits)->firstWhere('level', 1);
                                $totalUnitsQueued = $purchasableUnit 
                                    ? collect($quickAddQueuedItems)->where('unit_id', $purchasableUnit['id'])->sum('quantity') 
                                    : 0;
                                $unitLabelPlural = $purchasableUnit ? $purchasableUnit['name'] . 's' : 'Pieces';
                            @endphp
                            @if($quickAddHasLvl2Unit)
                                <span>Total Selected: {{ $totalUnitsQueued }} {{ $unitLabelPlural }} ({{ $totalPiecesDisplay }} pcs)</span>
                                <span>MOQ: {{ $quickAddMoq }} {{ $unitLabelPlural }}</span>
                            @else
                                <span>Total Selected: {{ $totalPiecesDisplay }} Pieces</span>
                                <span>MOQ: {{ $quickAddMoq }} Pieces</span>
                            @endif
                        </div>
                    </div>

                    {{-- Live MOQ warning if below minimum --}}
                    @php
                        $isBelowMoq = false;
                        if ($quickAddHasLvl2Unit) {
                            $isBelowMoq = $totalUnitsQueued > 0 && $totalUnitsQueued < $quickAddMoq;
                        } else {
                            $isBelowMoq = $totalPiecesDisplay > 0 && $totalPiecesDisplay < $quickAddMoq;
                        }
                    @endphp
                    @if($isBelowMoq)
                        <div class="flex items-start gap-2 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 mt-2">
                            <span class="material-symbols-outlined text-sm text-rose-500 select-none mt-0.5">warning</span>
                            <span class="text-xs font-semibold text-rose-700">
                                Minimum order is <strong>{{ $quickAddMoq }} {{ $quickAddHasLvl2Unit ? $purchasableUnit['name'] . 's' : 'pieces' }}</strong>. Please select more.
                            </span>
                        </div>
                    @endif

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

    <!-- Mobile Filters Drawer -->
    <div x-show="mobileFiltersOpen" 
         class="fixed inset-0 z-50 lg:hidden" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/60" @click="mobileFiltersOpen = false"></div>
        
        <!-- Drawer content -->
        <div class="absolute bottom-0 inset-x-0 bg-white rounded-t-2xl shadow-xl max-h-[85vh] flex flex-col"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full"
             x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0"
             x-transition:leave-end="translate-y-full">
            
            <!-- Drawer Header -->
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between select-none bg-slate-50 rounded-t-2xl">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-[#001229] text-base">Filters</span>
                    @if($activeFiltersCount > 0)
                        <span class="px-2 py-0.5 bg-[#001229]/10 text-[#001229] text-xs font-bold rounded-full">{{ $activeFiltersCount }} active</span>
                    @endif
                </div>
                <div class="flex items-center gap-4">
                    <button wire:click="clearFilters" class="text-xs text-gold font-bold hover:underline cursor-pointer">Clear All</button>
                    <button type="button" @click="mobileFiltersOpen = false" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>
            
            <!-- Drawer Body -->
            <div class="p-5 overflow-y-auto space-y-6 flex-1 pb-12">
                <!-- Category Tree (Full recursive tree!) -->
                <div>
                    <h5 class="text-xs font-bold text-[#001229] uppercase tracking-wider mb-3 select-none">Categories</h5>
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
                    <h5 class="text-xs font-bold text-[#001229] uppercase tracking-wider mb-3 select-none">Availability</h5>
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

                <!-- Tags Filter -->
                <div>
                    <h5 class="text-xs font-bold text-[#001229] uppercase tracking-wider mb-3 select-none">Filter by Tags</h5>
                    <div class="flex flex-wrap gap-1.5 select-none">
                        @forelse($tagsList as $tag)
                            @php $isSel = in_array($tag->id, $selectedTags); @endphp
                            <button type="button" wire:click="toggleTagFilter({{ $tag->id }})" 
                                    class="px-3 py-1 rounded-full text-xs font-medium border transition-all flex items-center gap-1 cursor-pointer
                                    {{ $isSel 
                                        ? 'bg-gold/15 text-gold border-gold font-semibold' 
                                        : 'bg-slate-50 border-[#cbd5e1] text-slate-600 hover:border-slate-400' }}">
                                @if($isSel)
                                    <span class="material-symbols-outlined text-[12px] font-bold">close</span>
                                @endif
                                <span>{{ $tag->name }}</span>
                            </button>
                        @empty
                            <p class="text-xs text-slate-400 italic">No tags available.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Price Range -->
                @auth
                <div>
                    <h5 class="text-xs font-bold text-[#001229] uppercase tracking-wider mb-3 select-none">Max Price: ₹{{ number_format($max_price) }}</h5>
                    <div class="space-y-2">
                        <input type="range" min="100" max="10000" step="100" wire:model.live.debounce.150ms="max_price" class="w-full accent-gold bg-slate-100 rounded-lg appearance-none h-1.5 cursor-pointer">
                        <div class="flex justify-between text-xs text-slate-500 font-bold select-none">
                            <span>₹100</span>
                            <span>₹10,000</span>
                        </div>
                    </div>
                </div>
                @endauth
            </div>
            
            <!-- Drawer Footer (Sticky Apply Button) -->
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-4">
                <button type="button" @click="mobileFiltersOpen = false" class="w-full bg-[#001229] text-white py-2.5 rounded-lg text-sm font-bold shadow-md hover:bg-slate-800 transition-colors">
                    Apply Filters
                </button>
            </div>
        </div>
    </div>
</div>
