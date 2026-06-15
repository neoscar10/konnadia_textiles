@props(['type' => 'default'])

@php
    $classes = [
        'default' => 'bg-surface-container-high text-on-surface-variant',
        'success' => 'bg-[#E6F4EA] text-[#0F8A46]',
        'warning' => 'bg-secondary-container/50 text-secondary',
        'danger'  => 'bg-error-container text-error',
        'info'    => 'bg-primary-fixed-dim/30 text-primary-fixed-variant',
    ];

    $colorClass = $classes[$type] ?? $classes['default'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-sm py-xs rounded font-label-md uppercase tracking-wider text-[10px] $colorClass"]) }}>
    {{ $slot }}
</span>
