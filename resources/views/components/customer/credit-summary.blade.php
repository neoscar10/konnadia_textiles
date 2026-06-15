@props([
    'available' => 320000,
    'limit' => 500000,
    'outstanding' => 180000
])

@php
    $percentage = $limit > 0 ? min(100, max(0, ($outstanding / $limit) * 100)) : 0;
@endphp

<div class="bg-gradient-to-br from-[#001229] to-[#0f2744] text-white p-6 rounded-xl border border-slate-800 shadow-ambient">
    <div class="flex items-start justify-between mb-4">
        <div>
            <p class="text-xs text-slate-300 font-medium uppercase tracking-wider">Available Credit Limit</p>
            <h3 class="text-3xl font-extrabold text-gold mt-1">₹{{ number_format($available) }}</h3>
        </div>
        <span class="material-symbols-outlined text-gold/80 text-4xl">payments</span>
    </div>

    <!-- Progress bar -->
    <div class="space-y-1.5">
        <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
            <div class="h-full bg-gold rounded-full" style="width: {{ $percentage }}%"></div>
        </div>
        <div class="flex justify-between text-xs text-slate-300 font-medium">
            <span>Outstanding: ₹{{ number_format($outstanding) }}</span>
            <span>Limit: ₹{{ number_format($limit) }}</span>
        </div>
    </div>
</div>
