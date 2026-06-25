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
            
            <!-- Checkout Method Selection -->
            <x-customer.card>
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Select Checkout Method</span>
                </x-slot>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                    <!-- Manual Payment Card -->
                    <div wire:click="selectCheckoutMethod('manual_payment')" 
                         class="cursor-pointer border-2 rounded-xl p-4 transition-all duration-200 flex flex-col justify-between gap-3 {{ $checkoutMethod === 'manual_payment' ? 'border-[#001229] bg-slate-50/50' : 'border-outline-variant/30 hover:border-slate-300' }}">
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg {{ $checkoutMethod === 'manual_payment' ? 'bg-[#001229] text-white' : 'bg-slate-100 text-slate-600' }}">
                                <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">Manual Payment Receipt</h4>
                                <p class="text-[10px] text-slate-400 mt-0.5">Transfer funds offline and upload the receipt (JPG, PNG, PDF, WEBP up to 5MB).</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full border flex items-center justify-center {{ $checkoutMethod === 'manual_payment' ? 'border-[#001229]' : 'border-slate-300' }}">
                                @if($checkoutMethod === 'manual_payment')
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#001229]"></span>
                                @endif
                            </span>
                            <span class="text-[10px] font-bold {{ $checkoutMethod === 'manual_payment' ? 'text-slate-800' : 'text-slate-500' }}">Select</span>
                        </div>
                    </div>

                    <!-- Credit Purchase Card -->
                    <div wire:click="selectCheckoutMethod('credit')" 
                         class="cursor-pointer border-2 rounded-xl p-4 transition-all duration-200 flex flex-col justify-between gap-3 {{ $checkoutMethod === 'credit' ? 'border-[#001229] bg-slate-50/50' : 'border-outline-variant/30 hover:border-slate-300' }} {{ !$creditEligibility['can_use_credit'] ? 'opacity-60 cursor-not-allowed' : '' }}">
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg {{ $checkoutMethod === 'credit' ? 'bg-[#001229] text-gold' : 'bg-slate-100 text-slate-600' }}">
                                <span class="material-symbols-outlined text-xl">credit_card</span>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">Credit Purchase Facility</h4>
                                <p class="text-[10px] text-slate-400 mt-0.5">Use your pre-approved B2B credit limit. Pending review if limit is exceeded.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full border flex items-center justify-center {{ $checkoutMethod === 'credit' ? 'border-[#001229]' : 'border-slate-300' }}">
                                @if($checkoutMethod === 'credit')
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#001229]"></span>
                                @endif
                            </span>
                            <span class="text-[10px] font-bold {{ $checkoutMethod === 'credit' ? 'text-slate-800' : 'text-slate-500' }}">
                                @if(!$creditEligibility['can_use_credit'])
                                    Unavailable
                                @else
                                    Select
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Manual Payment receipt upload panel -->
                @if ($checkoutMethod === 'manual_payment')
                    <div class="mt-6 border border-dashed border-outline-variant/50 rounded-xl p-6 bg-slate-50/30">
                        <h4 class="text-xs font-bold text-slate-800 mb-3">Upload Offline Payment Receipt</h4>
                        <div class="flex flex-col items-center justify-center border-2 border-dashed border-outline-variant/30 rounded-xl p-4 bg-white hover:bg-slate-50/50 transition-colors relative">
                            <input type="file" wire:model="receiptFile" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/*,application/pdf">
                            
                            <div class="text-center space-y-2">
                                <span class="material-symbols-outlined text-3xl text-slate-400">upload_file</span>
                                <div>
                                    <p class="text-xs font-bold text-[#001229]">Drag & drop or click to upload</p>
                                    <p class="text-[9px] text-slate-400 mt-0.5">Supported formats: JPG, JPEG, PNG, WEBP, PDF (Max 5MB)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Uploaded File Display -->
                        @if ($receiptFile)
                            <div class="mt-4 p-3 bg-white border border-outline-variant/30 rounded-xl flex items-center justify-between gap-3 shadow-ambient">
                                <div class="flex items-center gap-2.5 overflow-hidden">
                                    <span class="material-symbols-outlined text-slate-500">description</span>
                                    <div class="overflow-hidden">
                                        <p class="text-xs font-bold text-slate-700 truncate">{{ $receiptFile->getClientOriginalName() }}</p>
                                        <p class="text-[10px] text-slate-400">Ready for upload</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="$set('receiptFile', null)" class="text-slate-400 hover:text-error">
                                    <span class="material-symbols-outlined text-lg">close</span>
                                </button>
                            </div>
                        @endif

                        <div wire:loading wire:target="receiptFile" class="text-[10px] text-slate-500 mt-2">
                            Uploading receipt file to server...
                        </div>

                        @error('receiptFile')
                            <p class="text-[10px] text-error font-semibold mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <!-- Credit info warning/info banner -->
                @if ($checkoutMethod === 'credit')
                    <div class="mt-6 p-4 rounded-xl border {{ $creditEligibility['can_use_credit'] ? 'bg-emerald-50 border-emerald-200/50 text-emerald-800' : 'bg-rose-50 border-rose-200/50 text-rose-800' }} flex items-start gap-2.5">
                        <span class="material-symbols-outlined text-lg mt-0.5">
                            {{ $creditEligibility['can_use_credit'] ? 'verified_user' : 'warning' }}
                        </span>
                        <div>
                            <p class="text-xs font-bold">{{ $creditEligibility['message'] }}</p>
                            @if ($creditEligibility['is_privileged_override'])
                                <p class="text-[10px] opacity-90 mt-0.5">Warning: This order exceeds your limit by <strong>₹{{ number_format($creditEligibility['excess_amount'], 2) }}</strong>. This order will require manual approval from the credit office before dispatch.</p>
                            @endif
                        </div>
                    </div>
                @endif
            </x-customer.card>

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
            <!-- Credit status check -->
            <div>
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Credit Limit Parameters</h4>
                <x-customer.credit-summary 
                    :available="$customerInfo['available_credit']" 
                    :limit="$customerInfo['credit_limit']" 
                    :outstanding="$customerInfo['outstanding_amount']" 
                />
            </div>

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
