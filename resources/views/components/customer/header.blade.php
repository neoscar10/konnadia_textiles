<header class="sticky top-0 z-50 w-full bg-white lg:bg-[#001229] border-b border-outline-variant/30 lg:border-none shadow-sm lg:shadow-md">
    <div class="max-w-[1440px] mx-auto px-4 md:px-8 h-16 flex items-center justify-between">
        
        <!-- Left: Logo / Branding -->
        <div class="flex items-center gap-8">
            <a href="{{ route('customer.dashboard') }}" wire:navigate class="flex items-center gap-2">
                <span class="material-symbols-outlined text-gold lg:text-gold text-3xl">storefront</span>
                <span class="font-bold text-lg tracking-tight text-[#001229] lg:text-white">
                    Kannodia<span class="text-gold"> Textiles</span>
                </span>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden lg:flex items-center gap-6">
                <a href="{{ route('customer.dashboard') }}" wire:navigate class="px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('customer.dashboard') ? 'text-gold' : 'text-slate-300 hover:text-white' }}">
                    Dashboard
                </a>
                <a href="{{ route('customer.products.index') }}" wire:navigate class="px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('customer.products.*') || request()->routeIs('customer.categories.*') ? 'text-gold' : 'text-slate-300 hover:text-white' }}">
                    Shop
                </a>
                <a href="{{ route('customer.orders.index') }}" wire:navigate class="px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('customer.orders.*') ? 'text-gold' : 'text-slate-300 hover:text-white' }}">
                    Orders
                </a>

            </nav>
        </div>



        <!-- Right: Actions & Profile -->
        <div class="flex items-center gap-4">
            

            <!-- Cart Link -->
            @php
                $cartCount = 0;
                if (auth()->check()) {
                    $cartCount = resolve(\App\Services\Cart\CartService::class)->getCartItemCount(auth()->user());
                }
            @endphp
            <a href="{{ route('customer.cart.index') }}" wire:navigate 
               x-data="{ count: {{ $cartCount }} }" 
               @cart-updated.window="count = $event.detail.count"
               class="relative p-2 text-slate-600 lg:text-slate-300 hover:text-slate-900 lg:hover:text-white rounded-full hover:bg-slate-100 lg:hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined text-2xl">shopping_cart</span>
                <span x-show="count > 0" class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-gold text-[10px] font-bold text-[#001229] ring-2 ring-white lg:ring-[#001229]" x-text="count"></span>
            </a>

            <!-- Profile Avatar (Desktop Dropdown UI, mobile simple link) -->
            <div class="relative flex items-center" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 focus:outline-none">
                    <div class="w-9 h-9 rounded-full bg-gold text-[#001229] font-bold flex items-center justify-center text-sm border-2 border-white lg:border-[#0f2744]">
                        {{ auth()->user()->initials }}
                    </div>
                    <span class="hidden lg:inline text-sm font-medium text-slate-200 hover:text-white select-none">
                        {{ auth()->user()->customer?->company_name ?? auth()->user()->name }}
                    </span>
                    <span class="hidden lg:inline material-symbols-outlined text-slate-400 text-sm">keyboard_arrow_down</span>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 top-11 w-48 bg-white border border-outline-variant/30 rounded-xl shadow-lg py-1 z-50">
                    <div class="px-4 py-2 border-b border-outline-variant/30">
                        <p class="text-xs text-slate-500">Logged in as</p>
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ auth()->user()->customer?->company_name ?? auth()->user()->name }}</p>
                    </div>
                    <a href="{{ route('customer.profile.show') }}" wire:navigate class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                        <span class="material-symbols-outlined text-lg">person</span> My Profile
                    </a>
                    <a href="{{ route('customer.profile.change-password') }}" wire:navigate class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                        <span class="material-symbols-outlined text-lg">lock</span> Change Password
                    </a>
                    <div class="border-t border-outline-variant/30 my-1"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-error hover:bg-slate-50 text-left">
                            <span class="material-symbols-outlined text-lg">logout</span> Logout
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</header>
