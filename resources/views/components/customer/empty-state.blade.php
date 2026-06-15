@props([
    'icon' => 'sentiment_dissatisfied',
    'title' => 'No Data Found',
    'description' => 'We couldn\'t find any records matching your request.',
    'actionText' => null,
    'actionUrl' => null
])

<div class="flex flex-col items-center justify-center text-center p-8 bg-white rounded-xl border border-outline-variant/30 shadow-ambient py-16">
    <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 mb-4 ring-8 ring-slate-50/50">
        <span class="material-symbols-outlined text-4xl" style="font-variation-settings: 'FILL' 0">{{ $icon }}</span>
    </div>
    
    <h3 class="text-lg font-bold text-[#001229] mb-1.5">{{ $title }}</h3>
    <p class="text-sm text-slate-500 max-w-sm mb-6 leading-relaxed">{{ $description }}</p>

    @if($actionText && $actionUrl)
        <a href="{{ $actionUrl }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg text-sm font-semibold bg-[#001229] text-white hover:bg-slate-800 transition-colors shadow-sm">
            {{ $actionText }}
        </a>
    @endif
</div>
