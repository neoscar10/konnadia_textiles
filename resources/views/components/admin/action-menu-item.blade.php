@props(['icon' => null, 'label', 'danger' => false, 'href' => '#'])

@php
    $class = "flex items-center gap-sm block w-full text-left px-md py-xs text-sm transition-colors " . 
             ($danger ? "text-error hover:bg-error/10" : "text-on-surface hover:bg-surface-container-low hover:text-primary");
@endphp

@if ($attributes->has('wire:click') || $attributes->has('@click') || $attributes->has('x-on:click'))
    <button {{ $attributes->merge(['class' => $class]) }} role="menuitem">
        @if($icon)
            <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
        @endif
        {{ $label }}
    </button>
@else
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }} role="menuitem">
        @if($icon)
            <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
        @endif
        {{ $label }}
    </a>
@endif
