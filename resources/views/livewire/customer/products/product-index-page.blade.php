<div>
    <!-- Page title -->
    <x-customer.page-title 
        title="Products Catalog" 
        subtitle="Browse our current in-stock and upcoming textile fabric selections."
        :breadcrumbs="['Home' => route('customer.dashboard'), 'Products' => '#']"
    />

    <!-- Mobile horizontal scrolling filter chips -->
    <div class="lg:hidden flex items-center gap-2 overflow-x-auto hide-scrollbar mb-4 pb-2">
        <button wire:click="clearFilters" class="px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap {{ empty($category) && $availability === 'all' ? 'bg-[#001229] text-white' : 'bg-white border border-outline-variant/30 text-slate-700' }}">All Items</button>
        @foreach($categoriesList as $cat)
            @if(!$cat['parent_id'])
                <button wire:click="$set('category', '{{ $cat['id'] }}')" class="px-4 py-1.5 rounded-full text-xs font-medium whitespace-nowrap {{ (string)$category === (string)$cat['id'] ? 'bg-[#001229] text-white' : 'bg-white border border-outline-variant/30 text-slate-700' }}">
                    {{ $cat['name'] }}
                </button>
            @endif
        @endforeach
    </div>

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
                    <div class="space-y-2.5">
                        <label class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 cursor-pointer">
                            <input type="radio" wire:model.live="category" value="" class="rounded-full border-outline-variant text-[#001229] focus:ring-gold focus:ring-offset-0">
                            <span>All Categories</span>
                        </label>
                        @foreach($categoriesList as $cat)
                            <label class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 cursor-pointer" style="margin-left: {{ $cat['parent_id'] ? '1.25rem' : '0' }}">
                                <input type="radio" wire:model.live="category" value="{{ $cat['id'] }}" class="rounded-full border-outline-variant text-[#001229] focus:ring-gold focus:ring-offset-0">
                                <span>{{ $cat['name'] }}</span>
                            </label>
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
