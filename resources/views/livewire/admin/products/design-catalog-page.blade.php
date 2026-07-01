<div>
    <x-slot:title>Design Catalog</x-slot:title>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-md mb-xl select-none">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight font-extrabold">Design Catalog</h1>
            <p class="font-body-md text-on-surface-variant">Browse products with high-resolution imagery, direct stock levels, and complete taxonomic category paths.</p>
        </div>
    </div>

    <!-- Filter & Search Panel - Single Row Layout -->
    <div class="bg-white rounded-xl card-shadow border border-outline-variant/30 p-md mb-xl select-none">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-md w-full">
            <!-- Search Input -->
            <div class="flex-1 flex items-center gap-sm bg-surface-container-low border border-outline-variant/50 rounded-lg px-sm focus-within:ring-2 focus-within:ring-secondary focus-within:border-secondary transition-all">
                <span class="material-symbols-outlined text-on-surface-variant/60 text-[20px] select-none pl-xs">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by title, SKU, description..." class="w-full bg-transparent border-none py-sm pr-xs font-body-md focus:ring-0 focus:outline-none outline-none text-on-surface">
            </div>

            <!-- Category Path Filter -->
            <div class="w-full sm:w-80 shrink-0">
                <select wire:model.live="filterCategory" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none cursor-pointer">
                    <option value="">All Leaf Categories</option>
                    @foreach($leafCategories as $leaf)
                        <option value="{{ $leaf->id }}">{{ $leaf->full_path }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Total counter -->
            <div class="shrink-0 text-xs text-on-surface-variant font-semibold bg-surface-container/40 border border-outline-variant/20 px-md py-sm rounded-lg flex items-center gap-xs">
                <span class="material-symbols-outlined text-[16px] text-primary">analytics</span>
                <span>Designs: <strong class="text-primary">{{ $products->total() }}</strong></span>
            </div>
        </div>
    </div>

    <!-- Product List -->
    <div class="space-y-md">
        @forelse($products as $prod)
            <div class="bg-white rounded-xl card-shadow border border-outline-variant/30 overflow-hidden flex flex-col md:flex-row hover:shadow-md transition-all duration-300 group">
                
                <!-- Left: Product Image -->
                <div class="w-full md:w-60 h-48 md:h-auto shrink-0 relative bg-surface-container border-b md:border-b-0 md:border-r border-outline-variant/20 overflow-hidden flex items-center justify-center">
                    @if($prod->primaryMedia)
                        <img src="{{ Storage::url($prod->primaryMedia->file_path) }}" alt="{{ $prod->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="w-full h-full bg-surface-container-low flex flex-col items-center justify-center text-on-surface-variant/30 p-md select-none">
                            <span class="material-symbols-outlined text-[48px] mb-xxs">image</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider">No Image</span>
                        </div>
                    @endif
                </div>

                <!-- Right: Product Info & Actions -->
                <div class="p-lg flex-1 flex flex-col justify-between">
                    <div>
                        <!-- Category path (Top Line) -->
                        <div class="flex flex-wrap items-center gap-xs mb-sm select-none">
                            @forelse($prod->category_paths as $path)
                                <span class="inline-flex items-center gap-xxs text-[11px] font-bold text-secondary bg-secondary/5 border border-secondary/15 px-sm py-0.5 rounded">
                                    <span class="material-symbols-outlined text-[12px] opacity-70">folder</span>
                                    {{ $path }}
                                </span>
                            @empty
                                <span class="inline-flex items-center gap-xxs text-[11px] font-bold text-outline bg-surface-container px-sm py-0.5 rounded">
                                    Unassigned
                                </span>
                            @endforelse
                        </div>

                        <!-- Title and SKU -->
                        <div class="flex flex-col sm:flex-row justify-between items-start gap-xs mb-sm">
                            <div>
                                <h3 class="font-title-md text-primary font-bold tracking-tight group-hover:text-secondary transition-colors">
                                    {{ $prod->title }}
                                </h3>
                                <p class="font-mono text-xs text-on-surface-variant/80 mt-xxs">SKU: {{ $prod->sku }}</p>
                            </div>

                            <!-- MRP Price -->
                            <div class="text-left sm:text-right shrink-0">
                                <span class="text-[10px] text-on-surface-variant block uppercase tracking-wider font-bold select-none">MRP Price</span>
                                <span class="font-title-md text-primary font-black">₹{{ number_format($prod->base_price, 2) }}</span>
                            </div>
                        </div>

                        <!-- Description Preview -->
                        <p class="font-body-sm text-on-surface-variant/90 line-clamp-2 mb-md">
                            {{ strip_tags($prod->description) ?: 'No description provided for this design.' }}
                        </p>
                    </div>

                    <!-- Footer line: Stock status & Details Button -->
                    <div class="border-t border-outline-variant/15 pt-md flex items-center justify-between gap-md select-none">
                        <!-- Stock Status -->
                        <div>
                            @if($prod->stock_quantity === null)
                                <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded bg-surface-container text-on-surface-variant text-xs font-semibold">
                                    <span class="material-symbols-outlined text-[14px]">all_inclusive</span>
                                    Unlimited Stock
                                </span>
                            @elseif($prod->stock_quantity > 10)
                                <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded bg-success-container text-on-success-container text-xs font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                    {{ number_format($prod->stock_quantity) }} In Stock
                                </span>
                            @elseif($prod->stock_quantity > 0)
                                <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded bg-warning/15 text-warning text-xs font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
                                    {{ $prod->stock_quantity }} Low Stock
                                </span>
                            @else
                                <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded bg-error/10 text-error text-xs font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                                    Out of stock
                                </span>
                            @endif
                        </div>

                        <!-- Action Button -->
                        <a href="{{ route('admin.products.show', ['id' => $prod->id]) }}" class="px-md py-sm bg-primary hover:bg-primary/95 text-on-primary rounded-lg font-bold text-xs shadow-sm hover:shadow transition-all flex items-center gap-xs">
                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                            View Details
                        </a>
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

    <!-- Pagination -->
    @if($products->hasPages())
        <div class="mt-xl">
            {{ $products->links() }}
        </div>
    @endif
</div>
