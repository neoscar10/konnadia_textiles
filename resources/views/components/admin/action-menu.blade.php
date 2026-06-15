<div x-data="{ open: false }" class="relative inline-block text-left" @keydown.escape.window="open = false">
    <button @click="open = !open" @click.away="open = false" type="button" class="p-xs rounded-md text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors" aria-haspopup="true" :aria-expanded="open.toString()">
        <span class="material-symbols-outlined text-[20px]">more_vert</span>
    </button>

    <div x-show="open" x-transition.opacity.duration.200ms class="absolute right-0 z-50 mt-1 w-40 origin-top-right rounded-md bg-surface shadow-ambient ring-1 ring-outline-variant/30 focus:outline-none" role="menu" aria-orientation="vertical" style="display: none;">
        <div class="py-xs" role="none">
            {{ $slot }}
        </div>
    </div>
</div>
