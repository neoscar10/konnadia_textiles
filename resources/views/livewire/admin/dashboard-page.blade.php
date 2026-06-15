<div>
    <x-slot:title>Dashboard</x-slot:title>
    <div class="space-y-xl">
        <!-- Summary Cards Section -->
        <section class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-gutter">
            <x-admin.stat-card title="Total Customers" value="12,482" icon="group" trend="↑ 12%" />
            <x-admin.stat-card title="Total Products" value="1,845" icon="inventory_2" trend="↑ 5%" />
            <x-admin.stat-card title="Pending Orders" value="48" icon="pending_actions" trend="↓ 2%" trendType="down" />
            <x-admin.stat-card title="Approved Orders" value="142" icon="check_circle" trend="↑ 8%" />
            <x-admin.stat-card title="Credit Exposure" value="\$1.2M" icon="account_balance_wallet" trend="Stable" trendType="neutral" />
            <x-admin.stat-card title="Low Stock Items" value="23" icon="warning" trend="Action Needed" trendType="down" />
        </section>

        <!-- Main Tables Layout: Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-xl">
            <!-- Pending Approval Table -->
            <x-admin.card>
                <x-slot:header class="flex justify-between items-center">
                    <div>
                        <h3 class="font-title-lg text-primary">Pending Approvals</h3>
                        <p class="font-label-md text-on-surface-variant">Requires manager authorization</p>
                    </div>
                    <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-sm py-[2px] rounded-full">48 ACTIONABLE</span>
                </x-slot:header>

                <x-slot:bodyClass>p-0</x-slot:bodyClass>

                <div class="overflow-x-auto">
                    <table class="w-full text-left font-body-md">
                        <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider">
                            <tr>
                                <th class="px-lg py-md">Order #</th>
                                <th class="px-lg py-md">Customer</th>
                                <th class="px-lg py-md">Value</th>
                                <th class="px-lg py-md text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="px-lg py-md font-bold text-primary">#ORD-9021</td>
                                <td class="px-lg py-md">Golden Threads Wholesalers</td>
                                <td class="px-lg py-md">$14,200.00</td>
                                <td class="px-lg py-md text-right ">
    <x-admin.action-menu>
        <x-admin.action-menu-item icon="check_circle" label="Approve" />
        <x-admin.action-menu-item icon="cancel" label="Reject" danger="true" />
    </x-admin.action-menu>
</td>
                            </tr>
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="px-lg py-md font-bold text-primary">#ORD-9018</td>
                                <td class="px-lg py-md">Urban Weaves Retail</td>
                                <td class="px-lg py-md">$8,450.00</td>
                                <td class="px-lg py-md text-right ">
    <x-admin.action-menu>
        <x-admin.action-menu-item icon="check_circle" label="Approve" />
        <x-admin.action-menu-item icon="cancel" label="Reject" danger="true" />
    </x-admin.action-menu>
</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-admin.card>

            <!-- Recent Customers Table -->
            <x-admin.card>
                <x-slot:header class="flex justify-between items-center">
                    <div>
                        <h3 class="font-title-lg text-primary">Recent Customers</h3>
                        <p class="font-label-md text-on-surface-variant">Newly registered partners</p>
                    </div>
                    <a href="{{ route('admin.customers.index') }}" class="text-secondary font-button text-sm hover:underline">View Directory</a>
                </x-slot:header>

                <x-slot:bodyClass>p-0</x-slot:bodyClass>

                <div class="overflow-x-auto">
                    <table class="w-full text-left font-body-md">
                        <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider">
                            <tr>
                                <th class="px-lg py-md">Name</th>
                                <th class="px-lg py-md">Type</th>
                                <th class="px-lg py-md">Status</th>
                                <th class="px-lg py-md text-right">View</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="px-lg py-md">
                                    <div class="flex items-center gap-md">
                                        <div class="w-8 h-8 rounded bg-primary-fixed-dim text-on-primary-fixed font-bold flex items-center justify-center text-xs">SV</div>
                                        Silk Valley Co.
                                    </div>
                                </td>
                                <td class="px-lg py-md">Wholesale</td>
                                <td class="px-lg py-md"><x-admin.badge type="success">Active</x-admin.badge></td>
                                <td class="px-lg py-md text-right">
    <x-admin.action-menu>
        <x-admin.action-menu-item icon="visibility" label="View Details" />
    </x-admin.action-menu>
</td>
                            </tr>
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="px-lg py-md">
                                    <div class="flex items-center gap-md">
                                        <div class="w-8 h-8 rounded bg-secondary-fixed text-on-secondary-fixed font-bold flex items-center justify-center text-xs">EL</div>
                                        Elite Linens
                                    </div>
                                </td>
                                <td class="px-lg py-md">Platinum</td>
                                <td class="px-lg py-md"><x-admin.badge type="warning">Pending</x-admin.badge></td>
                                <td class="px-lg py-md text-right">
    <x-admin.action-menu>
        <x-admin.action-menu-item icon="visibility" label="View Details" />
    </x-admin.action-menu>
</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-admin.card>

            <!-- Recent Orders Table (Full Width Span) -->
            <x-admin.card class="xl:col-span-2">
                <x-slot:header class="flex justify-between items-center">
                    <div>
                        <h3 class="font-title-lg text-primary">Recent Orders Overview</h3>
                        <p class="font-label-md text-on-surface-variant">Tracking latest logistical movements</p>
                    </div>
                    <div class="flex gap-md">
                        <x-admin.button variant="outline" icon="filter_list">Filter</x-admin.button>
                        <x-admin.button variant="primary" icon="download">Export</x-admin.button>
                    </div>
                </x-slot:header>

                <x-slot:bodyClass>p-0</x-slot:bodyClass>

                <div class="overflow-x-auto">
                    <table class="w-full text-left font-body-md border-collapse">
                        <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider">
                            <tr>
                                <th class="px-lg py-md border-b border-outline-variant/10">Order ID</th>
                                <th class="px-lg py-md border-b border-outline-variant/10">Customer Name</th>
                                <th class="px-lg py-md border-b border-outline-variant/10">Date</th>
                                <th class="px-lg py-md border-b border-outline-variant/10">Value</th>
                                <th class="px-lg py-md border-b border-outline-variant/10">Status</th>
                                <th class="px-lg py-md border-b border-outline-variant/10 text-right">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="px-lg py-md font-bold text-primary">#ORD-9020</td>
                                <td class="px-lg py-md">Weave Masters Inc.</td>
                                <td class="px-lg py-md text-on-surface-variant">Oct 24, 2024</td>
                                <td class="px-lg py-md">$21,500.00</td>
                                <td class="px-lg py-md"><x-admin.badge type="info">Processing</x-admin.badge></td>
                                <td class="px-lg py-md text-right">
    <x-admin.action-menu>
        <x-admin.action-menu-item icon="visibility" label="View Details" />
    </x-admin.action-menu>
</td>
                            </tr>
                            <tr class="hover:bg-surface-container-low transition-colors">
                                <td class="px-lg py-md font-bold text-primary">#ORD-9019</td>
                                <td class="px-lg py-md">Global Fabrics Ltd.</td>
                                <td class="px-lg py-md text-on-surface-variant">Oct 23, 2024</td>
                                <td class="px-lg py-md">$5,200.00</td>
                                <td class="px-lg py-md"><x-admin.badge type="success">Shipped</x-admin.badge></td>
                                <td class="px-lg py-md text-right">
    <x-admin.action-menu>
        <x-admin.action-menu-item icon="visibility" label="View Details" />
    </x-admin.action-menu>
</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-admin.card>
        </div>
    </div>
</div>
