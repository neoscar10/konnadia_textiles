<div>
    <!-- Page Header & Stepper -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <x-customer.page-title 
                title="Review &amp; Submit Order" 
                subtitle="Confirm order items, select checkout method, and verify details."
                :breadcrumbs="[
                    'Home' => route('customer.dashboard'), 
                    'Cart' => route('customer.cart.index'),
                    'Review' => '#'
                ]"
            />
        </div>
        
        <!-- Progress Stepper -->
        <div class="flex items-center gap-2 bg-white px-4 py-2 border border-outline-variant/30 rounded-xl shadow-ambient">
            <span class="text-xs font-semibold text-slate-400">Cart</span>
            <span class="material-symbols-outlined text-xs text-slate-300">chevron_right</span>
            <span class="text-xs font-bold text-gold">Review</span>
            <span class="material-symbols-outlined text-xs text-slate-300">chevron_right</span>
            <span class="text-xs font-semibold text-slate-400">Success</span>
        </div>
    </div>

    <!-- Layout: Left (Items & Details), Right (Bill details, credit checks) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Items & Details (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Checkout Method Bypassed -->

            <!-- Order Items review -->
            <x-customer.card>
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Review Items</span>
                </x-slot>

                <div class="divide-y divide-slate-100">
                    @foreach($items as $item)
                        <div class="py-4 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <img src="{{ $item['product_image_url'] }}" alt="{{ $item['product_title'] }}" class="w-12 h-12 object-cover rounded border">
                                <div>
                                    <h5 class="text-xs font-bold text-[#001229]">{{ $item['product_title'] }}</h5>
                                    <p class="text-[10px] text-slate-400">
                                        SKU: {{ $item['product_sku'] }}
                                        @if($item['selected_options'])
                                            &bull; {{ implode(' | ', array_map(fn($k, $v) => "$k: $v", array_keys($item['selected_options']), $item['selected_options'])) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold text-slate-700">
                                    @if(!empty($item['has_lvl2_unit']))
                                        <span class="block">
                                            {{ $item['quantity_lvl2'] }} {{ $item['lvl2_unit_name'] }}{{ $item['quantity_lvl2'] != 1 ? 's' : '' }}, 
                                            {{ $item['quantity_lvl1'] }} {{ $item['lvl1_unit_name'] }}{{ $item['quantity_lvl1'] != 1 ? 's' : '' }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 block mt-0.5 font-medium">(Total: {{ $item['quantity'] }} Pcs)</span>
                                    @else
                                        {{ $item['quantity'] }} {{ $item['unit_short_code'] }} 
                                        @if ($item['unit_short_code'] !== 'Pcs')
                                            <span class="text-[10px] text-slate-400 block mt-0.5 font-medium">({{ round($item['quantity'] * $item['unit_conversion_quantity']) }} Pcs)</span>
                                        @endif
                                    @endif
                                </span>
                                <p class="text-[10px] text-slate-400">₹{{ number_format($item['line_total'], 2) }} (Incl. GST)</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-customer.card>

            <!-- Order Remarks / Remarks -->
            <x-customer.card>
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Order Remarks</span>
                </x-slot>
                <div>
                    <label class="text-xs text-slate-500 font-semibold block mb-2">Special Instructions / Remarks</label>
                    <textarea wire:model.defer="customerNotes" rows="3" placeholder="Specify logistics partner preferences, custom tags or packaging requirements..." class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-3 focus:outline-none focus:ring-1 focus:ring-gold"></textarea>
                </div>
            </x-customer.card>
        </div>

        <!-- Right Side: Billing & Credit check (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Credit parameters bypassed -->

            <!-- Bill Details -->
            <x-customer.card bodyClass="p-5 space-y-4">
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Bill Details</span>
                </x-slot>

                <div class="space-y-3 text-xs">
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>Items Total ({{ $totals['items_count'] }} styles)</span>
                        <span class="font-bold text-slate-800">₹{{ number_format($totals['subtotal'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>GST</span>
                        <span class="font-bold text-slate-800">₹{{ number_format($totals['gst_amount'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>Shipping / Cargo Fee</span>
                        <span class="font-bold text-slate-800 text-emerald-700">F.O.R Surat (Free)</span>
                    </div>
                    
                    <div class="border-t border-dashed border-slate-200 pt-3 flex justify-between text-sm font-extrabold text-[#001229]">
                        <span>Grand Total</span>
                        <span class="text-[#001229]">₹{{ number_format($totals['total'], 2) }}</span>
                    </div>
                </div>

                <!-- Submit CTA -->
                <div class="space-y-3">
                    <button wire:click="submitOrder" 
                            wire:loading.attr="disabled"
                            class="w-full flex items-center justify-center gap-1.5 py-3 rounded-lg text-xs font-bold bg-[#001229] text-white hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm">
                        <span wire:loading wire:target="submitOrder" class="inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        Submit Wholesale Order
                    </button>
                    <a href="{{ route('customer.cart.index') }}" class="w-full flex items-center justify-center gap-1.5 py-2.5 rounded-lg text-xs font-bold text-slate-600 border border-outline-variant/30 hover:bg-slate-50 bg-white transition-colors">
                        Back to Cart
                    </a>
                </div>
            </x-customer.card>
        </div>

    </div>
</div>
