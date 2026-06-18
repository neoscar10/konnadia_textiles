<div>
    <x-slot:title>Dashboard</x-slot:title>

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Dashboard</h1>
            <p class="font-body-md text-on-surface-variant">Business overview and operational insights</p>
        </div>
        <div class="flex gap-md items-center">
            <!-- Date Range Filter -->
            <select wire:model.live="dateRange" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="today">Today</option>
                <option value="7_days">Last 7 Days</option>
                <option value="30_days" selected>Last 30 Days</option>
                <option value="90_days">Last 90 Days</option>
                <option value="this_month">This Month</option>
                <option value="this_year">This Year</option>
                <option value="all_time">All Time</option>
            </select>

            <!-- Refresh Button -->
            <button 
                wire:click="refreshDashboard" 
                {{ $isLoading ? 'disabled' : '' }}
                class="flex items-center gap-xs px-lg py-sm bg-primary text-on-primary rounded-lg font-label-md hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span class="material-symbols-outlined text-[18px]">refresh</span>
                <span class="hidden sm:inline">Refresh</span>
            </button>
        </div>
    </div>

    <!-- KPI Cards Grid -->
    @if(!empty($dashboard['kpis']))
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-gutter mb-xl">
            @foreach($dashboard['kpis'] as $kpi)
                <x-admin.card class="hover:shadow-md transition-shadow cursor-pointer" onclick="window.location.href='{{ $kpi['route'] }}'">
                    <div class="p-lg">
                        <div class="flex items-start justify-between mb-lg">
                            <div>
                                <p class="font-label-md text-on-surface-variant uppercase tracking-wider mb-xs">{{ $kpi['label'] }}</p>
                                <p class="font-headline-md text-primary font-extrabold">{{ $kpi['formatted_value'] }}</p>
                            </div>
                            <span class="material-symbols-outlined text-secondary text-[28px]">{{ $kpi['icon'] }}</span>
                        </div>
                        
                        @if($kpi['status'])
                            <div class="flex items-center gap-xs pt-md border-t border-outline-variant/20">
                                <span class="w-2 h-2 rounded-full {{ 
                                    match($kpi['status']['value']) {
                                        'success' => 'bg-[#0F8A46]',
                                        'warning' => 'bg-warning',
                                        'danger' => 'bg-error',
                                        'info' => 'bg-secondary',
                                        default => 'bg-on-surface-variant'
                                    }
                                }}"></span>
                                <span class="text-xs text-on-surface-variant font-medium">{{ $kpi['status']['label'] }}</span>
                            </div>
                        @endif
                    </div>
                </x-admin.card>
            @endforeach
        </section>
    @endif

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-xl mb-xl">
        <!-- Left Column -->
        <div class="space-y-xl">
            <!-- Pending Approvals -->
            <x-admin.card>
                <x-slot:header class="flex justify-between items-center">
                    <div>
                        <h3 class="font-title-lg text-primary">Pending Approvals</h3>
                        <p class="font-label-md text-on-surface-variant">Orders awaiting action</p>
                    </div>
                    @if($dashboard['pending_approvals']['count'] > 0)
                        <span class="bg-warning/20 text-warning text-[10px] font-bold px-sm py-[2px] rounded-full">{{ $dashboard['pending_approvals']['count'] }} PENDING</span>
                    @endif
                </x-slot:header>

                <x-slot:bodyClass>p-0</x-slot:bodyClass>

                @if(empty($dashboard['pending_approvals']['orders']))
                    <div class="p-xl text-center">
                        <span class="material-symbols-outlined text-outline-variant text-[32px] mb-md">check_circle</span>
                        <p class="font-body-md text-on-surface-variant">No pending approvals</p>
                        <p class="font-label-md text-on-surface-variant/70">All caught up for now.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left font-body-sm">
                            <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider">
                                <tr>
                                    <th class="px-lg py-md whitespace-nowrap">Order #</th>
                                    <th class="px-lg py-md whitespace-nowrap">Customer</th>
                                    <th class="px-lg py-md text-right whitespace-nowrap">Amount</th>
                                    <th class="px-lg py-md text-right whitespace-nowrap">Date</th>
                                    <th class="px-lg py-md text-center whitespace-nowrap">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/10">
                                @foreach($dashboard['pending_approvals']['orders'] as $order)
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-lg py-md font-bold text-primary">{{ $order['order_number'] }}</td>
                                        <td class="px-lg py-md text-on-surface-variant truncate">{{ $order['customer_name'] }}</td>
                                        <td class="px-lg py-md text-right font-semibold">{{ $order['formatted_amount'] }}</td>
                                        <td class="px-lg py-md text-right text-on-surface-variant text-xs">{{ $order['formatted_date'] }}</td>
                                        <td class="px-lg py-md text-center">
                                            <span class="inline-flex items-center px-xs py-[2px] rounded-full text-[10px] font-bold bg-warning/20 text-warning-variant">
                                                {{ ucfirst(str_replace('_', ' ', $order['status'])) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($dashboard['pending_approvals']['count'] > 5)
                        <div class="p-md border-t border-outline-variant/20">
                            <a href="/admin/orders?status=pending" class="text-secondary font-label-md hover:underline">View All Pending Orders →</a>
                        </div>
                    @endif
                @endif
            </x-admin.card>

            <!-- Recent Orders -->
            <x-admin.card>
                <x-slot:header class="flex justify-between items-center">
                    <div>
                        <h3 class="font-title-lg text-primary">Recent Orders</h3>
                        <p class="font-label-md text-on-surface-variant">Latest customer activity</p>
                    </div>
                </x-slot:header>

                <x-slot:bodyClass>p-0</x-slot:bodyClass>

                @if(empty($dashboard['recent_orders']))
                    <div class="p-xl text-center">
                        <span class="material-symbols-outlined text-outline-variant text-[32px] mb-md">shopping_cart</span>
                        <p class="font-body-md text-on-surface-variant">No orders yet</p>
                        <p class="font-label-md text-on-surface-variant/70">Customer orders will appear here once submitted.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left font-body-sm">
                            <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider">
                                <tr>
                                    <th class="px-lg py-md whitespace-nowrap">Order #</th>
                                    <th class="px-lg py-md whitespace-nowrap">Customer</th>
                                    <th class="px-lg py-md text-right whitespace-nowrap">Amount</th>
                                    <th class="px-lg py-md text-center whitespace-nowrap">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/10">
                                @foreach($dashboard['recent_orders'] as $order)
                                    <tr class="hover:bg-surface-container-low transition-colors">
                                        <td class="px-lg py-md font-bold text-primary">
                                            <a href="/admin/orders/{{ $order['order_number'] }}" class="hover:underline">{{ $order['order_number'] }}</a>
                                        </td>
                                        <td class="px-lg py-md text-on-surface-variant truncate">{{ $order['customer_name'] }}</td>
                                        <td class="px-lg py-md text-right font-semibold">{{ $order['formatted_amount'] }}</td>
                                        <td class="px-lg py-md text-center">
                                            <span class="inline-flex items-center px-xs py-[2px] rounded-full text-[10px] font-bold {{ 
                                                match($order['status']) {
                                                    'approved' => 'bg-[#0F8A46]/20 text-[#0F8A46]',
                                                    'submitted', 'under_review', 'pending_approval' => 'bg-warning/20 text-warning-variant',
                                                    'pending_payment_verification' => 'bg-secondary/20 text-secondary',
                                                    'dispatched' => 'bg-secondary-container/20 text-secondary-container',
                                                    'rejected' => 'bg-error/20 text-error',
                                                    default => 'bg-outline-variant/20 text-on-surface-variant'
                                                }
                                            }}">
                                                {{ $order['status_label'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-admin.card>
        </div>

        <!-- Right Column -->
        <div class="space-y-xl">
            <!-- Recent Customers -->
            <x-admin.card>
                <x-slot:header class="flex justify-between items-center">
                    <div>
                        <h3 class="font-title-lg text-primary">Recent Customers</h3>
                        <p class="font-label-md text-on-surface-variant">Newest partners</p>
                    </div>
                    <a href="/admin/customers" class="text-secondary font-label-md hover:underline text-xs">View All →</a>
                </x-slot:header>

                <x-slot:bodyClass>p-0</x-slot:bodyClass>

                @if(empty($dashboard['recent_customers']))
                    <div class="p-xl text-center">
                        <span class="material-symbols-outlined text-outline-variant text-[32px] mb-md">groups</span>
                        <p class="font-body-md text-on-surface-variant">No customers yet</p>
                        <p class="font-label-md text-on-surface-variant/70">New customers will appear here.</p>
                    </div>
                @else
                    <div class="divide-y divide-outline-variant/10">
                        @foreach($dashboard['recent_customers'] as $customer)
                            <div class="p-lg hover:bg-surface-container-low transition-colors cursor-pointer">
                                <div class="flex items-start justify-between mb-xs">
                                    <div class="flex items-center gap-md flex-1 min-w-0">
                                        <div class="w-10 h-10 rounded-lg bg-primary-fixed-dim text-on-primary-fixed font-bold flex items-center justify-center text-sm shrink-0">
                                            {{ $customer['initials'] }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-label-md text-primary font-bold truncate">{{ $customer['company_name'] }}</p>
                                            <p class="text-xs text-on-surface-variant truncate">{{ $customer['customer_number'] }}</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-xs py-[2px] rounded-full text-[10px] font-bold shrink-0 {{ $customer['is_active'] ? 'bg-[#0F8A46]/20 text-[#0F8A46]' : 'bg-outline-variant/20 text-on-surface-variant' }}">
                                        {{ $customer['status'] }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs text-on-surface-variant mt-sm pt-sm border-t border-outline-variant/20">
                                    <span>{{ $customer['level_name'] }}</span>
                                    <a href="/admin/customers" class="text-secondary hover:underline">View →</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>

            <!-- Alerts & Warnings -->
            @if(!empty($dashboard['alerts']))
                <x-admin.card>
                    <x-slot:header class="flex justify-between items-center">
                        <div>
                            <h3 class="font-title-lg text-primary">Alerts & Warnings</h3>
                            <p class="font-label-md text-on-surface-variant">System notifications</p>
                        </div>
                    </x-slot:header>

                    <x-slot:bodyClass>p-lg space-y-md</x-slot:bodyClass>

                    @forelse($dashboard['alerts'] as $alert)
                        <div class="flex gap-md p-md rounded-lg {{ 
                            match($alert['severity']) {
                                'danger' => 'bg-error/10 border border-error/20',
                                'warning' => 'bg-warning/10 border border-warning/20',
                                'info' => 'bg-secondary/10 border border-secondary/20',
                                default => 'bg-surface-container border border-outline-variant/20'
                            }
                        }}">
                            <span class="material-symbols-outlined shrink-0 text-[20px] {{ 
                                match($alert['severity']) {
                                    'danger' => 'text-error',
                                    'warning' => 'text-warning-variant',
                                    'info' => 'text-secondary',
                                    default => 'text-on-surface-variant'
                                }
                            }}">{{ $alert['icon'] }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="font-label-md text-on-surface font-bold">{{ $alert['title'] }}</p>
                                <p class="text-xs text-on-surface-variant mt-xs">{{ $alert['message'] }}</p>
                                <a href="{{ $alert['action_route'] }}" class="text-secondary font-label-md text-xs hover:underline mt-xs inline-block">{{ $alert['action_label'] }} →</a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-lg">
                            <span class="material-symbols-outlined text-outline-variant text-[32px] mb-md">check_circle</span>
                            <p class="font-body-md text-on-surface-variant">No critical alerts</p>
                            <p class="font-label-md text-on-surface-variant/70">Everything looks stable.</p>
                        </div>
                    @endforelse
                </x-admin.card>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    @if(!empty($dashboard['quick_actions']))
        <x-admin.card>
            <x-slot:header>
                <h3 class="font-title-lg text-primary">Quick Actions</h3>
            </x-slot:header>

            <x-slot:bodyClass>p-lg</x-slot:bodyClass>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-md">
                @foreach($dashboard['quick_actions'] as $action)
                    <a href="{{ $action['route'] }}" class="flex flex-col items-center gap-sm p-md rounded-lg bg-surface-container-low hover:bg-surface-container-highest transition-colors text-center">
                        <span class="material-symbols-outlined text-secondary text-[28px]">{{ $action['icon'] }}</span>
                        <span class="font-label-md text-on-surface-variant text-xs">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </x-admin.card>
    @endif

    <!-- Footer Metadata -->
    @if(!empty($dashboard['metadata']))
        <div class="text-center text-xs text-on-surface-variant/70 mt-xl pt-lg border-t border-outline-variant/20">
            <p>Last updated: {{ \Carbon\Carbon::parse($dashboard['metadata']['generated_at'])->format('d M Y, h:i A') }}</p>
        </div>
    @endif
</div>
