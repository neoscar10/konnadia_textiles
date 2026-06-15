@props([
    'title',
    'sku',
    'price',
    'image',
    'qty' => 10,
    'size' => 'M',
    'color' => 'Navy Blue',
    'unit' => 'Pieces'
])

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-4 bg-white border border-outline-variant/20 rounded-xl shadow-ambient hover:shadow-md transition-shadow">
    <!-- Product Details -->
    <div class="flex items-center gap-4 flex-1">
        <img src="{{ $image }}" alt="{{ $title }}" class="w-16 h-16 object-cover rounded-lg border bg-slate-50">
        <div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">SKU: {{ $sku }}</span>
            <h4 class="text-sm font-bold text-[#001229] leading-snug">{{ $title }}</h4>
            
            <div class="flex flex-wrap gap-1.5 mt-1.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">Size: {{ $size }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">Color: {{ $color }}</span>
            </div>
        </div>
    </div>

    <!-- Stepper & Subtotal -->
    <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto pt-3 sm:pt-0 border-t border-slate-50 sm:border-none">
        <!-- Quantity control -->
        <x-customer.quantity-control :value="$qty" />

        <!-- Price Details -->
        <div class="text-right min-w-[100px]">
            <span class="text-[10px] text-slate-400 font-semibold uppercase block">Subtotal</span>
            <span class="text-base font-extrabold text-[#001229]">₹{{ number_format($price * $qty) }}</span>
            <span class="text-[10px] text-slate-500 block">@ ₹{{ number_format($price) }}</span>
        </div>

        <!-- Delete Action -->
        <button type="button" class="p-2 text-slate-400 hover:text-error hover:bg-rose-50 rounded-lg transition-colors">
            <span class="material-symbols-outlined">delete</span>
        </button>
    </div>
</div>
