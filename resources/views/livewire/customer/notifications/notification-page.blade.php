<div class="max-w-3xl mx-auto">
    <!-- Page Title & Actions -->
    <x-customer.page-title 
        title="Alert Notifications" 
        subtitle="Stay updated on order status changes, credit parameter checks, and account alerts."
        :breadcrumbs="['Home' => route('customer.dashboard'), 'Notifications' => '#']"
    >
        <x-slot name="actions">
            <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 border border-outline-variant/30 hover:bg-slate-50 bg-white transition-colors">
                <span class="material-symbols-outlined text-sm">done_all</span> Mark all read
            </button>
        </x-slot>
    </x-customer.page-title>

    <!-- Notifications Groups -->
    <div class="space-y-6">
        
        <!-- NEW GROUP -->
        <div>
            <h3 class="text-xs font-bold text-[#001229] uppercase tracking-wider mb-3 flex items-center gap-2">
                <span>Recent Alerts</span>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-rose-500 text-white">2 Unread</span>
            </h3>
            
            <div class="space-y-3">
                <!-- Alert 1 -->
                <div class="p-4 bg-white rounded-xl border border-outline-variant/30 shadow-ambient flex items-start gap-4 hover:border-gold transition-colors relative overflow-hidden">
                    <span class="absolute top-0 bottom-0 left-0 w-1 bg-gold"></span>
                    <div class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1">check_circle</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-3">
                            <h4 class="text-xs font-extrabold text-[#001229]">Wholesale Order #KT-ORD-100239 Approved</h4>
                            <span class="text-[9px] text-slate-400 font-semibold whitespace-nowrap">2 hours ago</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Your order totaling ₹1,24,500 has been verified and approved against your credit profile. Internal processing has begun.
                        </p>
                        <div class="flex items-center gap-4 mt-3">
                            <a href="{{ route('customer.orders.show', 'KT-ORD-100239') }}" class="text-[10px] font-bold text-gold hover:underline">View Order details</a>
                        </div>
                    </div>
                    <span class="w-2.5 h-2.5 bg-rose-500 rounded-full flex-shrink-0 mt-2"></span>
                </div>

                <!-- Alert 2 -->
                <div class="p-4 bg-white rounded-xl border border-outline-variant/30 shadow-ambient flex items-start gap-4 hover:border-gold transition-colors relative overflow-hidden">
                    <span class="absolute top-0 bottom-0 left-0 w-1 bg-gold"></span>
                    <div class="w-8 h-8 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1">rate_review</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-3">
                            <h4 class="text-xs font-extrabold text-[#001229]">Order #KT-ORD-100245 Under Review</h4>
                            <span class="text-[9px] text-slate-400 font-semibold whitespace-nowrap">4 hours ago</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Logistics unit is verifying parameters for order #KT-ORD-100245. Awaiting dispatcher assignment.
                        </p>
                        <div class="flex items-center gap-4 mt-3">
                            <a href="{{ route('customer.orders.show', 'KT-ORD-100245') }}" class="text-[10px] font-bold text-gold hover:underline">View Order details</a>
                        </div>
                    </div>
                    <span class="w-2.5 h-2.5 bg-rose-500 rounded-full flex-shrink-0 mt-2"></span>
                </div>
            </div>
        </div>

        <!-- EARLIER GROUP -->
        <div>
            <h3 class="text-xs font-bold text-[#001229] uppercase tracking-wider mb-3">Earlier Notifications</h3>
            
            <div class="space-y-3">
                <!-- Alert 3 -->
                <div class="p-4 bg-white/70 rounded-xl border border-outline-variant/20 shadow-xs flex items-start gap-4 opacity-85 hover:opacity-100 transition-opacity">
                    <div class="w-8 h-8 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-sm">inventory</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-3">
                            <h4 class="text-xs font-extrabold text-slate-700">Replenishment Manifest Placed</h4>
                            <span class="text-[9px] text-slate-400 font-semibold whitespace-nowrap">June 08, 2026</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Draft order submitted successfully. Total order value ₹1,24,500. Credit pre-authorization completed.
                        </p>
                    </div>
                </div>

                <!-- Alert 4 -->
                <div class="p-4 bg-white/70 rounded-xl border border-outline-variant/20 shadow-xs flex items-start gap-4 opacity-85 hover:opacity-100 transition-opacity">
                    <div class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1">cancel</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-3">
                            <h4 class="text-xs font-extrabold text-slate-700">Order #KT-ORD-100210 Rejected</h4>
                            <span class="text-[9px] text-slate-400 font-semibold whitespace-nowrap">May 15, 2026</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Order #KT-ORD-100210 was rejected due to credit limit block. Outstanding balances must be cleared.
                        </p>
                        <div class="flex items-center gap-4 mt-3">
                            <a href="#" class="text-[10px] font-bold text-gold hover:underline">Contact Support</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center py-6 text-xs text-slate-400 font-medium border-t border-outline-variant/10">
            No older alerts found.
        </div>

    </div>
</div>
