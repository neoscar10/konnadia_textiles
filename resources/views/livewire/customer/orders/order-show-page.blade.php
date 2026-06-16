<div>
    <!-- Page Header & Actions -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <x-customer.page-title 
            title="Order details #{{ $order['order_number'] }}" 
            subtitle="Placed on {{ $order['submitted_at'] }}"
            :breadcrumbs="[
                'Home' => route('customer.dashboard'), 
                'Orders' => route('customer.orders.index'),
                '#' . $order['order_number'] => '#'
            ]"
        />
        
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold bg-white text-slate-700 border border-outline-variant/30 hover:bg-slate-50 transition-colors shadow-sm">
                <span class="material-symbols-outlined text-sm">print</span> Print
            </button>
        </div>
    </div>

    <!-- Stepper Block -->
    <div class="bg-white border border-outline-variant/30 rounded-xl p-5 shadow-ambient mb-8">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-50">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Order Progress</span>
            <x-customer.badge :status="$order['status']" />
        </div>
        <x-customer.order-progress :status="$order['status']" />
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
                            <th class="px-5 py-3.5 text-right">Unit Price</th>
                            <th class="px-5 py-3.5 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($order['items'] as $item)
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $item['image_url'] }}" alt="{{ $item['product_title'] }}" class="w-10 h-10 object-cover rounded border">
                                        <span class="font-bold text-[#001229]">{{ $item['product_title'] }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-mono text-slate-500 block">{{ $item['product_sku'] }}</span>
                                    @if($item['options_display'])
                                        <span class="text-[10px] text-slate-400">{{ $item['options_display'] }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center font-bold">
                                    {{ $item['quantity'] }} {{ $item['unit_short_code'] }}
                                    @if ($item['unit_short_code'] !== 'Pcs')
                                        ({{ round($item['quantity'] * $item['unit_conversion_quantity']) }} Pcs)
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">₹{{ number_format($item['customer_unit_price'], 2) }}</td>
                                <td class="px-5 py-4 text-right font-bold">₹{{ number_format($item['line_total'], 2) }}</td>
                            </tr>
                        @endforeach
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
                    @if($order['customer_notes'])
                        <p class="text-xs text-slate-500 leading-relaxed italic">
                            "{{ $order['customer_notes'] }}"
                        </p>
                    @else
                        <p class="text-xs text-slate-400 leading-relaxed italic">
                            No special instructions or remarks provided.
                        </p>
                    @endif
                </x-customer.card>

                <!-- Admin Status logs -->
                <x-customer.card>
                    <x-slot name="header">
                        <span class="font-bold text-[#001229] text-sm">Logistics Status Logs</span>
                    </x-slot>
                    <div class="space-y-4">
                        @forelse($order['status_history'] as $history)
                            <div class="flex gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mt-1.5"></span>
                                <div>
                                    <p class="text-xs font-bold text-slate-700">
                                        Status changed to {{ ucfirst(str_replace('_', ' ', $history['to_status'])) }}
                                    </p>
                                    @if($history['note'])
                                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $history['note'] }}</p>
                                    @endif
                                    <span class="text-[9px] text-slate-400 block mt-0.5">{{ $history['created_at'] }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic">No history logs recorded.</p>
                        @endforelse
                    </div>
                </x-customer.card>
            </div>

            <!-- Receipts / Payment Proof (Only for manual payment) -->
            @if ($order['checkout_method'] === 'manual_payment')
                <x-customer.card>
                    <x-slot name="header">
                        <span class="font-bold text-slate-800 text-sm">Payment Proof Receipts</span>
                    </x-slot>
                    <div class="space-y-3">
                        @forelse($order['receipts'] as $receipt)
                            <div class="p-3 bg-slate-50 border border-outline-variant/30 rounded-xl flex items-center justify-between gap-4">
                                <div class="flex items-center gap-2.5 overflow-hidden">
                                    <span class="material-symbols-outlined text-slate-500">description</span>
                                    <div class="overflow-hidden">
                                        <p class="text-xs font-bold text-slate-700 truncate">{{ $receipt['original_name'] }}</p>
                                        <p class="text-[10px] text-slate-400">Uploaded on {{ $receipt['uploaded_at'] }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded border {{ $receipt['status'] === 'verified' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' }}">
                                        {{ $receipt['status'] }}
                                    </span>
                                    <a href="{{ $receipt['file_url'] }}" target="_blank" class="inline-flex items-center justify-center p-1.5 text-[#001229] hover:bg-white rounded-lg border border-outline-variant/30 transition-colors">
                                        <span class="material-symbols-outlined text-lg">download</span>
                                    </a>
                                </div>
                            </div>
                            @if ($receipt['admin_note'])
                                <div class="text-[10px] text-slate-500 bg-amber-50/50 p-2.5 rounded-lg border border-amber-100/50 mt-1">
                                    <strong>Admin Note:</strong> {{ $receipt['admin_note'] }}
                                </div>
                            @endif
                        @empty
                            <p class="text-xs text-slate-400 italic">No receipt file uploaded yet.</p>
                        @endforelse
                    </div>
                </x-customer.card>
            @endif
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
                        <span>Subtotal ({{ $order['items_count'] }} {{ $order['items_count'] === 1 ? 'style' : 'styles' }} &bull; {{ $order['total_base_quantity'] }} units)</span>
                        <span class="font-bold text-slate-800">₹{{ number_format($order['subtotal'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>GST (12%)</span>
                        <span class="font-bold text-slate-800">₹{{ number_format($order['gst_amount'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>Freight charges</span>
                        <span class="font-bold text-slate-800 text-emerald-700">F.O.R Surat (Free)</span>
                    </div>
                    <div class="border-t border-dashed border-slate-200 pt-3 flex justify-between text-sm font-extrabold text-[#001229]">
                        <span>Grand Total</span>
                        <span class="text-[#001229]">₹{{ number_format($order['total_amount'], 2) }}</span>
                    </div>
                </div>

                <!-- Billing Parameters snapshot -->
                <div class="pt-3 border-t border-slate-100 text-[10px] text-slate-400 space-y-1">
                    <p><strong>Checkout Method:</strong> {{ $order['checkout_method_label'] }}</p>
                    @if ($order['checkout_method'] === 'credit')
                        <p><strong>Credit Limit at Order:</strong> ₹{{ number_format($order['credit_limit_at_order'], 2) }}</p>
                        <p><strong>Available Credit at Order:</strong> ₹{{ number_format($order['available_credit_at_order'], 2) }}</p>
                        @if ($order['used_credit_override_privilege'])
                            <p class="text-rose-600"><strong>Note:</strong> Order was approved beyond available credit limit.</p>
                        @endif
                    @endif
                </div>

                <div class="space-y-2.5 pt-3">
                    <a href="{{ route('customer.dashboard') }}" class="w-full py-2.5 border border-outline-variant/30 hover:bg-slate-50 text-[#001229] font-bold text-xs bg-white rounded-lg transition-colors flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-xs">arrow_back</span> Back to Dashboard
                    </a>
                </div>
            </x-customer.card>

            <!-- Address card -->
            <x-customer.card bodyClass="p-5 space-y-4">
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Delivery parameters</span>
                </x-slot>

                <div>
                    <span class="text-[10px] text-slate-400 font-semibold block uppercase">Billing / Shipping address</span>
                    <p class="text-xs text-slate-700 leading-relaxed mt-1 whitespace-pre-line">
                        <strong>{{ auth()->user()->customer->company_name }}</strong><br>
                        Contact: {{ auth()->user()->customer->contact_person }}<br>
                        GSTIN: {{ auth()->user()->customer->gst_number }}<br>
                        {{ auth()->user()->customer->billing_address ?? 'No address set.' }}
                    </p>
                </div>
            </x-customer.card>
        </div>

    </div>
</div>
