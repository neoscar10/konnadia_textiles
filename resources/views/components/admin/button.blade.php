@props(['variant' => 'primary', 'type' => 'button', 'icon' => null])

@php
    $baseClasses = 'font-button rounded-lg transition-all flex items-center justify-center gap-sm active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100';
    
    $variants = [
        'primary'   => 'bg-primary text-on-primary hover:bg-primary-fixed-variant border border-transparent shadow-sm px-md py-sm',
        'secondary' => 'bg-secondary text-primary hover:bg-secondary-fixed-dim border border-transparent shadow-sm px-md py-sm',
        'outline'   => 'bg-surface border border-outline-variant text-on-surface hover:bg-surface-container px-md py-sm',
        'danger'    => 'bg-error text-on-error hover:bg-[#93000A] border border-transparent shadow-sm px-md py-sm',
        'ghost'     => 'bg-transparent text-on-surface-variant hover:text-primary hover:bg-surface-container px-sm py-sm',
        'icon'      => 'p-sm text-on-surface-variant hover:text-primary hover:bg-surface-container rounded-lg',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

@if ($attributes->has('href'))
    <a {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
        @endif
        {{ $slot }}
    </button>
@endif
