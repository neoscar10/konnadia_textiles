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


            @if(in_array('cancel', $allowedActions))
                <x-admin.button variant="danger" icon="cancel" wire:click="$set('showCancelModal', true)">Cancel Order</x-admin.button>
            @endif

            @if(in_array($orderData['status'], ['approved', 'partially_dispatched']) && !empty($selectedItemIds))
                <x-admin.button variant="primary" icon="local_shipping" wire:click="openBulkDispatchModal" class="!bg-purple-600 hover:!bg-purple-700 !text-white">
                    Bulk Dispatch ({{ count($selectedItemIds) }})
                </x-admin.button>
            @endif
        </div>
    </div>

    <!-- Top Info Banner -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-lg mb-xl">
        <div class="flex items-start gap-md bg-surface-container-lowest p-md border border-outline-variant/30 rounded-lg">
            <div class="w-10 h-10 rounded-full bg-primary-container text-white flex items-center justify-center flex-shrink-0">
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

        <div class="flex items-start gap-md bg-surface-container-lowest p-md border border-outline-variant/30 rounded-lg">
            <div class="w-10 h-10 rounded-full bg-primary-container text-white flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[20px]">shopping_bag</span>
            </div>
            <div>
                <p class="font-label-md text-on-surface-variant uppercase tracking-wider text-[10px] mb-1">Order Summary</p>
                <p class="font-title-md text-primary capitalize">{{ str_replace('_', ' ', $orderData['status']) }}</p>
                <p class="font-body-md text-on-surface-variant text-sm">Items: {{ count($orderData['items']) }}</p>
            </div>
        </div>
    </div>

    <!-- Main Content Split -->
    <div class="grid grid-cols-12 gap-xl pb-xl">
        <!-- Order Summary Row (Full Width) -->
        <div class="col-span-12">
            <x-admin.card>
                <x-slot:header class="bg-surface-container-low/30">
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-outlined text-primary-fixed-dim">receipt_long</span>
                        <h3 class="font-title-md text-primary">Order Summary</h3>
                    </div>
                </x-slot:header>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-xl divide-y md:divide-y-0 md:divide-x divide-outline-variant/30">
                    <div class="flex flex-col justify-center items-center py-sm md:py-0">
                        <span class="font-label-md text-on-surface-variant uppercase tracking-wider text-[11px] mb-1">Subtotal</span>
                        <span class="font-headline-sm text-primary font-semibold">{{ $orderData['formatted_subtotal'] }}</span>
                    </div>
                    <div class="flex flex-col justify-center items-center py-sm md:py-0 pt-md md:pt-0">
                        <span class="font-label-md text-on-surface-variant uppercase tracking-wider text-[11px] mb-1">GST</span>
                        <span class="font-headline-sm text-primary font-semibold">{{ $orderData['formatted_gst'] }}</span>
                    </div>
                    <div class="flex flex-col justify-center items-center py-sm md:py-0 pt-md md:pt-0">
                        <span class="font-label-md text-secondary uppercase tracking-wider text-[11px] mb-1 font-bold">Grand Total</span>
                        <span class="font-headline-md text-[#5c44c4] font-black">{{ $orderData['formatted_total'] }}</span>
                    </div>
                </div>
            </x-admin.card>
        </div>

        <!-- Order Items List (Full Width) -->
        <div class="col-span-12">
            <x-admin.card>
                <x-slot:header class="flex items-center justify-between bg-surface-container-low/30 w-full">
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-outlined text-primary-fixed-dim">inventory_2</span>
                        <h3 class="font-title-md text-primary">Order Items</h3>
                    </div>
                    @if(in_array($orderData['status'], ['approved', 'partially_dispatched']) && !empty($selectedItemIds))
                        <x-admin.button variant="primary" icon="local_shipping" wire:click="openBulkDispatchModal" class="!bg-purple-600 hover:!bg-purple-700 !text-white text-xs py-1.5 px-3">
                            Dispatch All Selected ({{ count($selectedItemIds) }})
                        </x-admin.button>
                    @endif
                </x-slot:header>

                <x-slot:bodyClass>p-0</x-slot:bodyClass>

                <div class="overflow-x-auto">
                    <table class="w-full text-left font-body-md">
                        <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                            <tr>
                                <th class="px-lg py-md">Product</th>
                                <th class="px-lg py-md">Qty/Unit</th>
                                <th class="px-lg py-md text-right">Price</th>
                                <th class="px-lg py-md text-center">Status</th>
                                <th class="px-lg py-md text-right">GST</th>
                                <th class="px-lg py-md text-right">Total</th>
                                <th class="px-lg py-md text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            @foreach($orderData['items'] as $item)
                                <tr class="hover:bg-primary/[0.02] transition-colors">
                                    <td class="px-lg py-md">
                                        <div class="flex items-start gap-md">
                                            @if(in_array($orderData['status'], ['approved', 'partially_dispatched']) && $item['status'] === 'pending_dispatch' && $item['product_type'] === 'retail')
                                                <input type="checkbox" wire:model.live="selectedItemIds" value="{{ $item['id'] }}" class="w-4.5 h-4.5 rounded border-outline-variant text-[#5c44c4] focus:ring-[#5c44c4] cursor-pointer mt-0.5">
                                            @endif
                                            
                                            <!-- Product Image and Catalog Link -->
                                            <div class="flex flex-col items-center gap-xs">
                                                @if(!empty($item['primary_media_file_path']))
                                                    <div class="w-20 h-20 rounded-lg overflow-hidden border border-outline-variant/30 shadow-xs bg-slate-50">
                                                        <img src="{{ Storage::url($item['primary_media_file_path']) }}" class="w-full h-full object-cover">
                                                    </div>
                                                @else
                                                    <div class="w-20 h-20 rounded-lg border border-dashed border-outline-variant/30 flex items-center justify-center bg-slate-50 text-on-surface-variant/40">
                                                        <span class="material-symbols-outlined text-[32px]">image</span>
                                                    </div>
                                                @endif
                                                <a href="{{ route('admin.design-catalog.index', ['search' => $item['product_sku']]) }}" 
                                                   class="inline-flex items-center gap-0.5 text-[10px] font-bold text-secondary hover:text-secondary-hover bg-secondary/10 px-2 py-0.5 rounded transition-all hover:scale-105 whitespace-nowrap mt-1 cursor-pointer">
                                                    <span class="material-symbols-outlined text-[12px] font-bold">visibility</span>
                                                    View in Catalog
                                                </a>
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <p class="font-title-md text-primary font-bold">{{ $item['product_title'] }}</p>
                                                <p class="font-body-md text-on-surface-variant text-xs font-mono mt-1">{{ $item['product_sku'] }}</p>
                                            </div>
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
                                    <td class="px-lg py-md text-center">
                                        @php
                                            $itemStatus = $item['status'] ?? 'pending_dispatch';
                                            $itemBadge = match($itemStatus) {
                                                'dispatched' => ['bg' => 'bg-purple-50 text-purple-700 border-purple-200/50', 'label' => 'Dispatched'],
                                                'cancelled' => ['bg' => 'bg-slate-100 text-slate-500 border-slate-200', 'label' => 'Cancelled'],
                                                default => ['bg' => 'bg-amber-50 text-amber-700 border-amber-200/50', 'label' => 'Pending Dispatch'],
                                            };
                                        @endphp
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full border {{ $itemBadge['bg'] }}">
                                            {{ $itemBadge['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-lg py-md text-right">
                                        <span class="block">₹{{ number_format($item['gst_amount'], 2) }}</span>
                                        <span class="text-[9px] text-on-surface-variant/70">({{ (float) $item['gst_percentage'] }}%)</span>
                                    </td>
                                    <td class="px-lg py-md text-right font-bold text-primary">₹{{ number_format($item['line_total'], 2) }}</td>
                                    <td class="px-lg py-md text-center">
                                        @if(in_array($orderData['status'], ['approved', 'partially_dispatched']) && ($item['status'] ?? 'pending_dispatch') === 'pending_dispatch')
                                            <div class="flex items-center justify-center gap-xs">
                                                <button type="button" wire:click="openDispatchItemModal({{ $item['id'] }})" class="px-2 py-1 bg-primary text-on-primary rounded text-xs font-bold hover:bg-primary-hover transition-colors flex items-center gap-xxs cursor-pointer">
                                                    <span class="material-symbols-outlined text-[14px]">local_shipping</span> Dispatch
                                                </button>
                                                <button type="button" wire:click="openCancelItemModal({{ $item['id'] }})" class="px-2 py-1 bg-error-container text-error rounded text-xs font-bold hover:bg-error-container/80 transition-colors flex items-center gap-xxs border border-error/20 cursor-pointer">
                                                    <span class="material-symbols-outlined text-[14px]">cancel</span> Cancel
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-xs text-on-surface-variant/50">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>

        <!-- Left Column: Status History Timeline -->
        <div class="col-span-12 lg:col-span-8 space-y-xl">
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

        <!-- Right Column: Remarks & Dispatches -->
        <div class="col-span-12 lg:col-span-4 space-y-xl">
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

            <!-- Dispatches History -->
            <x-admin.card>
                <x-slot:header class="bg-surface-container-low/30">
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-outlined text-primary-fixed-dim font-bold">local_shipping</span>
                        <h3 class="font-title-md text-primary">Dispatches</h3>
                    </div>
                </x-slot:header>

                @php
                    $dispatchedItems = collect($orderData['items'])->filter(fn($i) => ($i['status'] ?? '') === 'dispatched');
                @endphp

                @if($dispatchedItems->isEmpty())
                    <div class="flex flex-col items-center justify-center p-lg bg-surface-container-low/40 border border-dashed border-outline-variant/30 rounded-lg text-center opacity-60 select-none">
                        <span class="material-symbols-outlined text-[36px] text-on-surface-variant mb-xs">local_shipping</span>
                        <p class="font-body-md text-on-surface-variant text-sm">No dispatches logged for this order yet.</p>
                    </div>
                @else
                    <div class="space-y-md">
                        @foreach($dispatchedItems as $dispItem)
                            <div class="p-md bg-surface-container-low rounded-lg border border-outline-variant/20 space-y-xs">
                                <div class="flex items-start justify-between gap-sm">
                                    <div>
                                        <p class="font-title-sm text-primary font-bold">{{ $dispItem['product_title'] }}</p>
                                        <p class="text-xs text-on-surface-variant font-mono">{{ $dispItem['product_sku'] }}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-xs bg-purple-100 text-purple-700 font-bold px-2 py-0.5 rounded-full border border-purple-200">
                                            @if(!empty($dispItem['has_lvl2_unit']))
                                                @if($dispItem['quantity_lvl2'] > 0)
                                                    {{ $dispItem['quantity_lvl2'] }} {{ $dispItem['lvl2_unit_name'] }}{{ $dispItem['quantity_lvl2'] != 1 ? 's' : '' }}
                                                @endif
                                                @if($dispItem['quantity_lvl1'] > 0)
                                                    @if($dispItem['quantity_lvl2'] > 0), @endif
                                                    {{ $dispItem['quantity_lvl1'] }} {{ $dispItem['lvl1_unit_name'] }}{{ $dispItem['quantity_lvl1'] != 1 ? 's' : '' }}
                                                @endif
                                            @else
                                                {{ $dispItem['quantity'] }} {{ $dispItem['unit_short_code'] ?: 'Pcs' }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div class="pt-xs border-t border-outline-variant/10 text-xs text-on-surface-variant">
                                    <strong>Dispatch Note:</strong>
                                    <p class="mt-0.5 italic text-on-surface-variant/80">{{ $dispItem['dispatch_note'] ?: 'No dispatch note entered.' }}</p>
                                </div>
                            </div>
                        @endforeach
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
                <p class="font-body-md text-on-surface-variant mb-md">Reject this order? A rejection reason is **required**.</p>
                <textarea wire:model="rejectionReason" class="w-full border border-outline-variant/50 rounded-lg p-md font-body-md focus:ring-2 focus:ring-secondary outline-none transition-all resize-none bg-surface-container-low mb-sm" placeholder="Enter reason for rejection..." rows="3"></textarea>
                @error('rejectionReason') <span class="text-error text-xs block mb-md">{{ $message }}</span> @enderror
                <div class="flex justify-end gap-sm">
                    <x-admin.button variant="outline" wire:click="$set('showRejectModal', false)">Cancel</x-admin.button>
                    <x-admin.button variant="danger" wire:click="rejectOrder">Confirm Rejection</x-admin.button>
                </div>
            </div>
        </div>
    @endif


    <!-- Cancel Order Modal -->
    @if($showCancelModal)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-lg z-50">
            <div class="bg-surface-container-lowest p-xl border border-outline-variant/30 rounded-xl shadow-lg w-full max-w-md">
                <h3 class="font-headline-md text-error mb-md">Cancel Order</h3>
                <p class="font-body-md text-on-surface-variant mb-md">Cancel this order? If inventory stock was deducted, it will be automatically restored.</p>
                <textarea wire:model="adminComment" class="w-full border border-outline-variant/50 rounded-lg p-md font-body-md focus:ring-2 focus:ring-secondary outline-none transition-all resize-none bg-surface-container-low mb-md" placeholder="Enter cancellation reason/note (optional)..." rows="3"></textarea>
                <div class="flex justify-end gap-sm">
                    <x-admin.button variant="outline" wire:click="$set('showCancelModal', false)">Go Back</x-admin.button>
                    <x-admin.button variant="danger" wire:click="cancelOrder">Confirm Cancellation</x-admin.button>
                </div>
            </div>
        </div>
    @endif

    <!-- Dispatch Item Modal -->
    @if($showItemDispatchModal)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-lg z-50">
            <div class="bg-surface-container-lowest p-xl border border-outline-variant/30 rounded-xl shadow-lg w-full max-w-md">
                <h3 class="font-headline-md text-primary mb-md">Dispatch Item</h3>
                <p class="font-body-md text-on-surface-variant mb-md">Confirm the quantity getting dispatched in the unit the order was placed.</p>
                
                @php
                    $selItem = collect($orderData['items'])->firstWhere('id', $selectedItemId);
                    $unitName = $selItem ? ($selItem['unit_short_code'] ?: 'Pcs') : 'qty';
                    $maxQty = $selItem ? $selItem['quantity'] : 1;
                @endphp

                <div class="space-y-sm mb-md">
                    <label class="font-label-md text-on-surface-variant font-semibold">Quantity to Dispatch (Max {{ $maxQty }} {{ $unitName }})</label>
                    <div class="relative">
                        <input type="number" wire:model="dispatchQty" min="1" max="{{ $maxQty }}" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface" placeholder="Enter quantity to dispatch">
                        <span class="absolute right-md top-1/2 -translate-y-1/2 text-on-surface-variant font-bold text-sm">{{ $unitName }}</span>
                    </div>
                    @error('dispatchQty') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                @if($selItem && $selItem['product_type'] === 'retail')
                    <div class="space-y-sm mb-md">
                        <label class="font-label-md text-on-surface-variant font-semibold">Dispatch Note</label>
                        <textarea wire:model="dispatchNote" rows="3" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface" placeholder="Enter dispatch note..."></textarea>
                        @error('dispatchNote') <span class="text-error text-xs">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="flex justify-end gap-sm">
                    <x-admin.button variant="outline" wire:click="$set('showItemDispatchModal', false)">Cancel</x-admin.button>
                    <x-admin.button variant="primary" wire:click="confirmDispatchItem">Confirm Dispatch</x-admin.button>
                </div>
            </div>
        </div>
    @endif

    <!-- Bulk Dispatch Modal -->
    @if($showBulkDispatchModal)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-lg z-50">
            <div class="bg-surface-container-lowest p-xl border border-outline-variant/30 rounded-xl shadow-lg w-full max-w-lg">
                <h3 class="font-headline-md text-primary mb-md">Bulk Dispatch Items</h3>
                <p class="font-body-md text-on-surface-variant mb-md">Confirm quantities and enter a dispatch note for the selected manufactured items.</p>
                
                <div class="space-y-md max-h-[220px] overflow-y-auto pr-xs mb-md">
                    @foreach($selectedItemIds as $itemId)
                        @php
                            $selItem = collect($orderData['items'])->firstWhere('id', $itemId);
                        @endphp
                        @if($selItem)
                            <div class="flex items-center justify-between gap-md p-sm bg-surface-container-low rounded-lg border border-outline-variant/20">
                                <div class="flex-1">
                                    <p class="font-title-sm text-primary text-sm font-bold">{{ $selItem['product_title'] }}</p>
                                    <p class="text-xs text-on-surface-variant font-mono">{{ $selItem['product_sku'] }}</p>
                                </div>
                                <div class="w-36 flex items-center gap-xs">
                                    <input type="number" wire:model="bulkDispatchQuantities.{{ $itemId }}" min="1" max="{{ $selItem['quantity'] }}" class="w-full px-sm py-xs bg-white border border-outline-variant/50 rounded text-sm text-right focus:ring-1 focus:ring-secondary outline-none">
                                    <span class="text-xs text-on-surface-variant font-bold">{{ $selItem['unit_short_code'] ?: 'Pcs' }}</span>
                                </div>
                            </div>
                            @error("bulkDispatchQuantities.{$itemId}") <span class="text-error text-xs block text-right mt-xxs">{{ $message }}</span> @enderror
                        @endif
                    @endforeach
                </div>

                <div class="space-y-sm mb-md">
                    <label class="font-label-md text-on-surface-variant font-semibold">Dispatch Note</label>
                    <textarea wire:model="dispatchNote" rows="3" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface" placeholder="Enter dispatch note for all selected items..."></textarea>
                    @error('dispatchNote') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-sm">
                    <x-admin.button variant="outline" wire:click="$set('showBulkDispatchModal', false)">Cancel</x-admin.button>
                    <x-admin.button variant="primary" wire:click="confirmBulkDispatch">Confirm Dispatch</x-admin.button>
                </div>
            </div>
        </div>
    @endif

    <!-- Cancel Item Modal -->
    @if($showItemCancelModal)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-lg z-50">
            <div class="bg-surface-container-lowest p-xl border border-outline-variant/30 rounded-xl shadow-lg w-full max-w-md">
                <h3 class="font-headline-md text-error mb-md">Cancel Order Item</h3>
                <p class="font-body-md text-on-surface-variant mb-md">Are you sure you want to cancel this order item? If stock was deducted, it will be automatically restored, and the order total will be updated.</p>
                <div class="flex justify-end gap-sm">
                    <x-admin.button variant="outline" wire:click="$set('showItemCancelModal', false)">Go Back</x-admin.button>
                    <x-admin.button variant="danger" wire:click="confirmCancelItem">Confirm Cancellation</x-admin.button>
                </div>
            </div>
        </div>
    @endif
</div>
