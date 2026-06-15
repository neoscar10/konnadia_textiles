@props([
    'name',
    'image',
    'count' => null,
    'url' => '#'
])

<a href="{{ $url }}" class="group relative block overflow-hidden rounded-xl border border-outline-variant/20 shadow-ambient aspect-[3/4] lg:aspect-video bg-slate-900">
    <!-- Image -->
    <img src="{{ $image }}" alt="{{ $name }}" class="absolute inset-0 h-full w-full object-cover opacity-80 group-hover:scale-105 transition-transform duration-500">
    
    <!-- Gradient overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>

    <!-- Content -->
    <div class="absolute inset-x-0 bottom-0 p-5 flex items-end justify-between">
        <div>
            <h3 class="text-lg font-bold text-white tracking-tight group-hover:text-gold transition-colors">{{ $name }}</h3>
            @if($count)
                <p class="text-xs text-slate-300 mt-0.5 font-medium">{{ $count }} Items</p>
            @endif
        </div>
        
        <div class="w-8 h-8 rounded-full bg-white/10 group-hover:bg-gold text-white group-hover:text-[#001229] flex items-center justify-center backdrop-blur-sm transition-all">
            <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </div>
    </div>
</a>
