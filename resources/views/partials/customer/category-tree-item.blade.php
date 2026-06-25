@php
    $level = $level ?? 0;
    $hasChildren = collect($categoriesList)->where('parent_id', $cat['id'])->isNotEmpty();
    $isExpanded = in_array($cat['id'], $expandedCategories);
    $isActive = (string)$category === (string)$cat['id'];
    
    // Check if this category has an active descendant
    $hasActiveDescendant = false;
    if ($category) {
        $currId = (int)$category;
        while ($currId) {
            $currCat = collect($categoriesList)->firstWhere('id', $currId);
            if ($currCat && $currCat['parent_id'] == $cat['id']) {
                $hasActiveDescendant = true;
                break;
            }
            $currId = $currCat ? $currCat['parent_id'] : null;
        }
    }
    
    $textClass = $level === 0 ? 'text-sm' : 'text-xs';
    $textColor = $isActive || $hasActiveDescendant ? 'text-[#001229] font-bold' : ($level === 0 ? 'text-slate-700' : 'text-slate-600');
    $bgClass = $isActive || $hasActiveDescendant ? 'bg-[#001229]/5' : '';
@endphp

<div class="space-y-1" wire:key="cat-item-{{ $cat['id'] }}">
    <div class="flex items-center justify-between py-1 px-2 rounded hover:bg-slate-50 transition-colors {{ $bgClass }} {{ $textColor }}">
        <button wire:click="selectCategory('{{ $cat['id'] }}')" class="flex-1 text-left {{ $textClass }} hover:text-[#001229] cursor-pointer">
            {{ $cat['name'] }}
        </button>
        
        <div class="flex items-center gap-1.5">
            @if($isActive)
                <span class="material-symbols-outlined text-xs text-gold">check</span>
            @endif
            
            @if($hasChildren)
                <button wire:click.stop="toggleCategory('{{ $cat['id'] }}')" class="p-0.5 rounded hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition-colors">
                    <span class="material-symbols-outlined text-sm transform transition-transform {{ $isExpanded ? 'rotate-180' : '' }}">
                        keyboard_arrow_down
                    </span>
                </button>
            @endif
        </div>
    </div>

    @if($hasChildren && $isExpanded)
        <div class="pl-4 space-y-1 border-l border-slate-100 ml-3">
            @foreach($categoriesList as $subCat)
                @if($subCat['parent_id'] === $cat['id'])
                    @include('partials.customer.category-tree-item', ['cat' => $subCat, 'level' => $level + 1])
                @endif
            @endforeach
        </div>
    @endif
</div>
