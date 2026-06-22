<div>
    <!-- Welcome section -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">B2B Wholesale Portal</span>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#001229] tracking-tight mt-0.5">Welcome back, {{ $customer['company_name'] ?? 'Valued Customer' }}</h1>
            <p class="text-sm text-slate-500">Manage your wholesale orders, view outstanding credit limits, and check order statuses.</p>
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

    <!-- Alerts Strip -->
    @if(!empty($alerts))
        <div class="space-y-3 mb-6">
            @foreach($alerts as $alert)
                <div class="p-4 rounded-xl border flex items-start gap-3 shadow-ambient transition-all @if($alert['type'] === 'danger') bg-rose-50 border-rose-200 text-rose-800 @elseif($alert['type'] === 'warning') bg-amber-50 border-amber-200 text-amber-800 @else bg-blue-50 border-blue-200 text-blue-800 @endif">
                    <span class="material-symbols-outlined mt-0.5">
                        @if($alert['type'] === 'danger') error @elseif($alert['type'] === 'warning') warning @else info @endif
                    </span>
                    <div>
                        <h4 class="text-sm font-bold">{{ $alert['title'] }}</h4>
                        <p class="text-xs mt-0.5 opacity-90">{{ $alert['message'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Stats grid (Now First) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-customer.stat-card 
            title="Total Orders" 
            value="{{ $orders['total_orders'] ?? 0 }}" 
            icon="list_alt" 
            trend="{{ $orders['pending_orders'] ?? 0 }} Pending Verification" 
            trendType="neutral" 
        />
        <x-customer.stat-card 
            title="Dispatched Orders" 
            value="{{ $orders['dispatched_orders'] ?? 0 }}" 
            icon="local_shipping" 
            trend="{{ $orders['approved_orders'] ?? 0 }} Approved & Preparing" 
            trendType="up" 
        />
        <x-customer.stat-card 
            title="Total Order Value" 
            value="{{ $orders['formatted_total_order_value'] ?? '₹0.00' }}" 
            icon="payments" 
            trend="Excludes Rejected/Cancelled" 
            trendType="neutral" 
        />
    </div>

    <!-- Recent Orders & Sidebar grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Recent Orders (2 columns) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-extrabold text-[#001229]">Recent Orders</h3>
                <a href="{{ route('customer.orders.index') }}" class="text-xs font-bold text-gold hover:underline flex items-center gap-0.5">
                    View All Orders <span class="material-symbols-outlined text-sm">chevron_right</span>
                </a>
            </div>

            @if(empty($recentOrders))
                <x-customer.empty-state 
                    title="No orders found" 
                    description="You haven't placed any orders yet. Browse our catalog to get started." 
                    icon="shopping_bag" 
                />
            @else
                <div class="space-y-3">
                    @foreach($recentOrders as $order)
                        <a href="{{ route('customer.orders.show', $order['order_number']) }}" class="block bg-white border border-outline-variant/20 rounded-xl shadow-ambient hover:shadow-md hover:border-[#001229]/25 transition-all p-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-extrabold text-[#001229]">{{ $order['order_number'] }}</span>
                                    <x-customer.badge :status="$order['status']['label']" />
                                </div>
                                <div class="flex flex-wrap items-center gap-4 sm:gap-6">
                                    <div class="text-slate-500 font-medium">
                                        Placed: <span class="font-bold text-slate-700">{{ \Carbon\Carbon::parse($order['created_at'])->format('M d, Y') }}</span>
                                    </div>
                                    <div class="text-slate-500 font-medium">
                                        Items: <span class="font-bold text-[#001229]">{{ $order['items_count'] }}</span>
                                    </div>
                                    <div class="text-slate-500 font-medium">
                                        Payment: <span class="font-bold uppercase text-slate-700">{{ $order['payment_status']['label'] }}</span>
                                    </div>
                                    <div class="text-right sm:pl-4 sm:border-l border-slate-100">
                                        <span class="text-[9px] text-slate-400 font-bold uppercase block select-none">Total</span>
                                        <span class="text-sm font-extrabold text-[#001229]">{{ $order['formatted_total_amount'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Sidebar (Credit Limit summary + Cart Status) -->
        <div class="space-y-6">
            <!-- Credit Limit summary (Now in Sidebar) -->
            @if(!empty($credit))
                <div class="bg-gradient-to-br from-[#001229] to-[#0f2744] text-white p-5 rounded-xl border border-slate-800 shadow-ambient flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-[10px] text-slate-300 font-medium uppercase tracking-wider">Available Credit Limit</p>
                                <h3 class="text-2xl font-extrabold text-gold mt-0.5">{{ $credit['formatted_available_credit'] }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-gold/80 text-3xl">payments</span>
                        </div>
                        
                        <!-- Details Row -->
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <div>
                                <span class="text-[9px] text-slate-400 uppercase tracking-wider block">Total Limit</span>
                                <span class="text-xs font-bold text-white mt-0.5">{{ $credit['formatted_credit_limit'] }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-400 uppercase tracking-wider block">Outstanding</span>
                                <span class="text-xs font-bold text-white mt-0.5">{{ $credit['formatted_outstanding_amount'] }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-400 uppercase tracking-wider block">Overdue</span>
                                <span class="text-xs font-bold @if($credit['overdue_amount'] > 0) text-rose-400 @else text-slate-300 @endif mt-0.5">{{ $credit['formatted_overdue_amount'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="space-y-1.5 mt-auto">
                        <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gold rounded-full" style="width: {{ $credit['utilization_percentage'] }}%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-300 font-medium">
                            <span>Outstanding: {{ $credit['formatted_outstanding_amount'] }}</span>
                            <span>Used: {{ $credit['utilization_percentage'] }}%</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Current Cart Summary -->
            <div>
                <h3 class="text-base font-extrabold text-[#001229] mb-4">Cart Status</h3>
                <x-customer.card>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-gold">shopping_basket</span>
                            <span class="font-bold text-sm text-slate-800">Pending Cart</span>
                        </div>
                        <span class="text-xs text-slate-500 font-medium">{{ $cart['items_count'] }} {{ Str::plural('Item', $cart['items_count']) }}</span>
                    </div>
                    
                    @if(!$cart['exists'] || $cart['items_count'] == 0)
                        <div class="py-8 text-center text-slate-400 text-xs">
                            <span class="material-symbols-outlined text-3xl mb-1.5 opacity-60">shopping_cart</span>
                            <p>Your wholesale cart is empty.</p>
                        </div>
                    @else
                        <div class="border-t border-slate-100 pt-4 mb-4 flex justify-between items-baseline">
                            <span class="text-xs text-slate-500 font-semibold">Total Amount</span>
                            <span class="text-base font-extrabold text-[#001229]">{{ $cart['formatted_total_amount'] }}</span>
                        </div>

                        <a href="{{ route('customer.cart.index') }}" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg text-xs font-bold bg-[#001229] text-white hover:bg-slate-800 transition-colors">
                            Checkout Now <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    @endif
                </x-customer.card>
            </div>
        </div>
    </div>

    <!-- Recommended Products Section -->
    <div>
        <h3 class="text-base font-extrabold text-[#001229] mb-4">Recommended Products</h3>
        @if(empty($recommendedProducts))
            <div class="bg-white border border-outline-variant/20 rounded-xl p-8 text-center text-slate-500 text-sm">
                No recommended products at the moment.
            </div>
        @else
            <div class="flex overflow-x-auto gap-6 pb-4 snap-x snap-mandatory scrollbar-thin scroll-smooth">
                @foreach($recommendedProducts as $product)
                    <div class="w-72 flex-shrink-0 snap-start">
                        <x-customer.product-card 
                            :title="$product['title']" 
                            :sku="$product['sku']" 
                            :price="$product['price']['customer_price']" 
                            :moq="$product['minimum_order_quantity']" 
                            :image="$product['primary_image_url']" 
                            :inStock="$product['stock']['status'] === 'in_stock'" 
                            :url="route('customer.products.show', $product['slug'])" 
                            :productId="$product['id']"
                        />
                    </div>
                @endforeach
            </div>
        @endif
    </div>

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

                    <!-- Quantity Input (Dual-unit if lvl2 unit exists) -->
                    <div>
                        @if($quickAddHasLvl2Unit)
                            @php
                                $lvl1 = collect($quickAddUnits)->firstWhere('level', 1);
                                $lvl2 = collect($quickAddUnits)->firstWhere('level', 2);
                            @endphp
                            <h5 class="text-xs font-bold text-slate-700 mb-2">Order Quantity</h5>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="text-[10px] text-slate-400 font-bold uppercase">{{ $lvl2['name'] }}s</label>
                                    <div class="flex items-center border border-outline-variant/30 rounded-lg bg-slate-50 p-1">
                                        <input type="number" wire:model.live="quickAddQtyLvl2" min="0" class="w-full text-center bg-transparent border-none focus:outline-none focus:ring-0 text-xs font-bold text-[#001229]">
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] text-slate-400 font-bold uppercase">{{ $lvl1['name'] }}s</label>
                                    <div class="flex items-center border border-outline-variant/30 rounded-lg bg-slate-50 p-1">
                                        <input type="number" wire:model.live="quickAddQtyLvl1" min="0" class="w-full text-center bg-transparent border-none focus:outline-none focus:ring-0 text-xs font-bold text-[#001229]">
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center mt-2 select-none">
                                <span class="text-[10px] text-slate-400 font-semibold">Total: {{ $quickAddQty }} Pieces</span>
                                <span class="text-[10px] text-slate-400 font-semibold">MOQ: {{ $quickAddMoq }} Pieces</span>
                            </div>
                        @else
                            <h5 class="text-xs font-bold text-slate-700 mb-2">Order Quantity</h5>
                            <div class="flex items-center border border-outline-variant/30 rounded-lg bg-slate-50 p-1 w-32">
                                <input type="number" wire:model.live="quickAddQty" min="1" class="w-full text-center bg-transparent border-none focus:outline-none focus:ring-0 text-sm font-bold text-[#001229]">
                            </div>
                        @endif
                    </div>

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
