<div>
    <!-- Page Header & Breadcrumbs -->
    <x-customer.page-title 
        title="{{ $categoryName }}" 
        subtitle="Explore sub-categories, specific styles, and bulk inventory groups."
        :breadcrumbs="[
            'Home' => route('customer.dashboard'), 
            'Categories' => route('customer.categories.index'), 
            $categoryName => '#'
        ]"
    />

    <!-- Search input -->
    <div class="mb-8 max-w-md">
        <div class="relative w-full">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                <span class="material-symbols-outlined text-xl">search</span>
            </span>
            <input type="text" placeholder="Search in {{ $categoryName }}..." class="w-full bg-white text-[#001229] placeholder-slate-400 pl-10 pr-4 py-2.5 rounded-xl text-sm border border-outline-variant/30 focus:outline-none focus:ring-2 focus:ring-gold shadow-ambient">
        </div>
    </div>

    <!-- Layout: Left (Sub-categories list), Right (Promo/Trust cards) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sub-categories Grid (2 columns) -->
        <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Shirts -->
            <div class="bg-white border border-outline-variant/20 rounded-xl overflow-hidden shadow-ambient hover:shadow-md transition-shadow flex flex-col justify-between group">
                <div class="relative aspect-video bg-slate-100">
                    <img src="https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=500" alt="Shirts" class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-300">
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <h4 class="text-base font-bold text-[#001229] mb-1">Shirts &amp; Formals</h4>
                        <p class="text-xs text-slate-500 leading-relaxed">Cotton shirts, linens, business formals, and premium checks. Fabric counts from 40s to 80s.</p>
                    </div>
                    <a href="{{ route('customer.products.index') }}?category=shirts" class="mt-4 flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-[#001229] border border-outline-variant/30 hover:bg-slate-50 transition-colors">
                        View Products <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </div>

            <!-- T-Shirts -->
            <div class="bg-white border border-outline-variant/20 rounded-xl overflow-hidden shadow-ambient hover:shadow-md transition-shadow flex flex-col justify-between group">
                <div class="relative aspect-video bg-slate-100">
                    <img src="https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=500" alt="T-Shirts" class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-300">
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <h4 class="text-base font-bold text-[#001229] mb-1">T-Shirts &amp; Polos</h4>
                        <p class="text-xs text-slate-500 leading-relaxed">Round neck tees, premium cotton polos, active wear, and customizable event styles.</p>
                    </div>
                    <a href="{{ route('customer.products.index') }}?category=tshirts" class="mt-4 flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-[#001229] border border-outline-variant/30 hover:bg-slate-50 transition-colors">
                        View Products <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </div>

            <!-- Jeans -->
            <div class="bg-white border border-outline-variant/20 rounded-xl overflow-hidden shadow-ambient hover:shadow-md transition-shadow flex flex-col justify-between group">
                <div class="relative aspect-video bg-slate-100">
                    <img src="https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=500" alt="Jeans" class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-300">
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <h4 class="text-base font-bold text-[#001229] mb-1">Jeans &amp; Denims</h4>
                        <p class="text-xs text-slate-500 leading-relaxed">Heavyweight raw denim, stretch fits, chinos, and rugged wholesale work pants.</p>
                    </div>
                    <a href="{{ route('customer.products.index') }}?category=jeans" class="mt-4 flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-[#001229] border border-outline-variant/30 hover:bg-slate-50 transition-colors">
                        View Products <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </div>

            <!-- Jackets -->
            <div class="bg-white border border-outline-variant/20 rounded-xl overflow-hidden shadow-ambient hover:shadow-md transition-shadow flex flex-col justify-between group">
                <div class="relative aspect-video bg-slate-100">
                    <img src="https://images.unsplash.com/photo-1551028719-00167b16eac5?w=500" alt="Jackets" class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-300">
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <h4 class="text-base font-bold text-[#001229] mb-1">Jackets &amp; Outerwear</h4>
                        <p class="text-xs text-slate-500 leading-relaxed">Windbreakers, leather replicas, winter jackets, hoodies, and fleece sweatshirts.</p>
                    </div>
                    <a href="{{ route('customer.products.index') }}?category=jackets" class="mt-4 flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-[#001229] border border-outline-variant/30 hover:bg-slate-50 transition-colors">
                        View Products <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Right Side sidebar info -->
        <div class="space-y-6">
            <!-- Wholesale Only Callout -->
            <div class="bg-gradient-to-br from-[#001229] to-[#0f2744] text-white p-6 rounded-xl border border-slate-800 shadow-ambient">
                <span class="material-symbols-outlined text-gold text-3xl mb-2">lock</span>
                <h4 class="text-sm font-bold text-white mb-2">Wholesale Restrictions</h4>
                <p class="text-xs text-slate-300 leading-relaxed mb-4">
                    All collections in this department are strictly restricted to registered distributors. MOQs apply to each style colorway.
                </p>
                <div class="flex items-center gap-2 text-xs font-bold text-gold">
                    <span class="material-symbols-outlined text-sm">check_circle</span> Minimum order value: ₹25,000
                </div>
            </div>

            <!-- In-Stock Assurance -->
            <x-customer.card>
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-emerald-600">verified_user</span>
                    <h4 class="text-sm font-bold text-slate-800">In-Stock Assurance</h4>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">
                    All items flagged as "In Stock" are reserved physically in our Surat warehouse. Realtime API sync keeps stock levels accurate.
                </p>
            </x-customer.card>
        </div>
    </div>
</div>
