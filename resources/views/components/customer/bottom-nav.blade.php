<nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-outline-variant/30 px-2 py-1 pb-safe z-50 flex items-center justify-around shadow-lg">
    <!-- Home Tab -->
    <a href="{{ route('customer.dashboard') }}" wire:navigate class="flex flex-col items-center gap-0.5 py-1 px-3 rounded-xl transition-all {{ request()->routeIs('customer.dashboard') ? 'text-gold' : 'text-slate-500' }}">
        <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' {{ request()->routeIs('customer.dashboard') ? 1 : 0 }}">home</span>
        <span class="text-[10px] font-medium">Home</span>
    </a>

    <!-- Shop Tab -->
    <a href="{{ route('customer.products.index') }}" wire:navigate class="flex flex-col items-center gap-0.5 py-1 px-3 rounded-xl transition-all {{ request()->routeIs('customer.products.*') || request()->routeIs('customer.categories.*') ? 'text-gold' : 'text-slate-500' }}">
        <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' {{ request()->routeIs('customer.products.*') || request()->routeIs('customer.categories.*') ? 1 : 0 }}">dashboard_customize</span>
        <span class="text-[10px] font-medium">Shop</span>
    </a>

    @auth
    <!-- Cart Tab -->
    @php
        $cartCount = 0;
        if (auth()->check()) {
            $cartCount = resolve(\App\Services\Cart\CartService::class)->getCartItemCount(auth()->user());
        }
    @endphp
    <a href="{{ route('customer.cart.index') }}" wire:navigate 
       x-data="{ count: {{ $cartCount }} }" 
       @cart-updated.window="count = $event.detail.count"
       class="relative flex flex-col items-center gap-0.5 py-1 px-3 rounded-xl transition-all {{ request()->routeIs('customer.cart.index') ? 'text-gold' : 'text-slate-500' }}">
        <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' {{ request()->routeIs('customer.cart.index') ? 1 : 0 }}">shopping_cart</span>
        <span class="text-[10px] font-medium">Cart</span>
        <span x-show="count > 0" class="absolute top-1.5 right-3.5 flex h-4 w-4 items-center justify-center rounded-full bg-gold text-[9px] font-bold text-[#001229] ring-2 ring-white" x-text="count"></span>
    </a>

    <!-- Orders Tab -->
    <a href="{{ route('customer.orders.index') }}" wire:navigate class="flex flex-col items-center gap-0.5 py-1 px-3 rounded-xl transition-all {{ request()->routeIs('customer.orders.*') ? 'text-gold' : 'text-slate-500' }}">
        <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' {{ request()->routeIs('customer.orders.*') ? 1 : 0 }}">receipt_long</span>
        <span class="text-[10px] font-medium">Orders</span>
    </a>

    <!-- Profile Tab -->
    <a href="{{ route('customer.profile.show') }}" wire:navigate class="flex flex-col items-center gap-0.5 py-1 px-3 rounded-xl transition-all {{ request()->routeIs('customer.profile.show') || request()->routeIs('customer.profile.change-password') ? 'text-gold' : 'text-slate-500' }}">
        <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' {{ request()->routeIs('customer.profile.show') || request()->routeIs('customer.profile.change-password') ? 1 : 0 }}">person</span>
        <span class="text-[10px] font-medium">Profile</span>
    </a>
    @else
    <!-- Login Tab -->
    <a href="{{ route('login') }}" class="flex flex-col items-center gap-0.5 py-1 px-3 rounded-xl transition-all text-slate-500">
        <span class="material-symbols-outlined text-2xl">login</span>
        <span class="text-[10px] font-medium">Login</span>
    </a>
    @endauth
</nav>
