@props(['id', 'title', 'maxWidth' => 'md'])

@php
$maxWidthClass = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '2.5xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
    '5xl' => 'sm:max-w-5xl',
    '6xl' => 'sm:max-w-6xl',
    '7xl' => 'sm:max-w-7xl',
][$maxWidth];

$inlineStyle = '';
if ($maxWidth === '2.5xl') {
    $inlineStyle = 'max-width: 630px;';
}
@endphp

<div 
    x-data="{ show: false }"
    x-on:open-modal.window="if ($event.detail == '{{ $id }}') { show = true }"
    x-on:close-modal.window="if ($event.detail == '{{ $id }}') { show = false }"
    x-on:keydown.escape.window="show = false"
    style="display: none;"
    x-show="show"
    class="fixed inset-0 z-50 overflow-y-auto"
>
    <!-- Backdrop -->
    <div 
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-primary/40 backdrop-blur-sm transition-opacity" 
        @click="show = false"
    ></div>

    <!-- Modal Panel -->
    <div class="flex min-h-full items-center justify-center p-4 sm:p-0">
        <div 
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-xl bg-surface-container-lowest text-left shadow-xl transition-all sm:my-8 w-full {{ $maxWidthClass }} border border-outline-variant/30"
            style="{{ $inlineStyle }}"
            @click.stop
        >
            <!-- Header -->
            <div class="px-xl py-lg border-b border-outline-variant/30 flex justify-between items-center bg-surface-container-low/30">
                <h3 class="font-headline-md text-primary">{{ $title }}</h3>
                <button @click="show = false" class="text-on-surface-variant hover:text-error transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="p-xl bg-surface-container-lowest">
                {{ $slot }}
            </div>

            <!-- Footer -->
            @isset($footer)
                <div class="px-xl py-lg border-t border-outline-variant/30 bg-surface-container-low/50 flex justify-end gap-md">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
