@props([
    'value' => 10,
    'min' => 1,
    'max' => 9999,
    'step' => 1
])

<div x-data="{ qty: {{ $value }}, min: {{ $min }}, max: {{ $max }}, step: {{ $step }} }" class="inline-flex items-center border border-outline-variant/30 rounded-lg bg-slate-50 p-1">
    <button type="button" @click="if(qty > min) qty -= step" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-white hover:shadow-sm active:bg-slate-100 transition-all focus:outline-none">
        <span class="material-symbols-outlined text-lg">remove</span>
    </button>
    <input type="number" x-model.number="qty" @change="qty = Math.min(max, Math.max(min, qty))" class="w-12 text-center bg-transparent border-none focus:outline-none focus:ring-0 text-sm font-bold text-[#001229] [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
    <button type="button" @click="if(qty < max) qty += step" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-white hover:shadow-sm active:bg-slate-100 transition-all focus:outline-none">
        <span class="material-symbols-outlined text-lg">add</span>
    </button>
</div>
