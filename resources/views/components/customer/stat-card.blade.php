@props([
    'title',
    'value',
    'icon',
    'trend' => null,
    'trendType' => 'up'
])

<div class="bg-white p-5 rounded-xl border border-outline-variant/30 shadow-ambient flex flex-col justify-between h-32 hover:-translate-y-0.5 transition-transform duration-200">
    <div class="flex justify-between items-start">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $title }}</p>
        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-600">
            <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1">{{ $icon }}</span>
        </div>
    </div>
    <div class="flex items-end justify-between mt-2">
        <h3 class="text-2xl font-extrabold text-[#001229]">{{ $value }}</h3>
        
        @if($trend)
            @if($trendType === 'up')
                <div class="flex items-center gap-0.5 text-xs text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded-full">
                    <span class="material-symbols-outlined text-xs">trending_up</span>
                    {{ $trend }}
                </div>
            @elseif($trendType === 'down')
                <div class="flex items-center gap-0.5 text-xs text-rose-700 font-semibold bg-rose-50 px-2 py-0.5 rounded-full">
                    <span class="material-symbols-outlined text-xs">trending_down</span>
                    {{ $trend }}
                </div>
            @else
                <div class="flex items-center gap-0.5 text-xs text-slate-600 font-semibold bg-slate-100 px-2 py-0.5 rounded-full">
                    {{ $trend }}
                </div>
            @endif
        @endif
    </div>
</div>
