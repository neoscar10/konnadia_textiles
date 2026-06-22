<div>
    <!-- Page Header -->
    <x-customer.page-title 
        title="Wholesale Shopping Cart" 
        subtitle="Manage current products, quantities, and verify B2B credit parameters."
        :breadcrumbs="['Home' => route('customer.dashboard'), 'Cart' => '#']"
    />

    @if($isEmpty)
        <!-- Empty Cart State -->
        <div class="py-16">
            <x-customer.empty-state 
                icon="shopping_cart"
                title="Your cart is empty"
                description="Browse our product catalog to start building your wholesale order."
                actionText="Browse Products"
                :actionUrl="route('customer.products.index')"
            />
        </div>
    @else
        <!-- Main Grid layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Left: Cart Items List (8 cols) -->
            <div class="lg:col-span-8 space-y-4">
                <!-- Items header / controls -->
                <div class="flex items-center justify-between pb-2 border-b border-outline-variant/10">
                    <span class="text-sm font-bold text-slate-700">{{ count($items) }} {{ count($items) === 1 ? 'Item' : 'Items' }} in Cart</span>
                    <button wire:click="clearCart" wire:confirm="Are you sure you want to clear your entire cart?" class="text-xs text-error font-bold hover:underline flex items-center gap-0.5">
                        <span class="material-symbols-outlined text-sm">delete_sweep</span> Clear Cart
                    </button>
                </div>

                <!-- Cart Items -->
                @foreach($items as $item)
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-4 bg-white border border-outline-variant/20 rounded-xl shadow-ambient hover:shadow-md transition-shadow" wire:key="cart-item-{{ $item['id'] }}">
                        <!-- Product Details -->
                        <div class="flex items-center gap-4 flex-1">
                            <img src="{{ $item['product_image_url'] }}" alt="{{ $item['product_title'] }}" class="w-16 h-16 object-cover rounded-lg border bg-slate-50">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">SKU: {{ $item['product_sku'] }}</span>
                                <h4 class="text-sm font-bold text-[#001229] leading-snug">{{ $item['product_title'] }}</h4>
                                
                                <div class="flex flex-wrap gap-1.5 mt-1.5">
                                    @if($item['selected_options'] && is_array($item['selected_options']))
                                        @foreach($item['selected_options'] as $key => $val)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">{{ $key }}: {{ $val }}</span>
                                        @endforeach
                                    @endif
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700">{{ $item['unit_name'] }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stepper & Subtotal -->
                        <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto pt-3 sm:pt-0 border-t border-slate-50 sm:border-none">
                            <!-- Quantity control -->
                            <div class="flex flex-col items-center">
                                @if($item['has_lvl2_unit'])
                                    <div class="flex items-center gap-2">
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] text-slate-400 font-bold uppercase">{{ $item['lvl2_unit_name'] }}s</span>
                                            <div class="inline-flex items-center border border-outline-variant/30 rounded-lg bg-slate-50 p-0.5 w-14">
                                                <input type="number" value="{{ $item['quantity_lvl2'] }}" wire:change="updateQuantityLvl2({{ $item['id'] }}, $event.target.value)" class="w-full text-center bg-transparent border-none focus:outline-none focus:ring-0 text-xs font-bold text-[#001229] p-0.5" min="0">
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] text-slate-400 font-bold uppercase">{{ $item['lvl1_unit_name'] }}s</span>
                                            <div class="inline-flex items-center border border-outline-variant/30 rounded-lg bg-slate-50 p-0.5 w-14">
                                                <input type="number" value="{{ $item['quantity_lvl1'] }}" wire:change="updateQuantityLvl1({{ $item['id'] }}, $event.target.value)" class="w-full text-center bg-transparent border-none focus:outline-none focus:ring-0 text-xs font-bold text-[#001229] p-0.5" min="0">
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-[9px] text-slate-400 font-semibold mt-1">Total: {{ $item['quantity'] }} {{ $item['unit_short_code'] }}</span>
                                @else
                                    <div class="inline-flex items-center border border-outline-variant/30 rounded-lg bg-slate-50 p-1">
                                        <button type="button" wire:click="decrementQuantity({{ $item['id'] }})" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-white hover:shadow-sm active:bg-slate-100 transition-all focus:outline-none">
                                            <span class="material-symbols-outlined text-lg">remove</span>
                                        </button>
                                        <input type="number" value="{{ $item['quantity'] }}" wire:change="updateQuantity({{ $item['id'] }}, $event.target.value)" class="w-12 text-center bg-transparent border-none focus:outline-none focus:ring-0 text-sm font-bold text-[#001229] [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" min="1">
                                        <button type="button" wire:click="incrementQuantity({{ $item['id'] }})" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-white hover:shadow-sm active:bg-slate-100 transition-all focus:outline-none">
                                            <span class="material-symbols-outlined text-lg">add</span>
                                        </button>
                                    </div>
                                @endif
                            </div>

                            <!-- Price Details -->
                            <div class="text-right min-w-[100px]">
                                <span class="text-[10px] text-slate-400 font-semibold uppercase block">Subtotal</span>
                                <span class="text-base font-extrabold text-[#001229]">₹{{ number_format($item['line_subtotal'], 2) }}</span>
                                <span class="text-[10px] text-slate-500 block">@ ₹{{ number_format($item['customer_unit_price'], 2) }}/{{ $item['unit_short_code'] }}</span>
                            </div>

                            <!-- Delete Action -->
                            <button type="button" wire:click="removeItem({{ $item['id'] }})" class="p-2 text-slate-400 hover:text-error hover:bg-rose-50 rounded-lg transition-colors">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </div>
                    </div>
                @endforeach

                <!-- Continue Shopping -->
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <a href="{{ route('customer.products.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">arrow_back</span> Continue Shopping
                    </a>
                </div>
            </div>

            <!-- Right Side: Order Summary & Credit Summary (4 cols) -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Order Summary Card -->
                <x-customer.card bodyClass="p-5 space-y-4">
                    <x-slot name="header">
                        <span class="font-bold text-slate-800 text-sm">Order Summary</span>
                    </x-slot>

                    <div class="space-y-3">
                        <div class="flex justify-between text-xs text-slate-500 font-medium">
                            <span>Total Products</span>
                            <span class="font-bold text-slate-800">{{ $totals['items_count'] ?? 0 }} Items</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-500 font-medium">
                            <span>Total Quantity</span>
                            <span class="font-bold text-slate-800">{{ $totals['total_base_quantity'] ?? 0 }} Pieces</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-500 font-medium">
                            <span>Items Subtotal</span>
                            <span class="font-bold text-slate-800">₹{{ number_format($totals['subtotal'] ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-500 font-medium">
                            <span>Estimated GST</span>
                            <span class="font-bold text-slate-800">₹{{ number_format($totals['gst_amount'] ?? 0, 2) }}</span>
                        </div>
                        
                        <div class="border-t border-dashed border-slate-200 pt-3 flex justify-between text-sm font-extrabold text-[#001229]">
                            <span>Total Estimated Amount</span>
                            <span class="text-[#001229]">₹{{ number_format($totals['total'] ?? 0, 2) }}</span>
                        </div>
                    </div>

                    <!-- CTA -->
                    <a href="{{ route('customer.orders.review') }}" class="w-full flex items-center justify-center gap-1.5 py-3 rounded-lg text-xs font-bold bg-[#001229] text-white hover:bg-slate-800 transition-colors shadow-sm">
                        Proceed to Review Order <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </x-customer.card>

                <!-- Credit Limit Status Widget -->
                <div>
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Available Credit Limit</h4>
                    <x-customer.credit-summary 
                        :available="$creditSummary['available_credit'] ?? 0" 
                        :limit="$creditSummary['credit_limit'] ?? 0" 
                        :outstanding="$creditSummary['outstanding_amount'] ?? 0" 
                    />
                </div>
            </div>

        </div>
    @endif
</div>
