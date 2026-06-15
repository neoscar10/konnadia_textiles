@props([
    'headerClass' => '',
    'bodyClass' => '',
    'footerClass' => ''
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-outline-variant/30 shadow-ambient overflow-hidden']) }}>
    @isset($header)
        <div class="px-5 py-4 border-b border-outline-variant/20 {{ $headerClass }}">
            {{ $header }}
        </div>
    @endisset

    <div class="p-5 {{ $bodyClass }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="px-5 py-4 border-t border-outline-variant/20 bg-slate-50/50 {{ $footerClass }}">
            {{ $footer }}
        </div>
    @endisset
</div>
