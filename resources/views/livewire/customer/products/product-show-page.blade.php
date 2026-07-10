<div>
    <!-- Breadcrumbs -->
    <x-customer.page-title 
        title="{{ $title }}" 
        subtitle="SKU: {{ $currentSku }} | {{ $stockLabel }}"
        :breadcrumbs="$breadcrumb"
    />

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-12">
        
        <!-- Left: Image Gallery (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-slate-50 border border-outline-variant/30 rounded-xl aspect-[4/3] relative shadow-ambient flex items-center justify-center p-4">
                <img src="{{ $activeImage }}" alt="Product image" class="max-w-full max-h-full object-contain">
                <button class="absolute bottom-3 right-3 w-9 h-9 rounded-full bg-white/80 hover:bg-white flex items-center justify-center shadow-md backdrop-blur-xs text-slate-700 transition-all">
                    <span class="material-symbols-outlined text-lg">zoom_in</span>
                </button>
            </div>

            <!-- Thumbnails -->
            <div class="flex items-center gap-3">
                @foreach($media as $item)
                    <button wire:click="$set('activeImage', '{{ $item['url'] }}')" class="w-20 h-20 rounded-lg border-2 bg-white overflow-hidden shadow-sm transition-all {{ $activeImage === $item['url'] ? 'border-gold scale-95' : 'border-outline-variant/20 hover:border-slate-400' }}">
                        <img src="{{ $item['url'] }}" alt="Thumb" class="w-full h-full object-cover">
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Middle: Product specifications and selectors (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ $brand }}</span>
                <h2 class="text-xl font-extrabold text-[#001229] mt-0.5">{{ $title }}</h2>
                <div class="flex items-center gap-3 mt-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $stockStatus === 'out_of_stock' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/50' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/50' }}">
                        {{ $stockLabel }}
                    </span>
                    <span class="text-xs text-slate-500 font-medium">SKU: {{ $currentSku }}</span>
                </div>
            </div>

            <!-- Price display -->
            <div class="bg-slate-50 rounded-xl p-4 border border-outline-variant/10">
                @auth
                <span class="text-xs text-slate-400 font-semibold block">Wholesale Price (Excl. GST)</span>
                
                <div class="flex items-baseline gap-2 mt-0.5">
                    <p class="text-2xl font-black text-[#001229]">₹{{ number_format($pricePerPiece, 2) }}</p>
                </div>
                
                <p class="text-[10px] text-slate-500 font-medium mt-1">
                    @if($gstPercentage !== null)
                        GST {{ $gstPercentage }}% extra
                    @else
                        GST not configured
                    @endif
                </p>
                @else
                <span class="text-xs text-slate-400 font-semibold block">Wholesale Price</span>
                <p class="text-lg font-bold text-gold mt-1">
                    <a href="{{ route('login') }}" class="underline hover:text-gold-dark">Login to view price</a>
                </p>
                @endauth

                {{-- Prominent MOQ badge --}}
                <div class="mt-3 flex items-center gap-2 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200">
                    <span class="material-symbols-outlined text-base text-amber-600 select-none">info</span>
                    <span class="text-xs font-bold text-amber-800">
                        Min. Order Qty: 
                        <span class="text-amber-900">
                            @php
                                $purchasableUnit = $hasLvl2Unit 
                                    ? collect($units)->firstWhere('level', 2) 
                                    : collect($units)->firstWhere('level', 1);
                                $unitNameLabel = $purchasableUnit ? $purchasableUnit['name'] : 'Piece';
                            @endphp
                            {{ $minimumOrderQuantity }} {{ $unitNameLabel }}{{ $minimumOrderQuantity === 1 ? '' : 's' }}
                        </span>
                    </span>
                </div>
            </div>

            <!-- Price Warning Notice Banner -->
            <div class="bg-amber-50 border border-amber-200/60 rounded-xl p-3.5 flex items-start gap-2.5 text-amber-800 shadow-sm select-none">
                <span class="material-symbols-outlined text-amber-500 text-lg mt-0.5 select-none">info</span>
                <div class="text-xs font-semibold leading-relaxed">
                    Prices are subject to change. Please contact the company for final price confirmation.
                </div>
            </div>

            <!-- Fabric Info / Description -->
            <div>
                <h4 class="text-xs font-bold text-[#001229] uppercase tracking-wider mb-2">Description &amp; Specifications</h4>
                <div class="prose max-w-none text-xs text-slate-500 leading-relaxed">
                    {!! $descriptionHtml !!}
                </div>
            </div>

            <!-- Dynamic Variation Selectors -->
            @foreach($variations as $group)
                <div>
                    <h4 class="text-xs font-bold text-[#001229] uppercase tracking-wider mb-2">Select {{ $group['name'] }}</h4>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach($group['values'] as $val)
                            @if($group['display_type'] === 'color')
                                <button type="button" 
                                        wire:click="selectVariationValue('{{ $group['name'] }}', '{{ $val['value'] }}')" 
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold border transition-all" 
                                        :class="'{{ $selectedValues[$group['name']] ?? '' }}' === '{{ $val['value'] }}' ? 'border-[#001229] ring-2 ring-gold/20' : 'border-outline-variant/30 bg-slate-50/55 hover:border-slate-400'">
                                    <span class="w-4 h-4 rounded-full border border-slate-200" style="background-color: {{ $val['color_hex'] ?? '#ccc' }}"></span>
                                    <span>{{ $val['value'] }}</span>
                                </button>
                            @else
                                <button type="button" 
                                        wire:click="selectVariationValue('{{ $group['name'] }}', '{{ $val['value'] }}')" 
                                        class="px-3 py-2 rounded-lg text-xs font-bold border transition-all shadow-sm" 
                                        :class="'{{ $selectedValues[$group['name']] ?? '' }}' === '{{ $val['value'] }}' ? 'bg-[#001229] text-white border-[#001229]' : 'bg-white border-outline-variant/40 text-[#001229] hover:border-slate-400'">
                                    {{ $val['value'] }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Right: Purchase configuration card (3 cols) -->
        <div class="lg:col-span-3">
            @auth
            <x-customer.card bodyClass="p-5 space-y-6">
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Purchase Configuration</span>
                </x-slot>
 
                <!-- Quantity Selector per Unit -->
                <div class="space-y-4">
                    <h5 class="text-sm font-bold text-slate-700">Order Quantity by Unit</h5>
 
                    @if($hasLvl2Unit)
                        @php
                            $lvl1 = collect($units)->firstWhere('level', 1);
                            $lvl2 = collect($units)->firstWhere('level', 2);
                        @endphp
                        @if($lvl1 && $lvl2)
                            <div class="p-3 bg-secondary/10 border border-secondary/20 rounded-xl flex items-start gap-2 mb-2">
                                <span class="material-symbols-outlined text-secondary text-base select-none mt-0.5">info</span>
                                <div class="text-[11px] font-semibold text-secondary">
                                    Only <strong>{{ $lvl2['name'] }}</strong> purchases are allowed for this product.<br>
                                    Relation: 1 {{ $lvl2['name'] }} = {{ (int)$lvl2['conversion_to_base'] }} {{ $lvl1['name'] }}s.
                                </div>
                            </div>
                        @endif
                    @endif
 
                    @foreach($units as $u)
                        @if(!$hasLvl2Unit || $u['level'] === 2)
                            <div class="space-y-1 bg-slate-50 p-2.5 rounded-lg border border-outline-variant/20">
                                <div class="flex justify-between items-center">
                                    <label class="text-xs text-slate-500 font-bold uppercase block">Buy in {{ $u['name'] }}s</label>
                                    @if($u['level'] === 2)
                                        <span class="text-[10px] text-slate-400 block">1 {{ ucfirst($u['name']) }} = {{ (int)$u['conversion_to_base'] }} Pieces</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center justify-between border border-outline-variant/30 rounded-lg bg-white p-1 flex-1">
                                        <button type="button" wire:click="decrementUnitQuantity({{ $u['id'] }})" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-all focus:outline-none">
                                            <span class="material-symbols-outlined text-lg">remove</span>
                                        </button>
                                        <input type="number" wire:model.live="unitQuantities.{{ $u['id'] }}" min="0" class="w-12 text-center bg-transparent border-none focus:outline-none focus:ring-0 text-base font-extrabold text-[#001229] py-1">
                                        <button type="button" wire:click="incrementUnitQuantity({{ $u['id'] }})" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-all focus:outline-none">
                                            <span class="material-symbols-outlined text-lg">add</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
 
                    <!-- Queued Items List -->
                    @if(!empty($queuedItems))
                        <style>
                            @keyframes bulge-pulse {
                                0%, 100% { transform: scale(1); }
                                50% { transform: scale(1.04); }
                            }
                            .animate-bulge {
                                display: inline-block;
                                animation: bulge-pulse 2.5s ease-in-out infinite;
                                transform-origin: left center;
                            }
                        </style>
                        <div class="space-y-2 bg-[#f8fafc] border border-slate-200 rounded-lg p-3">
                            <span class="text-xs font-extrabold text-gold uppercase tracking-wider block border-b border-slate-200 pb-1.5 mb-1.5 select-none animate-bulge">Current Selection:</span>
                            <div class="space-y-1.5">
                                @foreach($queuedItems as $item)
                                    <div class="flex items-center justify-between bg-white border border-outline-variant/20 rounded-md px-2.5 py-1.5 text-xs text-slate-700">
                                        <span class="font-semibold">{{ $item['quantity'] }} {{ $item['unit_name'] }}(s)</span>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[10px] text-slate-400">({{ $item['quantity'] * $item['conversion_to_base'] }} pcs)</span>
                                            <button type="button" wire:click="removeUnitFromQueue({{ $item['unit_id'] }})" class="text-rose-500 hover:text-rose-700 focus:outline-none" title="Remove">
                                                <span class="material-symbols-outlined text-[16px] font-bold">close</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
 
                    <div class="flex justify-between items-center text-[10px] text-slate-400 font-semibold select-none">
                        @php
                            $totalPiecesQueued = collect($queuedItems)->sum(fn($i) => $i['quantity'] * $i['conversion_to_base']);
                            $purchasableUnit = $hasLvl2Unit 
                                ? collect($units)->firstWhere('level', 2) 
                                : collect($units)->firstWhere('level', 1);
                            $totalUnitsQueued = $purchasableUnit 
                                ? collect($queuedItems)->where('unit_id', $purchasableUnit['id'])->sum('quantity') 
                                : 0;
                            $unitLabelPlural = $purchasableUnit ? $purchasableUnit['name'] . 's' : 'Pieces';
                        @endphp
                        @if($hasLvl2Unit)
                            <span>Total Selected: {{ $totalUnitsQueued }} {{ $unitLabelPlural }} ({{ $totalPiecesQueued }} pcs)</span>
                            <span>MOQ: {{ $minimumOrderQuantity }} {{ $unitLabelPlural }}</span>
                        @else
                            <span>Total Selected: {{ $totalPiecesQueued }} Pieces</span>
                            <span>MOQ: {{ $minimumOrderQuantity }} Pieces</span>
                        @endif
                    </div>
                </div>
 
                {{-- Live MOQ warning if below minimum --}}
                @php
                    $isBelowMoq = false;
                    if ($hasLvl2Unit) {
                        $isBelowMoq = $totalUnitsQueued > 0 && $totalUnitsQueued < $minimumOrderQuantity;
                    } else {
                        $isBelowMoq = $totalPiecesQueued > 0 && $totalPiecesQueued < $minimumOrderQuantity;
                    }
                @endphp
                @if($isBelowMoq)
                    <div class="flex items-start gap-2 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 mt-2">
                        <span class="material-symbols-outlined text-sm text-rose-500 select-none mt-0.5">warning</span>
                        <span class="text-xs font-semibold text-rose-700">
                            Minimum order is <strong>{{ $minimumOrderQuantity }} {{ $hasLvl2Unit ? $purchasableUnit['name'] . 's' : 'pieces' }}</strong>. Please select more.
                        </span>
                    </div>
                @endif
 
                <!-- Pricing Summary Block -->
                <div class="border-t border-slate-100 pt-4 space-y-2">
                    <div class="flex justify-between text-xs text-slate-500 font-medium">
                        <span>Items Subtotal</span>
                        <span class="font-bold text-slate-800">₹{{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500 font-medium">
                        <span>
                            @if($gstPercentage !== null)
                                GST ({{ $gstPercentage }}%)
                            @else
                                GST (not configured)
                            @endif
                        </span>
                        <span class="font-bold text-slate-800">₹{{ number_format($gstAmount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-[#001229] font-extrabold pt-2 border-t border-dashed border-slate-200">
                        <span>Estimated Total</span>
                        <span class="text-[#001229]">₹{{ number_format($total, 2) }}</span>
                    </div>
                </div>
 
                <!-- Add to Cart CTA -->
                <button type="button" 
                        wire:click="addToCart"
                        @if(!$isPurchasable) disabled @endif
                        class="w-full flex items-center justify-center gap-2 py-3 rounded-lg text-xs font-bold text-white transition-colors shadow-sm {{ $isPurchasable ? 'bg-[#001229] hover:bg-slate-800' : 'bg-slate-300 cursor-not-allowed' }}">
                    <span class="material-symbols-outlined text-sm">shopping_cart</span> Add to Wholesale Cart
                </button>
            </x-customer.card>
            @else
            <x-customer.card bodyClass="p-5 text-center space-y-4">
                <span class="material-symbols-outlined text-4xl text-gold">lock</span>
                <h5 class="text-sm font-bold text-[#001229]">Wholesale Ordering Restricted</h5>
                <p class="text-xs text-slate-500 leading-relaxed">Please sign in to your B2B account to customize quantities and add items to your wholesale cart.</p>
                <a href="{{ route('login') }}" class="w-full flex items-center justify-center gap-2 py-3 rounded-lg text-xs font-bold text-white bg-[#001229] hover:bg-slate-800 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-sm">login</span> Sign In to Order
                </a>
            </x-customer.card>
            @endauth
        </div>

    </div>

    <!-- Mobile Sticky Footer Add to Cart Bar -->
    @auth
    <div class="lg:hidden fixed bottom-16 left-0 right-0 bg-white border-t border-outline-variant/30 p-4 z-40 flex items-center justify-between shadow-ambient">
        <div>
            <span class="text-[10px] text-slate-400 font-semibold block uppercase">Total Subtotal</span>
            <span class="text-base font-black text-[#001229]">₹{{ number_format($total, 2) }}</span>
        </div>
        <button type="button" 
                wire:click="addToCart"
                @if(!$isPurchasable) disabled @endif
                class="flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-lg text-xs font-bold text-white {{ $isPurchasable ? 'bg-[#001229]' : 'bg-slate-300 cursor-not-allowed' }}">
            <span class="material-symbols-outlined text-sm">shopping_cart</span> Add to Cart
        </button>
    </div>
    @else
    <div class="lg:hidden fixed bottom-16 left-0 right-0 bg-white border-t border-outline-variant/30 p-4 z-40 flex items-center justify-between shadow-ambient">
        <div>
            <span class="text-[10px] text-slate-400 font-semibold block uppercase">Ordering Restricted</span>
            <span class="text-xs text-slate-500 font-bold">Please log in to purchase</span>
        </div>
        <a href="{{ route('login') }}" class="flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-lg text-xs font-bold text-white bg-[#001229]">
            <span class="material-symbols-outlined text-sm">login</span> Log In
        </a>
    </div>
    @endauth
</div>
