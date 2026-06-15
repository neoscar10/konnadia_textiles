<div {{ $attributes->merge(['class' => 'bg-surface-container-lowest rounded-xl border border-outline-variant/30 card-shadow']) }}>
    @isset($header)
        <div class="px-xl py-lg border-b border-outline-variant/30 {{ $headerClass ?? '' }}">
            {{ $header }}
        </div>
    @endisset

    <div class="p-xl {{ $bodyClass ?? '' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="px-xl py-lg border-t border-outline-variant/30 bg-surface-container-low/50 {{ $footerClass ?? '' }}">
            {{ $footer }}
        </div>
    @endisset
</div>
