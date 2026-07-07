<div class="portal-home-content overflow-x-hidden w-full max-w-full">
    <!-- Welcome section -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        @auth
        <div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">B2B Wholesale Portal</span>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#001229] tracking-tight mt-0.5">Welcome back, {{ $customer['company_name'] ?? 'Valued Customer' }}</h1>
            <p class="text-sm text-slate-500">Manage your wholesale orders and check order statuses.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 text-xs font-bold text-slate-600 bg-white border border-outline-variant/30 px-3 py-1.5 rounded-lg shadow-ambient">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active Account ({{ $customer['level'] ?? 'Standard Partner' }} - {{ $customer['customer_number'] ?? '' }})
            </div>
            <button wire:click="refreshDashboard" class="flex items-center justify-center p-2 rounded-lg bg-white border border-outline-variant/30 text-slate-600 hover:text-[#001229] hover:border-[#001229] shadow-ambient transition-all" title="Refresh Dashboard">
                <span class="material-symbols-outlined text-lg">refresh</span>
            </button>
        </div>
        @else
        <div>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">B2B Wholesale Portal</span>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#001229] tracking-tight mt-0.5">Kannodia Textiles B2B Portal</h1>
            <p class="text-sm text-slate-500">Browse our collections and premium apparel. Sign in to view wholesale prices.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}" class="flex items-center gap-2 text-xs font-bold text-white bg-[#001229] hover:bg-slate-800 border border-transparent px-4 py-2 rounded-lg shadow-ambient transition-all">
                <span class="material-symbols-outlined text-sm">login</span> Sign In to View Prices
            </a>
        </div>
        @endauth
    </div>

    @auth
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

        <!-- Credit Limit summary -->
        <div class="mb-8">
            @if(!empty($credit))
                <div class="bg-gradient-to-br from-[#001229] to-[#0f2744] text-white p-6 rounded-xl border border-slate-800 shadow-ambient flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-xs text-slate-300 font-medium uppercase tracking-wider">Available Credit Limit</p>
                                <h3 class="text-3xl font-extrabold text-gold mt-1">{{ $credit['formatted_available_credit'] }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-gold/80 text-4xl">payments</span>
                        </div>
                        
                        <!-- Details Row -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider">Total Credit Limit</span>
                                <p class="text-sm font-bold text-white mt-0.5">{{ $credit['formatted_credit_limit'] }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider">Outstanding Balance</span>
                                <p class="text-sm font-bold text-white mt-0.5">{{ $credit['formatted_outstanding_amount'] }}</p>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase tracking-wider">Overdue Balance</span>
                                <p class="text-sm font-bold @if($credit['overdue_amount'] > 0) text-rose-400 @else text-slate-300 @endif mt-0.5">{{ $credit['formatted_overdue_amount'] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="space-y-1.5 mt-auto">
                        <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gold rounded-full" style="width: {{ $credit['utilization_percentage'] }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-slate-300 font-medium">
                            <span>Outstanding: {{ $credit['formatted_outstanding_amount'] }}</span>
                            <span>Limit Used: {{ $credit['utilization_percentage'] }}%</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Stats grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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

        <!-- Recent Orders & Cart Insights -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Recent Orders (2 columns) -->
            <div class="lg:col-span-2 space-y-4">
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
                    @foreach($recentOrders as $order)
                        <div class="bg-white border border-outline-variant/20 rounded-xl shadow-ambient overflow-hidden hover:shadow-md transition-shadow">
                            <!-- Header -->
                            <div class="px-5 py-4 border-b border-outline-variant/10 bg-slate-50/50 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-extrabold text-[#001229]">{{ $order['order_number'] }}</span>
                                    <x-customer.badge :status="$order['status']['label']" />
                                </div>
                                <div class="text-xs font-semibold text-slate-500">
                                    Placed on {{ \Carbon\Carbon::parse($order['created_at'])->format('M d, Y') }}
                                </div>
                            </div>

                            <!-- Body -->
                            <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="text-xs text-slate-600">
                                    <p class="font-medium">Items count: <span class="font-bold text-[#001229]">{{ $order['items_count'] }}</span></p>
                                    <p class="mt-1">Payment: <span class="font-bold uppercase text-slate-700">{{ $order['payment_status']['label'] }}</span></p>
                                </div>

                                <!-- Order Total & Actions -->
                                <div class="flex items-center justify-between sm:justify-end gap-6 pt-3 sm:pt-0 border-t sm:border-none border-slate-50">
                                    <div class="text-right">
                                        <span class="text-[10px] text-slate-400 font-semibold uppercase block">Order Total</span>
                                        <span class="text-base font-extrabold text-[#001229]">{{ $order['formatted_total_amount'] }}</span>
                                    </div>
                                    
                                    <a href="{{ route('customer.orders.show', $order['order_number']) }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold text-[#001229] border border-outline-variant/50 hover:bg-slate-50 transition-colors rounded-lg">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Right: Current Cart Summary -->
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
                    
                    @if(empty($cart) || !$cart['exists'] || ($cart['items_count'] ?? 0) == 0)
                        <div class="py-8 text-center text-slate-400 text-xs">
                            <span class="material-symbols-outlined text-3xl mb-1.5 opacity-60">shopping_cart</span>
                            <p>Your wholesale cart is empty.</p>
                        </div>
                    @else
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
    @endauth



    @if(!empty($dynamicSections))
        @foreach($dynamicSections as $section)
            @if($section['type'] === 'banner')
                @if(!empty($section['title']))
                    <div class="mb-4">
                        <h3 class="text-base font-extrabold text-[#001229]">{{ $section['title'] }}</h3>
                        @if(!empty($section['subtitle']))
                            <p class="text-xs text-slate-500 font-semibold">{{ $section['subtitle'] }}</p>
                        @endif
                    </div>
                @endif
                @foreach($section['items'] as $item)
                    @if($item['link']['url'] && empty($item['cta_label']))
                        <a href="{{ $item['link']['url'] }}" target="{{ $item['link']['target'] ?? '_self' }}" class="block mb-8 rounded-2xl overflow-hidden shadow-ambient border border-outline-variant/10 hover:shadow-md transition-all hover:scale-[1.005] duration-300 bg-[#001229] max-w-full relative">
                            <div class="aspect-[16/8] md:aspect-auto md:h-auto w-full flex items-center justify-center">
                                <img src="{{ $item['image_url'] }}" class="w-full h-full md:h-auto object-contain md:object-contain block max-w-full" alt="{{ $item['image_alt'] }}">
                            </div>
                            @if(!empty($item['title']))
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex flex-col justify-end p-6 md:p-8 text-white">
                                    <h4 class="text-lg md:text-xl font-bold text-white">{{ $item['title'] }}</h4>
                                    @if(!empty($item['subtitle']))
                                        <p class="text-xs md:text-sm text-slate-200 mt-1">{{ $item['subtitle'] }}</p>
                                    @endif
                                </div>
                            @endif
                        </a>
                    @else
                        <div class="mb-8 relative w-full rounded-2xl overflow-hidden shadow-ambient border border-outline-variant/10 bg-[#001229] max-w-full">
                            <div class="aspect-[16/8] md:aspect-auto md:h-auto w-full flex items-center justify-center">
                                <img src="{{ $item['image_url'] }}" class="w-full h-full md:h-auto object-contain md:object-contain block max-w-full" alt="{{ $item['image_alt'] }}">
                            </div>
                            @if(!empty($item['title']))
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex flex-col justify-end p-6 md:p-8 text-white">
                                    <h4 class="text-lg md:text-xl font-bold text-white">{{ $item['title'] }}</h4>
                                    @if(!empty($item['subtitle']))
                                        <p class="text-xs md:text-sm text-slate-200 mt-1">{{ $item['subtitle'] }}</p>
                                    @endif
                                </div>
                            @endif
                            @if($item['link']['url'] && !empty($item['cta_label']))
                                <div class="absolute bottom-4 left-4 md:bottom-8 md:left-8 z-10">
                                    <a href="{{ $item['link']['url'] }}" target="{{ $item['link']['target'] ?? '_self' }}"
                                       class="group inline-flex items-center gap-2 px-4 py-2 md:px-5 md:py-2.5 rounded-full bg-gold/90 backdrop-blur-sm text-[#001229] text-[10px] md:text-xs font-black shadow-lg ring-1 ring-white/20 transition-all duration-200 hover:bg-gold hover:shadow-xl hover:scale-[1.04] active:scale-100">
                                        <span>{{ $item['cta_label'] }}</span>
                                        <span class="material-symbols-outlined text-[13px] md:text-[15px] font-black transition-transform duration-200 group-hover:translate-x-0.5">arrow_forward</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            @endif

            <!-- Banner Slider -->
            @if($section['type'] === 'banner_slider')
                <div class="mb-8 w-full rounded-2xl overflow-hidden shadow-ambient border border-outline-variant/10 relative"
                     x-data="{ 
                        activeSlide: 0, 
                        slidesCount: {{ count($section['items']) }},
                        next() { this.activeSlide = (this.activeSlide + 1) % this.slidesCount },
                        prev() { this.activeSlide = (this.activeSlide - 1 + this.slidesCount) % this.slidesCount }
                     }"
                     x-init="setInterval(() => next(), 6000)">
                    
                    <!-- Slides -->
                    <div class="relative w-full overflow-hidden bg-[#001229] max-w-full">
                        @foreach($section['items'] as $index => $item)
                            <div x-show="activeSlide === {{ $index }}" 
                                 x-transition:enter="transition ease-out duration-500"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 class="w-full relative">
                                @if($item['link']['url'] && empty($item['cta_label']))
                                    <a href="{{ $item['link']['url'] }}" target="{{ $item['link']['target'] ?? '_self' }}" class="block">
                                        <div class="aspect-[16/8] md:aspect-auto md:h-auto w-full flex items-center justify-center">
                                            <img src="{{ $item['image_url'] }}" class="w-full h-full md:h-auto object-contain md:object-contain block max-w-full" alt="{{ $item['image_alt'] }}">
                                        </div>
                                    </a>
                                @else
                                    <div class="relative w-full">
                                        <div class="aspect-[16/8] md:aspect-auto md:h-auto w-full flex items-center justify-center">
                                            <img src="{{ $item['image_url'] }}" class="w-full h-full md:h-auto object-contain md:object-contain block max-w-full" alt="{{ $item['image_alt'] }}">
                                        </div>
                                        @if($item['link']['url'] && !empty($item['cta_label']))
                                            <div class="absolute bottom-4 left-4 md:bottom-8 md:left-8 z-10">
                                                <a href="{{ $item['link']['url'] }}" target="{{ $item['link']['target'] ?? '_self' }}"
                                                   class="group inline-flex items-center gap-2 px-4 py-2 md:px-5 md:py-2.5 rounded-full bg-gold/90 backdrop-blur-sm text-[#001229] text-[10px] md:text-xs font-black shadow-lg ring-1 ring-white/20 transition-all duration-200 hover:bg-gold hover:shadow-xl hover:scale-[1.04] active:scale-100">
                                                    <span>{{ $item['cta_label'] }}</span>
                                                    <span class="material-symbols-outlined text-[13px] md:text-[15px] font-black transition-transform duration-200 group-hover:translate-x-0.5">arrow_forward</span>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Navigation Arrows -->
                    @if(count($section['items']) > 1)
                        <button @click="prev()" class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center transition-all focus:outline-none z-20">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </button>
                        <button @click="next()" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center transition-all focus:outline-none z-20">
                            <span class="material-symbols-outlined text-sm">chevron_right</span>
                        </button>

                        <!-- Indicators -->
                        <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1.5 z-20">
                            @foreach($section['items'] as $index => $item)
                                <button @click="activeSlide = {{ $index }}" 
                                        class="w-2 h-2 rounded-full transition-all focus:outline-none"
                                        :class="activeSlide === {{ $index }} ? 'bg-gold w-4' : 'bg-white/40 hover:bg-white/60'"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <!-- Image/Text Card -->
            @if($section['type'] === 'image_text_card')
                <div class="mb-8 w-full bg-white border border-outline-variant/20 rounded-2xl shadow-ambient overflow-hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2">
                        @if(($section['alignment'] ?? 'left') === 'left')
                            <!-- Image left, text right -->
                            <div class="relative w-full h-64 md:h-auto min-h-[250px] bg-slate-50">
                                @if($section['image_url'])
                                    <img src="{{ $section['image_url'] }}" class="absolute inset-0 w-full h-full object-cover" alt="{{ $section['image_alt'] }}">
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center text-slate-400">
                                        <span class="material-symbols-outlined text-4xl">image</span>
                                    </div>
                                @endif
                            </div>
                            <div class="p-8 md:p-12 flex flex-col justify-center">
                                @if($section['title'])
                                    <h3 class="text-xl md:text-2xl font-black text-[#001229] mb-2">{{ $section['title'] }}</h3>
                                @endif
                                @if($section['subtitle'])
                                    <p class="text-xs text-slate-500 font-semibold mb-4">{{ $section['subtitle'] }}</p>
                                @endif
                                <div class="prose max-w-none text-slate-600 text-sm leading-relaxed prose-slate">
                                    {!! \Illuminate\Support\Str::markdown($section['markdown'] ?? '') !!}
                                </div>
                            </div>
                        @else
                            <!-- Text left, image right -->
                            <div class="p-8 md:p-12 flex flex-col justify-center order-2 md:order-1">
                                @if($section['title'])
                                    <h3 class="text-xl md:text-2xl font-black text-[#001229] mb-2">{{ $section['title'] }}</h3>
                                @endif
                                @if($section['subtitle'])
                                    <p class="text-xs text-slate-500 font-semibold mb-4">{{ $section['subtitle'] }}</p>
                                @endif
                                <div class="prose max-w-none text-slate-600 text-sm leading-relaxed prose-slate">
                                    {!! \Illuminate\Support\Str::markdown($section['markdown'] ?? '') !!}
                                </div>
                            </div>
                            <div class="relative w-full h-64 md:h-auto min-h-[250px] bg-slate-50 order-1 md:order-2">
                                @if($section['image_url'])
                                    <img src="{{ $section['image_url'] }}" class="absolute inset-0 w-full h-full object-cover" alt="{{ $section['image_alt'] }}">
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center text-slate-400">
                                        <span class="material-symbols-outlined text-4xl">image</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Category Slider -->
            @if($section['type'] === 'category_slider')
                <div class="mb-8 space-y-4" x-data="{
                    autoTimer: null,
                    paused: false,
                    step: 300,
                    init() {
                        const el = this.$refs.sliderContainer;
                        // Start in the middle clone so both directions are seamless
                        el.scrollLeft = el.scrollWidth / 2;
                        this.startAuto();
                        // Pause auto-scroll while user is touching/dragging
                        el.addEventListener('touchstart', () => { this.paused = true; }, { passive: true });
                        el.addEventListener('touchend',   () => { setTimeout(() => { this.paused = false; this.warpIfNeeded(); }, 800); }, { passive: true });
                    },
                    warpIfNeeded() {
                        const el = this.$refs.sliderContainer;
                        const half = el.scrollWidth / 2;
                        if (el.scrollLeft >= half - 2) {
                            el.style.scrollBehavior = 'auto';
                            el.scrollLeft -= half;
                            void el.offsetWidth; // force reflow
                            el.style.scrollBehavior = '';
                        } else if (el.scrollLeft <= 2) {
                            el.style.scrollBehavior = 'auto';
                            el.scrollLeft += half;
                            void el.offsetWidth;
                            el.style.scrollBehavior = '';
                        }
                    },
                    scrollNext() {
                        const el = this.$refs.sliderContainer;
                        this.warpIfNeeded();
                        el.scrollBy({ left: this.step, behavior: 'smooth' });
                    },
                    scrollPrev() {
                        const el = this.$refs.sliderContainer;
                        this.warpIfNeeded();
                        el.scrollBy({ left: -this.step, behavior: 'smooth' });
                    },
                    startAuto() {
                        this.autoTimer = setInterval(() => {
                            if (!this.paused) this.scrollNext();
                        }, 4000);
                    }
                }" x-init="init()">
                    <div class="flex justify-between items-end">
                        <div class="flex flex-col gap-0.5">
                            <h3 class="text-base font-extrabold text-[#001229]">{{ $section['title'] ?: 'Featured Collection' }}</h3>
                            @if($section['subtitle'])
                                <p class="text-xs text-slate-500 font-semibold">{{ $section['subtitle'] }}</p>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <button @click="scrollPrev()" class="w-8 h-8 rounded-full border border-outline-variant/30 bg-white hover:bg-slate-50 flex items-center justify-center text-[#001229] transition-all shadow-sm">
                                <span class="material-symbols-outlined text-sm font-bold">arrow_back</span>
                            </button>
                            <button @click="scrollNext()" class="w-8 h-8 rounded-full border border-outline-variant/30 bg-white hover:bg-slate-50 flex items-center justify-center text-[#001229] transition-all shadow-sm">
                                <span class="material-symbols-outlined text-sm font-bold">arrow_forward</span>
                            </button>
                        </div>
                    </div>

                    <!-- Horizontal Snap List -->
                    <div x-ref="sliderContainer" class="flex gap-6 overflow-x-auto pb-4" style="-ms-overflow-style: none; scrollbar-width: none; scroll-behavior: smooth;">
                        @foreach($section['items'] as $item)
                            @php $prod = $item['product']; @endphp
                            <div class="flex-shrink-0 w-72">
                                <x-customer.product-card 
                                    :title="$prod['title']" 
                                    :sku="$prod['sku']" 
                                    :price="$prod['price']['customer_price']" 
                                    :moq="$prod['minimum_order_quantity']" 
                                    :image="$prod['primary_image_url']" 
                                    :inStock="$prod['stock']['status'] !== 'out_of_stock'"
                                    :url="$item['link']['url']"
                                    :productId="$prod['id']"
                                />
                            </div>
                        @endforeach
                        <!-- Duplicate items for seamless infinite looping -->
                        @foreach($section['items'] as $item)
                            @php $prod = $item['product']; @endphp
                            <div class="flex-shrink-0 w-72">
                                <x-customer.product-card 
                                    :title="$prod['title']" 
                                    :sku="$prod['sku']" 
                                    :price="$prod['price']['customer_price']" 
                                    :moq="$prod['minimum_order_quantity']" 
                                    :image="$prod['primary_image_url']" 
                                    :inStock="$prod['stock']['status'] !== 'out_of_stock'"
                                    :url="$item['link']['url']"
                                    :productId="$prod['id']"
                                />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Product Slider -->
            @if($section['type'] === 'product_slider')
                <div class="mb-8 space-y-4" x-data="{
                    autoTimer: null,
                    paused: false,
                    step: 300,
                    init() {
                        const el = this.$refs.sliderContainer;
                        el.scrollLeft = el.scrollWidth / 2;
                        this.startAuto();
                        el.addEventListener('touchstart', () => { this.paused = true; }, { passive: true });
                        el.addEventListener('touchend',   () => { setTimeout(() => { this.paused = false; this.warpIfNeeded(); }, 800); }, { passive: true });
                    },
                    warpIfNeeded() {
                        const el = this.$refs.sliderContainer;
                        const half = el.scrollWidth / 2;
                        if (el.scrollLeft >= half - 2) {
                            el.style.scrollBehavior = 'auto';
                            el.scrollLeft -= half;
                            void el.offsetWidth;
                            el.style.scrollBehavior = '';
                        } else if (el.scrollLeft <= 2) {
                            el.style.scrollBehavior = 'auto';
                            el.scrollLeft += half;
                            void el.offsetWidth;
                            el.style.scrollBehavior = '';
                        }
                    },
                    scrollNext() {
                        const el = this.$refs.sliderContainer;
                        this.warpIfNeeded();
                        el.scrollBy({ left: this.step, behavior: 'smooth' });
                    },
                    scrollPrev() {
                        const el = this.$refs.sliderContainer;
                        this.warpIfNeeded();
                        el.scrollBy({ left: -this.step, behavior: 'smooth' });
                    },
                    startAuto() {
                        this.autoTimer = setInterval(() => {
                            if (!this.paused) this.scrollNext();
                        }, 4000);
                    }
                }" x-init="init()">
                    <div class="flex justify-between items-end">
                        <div class="flex flex-col gap-0.5">
                            <h3 class="text-base font-extrabold text-[#001229]">{{ $section['title'] ?: 'Featured Products' }}</h3>
                            @if($section['subtitle'])
                                <p class="text-xs text-slate-500 font-semibold">{{ $section['subtitle'] }}</p>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <button @click="scrollPrev()" class="w-8 h-8 rounded-full border border-outline-variant/30 bg-white hover:bg-slate-50 flex items-center justify-center text-[#001229] transition-all shadow-sm">
                                <span class="material-symbols-outlined text-sm font-bold">arrow_back</span>
                            </button>
                            <button @click="scrollNext()" class="w-8 h-8 rounded-full border border-outline-variant/30 bg-white hover:bg-slate-50 flex items-center justify-center text-[#001229] transition-all shadow-sm">
                                <span class="material-symbols-outlined text-sm font-bold">arrow_forward</span>
                            </button>
                        </div>
                    </div>

                    <!-- Horizontal Snap List -->
                    <div x-ref="sliderContainer" class="flex gap-6 overflow-x-auto pb-4" style="-ms-overflow-style: none; scrollbar-width: none; scroll-behavior: smooth;">
                        @foreach($section['items'] as $item)
                            @php $prod = $item['product']; @endphp
                            <div class="flex-shrink-0 w-72">
                                <x-customer.product-card 
                                    :title="$prod['title']" 
                                    :sku="$prod['sku']" 
                                    :price="$prod['price']['customer_price']" 
                                    :moq="$prod['minimum_order_quantity']" 
                                    :image="$prod['primary_image_url']" 
                                    :inStock="$prod['stock']['status'] !== 'out_of_stock'"
                                    :url="$item['link']['url']"
                                    :productId="$prod['id']"
                                />
                            </div>
                        @endforeach
                        <!-- Duplicate items for seamless infinite looping -->
                        @foreach($section['items'] as $item)
                            @php $prod = $item['product']; @endphp
                            <div class="flex-shrink-0 w-72">
                                <x-customer.product-card 
                                    :title="$prod['title']" 
                                    :sku="$prod['sku']" 
                                    :price="$prod['price']['customer_price']" 
                                    :moq="$prod['minimum_order_quantity']" 
                                    :image="$prod['primary_image_url']" 
                                    :inStock="$prod['stock']['status'] !== 'out_of_stock'"
                                    :url="$item['link']['url']"
                                    :productId="$prod['id']"
                                />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Image Slider -->
            @if($section['type'] === 'image_slider')
                <div class="mb-8 space-y-4" x-data="{
                    autoTimer: null,
                    paused: false,
                    step: 350,
                    init() {
                        const el = this.$refs.sliderContainer;
                        el.scrollLeft = el.scrollWidth / 2;
                        this.startAuto();
                        el.addEventListener('touchstart', () => { this.paused = true; }, { passive: true });
                        el.addEventListener('touchend',   () => { setTimeout(() => { this.paused = false; this.warpIfNeeded(); }, 800); }, { passive: true });
                    },
                    warpIfNeeded() {
                        const el = this.$refs.sliderContainer;
                        const half = el.scrollWidth / 2;
                        if (el.scrollLeft >= half - 2) {
                            el.style.scrollBehavior = 'auto';
                            el.scrollLeft -= half;
                            void el.offsetWidth;
                            el.style.scrollBehavior = '';
                        } else if (el.scrollLeft <= 2) {
                            el.style.scrollBehavior = 'auto';
                            el.scrollLeft += half;
                            void el.offsetWidth;
                            el.style.scrollBehavior = '';
                        }
                    },
                    scrollNext() {
                        const el = this.$refs.sliderContainer;
                        this.warpIfNeeded();
                        el.scrollBy({ left: this.step, behavior: 'smooth' });
                    },
                    scrollPrev() {
                        const el = this.$refs.sliderContainer;
                        this.warpIfNeeded();
                        el.scrollBy({ left: -this.step, behavior: 'smooth' });
                    },
                    startAuto() {
                        this.autoTimer = setInterval(() => {
                            if (!this.paused) this.scrollNext();
                        }, 4000);
                    }
                }" x-init="init()">
                    <div class="flex justify-between items-end">
                        <div class="flex flex-col gap-0.5">
                            <h3 class="text-base font-extrabold text-[#001229]">{{ $section['title'] ?: 'Promotions' }}</h3>
                            @if($section['subtitle'])
                                <p class="text-xs text-slate-500 font-semibold">{{ $section['subtitle'] }}</p>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            <button @click="scrollPrev()" class="w-8 h-8 rounded-full border border-outline-variant/30 bg-white hover:bg-slate-50 flex items-center justify-center text-[#001229] transition-all shadow-sm">
                                <span class="material-symbols-outlined text-sm font-bold">arrow_back</span>
                            </button>
                            <button @click="scrollNext()" class="w-8 h-8 rounded-full border border-outline-variant/30 bg-white hover:bg-slate-50 flex items-center justify-center text-[#001229] transition-all shadow-sm">
                                <span class="material-symbols-outlined text-sm font-bold">arrow_forward</span>
                            </button>
                        </div>
                    </div>

                    <!-- Horizontal Scroll List -->
                    <div x-ref="sliderContainer" class="flex gap-6 overflow-x-auto pb-4" style="-ms-overflow-style: none; scrollbar-width: none; scroll-behavior: smooth;">
                        @foreach($section['items'] as $item)
                            <div class="flex-shrink-0 w-80 md:w-96 relative rounded-2xl overflow-hidden aspect-[16/8] md:aspect-auto md:h-auto min-h-[200px] bg-[#001229] flex items-center justify-start border border-outline-variant/10 shadow-ambient max-w-full">
                                @if($item['image_url'])
                                    <img src="{{ $item['image_url'] }}" class="absolute inset-0 w-full h-full object-contain md:object-cover opacity-60 hover:scale-105 transition-transform duration-700" alt="{{ $item['image_alt'] }}">
                                @endif
                                <div class="relative z-10 text-left space-y-2 text-white p-6 md:p-8 max-w-xs">
                                    <h4 class="text-base font-black tracking-tight leading-tight">{{ $item['title'] }}</h4>
                                    @if($item['subtitle'])
                                        <p class="text-xs text-slate-200 leading-relaxed font-semibold">{{ $item['subtitle'] }}</p>
                                    @endif
                                    @if($item['link']['url'])
                                        <a href="{{ $item['link']['url'] }}" target="{{ $item['link']['target'] ?? '_self' }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gold text-[#001229] text-[10px] font-black transition-all hover:bg-white shadow-sm mt-1">
                                            {{ $item['cta_label'] ?: 'Shop Now' }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        <!-- Duplicate items for seamless infinite looping -->
                        @foreach($section['items'] as $item)
                            <div class="flex-shrink-0 w-80 md:w-96 relative rounded-2xl overflow-hidden aspect-[16/8] md:aspect-auto md:h-auto min-h-[200px] bg-[#001229] flex items-center justify-start border border-outline-variant/10 shadow-ambient max-w-full">
                                @if($item['image_url'])
                                    <img src="{{ $item['image_url'] }}" class="absolute inset-0 w-full h-full object-contain md:object-cover opacity-60 hover:scale-105 transition-transform duration-700" alt="{{ $item['image_alt'] }}">
                                @endif
                                <div class="relative z-10 text-left space-y-2 text-white p-6 md:p-8 max-w-xs">
                                    <h4 class="text-base font-black tracking-tight leading-tight">{{ $item['title'] }}</h4>
                                    @if($item['subtitle'])
                                        <p class="text-xs text-slate-200 leading-relaxed font-semibold">{{ $item['subtitle'] }}</p>
                                    @endif
                                    @if($item['link']['url'])
                                        <a href="{{ $item['link']['url'] }}" target="{{ $item['link']['target'] ?? '_self' }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gold text-[#001229] text-[10px] font-black transition-all hover:bg-white shadow-sm mt-1">
                                            {{ $item['cta_label'] ?: 'Shop Now' }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    @endif

    <!-- Variant Selection Modal Overlay -->
    @if($showQuickAddModal && $quickAddProduct)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-4 z-50">
            <div class="bg-white border border-outline-variant/30 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest select-none">{{ $quickAddProduct->brand }}</span>
                        <h3 class="text-base font-extrabold text-[#001229]">{{ $quickAddProduct->title }}</h3>
                    </div>
                    <button type="button" wire:click="$set('showQuickAddModal', false)" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-6 overflow-y-auto max-h-[70vh]">
                    <!-- Price and stock status -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-outline-variant/10 flex justify-between items-center">
                        <div>
                            <span class="text-[10px] text-slate-400 font-semibold uppercase block select-none">Price per piece</span>
                            <div class="flex items-baseline gap-2">
                                <span class="text-xl font-black text-[#001229]">₹{{ number_format($quickAddPricePerPiece, 2) }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $quickAddStockStatus === 'out_of_stock' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/50' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/50' }}">
                                {{ $quickAddStockLabel }}
                            </span>
                        </div>
                    </div>

                    <!-- Dynamic Variation Selectors -->
                    @foreach($quickAddVariations as $group)
                        <div class="space-y-2">
                            <h4 class="text-xs font-bold text-[#001229] uppercase tracking-wider">Select {{ $group['name'] }}</h4>
                            <div class="flex flex-wrap items-center gap-2">
                                @foreach($group['values'] as $val)
                                    @if($group['display_type'] === 'color')
                                        <button type="button" 
                                                wire:click="selectQuickAddVariationValue('{{ $group['name'] }}', '{{ $val['value'] }}')" 
                                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold border transition-all" 
                                                :class="'{{ $quickAddSelectedValues[$group['name']] ?? '' }}' === '{{ $val['value'] }}' ? 'border-[#001229] ring-2 ring-gold/20' : 'border-outline-variant/30 bg-slate-50/55 hover:border-slate-400'">
                                            <span class="w-4 h-4 rounded-full border border-slate-200" style="background-color: {{ $val['color_hex'] ?? '#ccc' }}"></span>
                                            <span>{{ $val['value'] }}</span>
                                        </button>
                                    @else
                                        <button type="button" 
                                                wire:click="selectQuickAddVariationValue('{{ $group['name'] }}', '{{ $val['value'] }}')" 
                                                class="px-3 py-2 rounded-lg text-xs font-bold border transition-all shadow-sm" 
                                                :class="'{{ $quickAddSelectedValues[$group['name']] ?? '' }}' === '{{ $val['value'] }}' ? 'bg-[#001229] text-white border-[#001229]' : 'bg-white border-outline-variant/40 text-[#001229] hover:border-slate-400'">
                                            {{ $val['value'] }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <!-- Unit Pricing & Conversion Info -->
                    @php
                        $lvl1 = collect($quickAddUnits)->firstWhere('level', 1);
                        $lvl2 = collect($quickAddUnits)->firstWhere('level', 2);
                    @endphp
                    @if($lvl1)
                        <div class="bg-slate-50 border border-outline-variant/10 rounded-xl p-4.5 space-y-2.5">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block select-none">Unit Pricing Details</span>
                            <div class="flex flex-col gap-2 text-xs text-slate-600 font-medium">
                                <div class="flex justify-between items-center">
                                    <span>Base Unit Price ({{ $lvl1['name'] }}):</span>
                                    <span class="font-bold text-slate-900">₹{{ number_format($lvl1['price'], 2) }}</span>
                                </div>
                                @if($quickAddHasLvl2Unit && $lvl2)
                                    <div class="flex justify-between items-center">
                                        <span>Bulk Unit Price ({{ $lvl2['name'] }}):</span>
                                        <span class="font-bold text-slate-900">₹{{ number_format($lvl2['price'], 2) }}</span>
                                    </div>
                                    <div class="text-[11px] text-[#001229] bg-white border border-outline-variant/20 rounded-lg px-3 py-2 font-semibold select-none flex items-center gap-1.5 shadow-sm mt-1">
                                        <span class="material-symbols-outlined text-sm text-gold select-none">info</span>
                                        <span>Relationship: 1 {{ $lvl2['name'] }} = {{ (int)$lvl2['conversion_to_base'] }} {{ $lvl1['name'] }}s</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Quantity Inputs: Dual-unit selection queue --}}
                    <div class="space-y-4">
                        <h5 class="text-sm font-bold text-slate-700">Order Quantity by Unit</h5>

                        @foreach($quickAddUnits as $u)
                            <div class="space-y-1 bg-slate-50 p-2.5 rounded-lg border border-outline-variant/20">
                                <div class="flex justify-between items-center">
                                    <label class="text-xs text-slate-500 font-bold uppercase block">Buy in {{ $u['name'] }}s</label>
                                    @if($u['level'] === 2)
                                        <span class="text-[10px] text-slate-400 block">1 {{ ucfirst($u['name']) }} = {{ (int)$u['conversion_to_base'] }} Pieces</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center justify-between border border-outline-variant/30 rounded-lg bg-white p-1 flex-1">
                                        <button type="button" wire:click="decrementQuickAddUnitQuantity({{ $u['id'] }})" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-all focus:outline-none">
                                            <span class="material-symbols-outlined text-lg">remove</span>
                                        </button>
                                        <input type="number" wire:model.live="quickAddUnitQuantities.{{ $u['id'] }}" min="0" class="w-12 text-center bg-transparent border-none focus:outline-none focus:ring-0 text-base font-extrabold text-[#001229] py-1">
                                        <button type="button" wire:click="incrementQuickAddUnitQuantity({{ $u['id'] }})" class="w-8 h-8 rounded-md flex items-center justify-center text-slate-600 hover:bg-slate-100 transition-all focus:outline-none">
                                            <span class="material-symbols-outlined text-lg">add</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <!-- Queued Items List -->
                        @if(!empty($quickAddQueuedItems))
                            <style>
                                @keyframes bulge-pulse {
                                    0%, 100% { transform: scale(1); }
                                    50% { transform: scale(1.04); }
                                }
                                .animate-bulge {
                                    display: inline-block;
                                    animation: bulge-pulse 2.5s ease-in-out infinite;
                                    transform-origin: left center;
                                }
                            </style>
                            <div class="space-y-2 bg-[#f8fafc] border border-slate-200 rounded-lg p-3">
                                <span class="text-xs font-extrabold text-gold uppercase tracking-wider block border-b border-slate-200 pb-1.5 mb-1.5 select-none animate-bulge">Current Selection:</span>
                                <div class="space-y-1.5">
                                    @foreach($quickAddQueuedItems as $item)
                                        <div class="flex items-center justify-between bg-white border border-outline-variant/20 rounded-md px-2.5 py-1.5 text-xs text-slate-700">
                                            <span class="font-semibold">{{ $item['quantity'] }} {{ $item['unit_name'] }}(s)</span>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[10px] text-slate-400">({{ $item['quantity'] * $item['conversion_to_base'] }} pcs)</span>
                                                <button type="button" wire:click="removeQuickAddUnitFromQueue({{ $item['unit_id'] }})" class="text-rose-500 hover:text-rose-700 focus:outline-none" title="Remove">
                                                    <span class="material-symbols-outlined text-[16px] font-bold">close</span>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-between items-center text-[10px] text-slate-400 font-semibold select-none">
                            @php
                                $totalPiecesDisplay = collect($quickAddQueuedItems)->sum(fn($i) => $i['quantity'] * $i['conversion_to_base']);
                            @endphp
                            <span>Total Selected: {{ $totalPiecesDisplay }} Pieces</span>
                            <span>MOQ: {{ $quickAddMoq }} Pieces</span>
                        </div>
                    </div>

                    {{-- Live MOQ warning if below minimum --}}
                    @if($totalPiecesDisplay > 0 && $totalPiecesDisplay < $quickAddMoq)
                        <div class="flex items-start gap-2 px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 mt-2">
                            <span class="material-symbols-outlined text-sm text-rose-500 select-none mt-0.5">warning</span>
                            <span class="text-xs font-semibold text-rose-700">
                                Minimum order is <strong>{{ $quickAddMoq }} pieces</strong>. Please select more.
                            </span>
                        </div>
                    @endif

                    <!-- Pricing Summary -->
                    <div class="border-t border-slate-100 pt-4 space-y-2">
                        <div class="flex justify-between text-xs text-slate-500 font-medium">
                            <span>Subtotal</span>
                            <span class="font-bold text-slate-800">₹{{ number_format($quickAddSubtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-slate-500 font-medium">
                            <span>GST</span>
                            <span class="font-bold text-slate-800">₹{{ number_format($quickAddGstAmount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-[#001229] font-extrabold pt-2 border-t border-dashed border-slate-200">
                            <span>Estimated Total</span>
                            <span class="text-[#001229]">₹{{ number_format($quickAddTotal, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50">
                    <button type="button" wire:click="$set('showQuickAddModal', false)" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-700 border border-outline-variant/30 hover:bg-slate-50 transition-colors bg-white shadow-xs">Cancel</button>
                    <button type="button" 
                            wire:click="addVariantToCart"
                            @if(!$quickAddIsPurchasable) disabled @endif
                            class="flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-white transition-colors shadow-sm {{ $quickAddIsPurchasable ? 'bg-[#001229] hover:bg-slate-800' : 'bg-slate-300 cursor-not-allowed' }}">
                        <span class="material-symbols-outlined text-sm">shopping_cart</span> Add to Cart
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
