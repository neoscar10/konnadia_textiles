<div>
    <!-- Welcome section -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">B2B Wholesale Portal</span>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#001229] tracking-tight mt-0.5">Welcome back, Raj Garments</h1>
            <p class="text-sm text-slate-500">Manage your wholesale orders, view outstanding credit limits, and check order statuses.</p>
        </div>
        <div class="flex items-center gap-2 text-xs font-bold text-slate-600 bg-white border border-outline-variant/30 px-3 py-1.5 rounded-lg shadow-ambient">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active Account (Level 2 Distributor)
        </div>
    </div>

    <!-- Credit Limit summary & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Credit Summary Component -->
        <div class="lg:col-span-2">
            <x-customer.credit-summary :available="320000" :limit="500000" :outstanding="180000" />
        </div>
        
        <!-- Quick Actions Card -->
        <x-customer.card bodyClass="h-full flex flex-col justify-between p-6">
            <x-slot name="header">
                <span class="font-bold text-slate-800 text-sm">Quick Operations</span>
            </x-slot>
            <div class="grid grid-cols-2 gap-3 flex-1 mt-4">
                <a href="{{ route('customer.products.index') }}" class="flex flex-col items-center justify-center p-3 rounded-lg border border-outline-variant/20 hover:border-gold hover:bg-slate-50 text-center transition-all">
                    <span class="material-symbols-outlined text-gold text-2xl mb-1.5">store</span>
                    <span class="text-xs font-bold text-[#001229]">Browse Shop</span>
                </a>
                <a href="{{ route('customer.cart.index') }}" class="flex flex-col items-center justify-center p-3 rounded-lg border border-outline-variant/20 hover:border-gold hover:bg-slate-50 text-center transition-all">
                    <span class="material-symbols-outlined text-gold text-2xl mb-1.5">shopping_cart</span>
                    <span class="text-xs font-bold text-[#001229]">View Cart</span>
                </a>
                <a href="{{ route('customer.orders.index') }}" class="flex flex-col items-center justify-center p-3 rounded-lg border border-outline-variant/20 hover:border-gold hover:bg-slate-50 text-center transition-all">
                    <span class="material-symbols-outlined text-gold text-2xl mb-1.5">receipt_long</span>
                    <span class="text-xs font-bold text-[#001229]">My Orders</span>
                </a>
                <a href="{{ route('customer.notifications.index') }}" class="flex flex-col items-center justify-center p-3 rounded-lg border border-outline-variant/20 hover:border-gold hover:bg-slate-50 text-center transition-all">
                    <span class="material-symbols-outlined text-gold text-2xl mb-1.5">notifications</span>
                    <span class="text-xs font-bold text-[#001229]">Alerts</span>
                </a>
            </div>
        </x-customer.card>
    </div>

    <!-- Stats grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-customer.stat-card title="Active Orders" value="12" icon="list_alt" trend="2 new today" />
        <x-customer.stat-card title="Pending Approval" value="3" icon="pending_actions" trend="Awaiting review" trendType="neutral" />
        <x-customer.stat-card title="This Month's Spend" value="₹4,20,000" icon="payments" trend="+14% vs last month" />
    </div>

    <!-- Recent Orders & Cart Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Orders (2 columns) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-extrabold text-[#001229]">Recent Orders</h3>
                <a href="{{ route('customer.orders.index') }}" class="text-xs font-bold text-gold hover:underline flex items-center gap-0.5">
                    View All Orders <span class="material-symbols-outlined text-sm">chevron_right</span>
                </a>
            </div>

            <!-- List of orders -->
            <x-customer.order-card 
                orderId="KT-ORD-100245" 
                status="under review" 
                date="June 12, 2026" 
                total="58800" 
                itemsCount="3"
                :images="['https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=120', 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=120', 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=120']"
            />
            <x-customer.order-card 
                orderId="KT-ORD-100239" 
                status="approved" 
                date="June 08, 2026" 
                total="124500" 
                itemsCount="5"
                :images="['https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=120', 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=120', 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=120']"
            />
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
                    <span class="text-xs text-slate-500 font-medium">3 Items</span>
                </div>
                
                <div class="space-y-3 mb-6">
                    <div class="flex items-center gap-3">
                        <img src="https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=80" alt="Product" class="w-10 h-10 object-cover rounded border">
                        <div class="flex-1 min-w-0">
                            <h5 class="text-xs font-bold text-[#001229] truncate">Premium Formal Cotton Shirt</h5>
                            <p class="text-[10px] text-slate-400">Qty: 20 Pieces &bull; M</p>
                        </div>
                        <span class="text-xs font-bold text-slate-700">₹7,000</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <img src="https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=80" alt="Product" class="w-10 h-10 object-cover rounded border">
                        <div class="flex-1 min-w-0">
                            <h5 class="text-xs font-bold text-[#001229] truncate">Casual Comfort Denim</h5>
                            <p class="text-[10px] text-slate-400">Qty: 10 Pieces &bull; L</p>
                        </div>
                        <span class="text-xs font-bold text-slate-700">₹8,500</span>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4 mb-4 flex justify-between items-baseline">
                    <span class="text-xs text-slate-500 font-semibold">Subtotal (Excl. GST)</span>
                    <span class="text-base font-extrabold text-[#001229]">₹15,500</span>
                </div>

                <a href="{{ route('customer.cart.index') }}" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg text-xs font-bold bg-[#001229] text-white hover:bg-slate-800 transition-colors">
                    Checkout Now <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </x-customer.card>
        </div>
    </div>
</div>
