<div>
    <!-- Page Title -->
    <x-customer.page-title 
        title="Product Categories" 
        subtitle="Browse our wholesale collections by apparel and department categories."
        :breadcrumbs="['Home' => route('customer.dashboard'), 'Categories' => '#']"
    />

    <!-- Search / Filter Area -->
    <div class="mb-8 max-w-md">
        <div class="relative w-full">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                <span class="material-symbols-outlined text-xl">search</span>
            </span>
            <input type="text" placeholder="Search categories..." class="w-full bg-white text-[#001229] placeholder-slate-400 pl-10 pr-4 py-2.5 rounded-xl text-sm border border-outline-variant/30 focus:outline-none focus:ring-2 focus:ring-gold shadow-ambient">
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <x-customer.category-card 
            name="Men's Wear" 
            image="https://images.unsplash.com/photo-1490114538077-0a7f8cb49891?w=600" 
            count="24" 
            url="{{ route('customer.categories.show', 'mens-wear') }}"
        />
        <x-customer.category-card 
            name="Women's Wear" 
            image="https://images.unsplash.com/photo-1509319117193-57bab727e09d?w=600" 
            count="32" 
            url="{{ route('customer.categories.show', 'womens-wear') }}"
        />
        <x-customer.category-card 
            name="Kids Wear" 
            image="https://images.unsplash.com/photo-1519457431-44ccd64a579b?w=600" 
            count="18" 
            url="{{ route('customer.categories.show', 'kids-wear') }}"
        />
        <x-customer.category-card 
            name="Home Decor" 
            image="https://images.unsplash.com/photo-1513694203232-719a280e022f?w=600" 
            count="12" 
            url="{{ route('customer.categories.show', 'home-decor') }}"
        />
        <x-customer.category-card 
            name="Accessories" 
            image="https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=600" 
            count="15" 
            url="{{ route('customer.categories.show', 'accessories') }}"
        />

        <!-- Wholesale catalog card CTA -->
        <div class="bg-gradient-to-br from-[#0f2744] to-[#001229] rounded-xl border border-slate-800 shadow-ambient p-6 flex flex-col justify-between text-white min-h-[220px]">
            <div>
                <span class="material-symbols-outlined text-gold text-4xl mb-3">download</span>
                <h4 class="text-lg font-bold text-white mb-2">Download Bulk Catalog</h4>
                <p class="text-xs text-slate-300 leading-relaxed">Get our complete offline pricing matrix, product lines, and size guides in one PDF brochure.</p>
            </div>
            
            <a href="#" class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg text-xs font-bold bg-gold text-[#001229] hover:bg-gold/90 transition-colors w-full mt-4">
                Download PDF Matrix
            </a>
        </div>
    </div>
</div>
