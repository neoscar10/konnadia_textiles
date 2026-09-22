<div>
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-1 bg-amber-500/10 text-amber-700 text-xs font-bold rounded-lg uppercase tracking-wider">Manufacturing Inventory</span>
                <span class="text-outline text-xs font-bold">• Leftover Production Output</span>
            </div>
            <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Spare Products Directory</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">View and manage leftover manufacturing products that were not converted during initial batch conversion. Pull spare stock into future storefront conversions.</p>
        </div>
    </div>

    <!-- Summary Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">inventory</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Available Spare Stock</p>
                <p class="text-2xl font-black text-amber-600">{{ number_format($totalAvailableSpare) }} <span class="text-xs font-bold text-outline">Pcs</span></p>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">style</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Active Design IDs</p>
                <p class="text-2xl font-black text-on-surface">{{ number_format($uniqueDesignIdsCount) }}</p>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">task_alt</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Utilized Spare Stock</p>
                <p class="text-2xl font-black text-emerald-600">{{ number_format($totalSpareUsed) }} <span class="text-xs font-bold text-outline">Pcs</span></p>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">production_quantity_limits</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Total Spare Recorded</p>
                <p class="text-2xl font-black text-on-surface">{{ number_format($totalSpareRecorded) }} <span class="text-xs font-bold text-outline">Pcs</span></p>
            </div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/60 mb-6 flex flex-wrap items-center gap-4 shadow-xs">
        <div class="w-full max-w-md">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-on-surface-variant">search</span>
                <input wire:model.live.debounce.300ms="search" class="w-full px-4 py-2.5 bg-surface rounded-xl border border-outline-variant/60 focus:ring-2 focus:ring-primary/20 focus:border-primary font-body-sm text-body-sm" placeholder="Search Design ID, Manufacturing Product, Batch Code..." type="text"/>
            </div>
        </div>
        <div>
            <select wire:model.live="designFilter" class="bg-surface border border-outline-variant/60 rounded-xl font-label-md text-label-md py-2.5 px-4 focus:ring-2 focus:ring-primary/20 focus:border-primary font-bold">
                <option value="">All Design IDs</option>
                @foreach($availableDesignIds as $dId)
                    <option value="{{ $dId }}">{{ $dId }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Spare Products Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 overflow-hidden shadow-xs">
        <table class="w-full text-left border-collapse font-body-md">
            <thead>
                <tr class="bg-surface-container-low border-b border-outline-variant/60 text-xs text-on-surface-variant uppercase tracking-wider">
                    <th class="px-6 py-4 font-bold">Design ID</th>
                    <th class="px-6 py-4 font-bold">Manufacturing Product</th>
                    <th class="px-6 py-4 font-bold">Source Batch</th>
                    <th class="px-6 py-4 font-bold text-center">Initial Spare Qty</th>
                    <th class="px-6 py-4 font-bold text-center">Used Qty</th>
                    <th class="px-6 py-4 font-bold text-center">Available Balance</th>
                    <th class="px-6 py-4 font-bold text-right">Created Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/40">
                @forelse($spareProducts as $sp)
                    <tr class="hover:bg-surface-container/50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-black text-primary text-sm font-mono block">{{ $sp->design_id }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-on-surface text-sm">{{ $sp->manufacturingProduct?->name }}</p>
                            <span class="text-xs text-outline font-mono">{{ $sp->manufacturingProduct?->code }}</span>
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-on-surface font-semibold">
                            {{ $sp->productionBatch?->batch_code ?? 'Manual Spare' }}
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-on-surface text-sm">
                            {{ number_format($sp->quantity) }} Pcs
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-emerald-700 text-sm">
                            {{ number_format($sp->used_quantity) }} Pcs
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($sp->available_quantity > 0)
                                <span class="px-3 py-1 bg-amber-100 text-amber-800 font-black rounded-full text-xs font-mono border border-amber-300">
                                    {{ number_format($sp->available_quantity) }} Pcs Avail
                                </span>
                            @else
                                <span class="px-3 py-1 bg-slate-100 text-slate-600 font-bold rounded-full text-xs font-mono">
                                    0 Pcs (Fully Used)
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-xs text-outline font-semibold">
                            {{ $sp->created_at ? $sp->created_at->format('M d, Y') : 'N/A' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-4xl text-outline mb-2">inventory_2</span>
                            <p class="font-body-lg text-body-lg">No spare products found.</p>
                            <p class="text-xs text-outline mt-1">Leftover unconverted outputs from batch conversions will appear here automatically.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($spareProducts->hasPages())
            <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/60">
                {{ $spareProducts->links() }}
            </div>
        @endif
    </div>
</div>
