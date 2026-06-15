<div>
    <x-slot:title>Order Details</x-slot:title>
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl border-b border-outline-variant/30 pb-lg">
        <div class="flex items-center gap-md">
            <a href="{{ route('admin.orders.index') }}" class="w-10 h-10 bg-surface-container-low rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <div>
                <div class="flex items-center gap-sm">
                    <h1 class="font-headline-lg text-primary tracking-tight">Order #KT-ORD-100248</h1>
                    <x-admin.badge type="warning" class="text-xs">Under Review</x-admin.badge>
                </div>
                <p class="font-body-md text-on-surface-variant mt-1">Placed on 07-Jun-2026 at 10:45 AM via B2B Portal</p>
            </div>
        </div>
        <div class="flex gap-md w-full sm:w-auto mt-md sm:mt-0">
            <x-admin.button variant="outline" icon="print">Print Invoice</x-admin.button>
            <x-admin.button variant="danger" icon="cancel">Reject</x-admin.button>
            <x-admin.button variant="primary" icon="check_circle">Approve Order</x-admin.button>
        </div>
    </div>

    <!-- Top Info Banner -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-lg mb-xl">
        <div class="flex items-start gap-md bg-surface-container-lowest p-md border border-outline-variant/30 rounded-lg">
            <div class="w-10 h-10 rounded-full bg-primary-container text-primary flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[20px]">person</span>
            </div>
            <div>
                <p class="font-label-md text-on-surface-variant uppercase tracking-wider text-[10px] mb-1">Customer Info</p>
                <p class="font-title-md text-primary">City Apparel Co.</p>
                <p class="font-body-md text-on-surface-variant text-sm">Retailer • +91 98765 43210</p>
            </div>
        </div>
        
        <div class="flex items-start gap-md bg-surface-container-lowest p-md border border-outline-variant/30 rounded-lg">
            <div class="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[20px]">local_shipping</span>
            </div>
            <div>
                <p class="font-label-md text-on-surface-variant uppercase tracking-wider text-[10px] mb-1">Shipping Details</p>
                <p class="font-title-md text-primary">Standard Delivery</p>
                <p class="font-body-md text-on-surface-variant text-sm">Est. 12-Jun-2026 to Mumbai Hub</p>
            </div>
        </div>

        <div class="flex items-start gap-md bg-surface-container-lowest p-md border border-error-container rounded-lg relative overflow-hidden">
            <div class="w-10 h-10 rounded-full bg-error-container text-error flex items-center justify-center flex-shrink-0 relative z-10">
                <span class="material-symbols-outlined text-[20px]">warning</span>
            </div>
            <div class="relative z-10">
                <p class="font-label-md text-error uppercase tracking-wider text-[10px] mb-1">Credit Warning</p>
                <p class="font-title-md text-primary">Limit Exceeded</p>
                <p class="font-body-md text-on-surface-variant text-sm">Order exceeds ₹50,000 credit limit.</p>
            </div>
            <div class="absolute right-0 top-0 bottom-0 w-1/2 bg-gradient-to-l from-error-container/30 to-transparent"></div>
        </div>
    </div>

    <!-- Main Content Split -->
    <div class="grid grid-cols-12 gap-xl pb-xl">
        <!-- Order Items List -->
        <div class="col-span-12 lg:col-span-8 space-y-xl">
            <x-admin.card>
                <x-slot:header class="flex items-center gap-sm bg-surface-container-low/30">
                    <span class="material-symbols-outlined text-primary-fixed-dim">inventory_2</span>
                    <h3 class="font-title-md text-primary">Order Items</h3>
                </x-slot:header>

                <x-slot:bodyClass>p-0</x-slot:bodyClass>

                <div class="overflow-x-auto">
                    <table class="w-full text-left font-body-md">
                        <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                            <tr>
                                <th class="px-lg py-md">Product</th>
                                <th class="px-lg py-md">Variants</th>
                                <th class="px-lg py-md">Qty/Unit</th>
                                <th class="px-lg py-md text-right">Price</th>
                                <th class="px-lg py-md text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <tr class="hover:bg-primary/[0.02] transition-colors">
                                <td class="px-lg py-md">
                                    <p class="font-title-md text-primary">Premium Cotton Shirt</p>
                                    <p class="font-body-md text-on-surface-variant text-xs font-mono mt-1">PCS-M-BLK-102</p>
                                </td>
                                <td class="px-lg py-md">
                                    <div class="flex flex-col gap-xs">
                                        <span class="px-xs py-[2px] bg-surface-container rounded text-[10px] font-bold w-fit text-on-surface">SIZE: M</span>
                                        <span class="px-xs py-[2px] bg-surface-container rounded text-[10px] font-bold w-fit text-on-surface">COLOR: BLACK</span>
                                    </div>
                                </td>
                                <td class="px-lg py-md">
                                    <p class="font-body-md text-primary">10 Packs</p>
                                    <p class="font-label-md text-on-surface-variant text-xs mt-1">12 pcs/pack</p>
                                </td>
                                <td class="px-lg py-md text-right font-medium">₹4,200.00</td>
                                <td class="px-lg py-md text-right font-bold text-primary">₹42,000.00</td>
                            </tr>
                            <tr class="hover:bg-primary/[0.02] transition-colors">
                                <td class="px-lg py-md">
                                    <p class="font-title-md text-primary">Slim Fit Chinos</p>
                                    <p class="font-body-md text-on-surface-variant text-xs font-mono mt-1">SFC-32-KHK-404</p>
                                </td>
                                <td class="px-lg py-md">
                                    <div class="flex flex-col gap-xs">
                                        <span class="px-xs py-[2px] bg-surface-container rounded text-[10px] font-bold w-fit text-on-surface">SIZE: 32</span>
                                        <span class="px-xs py-[2px] bg-surface-container rounded text-[10px] font-bold w-fit text-on-surface">COLOR: KHAKI</span>
                                    </div>
                                </td>
                                <td class="px-lg py-md">
                                    <p class="font-body-md text-primary">15 Packs</p>
                                    <p class="font-label-md text-on-surface-variant text-xs mt-1">10 pcs/pack</p>
                                </td>
                                <td class="px-lg py-md text-right font-medium">₹3,800.00</td>
                                <td class="px-lg py-md text-right font-bold text-primary">₹57,000.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>

        <!-- Summary & Remarks -->
        <div class="col-span-12 lg:col-span-4 space-y-xl">
            <!-- Order Summary -->
            <x-admin.card>
                <x-slot:header class="bg-surface-container-low/30">
                    <h3 class="font-title-md text-primary">Order Summary</h3>
                </x-slot:header>

                <div class="space-y-md">
                    <div class="flex justify-between">
                        <span class="font-body-md text-on-surface-variant">Total Products</span>
                        <span class="font-body-md font-bold text-primary">2 Categories</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-body-md text-on-surface-variant">Total Quantity</span>
                        <span class="font-body-md font-bold text-primary">25 Packs</span>
                    </div>
                    <div class="h-px bg-outline-variant/30"></div>
                    <div class="flex justify-between">
                        <span class="font-body-md text-on-surface-variant">Subtotal</span>
                        <span class="font-body-md font-medium text-primary">₹99,000.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-body-md text-on-surface-variant">GST (12%)</span>
                        <span class="font-body-md font-medium text-primary">₹11,880.00</span>
                    </div>
                    <div class="pt-md mt-md border-t-2 border-primary/10 flex justify-between items-center">
                        <span class="font-title-md text-primary">Grand Total</span>
                        <span class="font-headline-md text-primary font-bold">₹1,10,880.00</span>
                    </div>
                </div>
            </x-admin.card>

            <!-- Remarks -->
            <x-admin.card>
                <div class="mb-lg">
                    <h3 class="font-title-md text-primary mb-sm">Customer Remarks</h3>
                    <div class="bg-surface-container-low p-md rounded-lg italic text-on-surface-variant font-body-md border border-outline-variant/20">
                        "Please ensure double-packing for the cotton shirts. Delivery requested by next Thursday."
                    </div>
                </div>
                <div>
                    <label class="font-label-md text-on-surface-variant uppercase mb-sm block text-xs">Admin Remarks</label>
                    <textarea class="w-full border border-outline-variant/50 rounded-lg p-md font-body-md focus:ring-2 focus:ring-secondary outline-none transition-all resize-none bg-surface-container-low" placeholder="Enter approval or rejection remarks..." rows="4"></textarea>
                </div>
            </x-admin.card>
        </div>
    </div>
</div>
