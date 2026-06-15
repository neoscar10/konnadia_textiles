<div>
    <!-- Page Header -->
    <x-customer.page-title 
        title="Wholesale Shopping Cart" 
        subtitle="Manage current products, quantities, and verify B2B credit parameters."
        :breadcrumbs="['Home' => route('customer.dashboard'), 'Cart' => '#']"
    />

    <!-- Main Grid layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Cart Items List (8 cols) -->
        <div class="lg:col-span-8 space-y-4">
            <!-- Items header / controls -->
            <div class="flex items-center justify-between pb-2 border-b border-outline-variant/10">
                <span class="text-sm font-bold text-slate-700">3 Items in Cart</span>
                <button class="text-xs text-error font-bold hover:underline flex items-center gap-0.5">
                    <span class="material-symbols-outlined text-sm">delete_sweep</span> Clear Cart
                </button>
            </div>

            <!-- Cart Items -->
            <x-customer.cart-item 
                title="Premium Formal Cotton Shirt" 
                sku="TS-0012" 
                price="350" 
                qty="40" 
                size="M" 
                color="Navy Blue" 
                image="https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=160"
            />
            
            <x-customer.cart-item 
                title="Casual Comfort Denim Pants" 
                sku="TS-0015" 
                price="850" 
                qty="20" 
                size="L" 
                color="Dark Indigo" 
                image="https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=160"
            />

            <x-customer.cart-item 
                title="Standard Knitted Polo Tee" 
                sku="TS-0018" 
                price="210" 
                qty="50" 
                size="XL" 
                color="Sky Blue" 
                image="https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=160"
            />

            <!-- Save Cart Action -->
            <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                <a href="{{ route('customer.products.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">arrow_back</span> Continue Shopping
                </a>
                <button type="button" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold text-[#001229] border border-outline-variant/50 hover:bg-slate-50 transition-colors rounded-lg bg-white shadow-sm">
                    <span class="material-symbols-outlined text-sm">bookmark</span> Save Cart For Later
                </button>
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
                        <span class="font-bold text-slate-800">3 Items</span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500 font-medium">
                        <span>Total Quantity</span>
                        <span class="font-bold text-slate-800">110 Pieces</span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500 font-medium">
                        <span>Items Subtotal</span>
                        <span class="font-bold text-slate-800">₹41,500</span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500 font-medium">
                        <span>Estimated GST (12%)</span>
                        <span class="font-bold text-slate-800">₹4,980</span>
                    </div>
                    
                    <div class="border-t border-dashed border-slate-200 pt-3 flex justify-between text-sm font-extrabold text-[#001229]">
                        <span>Total Estimated Amount</span>
                        <span class="text-[#001229]">₹46,480</span>
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
                <x-customer.credit-summary :available="680000" :limit="1000000" :outstanding="320000" />
            </div>
        </div>

    </div>
</div>
