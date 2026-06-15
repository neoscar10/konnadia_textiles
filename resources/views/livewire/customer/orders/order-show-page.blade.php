<div>
    <!-- Page Header & Actions -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <x-customer.page-title 
            title="Order details #{{ $orderNumber }}" 
            subtitle="Placed on June 12, 2026 at 11:34 AM"
            :breadcrumbs="[
                'Home' => route('customer.dashboard'), 
                'Orders' => route('customer.orders.index'),
                '#' . $orderNumber => '#'
            ]"
        />
        
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <button class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold bg-white text-slate-700 border border-outline-variant/30 hover:bg-slate-50 transition-colors shadow-sm">
                <span class="material-symbols-outlined text-sm">print</span> Print
            </button>
            <button class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold bg-white text-slate-700 border border-outline-variant/30 hover:bg-slate-50 transition-colors shadow-sm">
                <span class="material-symbols-outlined text-sm">download</span> Invoice PDF
            </button>
        </div>
    </div>

    <!-- Stepper Block -->
    <div class="bg-white border border-outline-variant/30 rounded-xl p-5 shadow-ambient mb-8">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-50">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Order Progress</span>
            <x-customer.badge status="under review" />
        </div>
        <x-customer.order-progress status="under review" />
    </div>

    <!-- Layout: Left (Items Table), Right (Billing, remarks, addresses) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Items Table (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
            <x-customer.card bodyClass="p-0 overflow-hidden">
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Ordered items</span>
                </x-slot>

                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-bold uppercase border-b border-outline-variant/15 text-[10px] tracking-wider">
                            <th class="px-5 py-3.5">Product</th>
                            <th class="px-5 py-3.5">SKU / Specs</th>
                            <th class="px-5 py-3.5 text-center">Qty</th>
                            <th class="px-5 py-3.5">Unit Price</th>
                            <th class="px-5 py-3.5 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=100" alt="Product" class="w-10 h-10 object-cover rounded border">
                                    <span class="font-bold text-[#001229]">Premium Formal Cotton Shirt</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-mono text-slate-500 block">TS-0012</span>
                                <span class="text-[10px] text-slate-400">Size: M | Color: Navy Blue</span>
                            </td>
                            <td class="px-5 py-4 text-center font-bold">40 Pieces</td>
                            <td class="px-5 py-4">₹350</td>
                            <td class="px-5 py-4 text-right font-bold">₹14,000</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=100" alt="Product" class="w-10 h-10 object-cover rounded border">
                                    <span class="font-bold text-[#001229]">Casual Comfort Denim Pants</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-mono text-slate-500 block">TS-0015</span>
                                <span class="text-[10px] text-slate-400">Size: L | Color: Dark Indigo</span>
                            </td>
                            <td class="px-5 py-4 text-center font-bold">20 Pieces</td>
                            <td class="px-5 py-4">₹850</td>
                            <td class="px-5 py-4 text-right font-bold">₹17,000</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=100" alt="Product" class="w-10 h-10 object-cover rounded border">
                                    <span class="font-bold text-[#001229]">Standard Knitted Polo Tee</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="font-mono text-slate-500 block">TS-0018</span>
                                <span class="text-[10px] text-slate-400">Size: XL | Color: Sky Blue</span>
                            </td>
                            <td class="px-5 py-4 text-center font-bold">50 Pieces</td>
                            <td class="px-5 py-4">₹210</td>
                            <td class="px-5 py-4 text-right font-bold">₹10,500</td>
                        </tr>
                    </tbody>
                </table>
            </x-customer.card>

            <!-- Order Remarks & Status logs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Customer Remarks -->
                <x-customer.card>
                    <x-slot name="header">
                        <span class="font-bold text-slate-800 text-sm">Customer Remarks</span>
                    </x-slot>
                    <p class="text-xs text-slate-500 leading-relaxed italic">
                        "Deliver via SafeCargo logistics if possible. Please confirm packaging parameters."
                    </p>
                </x-customer.card>

                <!-- Admin Status logs -->
                <x-customer.card>
                    <x-slot name="header">
                        <span class="font-bold text-[#001229] text-sm">Logistics Status Logs</span>
                    </x-slot>
                    <div class="space-y-3">
                        <div class="flex gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mt-1.5"></span>
                            <div>
                                <p class="text-xs font-bold text-slate-700">Awaiting internal validation check</p>
                                <span class="text-[9px] text-slate-400">June 12, 2026 at 11:34 AM</span>
                            </div>
                        </div>
                    </div>
                </x-customer.card>
            </div>
        </div>

        <!-- Right Side: Order summary & Shipping addresses (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Totals Summary Card -->
            <x-customer.card bodyClass="p-5 space-y-4">
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Billing Breakdown</span>
                </x-slot>

                <div class="space-y-3 text-xs">
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>Subtotal (110 units)</span>
                        <span class="font-bold text-slate-800">₹41,500</span>
                    </div>
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>GST (12%)</span>
                        <span class="font-bold text-slate-800">₹4,980</span>
                    </div>
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>Freight charges</span>
                        <span class="font-bold text-slate-800 text-emerald-700">F.O.R Surat (Free)</span>
                    </div>
                    <div class="border-t border-dashed border-slate-200 pt-3 flex justify-between text-sm font-extrabold text-[#001229]">
                        <span>Grand Total</span>
                        <span class="text-[#001229]">₹46,480</span>
                    </div>
                </div>

                <div class="space-y-2.5 pt-3">
                    <button class="w-full py-2 bg-[#001229] hover:bg-slate-800 text-white font-bold text-xs rounded-lg transition-colors flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-xs">restore</span> Re-order Items
                    </button>
                    <button class="w-full py-2 border border-outline-variant/30 hover:bg-slate-50 text-[#001229] font-bold text-xs bg-white rounded-lg transition-colors flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-xs">contact_support</span> Contact Support
                    </button>
                </div>
            </x-customer.card>

            <!-- Address card -->
            <x-customer.card bodyClass="p-5 space-y-4">
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Delivery parameters</span>
                </x-slot>

                <div>
                    <span class="text-[10px] text-slate-400 font-semibold block uppercase">Shipping address</span>
                    <p class="text-xs text-slate-700 leading-relaxed mt-1">
                        Raj Garments Outlet 4,<br>
                        Main Bazaar Road, Opp Axis Bank,<br>
                        Ahmadabad, Gujarat - 380001
                    </p>
                </div>
            </x-customer.card>
        </div>

    </div>
</div>
