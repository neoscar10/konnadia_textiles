<div class="p-6 space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 p-6 rounded-2xl shadow-xs flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-on-surface-variant mb-2">
                <a href="{{ route('factory.raw-materials.index') }}" wire:navigate class="hover:text-primary transition-colors flex items-center gap-1 font-semibold">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Raw Materials Master
                </a>
                <span>/</span>
                <span class="font-mono font-bold text-primary">{{ $material->code }}</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="font-headline-lg text-2xl font-black text-primary tracking-tight">{{ $material->name }}</h1>
                <span class="font-mono font-bold text-xs bg-primary/10 text-primary px-3 py-1 rounded-lg border border-primary/20">
                    {{ $material->code }}
                </span>
                @if($material->category)
                    <span class="inline-flex items-center gap-1 text-xs font-extrabold bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full border border-secondary/20 font-mono">
                        <span class="material-symbols-outlined text-[14px]">category</span>
                        {{ $material->category->name }} ({{ $material->category->code }})
                    </span>
                @endif
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-extrabold border {{ $material->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' }}">
                    <span class="w-2 h-2 rounded-full {{ $material->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                    {{ $material->is_active ? 'Active Material' : 'Inactive' }}
                </span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('factory.raw-materials.purchase') }}" wire:navigate class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold text-xs shadow-md transition-all active:scale-95">
                <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
                Record Purchase
            </a>
        </div>
    </div>

    <!-- Bento Stat Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Current Stock Balance -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Current Stock</span>
                <span class="material-symbols-outlined text-primary text-[22px]">inventory_2</span>
            </div>
            <p class="text-2xl font-black text-primary mt-2">
                {{ number_format($totalStockBalance, 2) }}
                <span class="text-xs font-bold text-on-surface-variant">{{ $material->unit }}</span>
            </p>
            <p class="text-[11px] text-on-surface-variant/70 mt-1 font-medium">Available across active batches</p>
        </div>

        <!-- Base Unit Stock Equivalent -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Base Unit Equivalent</span>
                <span class="material-symbols-outlined text-secondary text-[22px]">square_foot</span>
            </div>
            <p class="text-2xl font-black text-secondary mt-2">
                {{ number_format($baseStockBalance, 2) }}
                <span class="text-xs font-bold text-on-surface-variant">
                    {{ $material->unitGroup ? ($material->unitGroup->baseUnit ? $material->unitGroup->baseUnit->short_code : $material->unit) : $material->unit }}
                </span>
            </p>
            <p class="text-[11px] text-on-surface-variant/70 mt-1 font-medium">
                Normalized base quantity
            </p>
        </div>

        <!-- Total Inventory Value -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Stock Valuation</span>
                <span class="material-symbols-outlined text-emerald-600 text-[22px]">payments</span>
            </div>
            <p class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-2">
                ₹{{ number_format($totalInventoryValue, 2) }}
            </p>
            <p class="text-[11px] text-on-surface-variant/70 mt-1 font-medium">Estimated active inventory cost</p>
        </div>

        <!-- Total Received vs Consumed -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Total Procurement</span>
                <span class="material-symbols-outlined text-amber-600 text-[22px]">history_edu</span>
            </div>
            <p class="text-2xl font-black text-on-surface mt-2">
                {{ number_format($totalReceived, 2) }}
                <span class="text-xs font-bold text-on-surface-variant">{{ $material->unit }}</span>
            </p>
            <p class="text-[11px] text-on-surface-variant/70 mt-1 font-medium">
                Consumed: <strong class="text-rose-600">{{ number_format($totalConsumed, 2) }} {{ $material->unit }}</strong>
            </p>
        </div>

        <!-- Batches Count -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Batch Ledger</span>
                <span class="material-symbols-outlined text-indigo-600 text-[22px]">reorder</span>
            </div>
            <p class="text-2xl font-black text-indigo-700 dark:text-indigo-400 mt-2">
                {{ $activeBatchesCount }} <span class="text-xs text-on-surface-variant font-bold">Active</span>
            </p>
            <p class="text-[11px] text-on-surface-variant/70 mt-1 font-medium">
                Out of {{ $totalBatchesCount }} total batches
            </p>
        </div>
    </div>

    <!-- Specifications Banner -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs">
        <h3 class="text-xs font-bold text-on-surface uppercase tracking-wider mb-3 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px] text-primary">tune</span>
            Material Specifications & Unit Configuration
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
            <div class="bg-surface-container-low/40 p-3 rounded-xl border border-outline-variant/30">
                <span class="text-on-surface-variant block text-[10px] uppercase font-bold">Unit of Measurement</span>
                <span class="font-black text-primary text-sm mt-0.5 block">{{ $material->unit }}</span>
            </div>
            <div class="bg-surface-container-low/40 p-3 rounded-xl border border-outline-variant/30">
                <span class="text-on-surface-variant block text-[10px] uppercase font-bold">Unit Group</span>
                <span class="font-bold text-on-surface text-sm mt-0.5 block">
                    {{ $material->unitGroup ? $material->unitGroup->name . ' (' . $material->unitGroup->code . ')' : 'Standard Group' }}
                </span>
            </div>
            <div class="bg-surface-container-low/40 p-3 rounded-xl border border-outline-variant/30">
                <span class="text-on-surface-variant block text-[10px] uppercase font-bold">Standard Width</span>
                <span class="font-bold text-on-surface text-sm mt-0.5 block">
                    {{ $material->standard_width ? $material->standard_width . ' ' . $material->width_unit : 'N/A (Non-length material)' }}
                </span>
            </div>
            <div class="bg-surface-container-low/40 p-3 rounded-xl border border-outline-variant/30">
                <span class="text-on-surface-variant block text-[10px] uppercase font-bold">Base Unit Factor</span>
                <span class="font-bold text-emerald-700 dark:text-emerald-400 text-sm mt-0.5 block">
                    {{ $material->unitModel ? '1 ' . $material->unit . ' = ' . $material->unitModel->ratio_to_base . ' ' . ($material->unitGroup->baseUnit->short_code ?? 'Base') : '1.0 (Direct Base Unit)' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-outline-variant/60 flex items-center gap-6">
        <button
            wire:click="setTab('batches')"
            class="pb-3 text-xs font-extrabold transition-all border-b-2 cursor-pointer flex items-center gap-2
                {{ $activeTab === 'batches' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-primary' }}"
        >
            <span class="material-symbols-outlined text-[18px]">reorder</span>
            Procurement Batches ({{ $totalBatchesCount }})
        </button>
        <button
            wire:click="setTab('bom')"
            class="pb-3 text-xs font-extrabold transition-all border-b-2 cursor-pointer flex items-center gap-2
                {{ $activeTab === 'bom' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-primary' }}"
        >
            <span class="material-symbols-outlined text-[18px]">inventory_2</span>
            BOM Products Usage ({{ $bomProducts->count() }})
        </button>
        <button
            wire:click="setTab('logs')"
            class="pb-3 text-xs font-extrabold transition-all border-b-2 cursor-pointer flex items-center gap-2
                {{ $activeTab === 'logs' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-primary' }}"
        >
            <span class="material-symbols-outlined text-[18px]">history</span>
            Complete Consumption Audit Ledger
        </button>
    </div>

    <!-- TAB 1: BATCHES LIST -->
    @if($activeTab === 'batches')
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-surface-container-low/40 border-b border-outline-variant/60 text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                            <th class="px-6 py-4">Batch Number</th>
                            <th class="px-6 py-4">Supplier & Invoice</th>
                            <th class="px-6 py-4">Purchase Date</th>
                            <th class="px-6 py-4 text-right">Received Qty</th>
                            <th class="px-6 py-4 text-right">Consumed Qty</th>
                            <th class="px-6 py-4 text-right">Current Balance</th>
                            <th class="px-6 py-4 text-right">Unit Rate</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @forelse($batches as $batch)
                            <tr class="hover:bg-surface-container-low/20 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('factory.raw-materials.batches.show', ['batch' => $batch->id]) }}" wire:navigate class="font-mono font-black text-primary text-xs bg-primary/10 px-2.5 py-1 rounded-lg hover:underline inline-block">
                                        {{ $batch->batch_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="font-bold text-on-surface text-xs leading-tight">{{ $batch->supplier_name ?: '—' }}</p>
                                    <span class="text-[10px] text-on-surface-variant font-mono block">{{ $batch->invoice_number ? '#' . $batch->invoice_number : '' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-on-surface-variant">
                                    {{ $batch->purchase_date ? $batch->purchase_date->format('d M Y') : '—' }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap font-semibold">
                                    {{ number_format($batch->quantity_received, 2) }} <span class="text-xs text-on-surface-variant/70">{{ $batch->unit }}</span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap font-semibold text-on-surface-variant/80">
                                    {{ number_format($batch->quantity_consumed, 2) }} <span class="text-xs text-on-surface-variant/70">{{ $batch->unit }}</span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap font-bold text-primary">
                                    {{ number_format($batch->balance_quantity, 2) }} <span class="text-xs text-on-surface-variant/70">{{ $batch->unit }}</span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap font-bold text-emerald-700 dark:text-emerald-400">
                                    ₹{{ number_format(floatval($batch->purchase_rate ?: $batch->unit_cost), 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($batch->status === 'active')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-secondary-container text-on-secondary-container border border-secondary/20 font-mono">
                                            AVAILABLE
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-error-container text-on-error-container border border-error/20 font-mono">
                                            DEPLETED
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('factory.raw-materials.batches.show', ['batch' => $batch->id]) }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                                        Audit Batch &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-on-surface-variant">
                                    No procurement batches recorded for this material.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($batches->hasPages())
                <div class="px-6 py-4 border-t border-outline-variant/60 bg-surface-container-low/20">
                    {{ $batches->links(data: ['pageName' => 'batchesPage']) }}
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 2: BOM PRODUCTS USAGE -->
    @if($activeTab === 'bom')
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-outline-variant/60 bg-surface-container-low/20">
                <h3 class="font-bold text-sm text-on-surface">Manufacturing Products Consuming {{ $material->name }}</h3>
                <p class="text-xs text-on-surface-variant mt-0.5">List of all product Bill of Materials (BOM) configured with this raw material.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-surface-container-low/40 border-b border-outline-variant/60 text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                            <th class="px-6 py-4">Product Code</th>
                            <th class="px-6 py-4">Product Name</th>
                            <th class="px-6 py-4">Category</th>
                            <th class="px-6 py-4">Standard Labor Rate</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @forelse($bomProducts as $prod)
                            <tr class="hover:bg-surface-container-low/20 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono font-black text-primary text-xs bg-primary/10 px-2.5 py-1 rounded-lg">
                                        {{ $prod->code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-on-surface">
                                    {{ $prod->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ $prod->category?->name ?: '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-emerald-700">
                                    ₹{{ number_format($prod->standard_labor_rate, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('factory.products.edit', ['id' => $prod->id]) }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                                        View Product BOM &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-on-surface-variant">
                                    No manufacturing products currently reference this raw material in their BOM.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- TAB 3: COMPLETE CONSUMPTION AUDIT LOG -->
    @if($activeTab === 'logs')
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden space-y-4">
            <div class="p-4 border-b border-outline-variant/60 bg-surface-container-low/20 flex items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-sm text-on-surface">Comprehensive Stock Audit Trail</h3>
                    <p class="text-xs text-on-surface-variant mt-0.5">Chronological record of stock additions, stage deductions, and manual adjustments.</p>
                </div>
                <div class="relative w-64">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[16px]">search</span>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="logSearch"
                        placeholder="Search logs..."
                        class="w-full pl-9 pr-3 py-1.5 bg-surface border border-outline-variant/60 rounded-xl text-xs focus:outline-none focus:border-primary"
                    />
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-surface-container-low/40 border-b border-outline-variant/60 text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                            <th class="px-6 py-4">Timestamp</th>
                            <th class="px-6 py-4">Batch #</th>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Action</th>
                            <th class="px-6 py-4 text-right">Quantity</th>
                            <th class="px-6 py-4">Notes / Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @forelse($logs as $log)
                            <tr class="hover:bg-surface-container-low/20 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-on-surface-variant font-mono">
                                    {{ $log->created_at->format('d M Y H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-xs font-bold text-primary">
                                    @if($log->batch)
                                        <a href="{{ route('factory.raw-materials.batches.show', ['batch' => $log->batch->id]) }}" wire:navigate class="hover:underline">
                                            {{ $log->batch->batch_number }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-on-surface">
                                    {{ $log->user ? $log->user->name : 'System' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase font-mono
                                        {{ strtolower($log->action) === 'created' ? 'bg-emerald-100 text-emerald-800' : (strtolower($log->action) === 'consumed' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap font-mono font-bold text-sm">
                                    @if($log->quantity)
                                        {{ number_format($log->quantity, 2) }} <span class="text-xs text-on-surface-variant">{{ $material->unit }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-on-surface-variant">
                                    {{ $log->description ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                                    No audit log entries recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
                <div class="px-6 py-4 border-t border-outline-variant/60 bg-surface-container-low/20">
                    {{ $logs->links(data: ['pageName' => 'logsPage']) }}
                </div>
            @endif
        </div>
    @endif
</div>
