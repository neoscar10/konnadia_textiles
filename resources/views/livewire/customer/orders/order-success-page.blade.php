<div class="max-w-2xl mx-auto py-12 text-center">
    <!-- Success Icon -->
    <div class="w-20 h-20 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6 ring-8 ring-emerald-50/50">
        <span class="material-symbols-outlined text-5xl" style="font-variation-settings: 'FILL' 1">check_circle</span>
    </div>

    <!-- Message -->
    <h1 class="text-2xl md:text-3xl font-extrabold text-[#001229] tracking-tight">Order Placed Successfully!</h1>
    <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">Your wholesale manifest has been submitted and is currently being processed by the logistics unit.</p>

    <!-- Order Metadata Card -->
    <x-customer.card class="my-8 text-left">
        <div class="grid grid-cols-2 gap-4 text-xs p-2">
            <div>
                <span class="text-slate-400 font-semibold block uppercase">Order Reference</span>
                <span class="font-extrabold text-[#001229] text-sm mt-0.5">{{ $orderData['order_number'] }}</span>
            </div>
            <div>
                <span class="text-slate-400 font-semibold block uppercase">Order Date</span>
                <span class="font-extrabold text-[#001229] text-sm mt-0.5">{{ date('F d, Y') }}</span>
            </div>
            <div>
                <span class="text-slate-400 font-semibold block uppercase">Grand Total (Incl. GST)</span>
                <span class="font-extrabold text-[#001229] text-sm mt-0.5">₹{{ number_format($orderData['total_amount'], 2) }}</span>
            </div>
            <div>
                <span class="text-slate-400 font-semibold block uppercase">Billing Terms</span>
                <span class="font-extrabold text-[#001229] text-sm mt-0.5">
                    @if ($orderData['checkout_method'] === 'credit')
                        @if ($orderData['used_credit_override'])
                            Credit Purchase (Pending Credit Review)
                        @else
                            Credit Purchase (Approved)
                        @endif
                    @else
                        Manual Payment (Awaiting Receipt Verification)
                    @endif
                </span>
            </div>
        </div>
    </x-customer.card>

    <!-- Stepper (Submitted state highlighted) -->
    <div class="bg-white border border-outline-variant/30 rounded-xl p-5 shadow-ambient mb-8">
        <h3 class="text-xs font-bold text-[#001229] uppercase tracking-wider text-left mb-4">Current Progress</h3>
        @php
            $status = 'submitted';
            if ($orderData['checkout_method'] === 'credit' && $orderData['used_credit_override']) {
                $status = 'pending_credit_review';
            } elseif ($orderData['checkout_method'] === 'manual_payment') {
                $status = 'pending_payment_verification';
            }
        @endphp
        <x-customer.order-progress :status="$status" />
    </div>

    <!-- WhatsApp Support Banner -->
    <div class="bg-slate-50 border border-outline-variant/20 rounded-xl p-4 flex items-center justify-between gap-4 text-left mb-8">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-[#0F8A46] text-3xl">chat</span>
            <div>
                <h5 class="text-xs font-bold text-[#001229]">Instant WhatsApp Alerts</h5>
                <p class="text-[10px] text-slate-400">Order updates, invoice PDFs, and dispatch details sent directly to your phone.</p>
            </div>
        </div>
        <button class="px-3 py-1.5 bg-[#0F8A46] hover:bg-[#0c6b36] text-white font-bold text-xs rounded-lg transition-colors flex items-center gap-1">
            Opt-in
        </button>
    </div>

    <!-- Actions -->
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
        <a href="{{ route('customer.orders.show', $orderData['order_number']) }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 rounded-lg text-xs font-bold bg-[#001229] text-white hover:bg-slate-800 transition-colors shadow-sm">
            View Order Details
        </a>
        <a href="{{ route('customer.dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 rounded-lg text-xs font-bold text-slate-600 border border-outline-variant/30 hover:bg-slate-50 transition-colors bg-white">
            Back to Dashboard
        </a>
    </div>
</div>
