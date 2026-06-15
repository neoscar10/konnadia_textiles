<div>
    <!-- Page Title -->
    <x-customer.page-title 
        title="My Wholesale Orders" 
        subtitle="Track order progress, view invoices, or re-order items."
        :breadcrumbs="['Home' => route('customer.dashboard'), 'Orders' => '#']"
    />

    <!-- Stat cards (Desktop only) -->
    <div class="hidden md:grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-customer.stat-card title="Total Active Orders" value="12" icon="receipt_long" />
        <x-customer.stat-card title="Pending Review" value="3" icon="pending_actions" />
        <x-customer.stat-card title="Total Year Spend" value="₹14,28,450" icon="payments" />
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white border border-outline-variant/30 rounded-xl shadow-ambient p-4 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Status Filter Chips -->
        <div class="flex items-center gap-2 overflow-x-auto hide-scrollbar py-1">
            <button class="bg-[#001229] text-white px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap">All</button>
            <button class="bg-slate-50 border border-outline-variant/20 text-slate-600 px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap hover:bg-slate-100">Draft</button>
            <button class="bg-slate-50 border border-outline-variant/20 text-slate-600 px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap hover:bg-slate-100">Submitted</button>
            <button class="bg-slate-50 border border-outline-variant/20 text-slate-600 px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap hover:bg-slate-100">Under Review</button>
            <button class="bg-slate-50 border border-outline-variant/20 text-slate-600 px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap hover:bg-slate-100">Approved</button>
            <button class="bg-slate-50 border border-outline-variant/20 text-slate-600 px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap hover:bg-slate-100">Dispatched</button>
        </div>

        <!-- Search Input -->
        <div class="relative w-full md:w-64">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                <span class="material-symbols-outlined text-xl">search</span>
            </span>
            <input type="text" placeholder="Search orders by ID..." class="w-full bg-slate-50 text-[#001229] placeholder-slate-400 pl-10 pr-4 py-2 rounded-lg text-xs border border-outline-variant/20 focus:outline-none focus:ring-2 focus:ring-gold">
        </div>
    </div>

    <!-- Mobile view: List of Cards -->
    <div class="md:hidden space-y-4">
        <x-customer.order-card 
            orderId="KT-ORD-100245" 
            status="under review" 
            date="June 12, 2026" 
            total="58800" 
            itemsCount="3"
            :images="['https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=120', 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=120', 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=120']"
        />
        <x-customer.order-card 
            orderId="KT-ORD-100239" 
            status="approved" 
            date="June 08, 2026" 
            total="124500" 
            itemsCount="5"
            :images="['https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=120', 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=120', 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=120']"
        />
        <x-customer.order-card 
            orderId="KT-ORD-100222" 
            status="dispatched" 
            date="May 28, 2026" 
            total="32400" 
            itemsCount="2"
            :images="['https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=120', 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=120']"
        />
        <x-customer.order-card 
            orderId="KT-ORD-100210" 
            status="rejected" 
            date="May 15, 2026" 
            total="18900" 
            itemsCount="1"
            :images="['https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=120']"
        />
    </div>

    <!-- Desktop view: Table layout -->
    <div class="hidden md:block bg-white border border-outline-variant/30 rounded-xl shadow-ambient overflow-hidden">
        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="bg-slate-50 text-slate-500 font-bold uppercase border-b border-outline-variant/15 text-[10px] tracking-wider">
                    <th class="px-6 py-4">Order ID</th>
                    <th class="px-6 py-4">Placed Date</th>
                    <th class="px-6 py-4">Products</th>
                    <th class="px-6 py-4">Est. Total Amount</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
                <tr class="hover:bg-slate-50/50">
                    <td class="px-6 py-4 font-bold text-[#001229]">#KT-ORD-100245</td>
                    <td class="px-6 py-4">June 12, 2026</td>
                    <td class="px-6 py-4">3 Styles &bull; 110 units</td>
                    <td class="px-6 py-4 font-bold">₹46,480</td>
                    <td class="px-6 py-4">
                        <x-customer.badge status="under review" />
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('customer.orders.show', 'KT-ORD-100245') }}" class="inline-flex items-center justify-center px-3 py-1.5 text-[11px] font-bold text-[#001229] border border-outline-variant/40 hover:bg-white rounded-lg transition-colors bg-slate-50 shadow-sm">
                            View Details
                        </a>
                    </td>
                </tr>
                <tr class="hover:bg-slate-50/50">
                    <td class="px-6 py-4 font-bold text-[#001229]">#KT-ORD-100239</td>
                    <td class="px-6 py-4">June 08, 2026</td>
                    <td class="px-6 py-4">5 Styles &bull; 240 units</td>
                    <td class="px-6 py-4 font-bold">₹1,24,500</td>
                    <td class="px-6 py-4">
                        <x-customer.badge status="approved" />
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('customer.orders.show', 'KT-ORD-100239') }}" class="inline-flex items-center justify-center px-3 py-1.5 text-[11px] font-bold text-[#001229] border border-outline-variant/40 hover:bg-white rounded-lg transition-colors bg-slate-50 shadow-sm">
                            View Details
                        </a>
                    </td>
                </tr>
                <tr class="hover:bg-slate-50/50">
                    <td class="px-6 py-4 font-bold text-[#001229]">#KT-ORD-100222</td>
                    <td class="px-6 py-4">May 28, 2026</td>
                    <td class="px-6 py-4">2 Styles &bull; 60 units</td>
                    <td class="px-6 py-4 font-bold">₹32,400</td>
                    <td class="px-6 py-4">
                        <x-customer.badge status="dispatched" />
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('customer.orders.show', 'KT-ORD-100222') }}" class="inline-flex items-center justify-center px-3 py-1.5 text-[11px] font-bold text-[#001229] border border-outline-variant/40 hover:bg-white rounded-lg transition-colors bg-slate-50 shadow-sm">
                            View Details
                        </a>
                    </td>
                </tr>
                <tr class="hover:bg-slate-50/50">
                    <td class="px-6 py-4 font-bold text-[#001229]">#KT-ORD-100210</td>
                    <td class="px-6 py-4">May 15, 2026</td>
                    <td class="px-6 py-4">1 Style &bull; 40 units</td>
                    <td class="px-6 py-4 font-bold">₹18,900</td>
                    <td class="px-6 py-4">
                        <x-customer.badge status="rejected" />
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('customer.orders.show', 'KT-ORD-100210') }}" class="inline-flex items-center justify-center px-3 py-1.5 text-[11px] font-bold text-[#001229] border border-outline-variant/40 hover:bg-white rounded-lg transition-colors bg-slate-50 shadow-sm">
                            View Details
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- Pagination footer -->
        <div class="px-6 py-4 bg-slate-50 border-t border-outline-variant/10 flex items-center justify-between text-xs text-slate-500 font-medium">
            <span>Showing 1 to 4 of 12 orders</span>
            <div class="flex items-center gap-1.5">
                <button disabled class="w-7 h-7 rounded border border-outline-variant/30 flex items-center justify-center bg-white text-slate-400 cursor-not-allowed">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                <button class="w-7 h-7 rounded bg-[#001229] text-white font-bold flex items-center justify-center">1</button>
                <button class="w-7 h-7 rounded border border-outline-variant/30 flex items-center justify-center bg-white hover:bg-slate-50 text-slate-600">2</button>
                <button class="w-7 h-7 rounded border border-outline-variant/30 flex items-center justify-center bg-white hover:bg-slate-50 text-slate-600">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
</div>
