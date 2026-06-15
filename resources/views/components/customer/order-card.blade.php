@props([
    'orderId',
    'status',
    'date',
    'total',
    'itemsCount' => 3,
    'images' => []
])

<div class="bg-white border border-outline-variant/20 rounded-xl shadow-ambient overflow-hidden hover:shadow-md transition-shadow">
    <!-- Header -->
    <div class="px-5 py-4 border-b border-outline-variant/10 bg-slate-50/50 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="text-sm font-extrabold text-[#001229]">{{ $orderId }}</span>
            <x-customer.badge :status="$status" />
        </div>
        <div class="text-xs font-semibold text-slate-500">
            Placed on {{ $date }}
        </div>
    </div>

    <!-- Body -->
    <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Thumbnails -->
        <div class="flex items-center gap-2 overflow-x-auto hide-scrollbar">
            @foreach(array_slice($images, 0, 4) as $img)
                <img src="{{ $img }}" alt="Product thumbnail" class="w-12 h-12 object-cover rounded-lg border bg-slate-100 flex-shrink-0">
            @endforeach
            @if(count($images) > 4)
                <div class="w-12 h-12 rounded-lg border bg-slate-100 text-slate-500 font-bold flex items-center justify-center text-xs flex-shrink-0">
                    +{{ count($images) - 4 }}
                </div>
            @endif
        </div>

        <!-- Order Total & Actions -->
        <div class="flex items-center justify-between md:justify-end gap-6 pt-3 md:pt-0 border-t md:border-none border-slate-50">
            <div class="text-right">
                <span class="text-[10px] text-slate-400 font-semibold uppercase block">Order Total</span>
                <span class="text-base font-extrabold text-[#001229]">₹{{ number_format($total) }}</span>
                <span class="text-[10px] text-slate-500 block">{{ $itemsCount }} Products</span>
            </div>
            
            <a href="{{ route('customer.orders.show', $orderId) }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-[#001229] border border-outline-variant/50 hover:bg-slate-50 transition-colors rounded-lg">
                View Details
            </a>
        </div>
    </div>
</div>
