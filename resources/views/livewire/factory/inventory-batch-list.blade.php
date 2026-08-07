<div>
    <!-- Page Header & Actions -->
    <div class="bg-surface-container-lowest border-b border-outline-variant/60 p-6 rounded-2xl mb-6 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Inventory Batches</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Track raw material batch numbers, procurement history, consumption, and real-time balances.</p>
        </div>
        <a
            href="{{ route('factory.raw-materials.purchase') }}"
            wire:navigate
            class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary px-5 py-3 rounded-xl font-bold text-xs shadow-md transition-all active:scale-95"
        >
            <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
            Record Purchase
        </a>
    </div>

    <!-- Bento-card Summary Header -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-4 shadow-xs">
            <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Total Batches</span>
            <p class="text-2xl font-black text-primary mt-1">{{ $totalBatches }}</p>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-4 shadow-xs">
            <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Active Batches</span>
            <p class="text-2xl font-black text-secondary mt-1">{{ $activeBatches }}</p>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-4 shadow-xs">
            <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Depleted Batches</span>
            <p class="text-2xl font-black text-on-surface mt-1">{{ $depletedBatches }}</p>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-4 shadow-xs">
            <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Total Value (Active)</span>
            <p class="text-2xl font-black text-tertiary mt-1">₹{{ number_format($totalInventoryValue, 2) }}</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 p-4 rounded-2xl mb-6 shadow-xs flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[240px]">
            <label class="block text-[10px] font-bold uppercase text-on-surface-variant mb-1 ml-1">Search</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                <input
                    type="text"
                    wire:model.live.debounce.250ms="search"
                    placeholder="Search by Batch #, Supplier, Invoice..."
                    class="w-full pl-10 pr-4 py-2 bg-surface border border-outline-variant/60 rounded-lg text-body-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                />
            </div>
        </div>
        <div class="w-48">
            <label class="block text-[10px] font-bold uppercase text-on-surface-variant mb-1 ml-1">Raw Material</label>
            <select
                wire:model.live="materialFilter"
                class="w-full bg-surface border border-outline-variant/60 rounded-lg py-2 text-body-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
            >
                <option value="">All Materials</option>
                @foreach($materials as $material)
                    <option value="{{ $material->id }}">{{ $material->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-48">
            <label class="block text-[10px] font-bold uppercase text-on-surface-variant mb-1 ml-1">Category</label>
            <select
                wire:model.live="categoryFilter"
                class="w-full bg-surface border border-outline-variant/60 rounded-lg py-2 text-body-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
            >
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-32">
            <label class="block text-[10px] font-bold uppercase text-on-surface-variant mb-1 ml-1">Status</label>
            <select
                wire:model.live="statusFilter"
                class="w-full bg-surface border border-outline-variant/60 rounded-lg py-2 text-body-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
            >
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="depleted">Depleted</option>
            </select>
        </div>
        <div class="pt-5">
            <button
                type="button"
                wire:click="$set('search', ''); $set('materialFilter', ''); $set('categoryFilter', ''); $set('statusFilter', '');"
                class="px-4 py-2 text-primary font-bold text-xs hover:bg-surface-container-low rounded-lg transition-colors flex items-center gap-1"
            >
                <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                Reset Filters
            </button>
        </div>
    </div>

    <!-- Data Table Section -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-surface-container-low/50 border-b border-outline-variant/60 text-xs font-bold uppercase tracking-wider text-on-surface-variant whitespace-nowrap select-none">
                        <th class="px-6 py-4">Batch Number</th>
                        <th class="px-6 py-4">Material & Category</th>
                        <th class="px-6 py-4">Purchase Date</th>
                        <th class="px-6 py-4">Supplier & Invoice</th>
                        <th class="px-6 py-4 text-right">Received Qty</th>
                        <th class="px-6 py-4 text-right">Consumed Qty</th>
                        <th class="px-6 py-4 text-right">Balance Qty</th>
                        <th class="px-6 py-4 text-right">Unit Rate</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($batches as $batch)
                        @php
                            $totalQty = floatval($batch->quantity_received ?: 0);
                            $consumedQty = floatval($batch->quantity_consumed ?: 0);
                            $balanceQty = floatval($batch->balance_quantity ?: 0);
                            $percent = $totalQty > 0 ? max(0, min(100, ($balanceQty / $totalQty) * 100)) : 0;
                        @endphp
                        <tr class="hover:bg-surface-container-low/20 transition-colors {{ $batch->status === 'depleted' ? 'opacity-70 bg-surface-container-low/10' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('factory.raw-materials.batches.show', ['batch' => $batch->id]) }}" wire:navigate class="font-mono font-black text-primary text-xs bg-primary/10 px-2.5 py-1 rounded-lg hover:underline">
                                    {{ $batch->batch_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    @if($batch->raw_material_id)
                                        <a href="{{ route('factory.raw-materials.show', ['material' => $batch->raw_material_id]) }}" wire:navigate class="font-bold text-sm text-on-surface hover:text-primary transition-colors leading-tight">
                                            {{ $batch->rawMaterial?->name }}
                                        </a>
                                    @else
                                        <p class="font-bold text-sm text-on-surface leading-tight">{{ $batch->rawMaterial?->name }}</p>
                                    @endif
                                    @if($batch->rawMaterial?->category)
                                        <span class="inline-block mt-0.5 text-[9px] font-extrabold bg-secondary-container text-on-secondary-container px-1.5 py-0.5 rounded font-mono">
                                            {{ $batch->rawMaterial->category->code }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-on-surface-variant">
                                {{ $batch->purchase_date ? $batch->purchase_date->format('d M Y') : '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="font-bold text-on-surface leading-tight text-xs">{{ $batch->supplier_name ?: '—' }}</p>
                                <span class="text-[10px] text-on-surface-variant font-mono mt-0.5 block">{{ $batch->invoice_number ? '#' . $batch->invoice_number : '' }}</span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap font-semibold">
                                {{ number_format($totalQty, 2) }} <span class="text-xs text-on-surface-variant/70">{{ $batch->unit }}</span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap font-semibold text-on-surface-variant/80">
                                {{ number_format($consumedQty, 2) }} <span class="text-xs text-on-surface-variant/70">{{ $batch->unit }}</span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap font-bold">
                                <div>
                                    <span>{{ number_format($balanceQty, 2) }}</span>
                                    <span class="text-xs text-on-surface-variant/70">{{ $batch->unit }}</span>
                                </div>
                                @if($batch->status === 'active')
                                    <div class="w-24 bg-outline-variant/30 rounded-full h-1 mt-1.5 overflow-hidden ml-auto">
                                        <div class="bg-secondary h-full rounded-full transition-all" style="width: {{ $percent }}%"></div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap font-bold text-primary">
                                ₹{{ number_format(floatval($batch->purchase_rate ?: $batch->unit_cost), 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($batch->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-secondary-container text-on-secondary-container border border-secondary/20 font-mono">
                                        AVAILABLE
                                    </span>
                                @elseif($batch->status === 'depleted')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-error-container text-on-error-container border border-error/20 font-mono">
                                        DEPLETED
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-outline-variant/20 text-on-surface-variant border border-outline-variant/40 font-mono">
                                        {{ strtoupper($batch->status) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2">reorder</span>
                                    <p class="text-sm font-semibold text-on-surface">No inventory batches found</p>
                                    <p class="text-xs text-on-surface-variant mt-1">Record a purchase entry to create your first inventory batch.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($batches->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/60 bg-surface-container-low/20">
                {{ $batches->links() }}
            </div>
        @endif
    </div>
</div>
