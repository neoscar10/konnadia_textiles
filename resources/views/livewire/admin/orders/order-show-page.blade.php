<div>
    <x-slot:title>Order Details</x-slot:title>

    <!-- Session Toasts -->
    @if(session()->has('success'))
        <div class="mb-lg p-md bg-success-container text-success border border-success rounded-lg font-body-md">
            {{ session('success') }}
        </div>
    @endif
    @if(session()->has('error'))
        <div class="mb-lg p-md bg-error-container text-error border border-error rounded-lg font-body-md">
            {{ session('error') }}
        </div>
    @endif

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl border-b border-outline-variant/30 pb-lg">
        <div class="flex items-center gap-md">
            <a href="{{ route('admin.orders.index') }}" class="w-10 h-10 bg-surface-container-low rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <div>
                <div class="flex items-center gap-sm">
                    <h1 class="font-headline-lg text-primary tracking-tight">Order #{{ $orderData['order_number'] }}</h1>
                    @php
                        $badge = app(\App\Services\Order\OrderStatusService::class)->getStatusBadge($orderData['status']);
                    @endphp
                    <span class="px-2.5 py-1 text-xs font-bold rounded-full {{ $badge['bg'] }} {{ $badge['text'] }}">
                        {{ $badge['label'] }}
                    </span>
                </div>
                <p class="font-body-md text-on-surface-variant mt-1">Placed on {{ $orderData['submitted_at'] }} via B2B Portal</p>
            </div>
        </div>
        
        <!-- Workflow Action Buttons -->
        <div class="flex flex-wrap gap-md w-full sm:w-auto mt-md sm:mt-0">
            @if(in_array('under_review', $allowedActions))
                <x-admin.button variant="outline" icon="rate_review" wire:click="$set('showReviewModal', true)">Mark Under Review</x-admin.button>
            @endif

            @if(in_array('verify_receipt', $allowedActions))
                <x-admin.button variant="primary" icon="verified_user" wire:click="$set('showVerifyReceiptModal', true)">Verify Receipt</x-admin.button>
                <x-admin.button variant="danger" icon="cancel" wire:click="$set('showRejectReceiptModal', true)">Reject Receipt</x-admin.button>
            @endif

            @if(in_array('approve', $allowedActions))
                <x-admin.button variant="primary" icon="check_circle" wire:click="$set('showApproveModal', true)">Approve Order</x-admin.button>
            @endif

            @if(in_array('reject', $allowedActions))
                <x-admin.button variant="danger" icon="cancel" wire:click="$set('showRejectModal', true)">Reject Order</x-admin.button>
            @endif

            @if(in_array('dispatch', $allowedActions))
                <x-admin.button variant="primary" icon="local_shipping" wire:click="$set('showDispatchModal', true)">Dispatch Order</x-admin.button>
            @endif

            @if(in_array('cancel', $allowedActions))
                <x-admin.button variant="danger" icon="cancel" wire:click="$set('showCancelModal', true)">Cancel Order</x-admin.button>
            @endif
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
                <p class="font-title-md text-primary">{{ $orderData['customer']['company_name'] }}</p>
                <p class="font-body-md text-on-surface-variant text-sm">{{ $orderData['customer']['customer_number'] }} • {{ $orderData['customer']['mobile_number'] }}</p>
            </div>
        </div>
        
        <div class="flex items-start gap-md bg-surface-container-lowest p-md border border-outline-variant/30 rounded-lg">
            <div class="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[20px]">payments</span>
            </div>
            <div>
                <p class="font-label-md text-on-surface-variant uppercase tracking-wider text-[10px] mb-1">Billing Details</p>
                <p class="font-title-md text-primary">{{ $orderData['checkout_method_label'] }}</p>
                <p class="font-body-md text-on-surface-variant text-sm">Payment status: <span class="font-semibold capitalize">{{ str_replace('_', ' ', $orderData['payment_status']) }}</span></p>
            </div>
        </div>

        @if($orderData['checkout_method'] === 'credit')
            <div class="flex items-start gap-md bg-surface-container-lowest p-md border rounded-lg {{ $orderData['used_credit_override_privilege'] ? 'border-error-container' : 'border-outline-variant/30' }} relative overflow-hidden">
                <div class="w-10 h-10 rounded-full {{ $orderData['used_credit_override_privilege'] ? 'bg-error-container text-error' : 'bg-primary-container text-primary' }} flex items-center justify-center flex-shrink-0 relative z-10">
                    <span class="material-symbols-outlined text-[20px]">warning</span>
                </div>
                <div class="relative z-10">
                    <p class="font-label-md uppercase tracking-wider text-[10px] mb-1 {{ $orderData['used_credit_override_privilege'] ? 'text-error' : 'text-on-surface-variant' }}">Credit Status</p>
                    <p class="font-title-md text-primary">
                        @if($orderData['used_credit_override_privilege'])
                            Extended Credit Used
                        @else
                            Within Limit
                        @endif
                    </p>
                    <p class="font-body-md text-on-surface-variant text-sm">Limit: ₹{{ number_format($orderData['credit_limit_at_order'], 2) }} • Available: ₹{{ number_format($orderData['available_credit_at_order'], 2) }}</p>
                </div>
                @if($orderData['used_credit_override_privilege'])
                    <div class="absolute right-0 top-0 bottom-0 w-1/2 bg-gradient-to-l from-error-container/30 to-transparent"></div>
                @endif
            </div>
        @else
            <div class="flex items-start gap-md bg-surface-container-lowest p-md border border-outline-variant/30 rounded-lg">
                <div class="w-10 h-10 rounded-full bg-[#0F8A46]/10 text-[#0F8A46] flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                </div>
                <div>
                    <p class="font-label-md text-on-surface-variant uppercase tracking-wider text-[10px] mb-1">Receipt Review</p>
                    <p class="font-title-md text-[#0F8A46]">Manual Payment</p>
                    <p class="font-body-md text-on-surface-variant text-sm">Receipts uploaded: {{ count($orderData['receipts']) }}</p>
                </div>
            </div>
        @endif
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
                                <th class="px-lg py-md text-center">HSN</th>
                                <th class="px-lg py-md text-right">GST</th>
                                <th class="px-lg py-md text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            @foreach($orderData['items'] as $item)
                                <tr class="hover:bg-primary/[0.02] transition-colors">
                                    <td class="px-lg py-md">
                                        <p class="font-title-md text-primary">{{ $item['product_title'] }}</p>
                                        <p class="font-body-md text-on-surface-variant text-xs font-mono mt-1">{{ $item['product_sku'] }}</p>
                                    </td>
                                    <td class="px-lg py-md">
                                        <div class="flex flex-col gap-xs">
                                            @if($item['selected_options'])
                                                @foreach($item['selected_options'] as $key => $val)
                                                    <span class="px-xs py-[2px] bg-surface-container rounded text-[10px] font-bold w-fit text-on-surface">{{ strtoupper($key) }}: {{ strtoupper($val) }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-on-surface-variant text-xs italic">Standard</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-lg py-md">
                                        @if(!empty($item['has_lvl2_unit']))
                                            <p class="font-body-md text-primary">
                                                {{ $item['quantity_lvl2'] }} {{ $item['lvl2_unit_name'] }}{{ $item['quantity_lvl2'] != 1 ? 's' : '' }}, 
                                                {{ $item['quantity_lvl1'] }} {{ $item['lvl1_unit_name'] }}{{ $item['quantity_lvl1'] != 1 ? 's' : '' }}
                                            </p>
                                            <p class="font-label-md text-on-surface-variant text-xs mt-1">(Total: {{ $item['quantity'] }} Pcs)</p>
                                        @else
                                            <p class="font-body-md text-primary">{{ $item['quantity'] }} {{ $item['unit_name'] }}</p>
                                            @if(($item['unit_short_code'] ?? 'Pcs') !== 'Pcs')
                                                <p class="font-label-md text-on-surface-variant text-xs mt-1">({{ $item['base_quantity'] }} Pcs)</p>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="px-lg py-md text-right font-medium">₹{{ number_format($item['customer_unit_price'], 2) }}</td>
                                    <td class="px-lg py-md text-center font-mono text-[10px] text-on-surface-variant">{{ $item['hsn_code'] ?? '—' }}</td>
                                    <td class="px-lg py-md text-right">
                                        <span class="block">₹{{ number_format($item['gst_amount'], 2) }}</span>
                                        <span class="text-[9px] text-on-surface-variant/70">({{ (float) $item['gst_percentage'] }}%)</span>
                                    </td>
                                    <td class="px-lg py-md text-right font-bold text-primary">₹{{ number_format($item['line_total'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.card>

            <!-- Status History Timeline -->
            <x-admin.card>
                <x-slot:header class="flex items-center gap-sm bg-surface-container-low/30">
                    <span class="material-symbols-outlined text-primary-fixed-dim">history</span>
                    <h3 class="font-title-md text-primary">Approval & Status History</h3>
                </x-slot:header>

                <div class="relative pl-lg border-l border-outline-variant/30 ml-md space-y-lg">
                    @foreach($orderData['status_history'] as $history)
                        <div class="relative">
                            <span class="absolute -left-[27px] top-1 w-3.5 h-3.5 rounded-full bg-primary border-4 border-surface-container-lowest"></span>
                            <div>
                                <p class="font-title-sm text-primary">Status updated to <span class="font-bold capitalize">{{ str_replace('_', ' ', $history['to_status']) }}</span></p>
                                @if($history['note'])
                                    <p class="font-body-md text-on-surface-variant mt-sm bg-surface-container-low p-sm rounded-lg border border-outline-variant/10">{{ $history['note'] }}</p>
                                @endif
                                <p class="text-[10px] text-on-surface-variant mt-xs">Changed by {{ $history['changed_by'] }} on {{ $history['created_at'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin.card>


        </div>

        <!-- Summary & Remarks -->
        <div class="col-span-12 lg:col-span-4 space-y-xl">
            <!-- Receipts Panel (If manual payment) -->
            @if($orderData['checkout_method'] === 'manual_payment')
                <x-admin.card>
                    <x-slot:header class="flex items-center gap-sm bg-surface-container-low/30">
                        <span class="material-symbols-outlined text-primary-fixed-dim">receipt</span>
                        <h3 class="font-title-md text-primary">Payment Proof Receipts</h3>
                    </x-slot:header>

                    <div class="space-y-sm">
                        @forelse($orderData['receipts'] as $receipt)
                            <div class="bg-surface-container-low p-sm border border-outline-variant/30 rounded-xl flex items-center justify-between gap-sm">
                                <div class="flex items-center gap-sm overflow-hidden">
                                    <span class="material-symbols-outlined text-primary text-xl flex-shrink-0">description</span>
                                    <div class="overflow-hidden">
                                        <p class="font-title-sm text-primary truncate text-xs">{{ $receipt['original_name'] }}</p>
                                        <div class="flex items-center gap-xs mt-0.5">
                                            <span class="text-[9px] text-on-surface-variant font-mono">{{ round($receipt['size'] / 1024) }} KB</span>
                                            <span class="px-1.5 py-[1px] text-[8px] font-extrabold uppercase border rounded {{ $receipt['status'] === 'verified' ? 'bg-[#0F8A46]/10 text-[#0F8A46] border-[#0F8A46]/20' : 'bg-warning-container/20 text-warning border-warning-container' }}">
                                                {{ $receipt['status'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ $receipt['file_url'] }}" target="_blank" class="px-sm py-xs border border-outline-variant/30 hover:bg-surface-container text-[#001229] font-bold text-[10px] bg-white rounded-lg transition-colors flex items-center gap-xs flex-shrink-0 shadow-xs">
                                    <span class="material-symbols-outlined text-xs">visibility</span> View
                                </a>
                            </div>
                        @empty
                            <p class="text-on-surface-variant italic text-xs">No receipts uploaded yet.</p>
                        @endforelse
                    </div>
                </x-admin.card>
            @endif

            <!-- Order Summary -->
            <x-admin.card>
                <x-slot:header class="bg-surface-container-low/30">
                    <h3 class="font-title-md text-primary">Order Summary</h3>
                </x-slot:header>

                <div class="space-y-md">
                    <div class="flex justify-between">
                        <span class="font-body-md text-on-surface-variant">Subtotal</span>
                        <span class="font-body-md font-medium text-primary">{{ $orderData['formatted_subtotal'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-body-md text-on-surface-variant">GST</span>
                        <span class="font-body-md font-medium text-primary">{{ $orderData['formatted_gst'] }}</span>
                    </div>
                    <div class="pt-md mt-md border-t-2 border-primary/10 flex justify-between items-center">
                        <span class="font-title-md text-primary">Grand Total</span>
                        <span class="font-headline-md text-primary font-bold">{{ $orderData['formatted_total'] }}</span>
                    </div>
                </div>
            </x-admin.card>

            <!-- Remarks -->
            <x-admin.card>
                <div class="mb-lg">
                    <h3 class="font-title-md text-primary mb-sm">Customer Remarks</h3>
                    <div class="bg-surface-container-low p-md rounded-lg italic text-on-surface-variant font-body-md border border-outline-variant/20">
                        "{{ $orderData['customer_notes'] ?: 'No special notes provided.' }}"
                    </div>
                </div>
                @if($orderData['admin_note'])
                    <div class="mb-lg">
                        <h3 class="font-title-md text-primary mb-sm">Admin Note</h3>
                        <div class="bg-surface-container-low p-md rounded-lg text-on-surface-variant font-body-md border border-outline-variant/20">
                            {{ $orderData['admin_note'] }}
                        </div>
                    </div>
                @endif
                @if($orderData['rejection_reason'])
                    <div>
                        <h3 class="font-title-md text-error mb-sm">Rejection Reason</h3>
                        <div class="bg-error-container/20 p-md rounded-lg text-error font-body-md border border-error-container">
                            {{ $orderData['rejection_reason'] }}
                        </div>
                    </div>
                @endif
            </x-admin.card>
        </div>
    </div>

    <!-- Review Modals -->
    <!-- Mark Under Review Modal -->
    @if($showReviewModal)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-lg z-50">
            <div class="bg-surface-container-lowest p-xl border border-outline-variant/30 rounded-xl shadow-lg w-full max-w-md">
                <h3 class="font-headline-md text-primary mb-md">Mark Under Review</h3>
                <p class="font-body-md text-on-surface-variant mb-md">Mark this order as under review? You can add an optional review note.</p>
                <textarea wire:model="adminComment" class="w-full border border-outline-variant/50 rounded-lg p-md font-body-md focus:ring-2 focus:ring-secondary outline-none transition-all resize-none bg-surface-container-low mb-md" placeholder="Enter review note (optional)..." rows="3"></textarea>
                <div class="flex justify-end gap-sm">
                    <x-admin.button variant="outline" wire:click="$set('showReviewModal', false)">Cancel</x-admin.button>
                    <x-admin.button variant="primary" wire:click="markUnderReview">Confirm</x-admin.button>
                </div>
            </div>
        </div>
    @endif

    <!-- Verify Receipt Modal -->
    @if($showVerifyReceiptModal)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-lg z-50">
            <div class="bg-surface-container-lowest p-xl border border-outline-variant/30 rounded-xl shadow-lg w-full max-w-md">
                <h3 class="font-headline-md text-primary mb-md">Verify Payment Receipt</h3>
                <p class="font-body-md text-on-surface-variant mb-md">Verify this payment receipt and mark the receipt status as verified? This will automatically place the order under review.</p>
                <textarea wire:model="adminComment" class="w-full border border-outline-variant/50 rounded-lg p-md font-body-md focus:ring-2 focus:ring-secondary outline-none transition-all resize-none bg-surface-container-low mb-md" placeholder="Enter verification note (optional)..." rows="3"></textarea>
                <div class="flex justify-end gap-sm">
                    <x-admin.button variant="outline" wire:click="$set('showVerifyReceiptModal', false)">Cancel</x-admin.button>
                    <x-admin.button variant="primary" wire:click="verifyReceipt">Confirm Verification</x-admin.button>
                </div>
            </div>
        </div>
    @endif

    <!-- Reject Receipt Modal -->
    @if($showRejectReceiptModal)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-lg z-50">
            <div class="bg-surface-container-lowest p-xl border border-outline-variant/30 rounded-xl shadow-lg w-full max-w-md">
                <h3 class="font-headline-md text-error mb-md">Reject Payment Receipt</h3>
                <p class="font-body-md text-on-surface-variant mb-md">Reject this payment receipt? A rejection reason is **required** and the order status will automatically transition to **Rejected**.</p>
                <textarea wire:model="rejectionReason" class="w-full border border-outline-variant/50 rounded-lg p-md font-body-md focus:ring-2 focus:ring-secondary outline-none transition-all resize-none bg-surface-container-low mb-sm" placeholder="Enter reason for receipt rejection..." rows="3"></textarea>
                @error('rejectionReason') <span class="text-error text-xs block mb-md">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-sm">
                    <x-admin.button variant="outline" wire:click="$set('showRejectReceiptModal', false)">Cancel</x-admin.button>
                    <x-admin.button variant="danger" wire:click="rejectReceipt">Confirm Rejection</x-admin.button>
                </div>
            </div>
        </div>
    @endif

    <!-- Approve Order Modal -->
    @if($showApproveModal)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-lg z-50">
            <div class="bg-surface-container-lowest p-xl border border-outline-variant/30 rounded-xl shadow-lg w-full max-w-md">
                <h3 class="font-headline-md text-primary mb-md">Approve Order</h3>
                <p class="font-body-md text-on-surface-variant mb-md">Approve this order? Approving will automatically deduct the required quantities from the products and combinations inventory stock.</p>
                <textarea wire:model="adminComment" class="w-full border border-outline-variant/50 rounded-lg p-md font-body-md focus:ring-2 focus:ring-secondary outline-none transition-all resize-none bg-surface-container-low mb-md" placeholder="Enter approval note (optional)..." rows="3"></textarea>
                <div class="flex justify-end gap-sm">
                    <x-admin.button variant="outline" wire:click="$set('showApproveModal', false)">Cancel</x-admin.button>
                    <x-admin.button variant="primary" wire:click="approveOrder">Approve & Deduct Stock</x-admin.button>
                </div>
            </div>
        </div>
    @endif

    <!-- Reject Order Modal -->
    @if($showRejectModal)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-lg z-50">
            <div class="bg-surface-container-lowest p-xl border border-outline-variant/30 rounded-xl shadow-lg w-full max-w-md">
                <h3 class="font-headline-md text-error mb-md">Reject Order</h3>
                <p class="font-body-md text-on-surface-variant mb-md">Reject this order? A rejection reason is **required**. If credit was applied to this order, it will be automatically reversed.</p>
                <textarea wire:model="rejectionReason" class="w-full border border-outline-variant/50 rounded-lg p-md font-body-md focus:ring-2 focus:ring-secondary outline-none transition-all resize-none bg-surface-container-low mb-sm" placeholder="Enter reason for rejection..." rows="3"></textarea>
                @error('rejectionReason') <span class="text-error text-xs block mb-md">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-sm">
                    <x-admin.button variant="outline" wire:click="$set('showRejectModal', false)">Cancel</x-admin.button>
                    <x-admin.button variant="danger" wire:click="rejectOrder">Confirm Rejection</x-admin.button>
                </div>
            </div>
        </div>
    @endif

    <!-- Dispatch Order Modal -->
    @if($showDispatchModal)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-lg z-50">
            <div class="bg-surface-container-lowest p-xl border border-outline-variant/30 rounded-xl shadow-lg w-full max-w-md">
                <h3 class="font-headline-md text-primary mb-md">Dispatch Order</h3>
                <p class="font-body-md text-on-surface-variant mb-md">Mark this order as dispatched? This will update the status to dispatched and log the action.</p>
                <textarea wire:model="adminComment" class="w-full border border-outline-variant/50 rounded-lg p-md font-body-md focus:ring-2 focus:ring-secondary outline-none transition-all resize-none bg-surface-container-low mb-md" placeholder="Enter dispatch details (optional)..." rows="3"></textarea>
                <div class="flex justify-end gap-sm">
                    <x-admin.button variant="outline" wire:click="$set('showDispatchModal', false)">Cancel</x-admin.button>
                    <x-admin.button variant="primary" wire:click="dispatchOrder">Confirm Dispatch</x-admin.button>
                </div>
            </div>
        </div>
    @endif

    <!-- Cancel Order Modal -->
    @if($showCancelModal)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-lg z-50">
            <div class="bg-surface-container-lowest p-xl border border-outline-variant/30 rounded-xl shadow-lg w-full max-w-md">
                <h3 class="font-headline-md text-error mb-md">Cancel Order</h3>
                <p class="font-body-md text-on-surface-variant mb-md">Cancel this order? If inventory stock was deducted, it will be automatically restored. If credit was applied, it will be reversed.</p>
                <textarea wire:model="adminComment" class="w-full border border-outline-variant/50 rounded-lg p-md font-body-md focus:ring-2 focus:ring-secondary outline-none transition-all resize-none bg-surface-container-low mb-md" placeholder="Enter cancellation reason/note (optional)..." rows="3"></textarea>
                <div class="flex justify-end gap-sm">
                    <x-admin.button variant="outline" wire:click="$set('showCancelModal', false)">Go Back</x-admin.button>
                    <x-admin.button variant="danger" wire:click="cancelOrder">Confirm Cancellation</x-admin.button>
                </div>
            </div>
        </div>
    @endif
</div>
