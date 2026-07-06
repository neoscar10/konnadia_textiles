<div x-on:copy-to-clipboard.window="navigator.clipboard.writeText($event.detail.url)">
    <x-slot:title>Design Catalog</x-slot:title>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-md mb-xl select-none">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight font-extrabold">Design Catalog</h1>
            <p class="font-body-md text-on-surface-variant">Browse products in a sleek grid layout with high-fidelity previews and stock details.</p>
        </div>
        <div class="flex gap-md w-full md:w-auto">
            <x-admin.button variant="primary" icon="share" wire:click="shareCatalog" class="bg-secondary text-on-secondary hover:bg-secondary/90 whitespace-nowrap">
                Share Filtered Catalog
            </x-admin.button>
        </div>
    </div>

    <!-- Filter & Search Panel - Single Row (grouped compactly) -->
    <div class="bg-white rounded-xl card-shadow border border-outline-variant/30 p-md mb-xl select-none">
        <div class="flex flex-col md:flex-row items-start md:items-center gap-md w-full">
            <!-- Search Input (reasonable fixed width, e.g. w-80) -->
            <div class="w-full md:w-80 flex items-center gap-sm bg-surface-container-low border border-outline-variant/50 rounded-lg px-sm focus-within:ring-2 focus-within:ring-secondary focus-within:border-secondary transition-all">
                <span class="material-symbols-outlined text-on-surface-variant/60 text-[20px] select-none pl-xs">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by title, SKU, description..." class="w-full bg-transparent border-none py-sm pr-xs font-body-md focus:ring-0 focus:outline-none outline-none text-on-surface">
            </div>

            <!-- Category Path Filter (reasonable fixed width, e.g. w-72) -->
            <div class="w-full md:w-72">
                <select wire:model.live="filterCategory" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none cursor-pointer">
                    <option value="">All Leaf Categories</option>
                    @foreach($leafCategories as $leaf)
                        <option value="{{ $leaf->id }}">{{ $leaf->full_path }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tag Filter -->
            <div class="w-full md:w-48">
                <select wire:model.live="filterTag" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none cursor-pointer">
                    <option value="">All Tags</option>
                    @foreach($tagsList as $tag)
                        <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Total counter (aligned neatly) -->
            <div class="text-xs text-on-surface-variant font-semibold bg-surface-container/40 border border-outline-variant/20 px-md py-sm rounded-lg flex items-center justify-center gap-xs">
                <span class="material-symbols-outlined text-[16px] text-primary">analytics</span>
                <span>Designs: <strong class="text-primary">{{ $products->total() }}</strong></span>
            </div>
        </div>
    </div>

    <!-- Grid Layout (4 items per row on desktop) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($products as $prod)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 overflow-hidden relative flex flex-col hover:shadow-md transition-shadow duration-200">
                
                <!-- Product Image Area -->
                <a href="{{ route('admin.products.show', ['id' => $prod->id]) }}" class="block aspect-square w-full bg-slate-50 border-b border-slate-100 overflow-hidden relative group">
                    @if($prod->primaryMedia)
                        <img src="{{ Storage::url($prod->primaryMedia->file_path) }}" 
                             alt="{{ $prod->title }}" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        >
                    @else
                        <div class="w-full h-full bg-[#e6f4fe] flex items-center justify-center">
                            <!-- Custom Dress Icon -->
                            <svg class="w-20 h-20 text-[#8ec8f6]" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm6 5.78V10c0 .55-.45 1-1 1h-1v10c0 .55-.45 1-1 1H9c-.55 0-1-.45-1-1V11H7c-.55 0-1-.45-1-1V7.78c0-.42.26-.79.66-.93l4.5-1.5c.54-.18 1.14-.18 1.68 0l4.5 1.5c.4.14.66.51.66.93z" />
                            </svg>
                        </div>
                    @endif
                </a>

                <!-- Card Details -->
                <div class="p-4 flex flex-col justify-between flex-1">
                    <div>
                        <div class="text-xs text-slate-500 font-medium mb-2 flex flex-col gap-y-0.5">
                            <div>Price: <span class="font-semibold text-slate-800">₹{{ number_format($prod->base_price, 2) }}</span></div>
                            <div>
                                Stock: 
                                @if($prod->computed_stock === PHP_INT_MAX)
                                    <span class="font-semibold text-slate-600">Unlimited</span>
                                @elseif($prod->computed_stock > 10)
                                    <span class="font-semibold text-emerald-600">{{ number_format($prod->computed_stock) }} In Stock</span>
                                @elseif($prod->computed_stock > 0)
                                    <span class="font-semibold text-amber-600">{{ number_format($prod->computed_stock) }} Low Stock</span>
                                @else
                                    <span class="font-semibold text-rose-600">Out of Stock</span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('admin.products.show', ['id' => $prod->id]) }}" class="block font-semibold text-slate-800 hover:text-[#0f82c5] text-sm leading-snug truncate" title="{{ $prod->title }}">
                            {{ $prod->title }}
                        </a>
                    </div>
                </div>

            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl shadow-sm border border-outline-variant/30 p-2xl text-center select-none">
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
