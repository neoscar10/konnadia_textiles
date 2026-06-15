<div x-data="{ open: false }" wire:key="cat-node-{{ $cat->id }}" class="select-none">
    <div class="flex items-center gap-xs py-xxs group">
        <!-- Chevron button if has children -->
        @if($cat->children && $cat->children->count() > 0)
            <button type="button" @click="open = !open" class="w-6 h-6 rounded flex items-center justify-center text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors focus:outline-none cursor-pointer">
                <span class="material-symbols-outlined text-[18px] transition-transform duration-200" :class="open ? 'rotate-90' : ''">
                    chevron_right
                </span>
            </button>
        @else
            <div class="w-6 h-6 flex items-center justify-center">
                <span class="w-1.5 h-1.5 rounded-full bg-outline-variant/50"></span>
            </div>
        @endif

        <!-- Checkbox -->
        <input type="checkbox" id="cat_{{ $cat->id }}" value="{{ $cat->id }}" wire:model.live="selectedCategoryIds" class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
        
        <!-- Label -->
        <label for="cat_{{ $cat->id }}" class="text-sm text-on-surface cursor-pointer select-none font-medium hover:text-primary transition-colors">
            {{ $cat->name }}
        </label>
    </div>

    <!-- Children list -->
    @if($cat->children && $cat->children->count() > 0)
        <div x-show="open" class="pl-md border-l border-outline-variant/30 ml-3 space-y-xxs py-xxs">
            @foreach($cat->children as $child)
                @include('admin.products.category-checkbox-item', ['cat' => $child, 'prefix' => ''])
            @endforeach
        </div>
    @endif
</div>
