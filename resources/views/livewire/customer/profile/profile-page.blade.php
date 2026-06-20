<div>
    <!-- Page Header & Badging -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 bg-white border border-outline-variant/30 rounded-xl shadow-ambient">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-gold text-[#001229] font-black text-2xl flex items-center justify-center">
                {{ auth()->user()->initials }}
            </div>
            <div>
                <h1 class="text-xl md:text-2xl font-extrabold text-[#001229] tracking-tight">
                    {{ auth()->user()->customer?->company_name ?? auth()->user()->name }}
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Customer ID: #{{ auth()->user()->customer?->customer_number ?? 'N/A' }} 
                    @if(auth()->user()->customer?->level)
                        &bull; {{ auth()->user()->customer->level->name }}
                    @endif
                </p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/50">Active Partner</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#001229]/5 text-[#001229] border border-outline-variant/20">Surat Cluster</span>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <a href="{{ route('customer.profile.change-password') }}" class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-xs font-bold bg-white text-slate-700 border border-outline-variant/30 hover:bg-slate-50 transition-colors shadow-sm">
                <span class="material-symbols-outlined text-sm">lock</span> Change Password
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-xs font-bold bg-rose-50 text-error border border-rose-200 hover:bg-rose-100 transition-colors">
                    <span class="material-symbols-outlined text-sm">logout</span> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Core info sections (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
            <!-- Business Parameters -->
            <x-customer.card>
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Business Registration details</span>
                </x-slot>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-xs">
                    <div>
                        <span class="text-slate-400 font-semibold uppercase block">Official Business name</span>
                        <span class="font-bold text-[#001229] text-sm mt-0.5 block">{{ auth()->user()->customer?->company_name ?? auth()->user()->name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold uppercase block">GST Registration number</span>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="font-bold text-[#001229] text-sm">{{ auth()->user()->customer?->gst_number ?? 'N/A' }}</span>
                            @if(auth()->user()->customer?->gst_number)
                                <span class="material-symbols-outlined text-emerald-600 text-sm" style="font-variation-settings: 'FILL' 1">verified</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold uppercase block">Contact Mobile</span>
                        <span class="font-bold text-[#001229] text-sm mt-0.5 block">{{ auth()->user()->customer?->mobile_number ?? auth()->user()->mobile_number ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 font-semibold uppercase block">Official Email</span>
                        <span class="font-bold text-[#001229] text-sm mt-0.5 block">{{ auth()->user()->customer?->email ?? auth()->user()->email ?? 'N/A' }}</span>
                    </div>
                </div>
            </x-customer.card>

            <!-- Address Parameters -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Billing address -->
                <x-customer.card>
                    <x-slot name="header">
                        <span class="font-bold text-slate-800 text-sm">Billing Office Address</span>
                    </x-slot>
                    <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">@if(auth()->user()->customer?->billing_address){{ auth()->user()->customer->billing_address }}@else{{"No address specified."}}@endif</p>
                </x-customer.card>

                <!-- Shipping address -->
                <x-customer.card>
                    <x-slot name="header">
                        <span class="font-bold text-slate-800 text-sm">Primary Delivery Warehouse</span>
                    </x-slot>
                    <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">@if(auth()->user()->customer?->billing_address){{ auth()->user()->customer->billing_address }}@else{{"No address specified."}}@endif</p>
                </x-customer.card>
            </div>
        </div>

        <!-- Right Side: Account Manager & Credit parameters (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            <!-- Account manager card -->
            <x-customer.card>
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Dedicated Account manager</span>
                </x-slot>
                
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-[#001229] font-bold text-sm">
                        SK
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-[#001229]">Siddharth Konnadia</h4>
                        <p class="text-[10px] text-slate-500">Logistics coordinator</p>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-3 mt-4 space-y-2 text-xs font-medium">
                    <div class="flex items-center gap-2 text-slate-600">
                        <span class="material-symbols-outlined text-slate-400 text-base">phone_in_talk</span>
                        <span>+91 99999 88888</span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-600">
                        <span class="material-symbols-outlined text-slate-400 text-base">mail</span>
                        <span>sid@kannodiatextiles.com</span>
                    </div>
                </div>
            </x-customer.card>

            <!-- Credit metrics -->
            <div>
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">My Credit profile</h4>
                <x-customer.credit-summary 
                    :available="auth()->user()->customer?->available_credit ?? 0" 
                    :limit="auth()->user()->customer?->credit_limit ?? 0" 
                    :outstanding="auth()->user()->customer?->outstanding_amount ?? 0" />
            </div>
        </div>

    </div>
</div>
