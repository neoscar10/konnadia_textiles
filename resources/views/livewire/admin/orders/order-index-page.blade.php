<div>
    <x-slot:title>Orders</x-slot:title>
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Order Management</h1>
            <p class="font-body-md text-on-surface-variant">Review, approve, and track wholesale and retail orders.</p>
        </div>
        <div class="flex gap-md w-full sm:w-auto">
            <x-admin.button variant="outline" icon="download">Export</x-admin.button>
            <x-admin.button variant="primary" icon="add" x-data @click="$dispatch('open-modal', 'create-order')">Create Order</x-admin.button>
        </div>
    </div>

    <!-- Metrics Bento -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-lg mb-xl">
        <div class="bg-surface-container-lowest p-lg rounded-xl shadow-sm border border-outline-variant/30 flex flex-col gap-sm">
            <span class="text-label-md text-on-surface-variant uppercase">Total Pending Value</span>
            <div class="flex items-baseline justify-between">
                <span class="text-headline-md text-primary">₹18.4L</span>
                <span class="text-[#0F8A46] font-bold text-label-md">↑ 12%</span>
            </div>
            <div class="w-full bg-surface-container h-1 rounded-full overflow-hidden mt-2">
                <div class="bg-primary w-3/4 h-full"></div>
            </div>
        </div>
        
        <div class="bg-surface-container-lowest p-lg rounded-xl shadow-sm border border-outline-variant/30 flex flex-col gap-sm">
            <span class="text-label-md text-on-surface-variant uppercase">Orders Today</span>
            <div class="flex items-baseline justify-between">
                <span class="text-headline-md text-primary">42</span>
                <span class="text-secondary font-bold text-label-md">New</span>
            </div>
            <div class="w-full bg-surface-container h-1 rounded-full overflow-hidden mt-2">
                <div class="bg-secondary w-1/2 h-full"></div>
            </div>
        </div>
        
        <div class="bg-surface-container-lowest p-lg rounded-xl shadow-sm border border-outline-variant/30 flex flex-col gap-sm">
            <span class="text-label-md text-on-surface-variant uppercase">Avg. Processing Time</span>
            <div class="flex items-baseline justify-between">
                <span class="text-headline-md text-primary">2.4 Days</span>
                <span class="text-[#0F8A46] font-bold text-label-md">↓ 0.5d</span>
            </div>
            <div class="w-full bg-surface-container h-1 rounded-full overflow-hidden mt-2">
                <div class="bg-[#0F8A46] w-full h-full"></div>
            </div>
        </div>
        
        <div class="bg-surface-container-lowest p-lg rounded-xl shadow-sm border border-outline-variant/30 relative overflow-hidden group cursor-pointer hover:bg-surface-container-low transition-colors">
            <div class="relative z-10 flex flex-col justify-between h-full">
                <span class="text-label-md text-on-surface-variant uppercase">Quick Statistics</span>
                <button class="text-primary font-bold text-body-md flex items-center gap-xs mt-4">
                    View Full Report <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </div>
            <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:opacity-20 transition-opacity">
                <span class="material-symbols-outlined text-[120px]">analytics</span>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <x-admin.card class="mb-xl">
        <x-slot:bodyClass>p-md flex flex-wrap gap-md items-center justify-between</x-slot:bodyClass>
        
        <div class="flex flex-wrap gap-md items-center w-full lg:w-auto">
            <div class="relative w-full sm:w-64">
                <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant/50">search</span>
                <input type="text" placeholder="Search Order Number, Customer..." class="w-full pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md focus:ring-2 focus:ring-secondary outline-none transition-all">
            </div>
            
            <select class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
            </select>
            
            <input type="date" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
        </div>
    </x-admin.card>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="overflow-x-auto">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr>
                        <th class="px-lg py-md">Order No.</th>
                        <th class="px-lg py-md">Date</th>
                        <th class="px-lg py-md">Customer</th>
                        <th class="px-lg py-md">Level</th>
                        <th class="px-lg py-md text-right">Value (₹)</th>
                        <th class="px-lg py-md text-center">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    <tr class="hover:bg-primary/[0.02] transition-colors group cursor-pointer" onclick="window.location='{{ route('admin.orders.show') }}'">
                        <td class="px-lg py-md font-bold text-primary">KT-ORD-100248</td>
                        <td class="px-lg py-md text-on-surface-variant">07-Jun-2026</td>
                        <td class="px-lg py-md font-semibold text-on-surface">City Apparel Co.</td>
                        <td class="px-lg py-md text-on-surface-variant">Retailer</td>
                        <td class="px-lg py-md text-right font-medium">42,000</td>
                        <td class="px-lg py-md text-center"><x-admin.badge type="warning">Under Review</x-admin.badge></td>
                        <td class="px-lg py-md text-right">
    <x-admin.action-menu>
        <x-admin.action-menu-item icon="visibility" label="View Details" />
    </x-admin.action-menu>
</td>
                    </tr>
                    <tr class="hover:bg-primary/[0.02] transition-colors group cursor-pointer" onclick="window.location='{{ route('admin.orders.show') }}'">
                        <td class="px-lg py-md font-bold text-primary">KT-ORD-100247</td>
                        <td class="px-lg py-md text-on-surface-variant">07-Jun-2026</td>
                        <td class="px-lg py-md font-semibold text-on-surface">Northern Fabrics</td>
                        <td class="px-lg py-md text-on-surface-variant">Wholesale</td>
                        <td class="px-lg py-md text-right font-medium">1,25,000</td>
                        <td class="px-lg py-md text-center"><x-admin.badge type="success">Approved</x-admin.badge></td>
                        <td class="px-lg py-md text-right">
    <x-admin.action-menu>
        <x-admin.action-menu-item icon="visibility" label="View Details" />
    </x-admin.action-menu>
</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <x-slot:footer class="flex justify-between items-center">
            <span class="font-label-md text-on-surface-variant">Showing 1 to 2 of 120 orders</span>
            <div class="flex items-center gap-xs">
                <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-outline-variant text-outline hover:bg-surface-container transition-colors disabled:opacity-50" disabled>
                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                </button>
                <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-on-primary font-label-md shadow-sm">1</button>
                <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors">
                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                </button>
            </div>
        </x-slot:footer>
    </x-admin.card>
</div>
