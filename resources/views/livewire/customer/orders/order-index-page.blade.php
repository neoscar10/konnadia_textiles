<div>
    <!-- Page Title -->
    <x-customer.page-title 
        title="My Wholesale Orders" 
        subtitle="Track order progress, view invoices, or re-order items."
        :breadcrumbs="['Home' => route('customer.dashboard'), 'Orders' => '#']"
    />

    <!-- Stat cards (Desktop only) -->
    <div class="hidden md:grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-customer.stat-card title="Total Active Orders" :value="$totalActive" icon="receipt_long" />
        <x-customer.stat-card title="Pending Review" :value="$pendingReview" icon="pending_actions" />
        @php
            $yearSpend = \App\Models\Order::where('user_id', auth()->id())
                ->whereYear('created_at', date('Y'))
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->sum('total_amount');
        @endphp
        <x-customer.stat-card title="Total Year Spend" value="₹{{ number_format($yearSpend, 2) }}" icon="payments" />
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white border border-outline-variant/30 rounded-xl shadow-ambient p-4 mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <!-- Status Filter Chips -->
        <div class="flex items-center gap-2 overflow-x-auto hide-scrollbar py-1">
            @php
                $statusList = [
                    'all' => 'All',
                    'pending_payment_verification' => 'Pending Receipt',
                    'pending_credit_review' => 'Pending Credit',
                    'submitted' => 'Submitted',
                    'under_review' => 'Under Review',
                    'approved' => 'Approved',
                    'dispatched' => 'Dispatched',
                    'cancelled' => 'Cancelled',
                    'rejected' => 'Rejected',
                ];
            @endphp
            @foreach($statusList as $key => $label)
                <button wire:click="setStatus('{{ $key }}')" 
                        class="px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-colors {{ $status === $key ? 'bg-[#001229] text-white' : 'bg-slate-50 border border-outline-variant/20 text-slate-600 hover:bg-slate-100' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <!-- Search Input -->
        <div class="relative w-full lg:w-64">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                <span class="material-symbols-outlined text-xl">search</span>
            </span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search orders by ID..." class="w-full bg-slate-50 text-[#001229] placeholder-slate-400 pl-10 pr-4 py-2 rounded-lg text-xs border border-outline-variant/20 focus:outline-none focus:ring-2 focus:ring-gold">
        </div>
    </div>

    @if($orders->isEmpty())
        <div class="bg-white border border-outline-variant/30 rounded-xl p-12 text-center shadow-ambient">
            <span class="material-symbols-outlined text-5xl text-slate-300">receipt_long</span>
            <h3 class="text-sm font-bold text-slate-800 mt-4">No Orders Found</h3>
            <p class="text-xs text-slate-400 mt-1">We couldn't find any wholesale orders matching your search filters.</p>
        </div>
    @else
        <!-- Mobile view: List of Cards -->
        <div class="md:hidden space-y-4">
            @foreach($orders as $order)
                <x-customer.order-card 
                    :orderId="$order['order_number']" 
                    :status="$order['status']['value'] ?? 'pending'" 
                    :date="\Carbon\Carbon::parse($order['submitted_at'] ?? $order['created_at'])->format('d M Y')" 
                    :total="$order['summary']['total'] ?? 0" 
                    :itemsCount="$order['items_count']"
                    :images="$order['first_item'] ? [$order['first_item']['image_url']] : []"
                />
            @endforeach
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
                        <th class="px-6 py-4">Billing Method</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach($orders as $order)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4 font-bold text-[#001229]">#{{ $order['order_number'] }}</td>
                            <td class="px-6 py-4">{{ \Carbon\Carbon::parse($order['submitted_at'] ?? $order['created_at'])->format('d M Y') }}</td>
                            <td class="px-6 py-4">
                                {{ $order['items_count'] }} {{ $order['items_count'] === 1 ? 'Style' : 'Styles' }}
                            </td>
                            <td class="px-6 py-4 font-bold">{{ $order['summary']['formatted_total'] }}</td>
                            <td class="px-6 py-4 capitalize">{{ $order['checkout_method']['label'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                <x-customer.badge :status="$order['status']['value'] ?? 'pending'" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('customer.orders.show', $order['order_number']) }}" class="inline-flex items-center justify-center px-3 py-1.5 text-[11px] font-bold text-[#001229] border border-outline-variant/40 hover:bg-white rounded-lg transition-colors bg-slate-50 shadow-sm">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <!-- Pagination footer -->
            <div class="px-6 py-4 bg-slate-50 border-t border-outline-variant/10">
                {{ $orders->links() }}
            </div>
        </div>
    @endif
</div>
