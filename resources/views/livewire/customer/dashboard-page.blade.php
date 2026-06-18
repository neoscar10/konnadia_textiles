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

    <!-- Credit Limit summary -->
    <div class="mb-8">
        <!-- Credit Summary Component -->
        @if(!empty($credit))
            <div class="bg-gradient-to-br from-[#001229] to-[#0f2744] text-white p-6 rounded-xl border border-slate-800 shadow-ambient flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-xs text-slate-300 font-medium uppercase tracking-wider">Available Credit Limit</p>
                            <h3 class="text-3xl font-extrabold text-gold mt-1">{{ $credit['formatted_available_credit'] }}</h3>
                        </div>
                        <span class="material-symbols-outlined text-gold/80 text-4xl">payments</span>
                    </div>
                    
                    <!-- Details Row -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase tracking-wider">Total Credit Limit</span>
                            <p class="text-sm font-bold text-white mt-0.5">{{ $credit['formatted_credit_limit'] }}</p>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase tracking-wider">Outstanding Balance</span>
                            <p class="text-sm font-bold text-white mt-0.5">{{ $credit['formatted_outstanding_amount'] }}</p>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase tracking-wider">Overdue Balance</span>
                            <p class="text-sm font-bold @if($credit['overdue_amount'] > 0) text-rose-400 @else text-slate-300 @endif mt-0.5">{{ $credit['formatted_overdue_amount'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Progress bar -->
                <div class="space-y-1.5 mt-auto">
                    <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                        <div class="h-full bg-gold rounded-full" style="width: {{ $credit['utilization_percentage'] }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-slate-300 font-medium">
                        <span>Outstanding: {{ $credit['formatted_outstanding_amount'] }}</span>
                        <span>Limit Used: {{ $credit['utilization_percentage'] }}%</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Stats grid -->
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

    <!-- Recent Orders & Cart Insights -->
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
                @foreach($recentOrders as $order)
                    <div class="bg-white border border-outline-variant/20 rounded-xl shadow-ambient overflow-hidden hover:shadow-md transition-shadow">
                        <!-- Header -->
                        <div class="px-5 py-4 border-b border-outline-variant/10 bg-slate-50/50 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-extrabold text-[#001229]">{{ $order['order_number'] }}</span>
                                <x-customer.badge :status="$order['status']['label']" />
                            </div>
                            <div class="text-xs font-semibold text-slate-500">
                                Placed on {{ \Carbon\Carbon::parse($order['created_at'])->format('M d, Y') }}
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="text-xs text-slate-600">
                                <p class="font-medium">Items count: <span class="font-bold text-[#001229]">{{ $order['items_count'] }}</span></p>
                                <p class="mt-1">Payment: <span class="font-bold uppercase text-slate-700">{{ $order['payment_status']['label'] }}</span></p>
                            </div>

                            <!-- Order Total & Actions -->
                            <div class="flex items-center justify-between sm:justify-end gap-6 pt-3 sm:pt-0 border-t sm:border-none border-slate-50">
                                <div class="text-right">
                                    <span class="text-[10px] text-slate-400 font-semibold uppercase block">Order Total</span>
                                    <span class="text-base font-extrabold text-[#001229]">{{ $order['formatted_total_amount'] }}</span>
                                </div>
                                
                                <a href="{{ route('customer.orders.show', $order['order_number']) }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-[#001229] border border-outline-variant/50 hover:bg-slate-50 transition-colors rounded-lg">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- Right: Current Cart Summary -->
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

    <!-- Recommended Products Section -->
    <div>
        <h3 class="text-base font-extrabold text-[#001229] mb-4">Recommended Products</h3>
        @if(empty($recommendedProducts))
            <div class="bg-white border border-outline-variant/20 rounded-xl p-8 text-center text-slate-500 text-sm">
                No recommended products at the moment.
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                @foreach($recommendedProducts as $product)
                    <x-customer.product-card 
                        :title="$product['title']" 
                        :sku="$product['sku']" 
                        :price="$product['price']['customer_price']" 
                        :moq="$product['minimum_order_quantity']" 
                        :image="$product['primary_image_url']" 
                        :inStock="$product['stock']['status'] === 'in_stock'" 
                        :url="route('customer.products.show', $product['slug'])" 
                    />
                @endforeach
            </div>
        @endif
    </div>
</div>
