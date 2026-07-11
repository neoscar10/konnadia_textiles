@php
    $hasChildren = $item->children->count() > 0;
    $isSel = in_array($item->id, $selectedCategoryIds);
@endphp

<div class="w-full py-0.5" wire:key="node-{{ $item->id }}">
    <div class="flex items-center gap-sm group select-none py-1 hover:bg-surface-container/30 rounded px-2 transition-colors">
        <!-- Expand/Collapse Chevron -->
        <div class="w-6 h-6 flex items-center justify-center">
            @if($hasChildren)
                <button type="button" 
                        @click.stop="expanded[{{ $item->id }}] = !expanded[{{ $item->id }}]" 
                        class="text-outline hover:text-primary transition-colors focus:outline-none flex items-center justify-center">
                    <span class="material-symbols-outlined text-[18px] transition-transform duration-200"
                          :class="(expanded[{{ $item->id }}] || isSearching) ? 'rotate-90' : ''">
                        chevron_right
                    </span>
                </button>
            @endif
        </div>

        <!-- Checkbox -->
        <input type="checkbox" 
               id="modal_cat_{{ $item->id }}" 
               value="{{ $item->id }}" 
               wire:click="toggleCategorySelection({{ $item->id }})"
               @if($isSel) checked @endif
               class="w-4 h-4 rounded border-outline-variant text-[#5c44c4] focus:ring-[#5c44c4] cursor-pointer">

        <!-- Label -->
        <label for="modal_cat_{{ $item->id }}" class="flex-1 flex items-center gap-xs text-sm text-on-surface cursor-pointer py-0.5">
            <!-- Icon based on leaf or folder -->
            @if($item->is_leaf)
                <span class="material-symbols-outlined text-[18px] select-none shrink-0 {{ $isSel ? 'text-primary' : 'text-outline' }}">bookmark</span>
            @else
                <span class="material-symbols-outlined text-[18px] select-none shrink-0 {{ $isSel ? 'text-primary' : 'text-outline' }}"
                      x-text="(expanded[{{ $item->id }}] || isSearching) ? 'folder_open' : 'folder'">folder</span>
            @endif
            
            <span class="{{ $isSel ? 'font-bold text-primary' : 'text-on-surface' }}">
                {{ $item->name }}
            </span>
        </label>
    </div>

    <!-- Children container -->
    @if($hasChildren)
        <div x-show="expanded[{{ $item->id }}] || isSearching" 
             class="pl-lg border-l border-outline-variant/20 ml-[23px] mt-0.5 space-y-0.5">
            @foreach($item->children as $child)
                @include('admin.tags.category-tree-node', ['item' => $child, 'selectedCategoryIds' => $selectedCategoryIds])
            @endforeach
        </div>
    @endif
</div>
