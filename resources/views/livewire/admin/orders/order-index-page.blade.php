<div>
    <x-slot:title>Orders</x-slot:title>
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight font-extrabold">Order Management</h1>
            <p class="font-body-md text-on-surface-variant">Review, approve, and track wholesale and retail orders through their lifecycle.</p>
        </div>
        <div class="flex gap-md w-full sm:w-auto">
            <x-admin.button variant="outline" icon="download" wire:click="$dispatch('export-orders')" class="shadow-sm hover:shadow transition-all">Export</x-admin.button>
        </div>
    </div>

    <!-- Premium Metrics Bento -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-lg mb-xl">
        <!-- Metric: Total Active Value -->
        <div class="bg-surface-container-lowest p-lg rounded-2xl shadow-sm border border-outline-variant/30 hover:border-primary/20 hover:shadow-md transition-all duration-300 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-lg opacity-10 group-hover:scale-110 transition-transform duration-300">
                <span class="material-symbols-outlined text-6xl text-primary">payments</span>
            </div>
            <div class="flex flex-col gap-sm">
                <span class="text-label-sm font-bold text-on-surface-variant tracking-wider uppercase">Total Active Value</span>
                <span class="text-headline-md font-extrabold text-primary tracking-tight">{{ $stats['formatted_total_value'] }}</span>
            </div>
            <div class="mt-4">
                <div class="w-full bg-surface-container/50 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-primary w-3/4 h-full rounded-full transition-all duration-500"></div>
                </div>
            </div>
        </div>
        
        <!-- Metric: Total Orders -->
        <div class="bg-surface-container-lowest p-lg rounded-2xl shadow-sm border border-outline-variant/30 hover:border-secondary/20 hover:shadow-md transition-all duration-300 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-lg opacity-10 group-hover:scale-110 transition-transform duration-300">
                <span class="material-symbols-outlined text-6xl text-secondary">shopping_bag</span>
            </div>
            <div class="flex flex-col gap-sm">
                <span class="text-label-sm font-bold text-on-surface-variant tracking-wider uppercase">Total Orders</span>
                <span class="text-headline-md font-extrabold text-primary tracking-tight">{{ $stats['total_orders'] }}</span>
            </div>
            <div class="mt-4">
                <div class="w-full bg-surface-container/50 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-secondary w-1/2 h-full rounded-full transition-all duration-500"></div>
                </div>
            </div>
        </div>
        
        <!-- Metric: Pending Review -->
        <div class="bg-surface-container-lowest p-lg rounded-2xl shadow-sm border border-outline-variant/30 hover:border-warning/20 hover:shadow-md transition-all duration-300 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-lg opacity-10 group-hover:scale-110 transition-transform duration-300">
                <span class="material-symbols-outlined text-6xl text-warning">rate_review</span>
            </div>
            <div class="flex flex-col gap-sm">
                <span class="text-label-sm font-bold text-on-surface-variant tracking-wider uppercase">Pending Review</span>
                <span class="text-headline-md font-extrabold text-primary tracking-tight">{{ $stats['pending_review'] }}</span>
            </div>
            <div class="mt-4">
                <div class="w-full bg-surface-container/50 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-warning w-2/3 h-full rounded-full transition-all duration-500"></div>
                </div>
            </div>
        </div>
        
        <!-- Metric: Pending Receipt -->
        <div class="bg-surface-container-lowest p-lg rounded-2xl shadow-sm border border-outline-variant/30 hover:border-error/20 hover:shadow-md transition-all duration-300 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-lg opacity-10 group-hover:scale-110 transition-transform duration-300">
                <span class="material-symbols-outlined text-6xl text-error">receipt</span>
            </div>
            <div class="flex flex-col gap-sm">
                <span class="text-label-sm font-bold text-on-surface-variant tracking-wider uppercase">Pending Receipt</span>
                <span class="text-headline-md font-extrabold text-primary tracking-tight">{{ $stats['pending_payment_verification'] }}</span>
            </div>
            <div class="mt-4">
                <div class="w-full bg-surface-container/50 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-error w-1/4 h-full rounded-full transition-all duration-500"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <x-admin.card class="mb-xl shadow-sm border border-outline-variant/20">
        <x-slot:bodyClass>p-lg</x-slot:bodyClass>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md items-end">
            <!-- Search field -->
            <div class="flex flex-col gap-xs">
                <label class="text-label-sm text-on-surface-variant font-bold uppercase tracking-wider">Search</label>
                <div class="flex items-center gap-sm px-md py-xs bg-surface-container-low border border-outline-variant/50 rounded-lg focus-within:ring-2 focus-within:ring-secondary focus-within:border-secondary transition-all">
                    <span class="material-symbols-outlined text-on-surface-variant/70 text-[20px] select-none shrink-0">search</span>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Order No, Customer..." class="w-full bg-transparent border-none p-0 font-body-md text-on-surface placeholder:text-on-surface-variant/50 focus:ring-0 outline-none h-8">
                </div>
            </div>
            
            <!-- Status filter -->
            <div class="flex flex-col gap-xs">
                <label class="text-label-sm text-on-surface-variant font-bold uppercase tracking-wider">Status</label>
                <select wire:model.live="status" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                    <option value="all">All Statuses</option>
                    <option value="submitted">Submitted</option>
                    <option value="under_review">Under Review</option>
                    <option value="pending_payment_verification">Pending Payment Verification</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="dispatched">Dispatched</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <!-- Billing Method filter -->
            <div class="flex flex-col gap-xs">
                <label class="text-label-sm text-on-surface-variant font-bold uppercase tracking-wider">Billing Method</label>
                <select wire:model.live="checkout_method" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                    <option value="all">All Billing Methods</option>
                    <option value="manual_payment">Manual Payment</option>
                    <option value="credit">Credit Purchase</option>
                </select>
            </div>

            <!-- Payment Status filter -->
            <div class="flex flex-col gap-xs">
                <label class="text-label-sm text-on-surface-variant font-bold uppercase tracking-wider">Payment Status</label>
                <select wire:model.live="payment_status" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                    <option value="all">All Payment Statuses</option>
                    <option value="not_required">Not Required</option>
                    <option value="pending_verification">Pending Verification</option>
                    <option value="verified">Verified</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <!-- Credit Status filter -->
            <div class="flex flex-col gap-xs">
                <label class="text-label-sm text-on-surface-variant font-bold uppercase tracking-wider">Credit Status</label>
                <select wire:model.live="credit_status" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                    <option value="all">All Credit Statuses</option>
                    <option value="within_limit">Within Limit</option>
                    <option value="over_limit_allowed">Over Limit Allowed</option>
                    <option value="over_limit_blocked">Over Limit Blocked</option>
                    <option value="pending_review">Pending Review</option>
                </select>
            </div>
            
            <!-- Date filter -->
            <div class="flex flex-col gap-xs sm:col-span-2">
                <label class="text-label-sm text-on-surface-variant font-bold uppercase tracking-wider">Date Range</label>
                <div class="flex items-center gap-sm">
                    <input type="date" wire:model.live="date_from" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                    <span class="text-on-surface-variant shrink-0">to</span>
                    <input type="date" wire:model.live="date_to" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none">
                </div>
            </div>

            <!-- Reset button -->
            <div class="w-full">
                <button wire:click="resetFilters" class="px-xl py-sm w-full text-sm font-bold text-primary border border-outline-variant/60 hover:bg-surface-container-low hover:text-primary-dark transition-colors rounded-lg bg-surface-container-lowest shadow-sm">Reset Filters</button>
            </div>
        </div>
    </x-admin.card>

    <!-- Data Table -->
    <x-admin.card class="shadow-sm border border-outline-variant/20 overflow-hidden">
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="overflow-x-auto">
            <table class="w-full text-left font-body-md border-collapse">
                <thead class="bg-surface-container-low text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20 select-none">
                    <tr class="whitespace-nowrap text-xs">
                        <th class="px-lg py-md whitespace-nowrap">Order No.</th>
                        <th class="px-lg py-md whitespace-nowrap">Date</th>
                        <th class="px-lg py-md whitespace-nowrap">Customer</th>
                        <th class="px-lg py-md whitespace-nowrap">Level</th>
                        <th class="px-lg py-md whitespace-nowrap">Checkout Method</th>
                        <th class="px-lg py-md text-right whitespace-nowrap">Value (₹)</th>
                        <th class="px-lg py-md text-center whitespace-nowrap">Status</th>
                        <th class="px-lg py-md text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse($orders as $order)
                        <tr class="hover:bg-primary/[0.02] transition-colors group cursor-pointer" onclick="window.location='{{ route('admin.orders.show', $order['order_number']) }}'">
                            <td class="px-lg py-md font-bold text-primary whitespace-nowrap">#{{ $order['order_number'] }}</td>
                            <td class="px-lg py-md text-on-surface-variant whitespace-nowrap">{{ $order['submitted_at'] }}</td>
                            <td class="px-lg py-md font-semibold text-on-surface whitespace-nowrap">{{ $order['customer_name'] }}</td>
                            <td class="px-lg py-md text-on-surface-variant whitespace-nowrap">{{ $order['customer_level'] }}</td>
                            <td class="px-lg py-md capitalize whitespace-nowrap">{{ str_replace('_', ' ', $order['checkout_method']) }}</td>
                            <td class="px-lg py-md text-right font-mono font-medium whitespace-nowrap">₹{{ number_format($order['total_amount'], 2) }}</td>
                            <td class="px-lg py-md text-center whitespace-nowrap">
                                @php
                                    $badge = app(\App\Services\Order\OrderStatusService::class)->getStatusBadge($order['status']);
                                @endphp
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full {{ $badge['bg'] }} {{ $badge['text'] }} inline-block shadow-sm">
                                    {{ $badge['label'] }}
                                </span>
                            </td>
                            <td class="px-lg py-md text-right whitespace-nowrap" onclick="event.stopPropagation()">
                                <x-admin.action-menu>
                                    <x-admin.action-menu-item icon="visibility" label="View Details" href="{{ route('admin.orders.show', $order['order_number']) }}" />
                                </x-admin.action-menu>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-lg py-xl text-center text-on-surface-variant italic">No orders found matching the filter criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer class="flex flex-col sm:flex-row gap-md justify-between items-center bg-surface-container-lowest border-t border-outline-variant/20 px-lg py-md select-none">
            <span class="font-label-md text-on-surface-variant">Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} orders</span>
            <div>
                {{ $orders->links() }}
            </div>
        </x-slot:footer>
    </x-admin.card>
</div>
