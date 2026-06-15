<div class="space-y-xs">
    <div class="flex items-center justify-between py-xs px-sm rounded-lg cursor-pointer hover:bg-surface-container-high transition-all {{ $currentCategoryId == $item->id ? 'bg-secondary/10 text-primary font-semibold border-l-2 border-secondary pl-xs' : 'text-on-surface-variant' }}"
         wire:click="selectCategory({{ $item->id }})">
        <div class="flex items-center gap-xs">
            <span class="material-symbols-outlined text-[18px] text-secondary select-none">folder</span>
            <span class="text-sm truncate max-w-[180px]">{{ $item->name }}</span>
        </div>
        @if(!$item->is_active)
        <span class="w-1.5 h-1.5 rounded-full bg-outline-variant/60" title="Inactive"></span>
        @endif
    </div>
    
    @if(!empty($openFolderIds) && in_array($item->id, $openFolderIds) && $item->children && $item->children->count() > 0)
    <div class="pl-md border-l border-outline-variant/20 ml-sm space-y-xs mt-xs">
        @foreach($item->children as $child)
            @include('admin.categories.tree-item', ['item' => $child, 'openFolderIds' => $openFolderIds])
        @endforeach
    </div>
    @endif
</div>
