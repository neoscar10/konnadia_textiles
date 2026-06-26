<div>
    <!-- Welcome section -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">B2B Wholesale Portal</span>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#001229] tracking-tight mt-0.5">Welcome back, {{ $customer['company_name'] ?? 'Valued Customer' }}</h1>
            <p class="text-sm text-slate-500">Manage your wholesale orders and check order statuses.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 text-xs font-bold text-slate-600 bg-white border border-outline-variant/30 px-3 py-1.5 rounded-lg shadow-ambient">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active Account ({{ $customer['level'] ?? 'Standard Partner' }} - {{ $customer['customer_number'] ?? '' }})
            </div>
            <button wire:click="refreshDashboard" class="flex items-center justify-center p-2 rounded-lg bg-white border border-outline-variant/30 text-slate-600 hover:text-[#001229] hover:border-[#001229] shadow-ambient transition-all" title="Refresh Dashboard">
                <span class="material-symbols-outlined text-lg">refresh</span>
            </button>
        </div>
    </div>



    @if(!empty($dynamicSections))
        @foreach($dynamicSections as $section)
            @if($section['type'] === 'banner')
                @foreach($section['items'] as $item)
                    <div class="mb-8 relative w-full rounded-2xl overflow-hidden min-h-[260px] md:min-h-[320px] bg-slate-900 flex items-center justify-start p-8 md:p-12 border border-outline-variant/10 shadow-ambient">
                        @if($item['image_url'])
                            <img src="{{ $item['image_url'] }}" class="absolute inset-0 w-full h-full object-cover opacity-70 hover:scale-105 transition-transform duration-700">
                        @endif
                        <div class="relative z-10 text-left space-y-3 max-w-lg text-white">
                            <span class="text-[10px] font-bold text-gold uppercase tracking-widest block">{{ $section['title'] ?: 'OFFER' }}</span>
                            <h3 class="text-2xl md:text-4xl font-black tracking-tight leading-tight">{{ $item['title'] }}</h3>
                            @if($item['subtitle'])
                                <p class="text-sm text-slate-200 leading-relaxed font-semibold">{{ $item['subtitle'] }}</p>
                            @endif
                            @if($item['link']['url'])
                                <a href="{{ $item['link']['url'] }}" target="{{ $item['link']['target'] ?? '_self' }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-gold text-[#001229] text-xs font-black transition-all hover:bg-white hover:scale-105 shadow-md">
                                    {{ $item['cta_label'] ?: 'Shop Now' }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif

            <!-- Category Slider -->
            @if($section['type'] === 'category_slider')
                <div class="mb-8 space-y-4">
                    <div class="flex flex-col gap-0.5">
                        <h3 class="text-base font-extrabold text-[#001229]">{{ $section['title'] ?: 'Shop by Category' }}</h3>
                        @if($section['subtitle'])
                            <p class="text-xs text-slate-500 font-semibold">{{ $section['subtitle'] }}</p>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach($section['items'] as $item)
                            <a href="{{ $item['link']['url'] }}" class="flex flex-col items-center p-lg bg-white border border-outline-variant/20 rounded-2xl shadow-ambient hover:shadow-md hover:border-[#001229]/25 transition-all text-center group">
                                <div class="w-12 h-12 rounded-full bg-[#001229]/5 text-[#001229] flex items-center justify-center group-hover:bg-gold/10 group-hover:text-gold transition-colors">
                                    <span class="material-symbols-outlined text-2xl font-bold select-none">widgets</span>
                                </div>
                                <h4 class="text-xs font-bold text-[#001229] mt-3 group-hover:text-primary transition-colors">{{ $item['category']['name'] }}</h4>
                                <span class="text-[10px] text-slate-400 font-semibold mt-1">{{ $item['category']['products_count'] }} Products</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Product Slider -->
            @if($section['type'] === 'product_slider')
                <div class="mb-8 space-y-4">
                    <div class="flex flex-col gap-0.5">
                        <h3 class="text-base font-extrabold text-[#001229]">{{ $section['title'] ?: 'Featured Products' }}</h3>
                        @if($section['subtitle'])
                            <p class="text-xs text-slate-500 font-semibold">{{ $section['subtitle'] }}</p>
                        @endif
                    </div>
                    <!-- Grid / Horizontal flex list -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach($section['items'] as $item)
                            @php $prod = $item['product']; @endphp
                            <x-customer.product-card 
                                :title="$prod['title']" 
                                :sku="$prod['sku']" 
                                :price="$prod['price']['customer_price']" 
                                :moq="$prod['minimum_order_quantity']" 
                                :image="$prod['primary_image_url']" 
                                :inStock="$prod['stock']['status'] !== 'out_of_stock'"
                                :url="$item['link']['url']"
                                :productId="$prod['id']"
                            />
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Image Slider -->
            @if($section['type'] === 'image_slider')
                <div class="mb-8 space-y-4">
                    <div class="flex flex-col gap-0.5">
                        <h3 class="text-base font-extrabold text-[#001229]">{{ $section['title'] ?: 'Promotions' }}</h3>
                        @if($section['subtitle'])
                            <p class="text-xs text-slate-500 font-semibold">{{ $section['subtitle'] }}</p>
                        @endif
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($section['items'] as $item)
                            <div class="relative rounded-2xl overflow-hidden min-h-[200px] bg-slate-900 flex items-center justify-start p-6 md:p-10 border border-outline-variant/10 shadow-ambient">
                                @if($item['image_url'])
                                    <img src="{{ $item['image_url'] }}" class="absolute inset-0 w-full h-full object-cover opacity-60 hover:scale-105 transition-transform duration-700">
                                @endif
                                <div class="relative z-10 text-left space-y-2 text-white max-w-xs">
                                    <h4 class="text-lg font-black tracking-tight leading-tight">{{ $item['title'] }}</h4>
                                    @if($item['subtitle'])
                                        <p class="text-xs text-slate-200 leading-relaxed font-semibold">{{ $item['subtitle'] }}</p>
                                    @endif
                                    @if($item['link']['url'])
                                        <a href="{{ $item['link']['url'] }}" target="{{ $item['link']['target'] ?? '_self' }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gold text-[#001229] text-[10px] font-black transition-all hover:bg-white shadow-sm mt-1">
                                            {{ $item['cta_label'] ?: 'Shop Now' }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    @endif

    <!-- Variant Selection Modal Overlay -->
    @if($showQuickAddModal && $quickAddProduct)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-4 z-50">
            <div class="bg-white border border-outline-variant/30 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest select-none">{{ $quickAddProduct->brand }}</span>
                        <h3 class="text-base font-extrabold text-[#001229]">{{ $quickAddProduct->title }}</h3>
                    </div>
                    <button type="button" wire:click="$set('showQuickAddModal', false)" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-6 overflow-y-auto max-h-[70vh]">
                    <!-- Price and stock status -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-outline-variant/10 flex justify-between items-center">
                        <div>
                            <span class="text-[10px] text-slate-400 font-semibold uppercase block select-none">Price per piece</span>
                            <div class="flex items-baseline gap-2">
                                <span class="text-xl font-black text-[#001229]">₹{{ number_format($quickAddPricePerPiece, 2) }}</span>
                                @if($quickAddDiscountPercentage > 0)
                                    <span class="text-xs text-slate-400 line-through">₹{{ number_format($quickAddEffectiveBasePrice, 2) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $quickAddStockStatus === 'out_of_stock' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/50' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/50' }}">
                                {{ $quickAddStockLabel }}
                            </span>
                        </div>
                    </div>

                    <!-- Dynamic Variation Selectors -->
                    @foreach($quickAddVariations as $group)
                        <div class="space-y-2">
                            <h4 class="text-xs font-bold text-[#001229] uppercase tracking-wider">Select {{ $group['name'] }}</h4>
                            <div class="flex flex-wrap items-center gap-2">
                                @foreach($group['values'] as $val)
                                    @if($group['display_type'] === 'color')
                                        <button type="button" 
                                                wire:click="selectQuickAddVariationValue('{{ $group['name'] }}', '{{ $val['value'] }}')" 
                                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold border transition-all" 
                                                :class="'{{ $quickAddSelectedValues[$group['name']] ?? '' }}' === '{{ $val['value'] }}' ? 'border-[#001229] ring-2 ring-gold/20' : 'border-outline-variant/30 bg-slate-50/55 hover:border-slate-400'">
                                            <span class="w-4 h-4 rounded-full border border-slate-200" style="background-color: {{ $val['color_hex'] ?? '#ccc' }}"></span>
                                            <span>{{ $val['value'] }}</span>
                                        </button>
                                    @else
                                        <button type="button" 
                                                wire:click="selectQuickAddVariationValue('{{ $group['name'] }}', '{{ $val['value'] }}')" 
                                                class="px-3 py-2 rounded-lg text-xs font-bold border transition-all shadow-sm" 
                                                :class="'{{ $quickAddSelectedValues[$group['name']] ?? '' }}' === '{{ $val['value'] }}' ? 'bg-[#001229] text-white border-[#001229]' : 'bg-white border-outline-variant/40 text-[#001229] hover:border-slate-400'">
                                            {{ $val['value'] }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <!-- Unit Pricing & Conversion Info -->
                    @php
                        $lvl1 = collect($quickAddUnits)->firstWhere('level', 1);
                        $lvl2 = collect($quickAddUnits)->firstWhere('level', 2);
                    @endphp
                    @if($lvl1)
                        <div class="bg-slate-50 border border-outline-variant/10 rounded-xl p-4.5 space-y-2.5">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block select-none">Unit Pricing Details</span>
                            <div class="flex flex-col gap-2 text-xs text-slate-600 font-medium">
                                <div class="flex justify-between items-center">
                                    <span>Base Unit Price ({{ $lvl1['name'] }}):</span>
                                    <span class="font-bold text-slate-900">₹{{ number_format($lvl1['price'], 2) }}</span>
                                </div>
                                @if($quickAddHasLvl2Unit && $lvl2)
                                    <div class="flex justify-between items-center">
                                        <span>Bulk Unit Price ({{ $lvl2['name'] }}):</span>
                                        <span class="font-bold text-slate-900">₹{{ number_format($lvl2['price'], 2) }}</span>
                                    </div>
                                    <div class="text-[11px] text-[#001229] bg-white border border-outline-variant/20 rounded-lg px-3 py-2 font-semibold select-none flex items-center gap-1.5 shadow-sm mt-1">
                                        <span class="material-symbols-outlined text-sm text-gold select-none">info</span>
                                        <span>Relationship: 1 {{ $lvl2['name'] }} = {{ (int)$lvl2['conversion_to_base'] }} {{ $lvl1['name'] }}s</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Quantity Inputs: Dual-unit selection queue --}}
                    <div class="space-y-4">
                        <h5 class="text-sm font-bold text-slate-700">Order Quantity by Unit</h5>

                        @foreach($quickAddUnits as $u)
                            <div class="space-y-1 bg-slate-50 p-2.5 rounded-lg border border-outline-variant/20">
                                <div class="flex justify-between items-center">
                                    <label class="text-xs text-slate-500 font-bold uppercase block">Buy in {{ $u['name'] }}s</label>
                                    @if($u['level'] === 2)
                                        <span class="text-[10px] text-slate-400 block">1 {{ ucfirst($u['name']) }} = {{ (int)$u['conversion_to_base'] }} Pieces</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center border border-outline-variant/30 rounded-lg bg-white p-1 flex-1">
                                        <button type="button" wire:click="decrementQuickAddUnitQuantity({{ $u['id'] }})" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-all focus:outline-none">
                                            <span class="material-symbols-outlined text-lg">remove</span>
                                        </button>
                                        <input type="number" wire:model.live="quickAddUnitQuantities.{{ $u['id'] }}" min="0" class="w-12 text-center bg-transparent border-none focus:outline-none focus:ring-0 text-base font-extrabold text-[#001229] py-1">
                                        <button type="button" wire:click="incrementQuickAddUnitQuantity({{ $u['id'] }})" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-all focus:outline-none">
                                            <span class="material-symbols-outlined text-lg">add</span>
                                        </button>
                                    </div>
                                    <button type="button" wire:click="addQuickAddUnitToQueue({{ $u['id'] }})" class="px-3 py-2 bg-[#001229] hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition-all shadow-sm">
                                        Add
                                    </button>
                                </div>
                            </div>
                        @endforeach

                        <!-- Queued Items List -->
                        @if(!empty($quickAddQueuedItems))
                            <div class="space-y-2 bg-[#f8fafc] border border-slate-200 rounded-lg p-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Current Selection:</span>
                                <div class="space-y-1.5">
                                    @foreach($quickAddQueuedItems as $item)
                                        <div class="flex items-center justify-between bg-white border border-outline-variant/20 rounded-md px-2.5 py-1.5 text-xs text-slate-700">
                                            <span class="font-semibold">{{ $item['quantity'] }} {{ $item['unit_name'] }}(s)</span>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[10px] text-slate-400">({{ $item['quantity'] * $item['conversion_to_base'] }} pcs)</span>
                                                <button type="button" wire:click="removeQuickAddUnitFromQueue({{ $item['unit_id'] }})" class="text-rose-500 hover:text-rose-700 focus:outline-none" title="Remove">
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
                                $totalPiecesDisplay = collect($quickAddQueuedItems)->sum(fn($i) => $i['quantity'] * $i['conversion_to_base']);
                            @endphp
                            <span>Total Selected: {{ $totalPiecesDisplay }} Pieces</span>
                            <span>MOQ: {{ $quickAddMoq }} Pieces</span>
                        </div>
                    </div>

                    {{-- Live MOQ warning if below minimum --}}
                    @if($totalPiecesDisplay > 0 && $totalPiecesDisplay < $quickAddMoq)
                        <div class="flex items-start gap-2 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 mt-2">
                            <span class="material-symbols-outlined text-sm text-rose-500 select-none mt-0.5">warning</span>
                            <span class="text-xs font-semibold text-rose-700">
                                Minimum order is <strong>{{ $quickAddMoq }} pieces</strong>. Please select more.
                            </span>
                        </div>
                    @endif

                    <!-- Pricing Summary -->
                    <div class="border-t border-slate-100 pt-4 space-y-2">
                        <div class="flex justify-between text-xs text-slate-500 font-medium">
                            <span>Subtotal</span>
                            <span class="font-bold text-slate-800">₹{{ number_format($quickAddSubtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-500 font-medium">
                            <span>GST</span>
                            <span class="font-bold text-slate-800">₹{{ number_format($quickAddGstAmount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-[#001229] font-extrabold pt-2 border-t border-dashed border-slate-200">
                            <span>Estimated Total</span>
                            <span class="text-[#001229]">₹{{ number_format($quickAddTotal, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50">
                    <button type="button" wire:click="$set('showQuickAddModal', false)" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-700 border border-outline-variant/30 hover:bg-slate-50 transition-colors bg-white shadow-xs">Cancel</button>
                    <button type="button" 
                            wire:click="addVariantToCart"
                            @if(!$quickAddIsPurchasable) disabled @endif
                            class="flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-white transition-colors shadow-sm {{ $quickAddIsPurchasable ? 'bg-[#001229] hover:bg-slate-800' : 'bg-slate-300 cursor-not-allowed' }}">
                        <span class="material-symbols-outlined text-sm">shopping_cart</span> Add to Cart
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
