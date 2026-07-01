<div>
    <x-slot:title>Design Catalog</x-slot:title>

    <!-- Header Section with Decorative Accent -->
    <div class="relative overflow-hidden bg-gradient-to-r from-primary to-primary-container text-on-primary rounded-2xl p-xl shadow-lg border border-outline-variant/10 mb-xl select-none">
        <div class="relative z-10">
            <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded-full bg-white/10 text-white text-[11px] font-bold uppercase tracking-wider mb-sm">
                <span class="material-symbols-outlined text-[14px]">auto_awesome</span>
                Exclusive Showroom Layout
            </span>
            <h1 class="font-headline-lg tracking-tight font-extrabold">Design Catalog</h1>
            <p class="font-body-md text-on-primary/80 mt-xxs max-w-2xl">Browse your collections with high-resolution imagery, direct stock levels, and complete taxonomic category paths.</p>
        </div>
        <div class="absolute -right-10 -bottom-10 opacity-10 select-none pointer-events-none">
            <span class="material-symbols-outlined text-[240px]">texture</span>
        </div>
    </div>

    <!-- Filter & Search Panel -->
    <div class="bg-white rounded-xl card-shadow border border-outline-variant/30 p-lg mb-xl flex flex-col md:flex-row items-center justify-between gap-md select-none">
        <div class="flex flex-wrap items-center gap-md w-full md:w-auto">
            <!-- Search Input -->
            <div class="flex items-center gap-sm w-full md:w-80 bg-surface-container-low border border-outline-variant/50 rounded-lg px-sm focus-within:ring-2 focus-within:ring-secondary focus-within:border-secondary transition-all">
                <span class="material-symbols-outlined text-on-surface-variant/60 text-[20px] select-none pl-xs">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by title, SKU, description..." class="w-full bg-transparent border-none py-sm pr-xs font-body-md focus:ring-0 focus:outline-none outline-none text-on-surface">
            </div>

            <!-- Category path dropdown filter -->
            <div class="w-full md:w-72">
                <select wire:model.live="filterCategory" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none cursor-pointer">
                    <option value="">All Leaf Categories</option>
                    @foreach($leafCategories as $leaf)
                        <option value="{{ $leaf->id }}">{{ $leaf->full_path }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="text-xs text-on-surface-variant font-medium">
            Showing <span class="text-primary font-bold">{{ $products->total() }}</span> designs
        </div>
    </div>

    <!-- Product List (Card view with larger images) -->
    <div class="space-y-lg">
        @forelse($products as $prod)
            <div class="bg-white rounded-xl card-shadow border border-outline-variant/35 overflow-hidden flex flex-col sm:flex-row hover:shadow-lg transition-all duration-300 group">
                <!-- Large Image Area -->
                <div class="w-full sm:w-64 h-64 sm:h-auto shrink-0 relative bg-surface-container-lowest overflow-hidden border-b sm:border-b-0 sm:border-r border-outline-variant/20 flex items-center justify-center">
                    @if($prod->primaryMedia)
                        <img src="{{ Storage::url($prod->primaryMedia->file_path) }}" alt="{{ $prod->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="w-full h-full bg-surface-container flex flex-col items-center justify-center text-on-surface-variant/30 select-none p-lg">
                            <span class="material-symbols-outlined text-[64px] mb-xs">texture</span>
                            <span class="text-xs font-bold uppercase tracking-wider">No Image Available</span>
                        </div>
                    @endif

                    <!-- Category tag absolute on image (Top Left) -->
                    @if($prod->categories->count() > 0)
                        <span class="absolute top-4 left-4 bg-primary/90 text-on-primary text-[10px] font-bold uppercase tracking-wider px-sm py-xxs rounded shadow-md select-none">
                            {{ $prod->categories->first()->name }}
                        </span>
                    @endif
                </div>

                <!-- Product Details Area -->
                <div class="p-xl flex-1 flex flex-col justify-between">
                    <div>
                        <!-- Title & SKU line -->
                        <div class="flex flex-col sm:flex-row justify-between items-start gap-xs mb-xs">
                            <h3 class="font-title-lg text-primary font-extrabold tracking-tight group-hover:text-secondary transition-colors">{{ $prod->title }}</h3>
                            <span class="font-mono text-xs text-on-surface-variant bg-surface-container px-sm py-xs rounded border border-outline-variant/30 select-none">SKU: {{ $prod->sku }}</span>
                        </div>

                        <!-- Category Paths List -->
                        <div class="flex flex-wrap items-center gap-xxs mb-md select-none">
                            <span class="material-symbols-outlined text-[16px] text-on-surface-variant">folder_open</span>
                            @forelse($prod->category_paths as $path)
                                <span class="text-xs font-semibold text-secondary-container bg-secondary/5 border border-secondary/15 px-sm py-0.5 rounded-full">
                                    {{ $path }}
                                </span>
                            @empty
                                <span class="text-xs italic text-on-surface-variant/60">Unassigned</span>
                            @endforelse
                        </div>

                        <!-- Description preview -->
                        <p class="font-body-md text-on-surface-variant line-clamp-3 mb-lg">
                            {{ strip_tags($prod->description) ?: 'No description provided for this design.' }}
                        </p>
                    </div>

                    <!-- Footer line: Stock, Price, Actions -->
                    <div class="border-t border-outline-variant/15 pt-md flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-md select-none">
                        <div class="flex items-center gap-xl">
                            <!-- Base Price -->
                            <div>
                                <span class="text-[10px] text-on-surface-variant block uppercase tracking-wider font-bold">MRP Price</span>
                                <span class="font-title-md text-primary font-black">₹{{ number_format($prod->base_price, 2) }}</span>
                            </div>

                            <!-- Stock level badge -->
                            <div>
                                <span class="text-[10px] text-on-surface-variant block uppercase tracking-wider font-bold mb-xxs">Available Stock</span>
                                @if($prod->stock_quantity === null)
                                    <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded bg-surface-container text-on-surface-variant text-xs font-semibold">
                                        <span class="material-symbols-outlined text-[14px]">all_inclusive</span>
                                        Unlimited (N/A)
                                    </span>
                                @elseif($prod->stock_quantity > 10)
                                    <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded bg-success-container text-on-success-container text-xs font-bold">
                                        {{ number_format($prod->stock_quantity) }} Units
                                    </span>
                                @elseif($prod->stock_quantity > 0)
                                    <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded bg-warning/15 text-warning text-xs font-bold">
                                        {{ $prod->stock_quantity }} Low Stock
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded bg-error/10 text-error text-xs font-bold">
                                        Out of stock
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-sm self-end sm:self-auto">
                            <!-- View Details Button -->
                            <a href="{{ route('admin.products.show', ['id' => $prod->id]) }}" class="px-lg py-sm bg-primary hover:bg-primary/95 text-on-primary rounded-lg font-semibold text-xs shadow-sm hover:shadow transition-all flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[16px]">visibility</span>
                                View Product Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl card-shadow border border-outline-variant/30 p-2xl text-center select-none">
                <span class="material-symbols-outlined text-5xl text-outline mb-md">inventory_2</span>
                <h3 class="font-title-md text-primary font-bold">No designs found</h3>
                <p class="text-sm text-on-surface-variant mt-xxs">Try adjusting your filters or search query.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination links -->
    @if($products->hasPages())
        <div class="mt-xl">
            {{ $products->links() }}
        </div>
    @endif
</div>
