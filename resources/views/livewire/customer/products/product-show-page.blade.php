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

                {{-- Prominent MOQ badge --}}
                <div class="mt-3 flex items-center gap-2 px-3 py-2 rounded-lg bg-amber-50 border border-amber-200">
                    <span class="material-symbols-outlined text-base text-amber-600 select-none">info</span>
                    <span class="text-xs font-bold text-amber-800">
                        Min. Order Qty: <span class="text-amber-900">{{ $minimumOrderQuantity }} {{ $minimumOrderQuantity === 1 ? 'Piece' : 'Pieces' }}</span>
                    </span>
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
            <x-customer.card bodyClass="p-5 space-y-6">
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Purchase Configuration</span>
                </x-slot>



                <!-- Quantity Selector per Unit -->
                <div class="space-y-4">
                    <h5 class="text-sm font-bold text-slate-700">Order Quantity by Unit</h5>

                    @foreach($units as $u)
                        <div class="space-y-1 bg-slate-50 p-2.5 rounded-lg border border-outline-variant/20">
                            <div class="flex justify-between items-center">
                                <label class="text-xs text-slate-500 font-bold uppercase block">Buy in {{ $u['name'] }}s</label>
                                @if($u['level'] === 2)
                                    <span class="text-[10px] text-slate-400 block">1 {{ ucfirst($u['name']) }} = {{ (int)$u['conversion_to_base'] }} Pieces</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center border border-outline-variant/30 rounded-lg bg-white p-1 flex-1">
                                    <button type="button" wire:click="decrementUnitQuantity({{ $u['id'] }})" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-all focus:outline-none">
                                        <span class="material-symbols-outlined text-lg">remove</span>
                                    </button>
                                    <input type="number" wire:model.live="unitQuantities.{{ $u['id'] }}" min="{{ $u['min_qty'] ?? 1 }}" class="w-12 text-center bg-transparent border-none focus:outline-none focus:ring-0 text-base font-extrabold text-[#001229] py-1">
                                    <button type="button" wire:click="incrementUnitQuantity({{ $u['id'] }})" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-all focus:outline-none">
                                        <span class="material-symbols-outlined text-lg">add</span>
                                    </button>
                                </div>
                                <button type="button" wire:click="addUnitToQueue({{ $u['id'] }})" class="px-3 py-2 bg-[#001229] hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition-all shadow-sm">
                                    Add
                                </button>
                            </div>
                        </div>
                    @endforeach

                    <!-- Queued Items List -->
                    @if(!empty($queuedItems))
                        <div class="space-y-2 bg-[#f8fafc] border border-slate-200 rounded-lg p-3">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Current Selection:</span>
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
                        @endphp
                        <span>Total Selected: {{ $totalPiecesQueued }} Pieces</span>
                        <span>MOQ: {{ $minimumOrderQuantity }} Pieces</span>
                    </div>
                </div>

                {{-- Live MOQ warning if below minimum --}}
                @if($totalPiecesQueued > 0 && $totalPiecesQueued < $minimumOrderQuantity)
                    <div class="flex items-start gap-2 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 mt-2">
                        <span class="material-symbols-outlined text-sm text-rose-500 select-none mt-0.5">warning</span>
                        <span class="text-xs font-semibold text-rose-700">
                            Minimum order is <strong>{{ $minimumOrderQuantity }} pieces</strong>. Please select more.
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
        </div>

    </div>

    <!-- Bottom Technical Specs & Trust Badges (Desktop) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pt-8 border-t border-slate-200">
        <!-- Specs -->
        <div class="lg:col-span-2 space-y-4">
            <h3 class="text-base font-extrabold text-[#001229]">Technical Specifications</h3>
            <x-customer.card bodyClass="p-0">
                <table class="w-full text-xs text-slate-700">
                    <tbody>
                        <tr class="border-b border-outline-variant/10">
                            <td class="px-5 py-3 font-bold bg-slate-50/50 text-slate-600 w-1/3">Composition</td>
                            <td class="px-5 py-3">100% Combed Cotton</td>
                        </tr>
                        <tr class="border-b border-outline-variant/10">
                            <td class="px-5 py-3 font-bold bg-slate-50/50 text-slate-600">Fabric Weight</td>
                            <td class="px-5 py-3">180 GSM (Heavyweight)</td>
                        </tr>
                        <tr class="border-b border-outline-variant/10">
                            <td class="px-5 py-3 font-bold bg-slate-50/50 text-slate-600">Weave Style</td>
                            <td class="px-5 py-3">Premium Fine Pique / Twill</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-3 font-bold bg-slate-50/50 text-slate-600">Origin</td>
                            <td class="px-5 py-3">Manufactured in Surat, Gujarat, India</td>
                        </tr>
                    </tbody>
                </table>
            </x-customer.card>
        </div>

        <!-- Trust Badges -->
        <div class="space-y-4">
            <h3 class="text-base font-extrabold text-[#001229]">Wholesale Assurance</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-3 bg-white border border-outline-variant/20 rounded-xl">
                    <span class="material-symbols-outlined text-emerald-600 text-3xl">local_shipping</span>
                    <div>
                        <h5 class="text-xs font-bold text-[#001229]">Express Delivery</h5>
                        <p class="text-[10px] text-slate-400">Dispatch within 48 hours via surface cargo.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 bg-white border border-outline-variant/20 rounded-xl">
                    <span class="material-symbols-outlined text-emerald-600 text-3xl">verified</span>
                    <div>
                        <h5 class="text-xs font-bold text-[#001229]">Quality Inspected</h5>
                        <p class="text-[10px] text-slate-400">Strict factory defect checks per piece.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 bg-white border border-outline-variant/20 rounded-xl">
                    <span class="material-symbols-outlined text-emerald-600 text-3xl">currency_rupee</span>
                    <div>
                        <h5 class="text-xs font-bold text-[#001229]">Flexible Credit Terms</h5>
                        <p class="text-[10px] text-slate-400">Authorized B2B billing against credit profiles.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Sticky Footer Add to Cart Bar -->
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
</div>
