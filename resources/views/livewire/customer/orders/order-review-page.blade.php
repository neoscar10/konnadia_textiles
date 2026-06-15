<div>
    <!-- Page Header & Stepper -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <x-customer.page-title 
                title="Review &amp; Submit Order" 
                subtitle="Confirm order items, shipping parameters, and available credit check."
                :breadcrumbs="[
                    'Home' => route('customer.dashboard'), 
                    'Cart' => route('customer.cart.index'),
                    'Review' => '#'
                ]"
            />
        </div>
        
        <!-- Progress Stepper (Cart -> Review -> Success) -->
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
            <!-- Order Items review -->
            <x-customer.card>
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Review Items</span>
                </x-slot>

                <div class="divide-y divide-slate-100">
                    <!-- Item 1 -->
                    <div class="py-4 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <img src="https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=100" alt="Product" class="w-12 h-12 object-cover rounded border">
                            <div>
                                <h5 class="text-xs font-bold text-[#001229]">Premium Formal Cotton Shirt</h5>
                                <p class="text-[10px] text-slate-400">Size: M &bull; Color: Navy Blue</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-slate-700">40 Pieces</span>
                            <p class="text-[10px] text-slate-400">₹14,000</p>
                        </div>
                    </div>

                    <!-- Item 2 -->
                    <div class="py-4 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <img src="https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=100" alt="Product" class="w-12 h-12 object-cover rounded border">
                            <div>
                                <h5 class="text-xs font-bold text-[#001229]">Casual Comfort Denim Pants</h5>
                                <p class="text-[10px] text-slate-400">Size: L &bull; Color: Dark Indigo</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-slate-700">20 Pieces</span>
                            <p class="text-[10px] text-slate-400">₹17,000</p>
                        </div>
                    </div>

                    <!-- Item 3 -->
                    <div class="py-4 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <img src="https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=100" alt="Product" class="w-12 h-12 object-cover rounded border">
                            <div>
                                <h5 class="text-xs font-bold text-[#001229]">Standard Knitted Polo Tee</h5>
                                <p class="text-[10px] text-slate-400">Size: XL &bull; Color: Sky Blue</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-slate-700">50 Pieces</span>
                            <p class="text-[10px] text-slate-400">₹10,500</p>
                        </div>
                    </div>
                </div>
            </x-customer.card>

            <!-- Order Remarks / Remarks -->
            <x-customer.card>
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Order Remarks</span>
                </x-slot>
                <div>
                    <label class="text-xs text-slate-500 font-semibold block mb-2">Special Instructions / Remarks</label>
                    <textarea rows="3" placeholder="Specify logistics partner preferences, custom tags or packaging requirements..." class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-3 focus:outline-none focus:ring-1 focus:ring-gold"></textarea>
                </div>
            </x-customer.card>
        </div>

        <!-- Right Side: Billing & Credit check (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Credit status check -->
            <div>
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Credit Limit Parameters</h4>
                <x-customer.credit-summary :available="680000" :limit="1000000" :outstanding="320000" />
            </div>

            <!-- Bill Details -->
            <x-customer.card bodyClass="p-5 space-y-4">
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Bill Details</span>
                </x-slot>

                <div class="space-y-3 text-xs">
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>Items Total (110 units)</span>
                        <span class="font-bold text-slate-800">₹41,500</span>
                    </div>
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>Integrated GST (12%)</span>
                        <span class="font-bold text-slate-800">₹4,980</span>
                    </div>
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>Shipping / Cargo Fee</span>
                        <span class="font-bold text-slate-800 text-emerald-700">F.O.R Surat (Free)</span>
                    </div>
                    
                    <div class="border-t border-dashed border-slate-200 pt-3 flex justify-between text-sm font-extrabold text-[#001229]">
                        <span>Grand Total</span>
                        <span class="text-[#001229]">₹46,480</span>
                    </div>
                </div>

                <div class="p-3 bg-emerald-50 rounded-xl flex gap-2">
                    <span class="material-symbols-outlined text-emerald-600 text-sm mt-0.5">verified_user</span>
                    <p class="text-[10px] text-emerald-800 leading-relaxed font-semibold">Order is within available credit limit. Auto-approval checks passed.</p>
                </div>

                <!-- CTA -->
                <div class="space-y-3">
                    <a href="{{ route('customer.orders.success') }}" class="w-full flex items-center justify-center gap-1.5 py-3 rounded-lg text-xs font-bold bg-[#001229] text-white hover:bg-slate-800 transition-colors shadow-sm">
                        Submit Wholesale Order
                    </a>
                    <a href="{{ route('customer.cart.index') }}" class="w-full flex items-center justify-center gap-1.5 py-2.5 rounded-lg text-xs font-bold text-slate-600 border border-outline-variant/30 hover:bg-slate-50 bg-white transition-colors">
                        Back to Cart
                    </a>
                </div>
            </x-customer.card>
        </div>

    </div>
</div>
