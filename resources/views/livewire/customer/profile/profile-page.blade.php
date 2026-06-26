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
            <button type="button" wire:click="openChangePasswordModal" class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-xs font-bold bg-white text-slate-700 border border-outline-variant/30 hover:bg-slate-50 transition-colors shadow-sm">
                <span class="material-symbols-outlined text-sm">lock</span> Change Password
            </button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-xs font-bold bg-rose-50 text-error border border-rose-200 hover:bg-rose-100 transition-colors">
                    <span class="material-symbols-outlined text-sm">logout</span> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Alerts Strip -->
    @if(!empty($alerts))
        <div class="space-y-3 mb-6">
            @foreach($alerts as $alert)
                <div class="p-4 rounded-xl border flex items-start gap-3 shadow-ambient transition-all @if($alert['type'] === 'danger') bg-rose-50 border-rose-200 text-rose-800 @elseif($alert['type'] === 'warning') bg-amber-50 border-amber-200 text-amber-800 @else bg-blue-50 border-blue-200 text-blue-800 @endif">
                    <span class="material-symbols-outlined mt-0.5">
                        @if($alert['type'] === 'danger') error @elseif($alert['type'] === 'warning') warning @else info @endif
                    </span>
                    <div>
                        <h4 class="text-sm font-bold">{{ $alert['title'] }}</h4>
                        <p class="text-xs mt-0.5 opacity-90">{{ $alert['message'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Stats grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <x-customer.stat-card 
            title="Total Orders" 
            value="{{ $orders['total_orders'] ?? 0 }}" 
            icon="list_alt" 
            trend="{{ $orders['pending_orders'] ?? 0 }} Pending Verification" 
            trendType="neutral" 
        />
        <x-customer.stat-card 
            title="Dispatched Orders" 
            value="{{ $orders['dispatched_orders'] ?? 0 }}" 
            icon="local_shipping" 
            trend="{{ $orders['approved_orders'] ?? 0 }} Approved & Preparing" 
            trendType="up" 
        />
        <x-customer.stat-card 
            title="Total Order Value" 
            value="{{ $orders['formatted_total_order_value'] ?? '₹0.00' }}" 
            icon="payments" 
            trend="Excludes Rejected/Cancelled" 
            trendType="neutral" 
        />
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
                    <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">@php
                        $c = auth()->user()->customer;
                        $hasAddress = $c && ($c->address || $c->city || $c->state || $c->pincode);
                    @endphp@if($hasAddress){{ $c->address }}
@if($c->city || $c->state || $c->pincode){{ $c->city }}{{ $c->city && $c->state ? ', ' : '' }}{{ $c->state }} {{ $c->pincode }}@endif
@else{{"No address specified."}}@endif</p>
                </x-customer.card>
            </div>

            <!-- Recent Orders -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-extrabold text-[#001229]">Recent Orders</h3>
                    <a href="{{ route('customer.orders.index') }}" class="text-xs font-bold text-gold hover:underline flex items-center gap-0.5">
                        View All Orders <span class="material-symbols-outlined text-sm">chevron_right</span>
                    </a>
                </div>

                @if(empty($recentOrders))
                    <x-customer.empty-state 
                        title="No orders found" 
                        description="You haven't placed any orders yet. Browse our catalog to get started." 
                        icon="shopping_bag" 
                    />
                @else
                    <div class="space-y-3">
                        @foreach($recentOrders as $order)
                            <a href="{{ route('customer.orders.show', $order['order_number']) }}" class="block bg-white border border-outline-variant/20 rounded-xl shadow-ambient hover:shadow-md hover:border-[#001229]/25 transition-all p-4">
                                <div class="flex items-center justify-between gap-3 text-xs">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm font-extrabold text-[#001229]">{{ $order['order_number'] }}</span>
                                            <x-customer.badge :status="$order['status']['label']" />
                                        </div>
                                        <p class="text-slate-500 text-[11px] font-medium">Created : {{ \Carbon\Carbon::parse($order['created_at'])->format('jS M, Y') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[9px] text-slate-400 font-bold uppercase block select-none">Total</span>
                                        <span class="text-sm font-extrabold text-[#001229]">{{ $order['formatted_total_amount'] }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Side: Account Manager & Cart Summary (4 cols) -->
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

            <!-- Current Cart Summary -->
            <div>
                <h3 class="text-base font-extrabold text-[#001229] mb-4">Cart Status</h3>
                <x-customer.card>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-gold">shopping_basket</span>
                            <span class="font-bold text-sm text-slate-800">Pending Cart</span>
                        </div>
                        <span class="text-xs text-slate-500 font-medium">{{ $cart['items_count'] ?? 0 }} {{ Str::plural('Item', $cart['items_count'] ?? 0) }}</span>
                    </div>
                    
                    @if(!($cart['exists'] ?? false) || ($cart['items_count'] ?? 0) == 0)
                        <div class="py-8 text-center text-slate-400 text-xs">
                            <span class="material-symbols-outlined text-3xl mb-1.5 opacity-60">shopping_cart</span>
                            <p>Your wholesale cart is empty.</p>
                        </div>
                    @else
                        <!-- Cart Items List (Constrained Height) -->
                        <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 pr-1.5 mb-4 -mx-1">
                            @foreach($cart['items'] as $item)
                                <div class="py-2 flex items-center justify-between gap-3 text-xs" wire:key="profile-cart-item-{{ $item['id'] }}">
                                    <div class="flex items-center gap-2 overflow-hidden">
                                        <img src="{{ $item['image_url'] }}" alt="{{ $item['title'] }}" class="w-8 h-8 object-cover rounded border bg-slate-50 flex-shrink-0">
                                        <div class="truncate">
                                            <p class="font-semibold text-slate-800 truncate">{{ $item['title'] }}</p>
                                            <p class="text-[10px] text-slate-400 font-medium">Qty: {{ $item['quantity'] }} {{ $item['unit_name'] }}</p>
                                        </div>
                                    </div>
                                    <span class="font-bold text-slate-700 whitespace-nowrap">{{ $item['formatted_line_total'] }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-slate-100 pt-4 mb-4 flex justify-between items-baseline">
                            <span class="text-xs text-slate-500 font-semibold">Total Amount</span>
                            <span class="text-base font-extrabold text-[#001229]">{{ $cart['formatted_total_amount'] }}</span>
                        </div>

                        <a href="{{ route('customer.cart.index') }}" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg text-xs font-bold bg-[#001229] text-white hover:bg-slate-800 transition-colors">
                            Checkout Now <span class="material-symbols-outlined text-sm">arrow_forward</span>
                        </a>
                    @endif
                </x-customer.card>
            </div>
        </div>

    </div>

    <!-- Change Password Modal -->
    @if($showChangePasswordModal)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-4 z-50">
            <div class="bg-white border border-outline-variant/30 rounded-2xl shadow-xl w-full max-w-md overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-[#001229]">Change Account Password</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Ensure your account security by keeping your password updated.</p>
                    </div>
                    <button type="button" wire:click="$set('showChangePasswordModal', false)" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Form Content -->
                <form wire:submit.prevent="changePassword" x-data="{ currentVisible: false, newVisible: false, confirmVisible: false }" class="p-6 space-y-4">
                    <!-- Current Password -->
                    <div>
                        <label class="text-xs text-slate-500 font-bold block mb-1.5">Current Account Password</label>
                        <div class="relative">
                            <input :type="currentVisible ? 'text' : 'password'" wire:model="current_password" placeholder="••••••••" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-3 pr-10 focus:outline-none focus:ring-1 focus:ring-gold">
                            <button type="button" @click="currentVisible = !currentVisible" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                <span class="material-symbols-outlined text-lg" x-text="currentVisible ? 'visibility_off' : 'visibility'">visibility</span>
                            </button>
                        </div>
                        @error('current_password')
                            <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- New Password -->
                    <div>
                        <label class="text-xs text-slate-500 font-bold block mb-1.5">New Password</label>
                        <div class="relative">
                            <input :type="newVisible ? 'text' : 'password'" wire:model="new_password" placeholder="••••••••" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-3 pr-10 focus:outline-none focus:ring-1 focus:ring-gold">
                            <button type="button" @click="newVisible = !newVisible" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                <span class="material-symbols-outlined text-lg" x-text="newVisible ? 'visibility_off' : 'visibility'">visibility</span>
                            </button>
                        </div>
                        @error('new_password')
                            <span class="text-rose-600 text-[10px] font-bold mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="text-xs text-slate-500 font-bold block mb-1.5">Confirm New Password</label>
                        <div class="relative">
                            <input :type="confirmVisible ? 'text' : 'password'" wire:model="new_password_confirmation" placeholder="••••••••" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-3 pr-10 focus:outline-none focus:ring-1 focus:ring-gold">
                            <button type="button" @click="confirmVisible = !confirmVisible" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                                <span class="material-symbols-outlined text-lg" x-text="confirmVisible ? 'visibility_off' : 'visibility'">visibility</span>
                            </button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50 -mx-6 -mb-6 p-4">
                        <button type="button" wire:click="$set('showChangePasswordModal', false)" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-700 border border-outline-variant/30 hover:bg-slate-50 transition-colors bg-white shadow-xs">Cancel</button>
                        <button type="submit" class="flex items-center justify-center px-4 py-2 rounded-lg text-xs font-bold text-white bg-[#001229] hover:bg-slate-800 transition-colors shadow-sm">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
