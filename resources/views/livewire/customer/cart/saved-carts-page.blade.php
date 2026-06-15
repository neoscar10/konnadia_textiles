<div>
    <!-- Page Header -->
    <x-customer.page-title 
        title="Saved Wholesale Carts" 
        subtitle="Retrieve previously saved product selections to build new wholesale drafts."
        :breadcrumbs="['Home' => route('customer.dashboard'), 'Saved Carts' => '#']"
    />

    <!-- Layout: Left (Saved Carts list), Right (Insights card) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Saved Carts (8 cols) -->
        <div class="lg:col-span-8 space-y-4">
            <!-- Search & Sort headers -->
            <div class="bg-white border border-outline-variant/30 rounded-xl shadow-ambient p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="relative flex-1 max-w-xs">
                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <span class="material-symbols-outlined text-xl">search</span>
                    </span>
                    <input type="text" placeholder="Search saved templates..." class="w-full bg-slate-50 text-[#001229] placeholder-slate-400 pl-10 pr-4 py-2 rounded-lg text-xs border border-outline-variant/20 focus:outline-none focus:ring-2 focus:ring-gold">
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 font-medium">Sort:</span>
                    <select class="bg-slate-50 text-slate-700 text-xs font-bold border border-outline-variant/30 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-gold">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="value_high">Value: High to Low</option>
                    </select>
                </div>
            </div>

            <!-- List of Saved Carts -->
            <div class="space-y-4">
                <!-- Cart Item 1 -->
                <x-customer.card bodyClass="p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2.5">
                            <h4 class="text-sm font-bold text-[#001229]">Monsoon Stock Replenishment</h4>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">Template</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Saved on June 10, 2026 &bull; Last updated by Raj Garments</p>
                        
                        <div class="flex items-center gap-4 mt-3 text-xs text-slate-600 font-semibold">
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-slate-400">category</span> 3 Styles</span>
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-slate-400">widgets</span> 110 Pieces</span>
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-slate-400">payments</span> ₹41,500</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 w-full sm:w-auto pt-3 sm:pt-0 border-t sm:border-none border-slate-50 justify-between sm:justify-end">
                        <button type="button" class="p-2 text-slate-400 hover:text-error hover:bg-rose-50 rounded-lg transition-colors">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                        <button class="inline-flex items-center gap-1 px-3 py-2 text-xs font-bold text-[#001229] border border-outline-variant/40 hover:bg-slate-50 transition-colors rounded-lg">
                            Details
                        </button>
                        <button class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold bg-[#001229] text-white hover:bg-slate-800 transition-colors rounded-lg">
                            <span class="material-symbols-outlined text-xs">restore</span> Load Cart
                        </button>
                    </div>
                </x-customer.card>

                <!-- Cart Item 2 -->
                <x-customer.card bodyClass="p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2.5">
                            <h4 class="text-sm font-bold text-[#001229]">Winter Warmups Bulk Pre-order</h4>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">Template</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Saved on May 28, 2026 &bull; Last updated by Raj Garments</p>
                        
                        <div class="flex items-center gap-4 mt-3 text-xs text-slate-600 font-semibold">
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-slate-400">category</span> 1 Style</span>
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-slate-400">widgets</span> 50 Pieces</span>
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm text-slate-400">payments</span> ₹22,500</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 w-full sm:w-auto pt-3 sm:pt-0 border-t sm:border-none border-slate-50 justify-between sm:justify-end">
                        <button type="button" class="p-2 text-slate-400 hover:text-error hover:bg-rose-50 rounded-lg transition-colors">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                        <button class="inline-flex items-center gap-1 px-3 py-2 text-xs font-bold text-[#001229] border border-outline-variant/40 hover:bg-slate-50 transition-colors rounded-lg">
                            Details
                        </button>
                        <button class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold bg-[#001229] text-white hover:bg-slate-800 transition-colors rounded-lg">
                            <span class="material-symbols-outlined text-xs">restore</span> Load Cart
                        </button>
                    </div>
                </x-customer.card>
            </div>
        </div>

        <!-- Right Side: Cart Insights (4 cols) -->
        <div class="lg:col-span-4">
            <x-customer.card>
                <x-slot name="header">
                    <span class="font-bold text-slate-800 text-sm">Cart Insights</span>
                </x-slot>
                
                <div class="space-y-4">
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="text-[10px] text-slate-400 font-semibold uppercase block">Quick Re-ordering</span>
                        <p class="text-xs text-slate-600 mt-1 leading-relaxed">Saving carts allows you to keep pre-configured order manifests for quick distributor catalog refills throughout the season.</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs font-semibold text-slate-700">
                        <span class="material-symbols-outlined text-gold">bookmark_border</span>
                        <span>Max 10 saved templates allowed</span>
                    </div>
                </div>
            </x-customer.card>
        </div>

    </div>
</div>
