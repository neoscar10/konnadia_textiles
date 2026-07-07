@props([
    'title',
    'sku',
    'price',
    'moq' => 10,
    'image',
    'inStock' => true,
    'url' => '#',
    'productId' => null
])

<div class="bg-white rounded-xl border border-outline-variant/30 shadow-ambient overflow-hidden flex flex-col hover:shadow-md transition-shadow group">
    <!-- Image Area -->
    <a href="{{ $url }}" class="relative flex items-center justify-center overflow-hidden aspect-[4/3] bg-slate-50 p-2">
        <img src="{{ $image }}" alt="{{ $title }}" class="max-w-full max-h-full object-contain group-hover:scale-102 transition-transform duration-300">
        
        <!-- Stock Badge -->
        <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $inStock ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/50' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/50' }}">
            {{ $inStock ? 'In Stock' : 'Out of Stock' }}
        </span>
    </a>

    <!-- Details -->
    <div class="p-4 flex-1 flex flex-col justify-between">
        <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">SKU: {{ $sku }}</div>
            <a href="{{ $url }}" class="block text-sm font-bold text-[#001229] hover:text-gold transition-colors mt-0.5 line-clamp-1">
                {{ $title }}
            </a>
            
            <!-- Price and MOQ -->
            <div class="flex items-baseline justify-between mt-3">
                @auth
                    <div>
                        <span class="text-xs text-slate-400 font-medium">Wholesale Price</span>
                        <p class="text-lg font-extrabold text-[#001229]">₹{{ number_format($price) }} <span class="text-xs font-normal text-slate-500">/ Piece</span></p>
                    </div>
                @else
                    <div>
                        <span class="text-xs text-slate-400 font-medium">Wholesale Price</span>
                        <p class="text-xs font-bold text-gold mt-1">
                            <a href="{{ route('login') }}" class="underline hover:text-gold-dark">Login to view price</a>
                        </p>
                    </div>
                @endauth
                <div class="text-right">
                    <span class="text-xs text-slate-400 font-medium">Min Order Qty</span>
                    <p class="text-xs font-bold text-slate-700">{{ $moq }} Pieces</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="grid grid-cols-2 gap-2 mt-4 pt-3 border-t border-slate-50">
            <a href="{{ $url }}" class="flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold text-[#001229] border border-outline-variant/50 hover:bg-slate-50 transition-colors rounded-lg">
                <span class="material-symbols-outlined text-sm">visibility</span> View
            </a>
            @auth
                <button type="button" @if($productId) wire:click.prevent="handleAddClick({{ $productId }})" @endif class="flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold bg-[#001229] text-white hover:bg-slate-800 transition-colors rounded-lg">
                    <span class="material-symbols-outlined text-sm">shopping_cart</span> Add
                </button>
            @else
                <a href="{{ route('login') }}" class="flex items-center justify-center gap-1 px-3 py-2 text-xs font-bold bg-[#001229] text-white hover:bg-slate-800 transition-colors rounded-lg">
                    <span class="material-symbols-outlined text-sm">login</span> Login
                </a>
            @endauth
        </div>
    </div>
</div>
