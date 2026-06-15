@props(['title', 'value', 'icon', 'trend' => null, 'trendType' => 'up'])

<div class="bg-surface-container-lowest p-lg rounded-xl card-shadow border border-outline-variant/30 flex flex-col justify-between h-32 hover:-translate-y-1 transition-transform">
    <div class="flex justify-between items-start">
        <p class="font-label-md text-on-surface-variant uppercase tracking-wider">{{ $title }}</p>
        <span class="material-symbols-outlined text-primary/40 text-[24px]" style="font-variation-settings: 'FILL' 1;">{{ $icon }}</span>
    </div>
    <div class="flex items-end justify-between mt-sm">
        <h3 class="font-headline-md text-primary">{{ $value }}</h3>
        
        @if($trend)
            @if($trendType === 'up')
                <div class="flex items-center text-xs text-[#0F8A46] font-medium bg-[#E6F4EA] px-xs py-0.5 rounded">
                    <span class="material-symbols-outlined text-[14px]">trending_up</span>
                    {{ $trend }}
                </div>
            @elseif($trendType === 'down')
                <div class="flex items-center text-xs text-error font-medium bg-error-container/50 px-xs py-0.5 rounded">
                    <span class="material-symbols-outlined text-[14px]">trending_down</span>
                    {{ $trend }}
                </div>
            @else
                <div class="flex items-center text-xs text-on-surface-variant font-medium bg-surface-container px-xs py-0.5 rounded">
                    <span class="material-symbols-outlined text-[14px]">trending_flat</span>
                    {{ $trend }}
                </div>
            @endif
        @endif
    </div>
</div>
