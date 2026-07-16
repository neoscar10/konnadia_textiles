<header class="sticky top-0 z-50 w-full bg-white lg:bg-[#001229] border-b border-outline-variant/30 lg:border-none shadow-sm lg:shadow-md">
    <div class="max-w-[1440px] mx-auto px-4 md:px-8 h-16 flex items-center justify-between gap-4">
        
        <!-- Left: Logo / Branding -->
        <div class="flex items-center gap-8 shrink-0">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2">
                <img src="{{ asset('logo.png') }}" class="h-10 w-auto object-contain bg-white rounded-md p-1" alt="Sapnay Lifestyle Logo">
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden lg:flex items-center gap-6">
                <a href="{{ auth()->check() ? route('customer.dashboard') : route('home') }}" wire:navigate class="px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('customer.dashboard') || request()->routeIs('home') ? 'text-gold' : 'text-slate-300 hover:text-white' }}">
                    {{ auth()->check() ? 'Dashboard' : 'Home' }}
                </a>
                <a href="{{ route('customer.products.index') }}" wire:navigate class="px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('customer.products.*') || request()->routeIs('customer.categories.*') ? 'text-gold' : 'text-slate-300 hover:text-white' }}">
                    Shop
                </a>
                @auth
                <a href="{{ route('customer.orders.index') }}" wire:navigate class="px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('customer.orders.*') ? 'text-gold' : 'text-slate-300 hover:text-white' }}">
                    Orders
                </a>
                @endauth
            </nav>
        </div>

        <!-- Centre: Search Bar -->
        @php $searchRoute = route('customer.products.index'); @endphp
        <style>
            #header-product-search:focus {
                background-color: #ffffff !important;
                color: #001229 !important;
            }
            #mobile-product-search:focus {
                background-color: #f1f5f9 !important;
                color: #001229 !important;
            }
        </style>
        <div
            class="flex-1 max-w-md hidden md:block relative"
            x-data="{ 
                q: '', 
                suggestions: [], 
                loading: false, 
                showSuggestions: false,
                go() { 
                    if (this.q.trim()) window.location.href = '{{ $searchRoute }}?search=' + encodeURIComponent(this.q.trim()); 
                },
                async fetchSuggestions() {
                    if (this.q.trim().length < 2) {
                        this.suggestions = [];
                        this.showSuggestions = false;
                        return;
                    }
                    this.loading = true;
                    this.showSuggestions = true;
                    try {
                        let res = await fetch('{{ route('customer.products.suggestions') }}?q=' + encodeURIComponent(this.q.trim()));
                        this.suggestions = await res.json();
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.loading = false;
                    }
                }
            }"
            @click.away="showSuggestions = false"
        >
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 lg:text-slate-500 text-xl pointer-events-none select-none">search</span>
                <input
                    id="header-product-search"
                    type="text"
                    x-model="q"
                    @input.debounce.300ms="fetchSuggestions()"
                    @focus="showSuggestions = true; fetchSuggestions()"
                    @keydown.enter="go()"
                    placeholder="Search products…"
                    class="w-full pl-10 pr-4 py-2 rounded-lg text-sm font-medium
                           bg-slate-100 lg:bg-white/10 text-slate-900 lg:text-white lg:placeholder-slate-400
                           border border-transparent lg:border-white/10
                           focus:outline-none focus:ring-2 focus:ring-gold/50 focus:bg-white lg:focus:bg-white focus:text-slate-900 lg:focus:text-slate-900 focus:placeholder-slate-400
                           transition-all"
                >
                <button
                    @click="go()"
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 lg:text-slate-300 hover:text-gold transition-colors select-none px-1"
                    type="button"
                    title="Search"
                >
                    <span class="material-symbols-outlined text-base leading-none">arrow_forward</span>
                </button>
            </div>

            <!-- Search Suggestions Dropdown (Desktop) -->
            <div
                x-show="showSuggestions && (suggestions.length > 0 || loading)"
                x-transition
                class="absolute left-0 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden z-50 select-none text-slate-800"
                style="display: none;"
            >
                <div x-show="loading" class="flex items-center justify-center p-4 gap-2 text-slate-500 text-sm">
                    <svg class="animate-spin h-5 w-5 text-gold" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Searching...
                </div>
                <div x-show="!loading && suggestions.length > 0" class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                    <template x-for="item in suggestions" :key="item.id">
                        <a :href="item.url" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition-colors">
                            <div class="w-10 h-10 rounded overflow-hidden bg-slate-100 flex-shrink-0 border border-slate-200/50 flex items-center justify-center">
                                <template x-if="item.image">
                                    <img :src="item.image" alt="Product Image" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!item.image">
                                    <span class="material-symbols-outlined text-slate-400 text-lg">image</span>
                                </template>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-semibold text-sm text-slate-900 truncate" x-text="item.title"></h4>
                                <p class="text-xs text-slate-400 uppercase tracking-wider" x-text="'SKU: ' + item.sku"></p>
                            </div>
                            <div class="text-right">
                                <template x-if="item.base_price !== null">
                                    <span class="font-bold text-sm text-gold" x-text="'₹' + parseFloat(item.base_price).toFixed(2)"></span>
                                </template>
                                <template x-if="item.base_price === null">
                                    <span class="text-[11px] text-slate-400 font-semibold italic">Sign in for price</span>
                                </template>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>

        <!-- Right: Actions & Profile -->
        <div class="flex items-center gap-2 shrink-0">

            <!-- Mobile Search Icon (expands overlay) -->
            <div
                class="md:hidden"
                x-data="{ 
                    open: false, 
                    q: '', 
                    suggestions: [], 
                    loading: false, 
                    showSuggestions: false,
                    go() { 
                        if (this.q.trim()) window.location.href = '{{ $searchRoute }}?search=' + encodeURIComponent(this.q.trim()); 
                    },
                    async fetchSuggestions() {
                        if (this.q.trim().length < 2) {
                            this.suggestions = [];
                            this.showSuggestions = false;
                            return;
                        }
                        this.loading = true;
                        this.showSuggestions = true;
                        try {
                            let res = await fetch('{{ route('customer.products.suggestions') }}?q=' + encodeURIComponent(this.q.trim()));
                            this.suggestions = await res.json();
                        } catch (e) {
                            console.error(e);
                        } finally {
                            this.loading = false;
                        }
                    }
                }"
                @click.away="showSuggestions = false"
            >
                <button @click="open = !open" class="p-2 rounded-full text-slate-600 hover:bg-slate-100 transition-colors">
                    <span class="material-symbols-outlined text-2xl">search</span>
                </button>
                <!-- Mobile search overlay -->
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                    class="absolute top-16 left-0 right-0 bg-white border-b border-slate-200 shadow-lg px-4 py-3 z-50"
                >
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl pointer-events-none">search</span>
                        <input
                            id="mobile-product-search"
                            type="text"
                            x-model="q"
                            @input.debounce.300ms="fetchSuggestions()"
                            @focus="showSuggestions = true; fetchSuggestions()"
                            @keydown.enter="go()"
                            x-ref="mobileSearchInput"
                            x-effect="if (open) $nextTick(() => $refs.mobileSearchInput.focus())"
                            placeholder="Search products…"
                            class="w-full pl-10 pr-16 py-2.5 rounded-lg text-sm bg-slate-100 border-none focus:outline-none focus:ring-2 focus:ring-gold/40 text-slate-900"
                        >
                        <button
                            @click="go()"
                            class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1 rounded-md bg-[#001229] text-white text-xs font-bold hover:bg-slate-800 transition-colors"
                        >Search</button>
                    </div>

                    <!-- Search Suggestions Dropdown (Mobile) -->
                    <div
                        x-show="showSuggestions && (suggestions.length > 0 || loading)"
                        x-transition
                        class="mt-2 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden select-none text-slate-800"
                        style="display: none;"
                    >
                        <div x-show="loading" class="flex items-center justify-center p-4 gap-2 text-slate-500 text-sm">
                            <svg class="animate-spin h-5 w-5 text-gold" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Searching...
                        </div>
                        <div x-show="!loading && suggestions.length > 0" class="divide-y divide-slate-100 max-h-60 overflow-y-auto">
                            <template x-for="item in suggestions" :key="item.id">
                                <a :href="item.url" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition-colors">
                                    <div class="w-10 h-10 rounded overflow-hidden bg-slate-100 flex-shrink-0 border border-slate-200/50 flex items-center justify-center">
                                        <template x-if="item.image">
                                            <img :src="item.image" alt="Product Image" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!item.image">
                                            <span class="material-symbols-outlined text-slate-400 text-lg">image</span>
                                        </template>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-sm text-slate-900 truncate" x-text="item.title"></h4>
                                        <p class="text-xs text-slate-400 uppercase tracking-wider" x-text="'SKU: ' + item.sku"></p>
                                    </div>
                                    <div class="text-right">
                                        <template x-if="item.base_price !== null">
                                            <span class="font-bold text-sm text-gold" x-text="'₹' + parseFloat(item.base_price).toFixed(2)"></span>
                                        </template>
                                        <template x-if="item.base_price === null">
                                            <span class="text-[11px] text-slate-400 font-semibold italic">Sign in for price</span>
                                        </template>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            @auth
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
            @else
                <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg bg-gold hover:bg-gold/90 text-[#001229] font-bold text-sm transition-all select-none">
                    Login
                </a>
            @endauth

        </div>
    </div>
</header>

