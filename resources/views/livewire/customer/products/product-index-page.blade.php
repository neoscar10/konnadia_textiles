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
        <aside class="hidden lg:block lg:col-span-1 space-y-6">
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
                                @php
                                    $hasChildren = collect($categoriesList)->where('parent_id', $cat['id'])->isNotEmpty();
                                    $isExpanded = in_array($cat['id'], $expandedCategories);
                                    $isActive = (string)$category === (string)$cat['id'];
                                    $hasActiveChild = (collect($categoriesList)->firstWhere('id', $category)['parent_id'] ?? null) == $cat['id'];
                                @endphp
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between py-1.5 px-2 rounded-lg hover:bg-slate-50 transition-colors {{ $isActive || $hasActiveChild ? 'bg-[#001229]/5 font-bold text-[#001229]' : 'text-slate-700' }}">
                                        <button wire:click="selectCategory('{{ $cat['id'] }}')" class="flex-1 text-left text-sm hover:text-[#001229] cursor-pointer">
                                            {{ $cat['name'] }}
                                        </button>
                                        
                                        <div class="flex items-center gap-1.5">
                                            @if($isActive)
                                                <span class="material-symbols-outlined text-xs text-gold">check</span>
                                            @endif
                                            
                                            @if($hasChildren)
                                                <button wire:click.stop="toggleCategory('{{ $cat['id'] }}')" class="p-0.5 rounded hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition-colors">
                                                    <span class="material-symbols-outlined text-sm transform transition-transform {{ $isExpanded ? 'rotate-180' : '' }}">
                                                        keyboard_arrow_down
                                                    </span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Subcategories (Drill down) -->
                                    @if($hasChildren && $isExpanded)
                                        <div class="pl-4 space-y-1 border-l border-slate-100 ml-3">
                                            @foreach($categoriesList as $subCat)
                                                @if($subCat['parent_id'] === $cat['id'])
                                                    @php
                                                        $isSubActive = (string)$category === (string)$subCat['id'];
                                                    @endphp
                                                    <div class="flex items-center justify-between py-1 px-2 rounded hover:bg-slate-50 transition-colors {{ $isSubActive ? 'bg-[#001229]/5 font-semibold text-[#001229]' : 'text-slate-600' }}">
                                                        <button wire:click="selectCategory('{{ $subCat['id'] }}')" class="flex-1 text-left text-xs hover:text-slate-800 cursor-pointer">
                                                            {{ $subCat['name'] }}
                                                        </button>
                                                        @if($isSubActive)
                                                            <span class="material-symbols-outlined text-xs text-gold">check</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
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
</div>
