{{--
    Recursive tree-item partial for the category left panel.
    Variables: $item (Category), $openFolderIds (array of ancestor ids)
--}}
@php
    $isOpen     = in_array($item->id, $openFolderIds);
    $isCurrent  = $currentCategoryId === $item->id;
    $hasChildren = $item->children->count() > 0;
@endphp

<div class="w-full">
    <div class="flex items-center gap-xs py-xs px-sm rounded-lg transition-all cursor-pointer group
                {{ $isCurrent ? 'bg-primary text-on-primary font-semibold' : 'hover:bg-surface-container-high text-on-surface-variant' }}"
         wire:click="selectCategory({{ $item->id }})">

        @if($item->is_leaf)
            <span class="material-symbols-outlined text-[18px] shrink-0 select-none
                         {{ $isCurrent ? 'text-on-primary' : 'text-secondary' }}">bookmark</span>
        @else
            <span class="material-symbols-outlined text-[18px] shrink-0 select-none
                         {{ $isCurrent ? 'text-on-primary' : ($isOpen ? 'text-primary' : 'text-outline') }}">
                {{ $isOpen ? 'folder_open' : 'folder' }}
            </span>
        @endif

        <span class="flex-1 text-sm truncate {{ $isCurrent ? '' : ($item->is_active ? '' : 'opacity-50 italic') }}">
            {{ $item->name }}
        </span>

        @if($hasChildren && !$item->is_leaf)
            <span class="material-symbols-outlined text-[16px] shrink-0 select-none transition-transform
                         {{ $isCurrent ? 'text-on-primary/70' : 'text-outline/60' }}
                         {{ $isOpen ? 'rotate-90' : '' }}">chevron_right</span>
        @endif
    </div>

    @if($hasChildren && $isOpen && !$item->is_leaf)
        <div class="pl-lg space-y-xs border-l border-outline-variant/20 ml-sm mt-xs">
            @foreach($item->children as $child)
                @include('admin.categories.tree-item', ['item' => $child])
            @endforeach
        </div>
    @endif
</div>
