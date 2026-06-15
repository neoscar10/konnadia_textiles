@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => []
])

<div class="mb-6">
    @if(!empty($breadcrumbs))
        <nav class="flex items-center gap-1.5 text-xs text-slate-500 mb-2">
            @foreach($breadcrumbs as $label => $url)
                @if(!$loop->last)
                    <a href="{{ $url }}" class="hover:text-gold hover:underline transition-colors">{{ $label }}</a>
                    <span class="material-symbols-outlined text-[10px] text-slate-400">chevron_right</span>
                @else
                    <span class="text-slate-600 font-medium">{{ $label }}</span>
                @endif
            @endforeach
        </nav>
    @endif

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#001229] tracking-tight">{{ $title }}</h1>
            @if($subtitle)
                <p class="text-sm text-slate-500 mt-1">{{ $subtitle }}</p>
            @endif
        </div>

        @if(isset($actions))
            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
