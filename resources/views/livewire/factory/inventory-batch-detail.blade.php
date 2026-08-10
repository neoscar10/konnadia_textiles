<div class="p-6 space-y-6">
    <!-- Header Section -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 p-6 rounded-2xl shadow-xs flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-on-surface-variant mb-2">
                <a href="{{ route('factory.raw-materials.batches') }}" wire:navigate class="hover:text-primary transition-colors flex items-center gap-1 font-semibold">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Inventory Batches
                </a>
                <span>/</span>
                <span class="font-mono font-bold text-primary">{{ $batch->batch_number }}</span>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="font-headline-lg text-2xl font-black text-primary tracking-tight">Batch #{{ $batch->batch_number }}</h1>
                @if($batch->status === 'active')
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-extrabold bg-secondary-container text-on-secondary-container border border-secondary/20 font-mono">
                        <span class="w-2 h-2 rounded-full bg-secondary"></span>
                        STOCK AVAILABLE
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-extrabold bg-error-container text-on-error-container border border-error/20 font-mono">
                        <span class="w-2 h-2 rounded-full bg-error"></span>
                        DEPLETED
                    </span>
                @endif
                @if($batch->supplier_name)
                    <span class="text-xs text-on-surface-variant font-medium bg-surface-container-high px-3 py-1 rounded-lg border border-outline-variant/30">
                        Supplier: <strong class="text-on-surface">{{ $batch->supplier_name }}</strong>
                    </span>
                @endif
                @if($batch->invoice_number)
                    <span class="text-xs text-on-surface-variant font-mono bg-surface-container-high px-3 py-1 rounded-lg border border-outline-variant/30">
                        Invoice #{{ $batch->invoice_number }}
                    </span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if($batch->raw_material_id)
                <a href="{{ route('factory.raw-materials.show', ['material' => $batch->raw_material_id]) }}" wire:navigate class="inline-flex items-center gap-2 border border-outline-variant/60 hover:bg-surface-container-high/30 text-on-surface px-4 py-2.5 rounded-xl font-bold text-xs shadow-xs transition-all">
                    <span class="material-symbols-outlined text-[18px]">analytics</span>
                    Audit Material Stock
                </a>
            @endif
        </div>
    </div>

    <!-- Bento Stat Cards Grid -->
    @php
        $totalQty = floatval($batch->quantity_received ?: 0);
        $consumedQty = floatval($batch->quantity_consumed ?: 0);
        $balanceQty = floatval($batch->balance_quantity ?: 0);
        $percent = $totalQty > 0 ? max(0, min(100, ($balanceQty / $totalQty) * 100)) : 0;
        $unitRate = floatval($batch->purchase_rate ?: $batch->unit_cost);
        $totalBatchValue = floatval($batch->total_amount ?: ($totalQty * $unitRate));
        $currentValue = $balanceQty * $unitRate;
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Received Qty -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs">
            <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Quantity Received</span>
            <p class="text-2xl font-black text-primary mt-2">
                {{ number_format($totalQty, 2) }}
                <span class="text-xs font-bold text-on-surface-variant">{{ $batch->unit }}</span>
            </p>
            <p class="text-[11px] text-on-surface-variant/70 mt-1 font-medium">Initial procurement stock</p>
        </div>

        <!-- Consumed Qty -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs">
            <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Quantity Consumed</span>
            <p class="text-2xl font-black text-rose-600 mt-2">
                {{ number_format($consumedQty, 2) }}
                <span class="text-xs font-bold text-on-surface-variant">{{ $batch->unit }}</span>
            </p>
            <p class="text-[11px] text-on-surface-variant/70 mt-1 font-medium">Used in production jobs</p>
        </div>

        <!-- Current Balance Qty -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs">
            <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Current Balance</span>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2">
                {{ number_format($balanceQty, 2) }}
                <span class="text-xs font-bold text-on-surface-variant">{{ $batch->unit }}</span>
            </p>
            <div class="w-full bg-outline-variant/30 rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="bg-emerald-500 h-full rounded-full transition-all" style="width: {{ $percent }}%"></div>
            </div>
        </div>

        <!-- Base Unit Equivalent -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs">
            <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Base Unit Balance</span>
            <p class="text-2xl font-black text-secondary mt-2">
                {{ number_format(floatval($batch->base_current_balance ?: $balanceQty), 2) }}
                <span class="text-xs font-bold text-on-surface-variant">
                    {{ $batch->rawMaterial?->unitGroup?->baseUnit?->short_code ?: $batch->unit }}
                </span>
            </p>
            <p class="text-[11px] text-on-surface-variant/70 mt-1 font-medium">Normalized stock ratio</p>
        </div>

        <!-- Valuation -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs">
            <span class="text-[10px] font-bold text-on-surface-variant/60 uppercase tracking-wider block">Batch Valuation</span>
            <p class="text-2xl font-black text-primary mt-2">
                ₹{{ number_format($currentValue, 2) }}
            </p>
            <p class="text-[11px] text-on-surface-variant/70 mt-1 font-medium">
                Rate: <strong>₹{{ number_format($unitRate, 2) }}/{{ $batch->unit }}</strong>
            </p>
        </div>
    </div>

    <!-- Batch & Material Details Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs">
        <h2 class="text-sm font-bold text-on-surface uppercase tracking-wider mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-[20px]">info</span>
            Batch & Material Metadata
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">
            <div class="space-y-2">
                <p class="text-on-surface-variant font-bold uppercase text-[10px]">Raw Material</p>
                @if($batch->rawMaterial)
                    <a href="{{ route('factory.raw-materials.show', ['material' => $batch->rawMaterial->id]) }}" wire:navigate class="text-base font-black text-primary hover:underline block">
                        {{ $batch->rawMaterial->name }} ({{ $batch->rawMaterial->code }})
                    </a>
                @else
                    <p class="text-base font-bold text-on-surface">—</p>
                @endif
                <p class="text-on-surface-variant">
                    Category: <strong>{{ $batch->rawMaterial?->category?->name ?: '—' }}</strong>
                </p>
            </div>

            <div class="space-y-2">
                <p class="text-on-surface-variant font-bold uppercase text-[10px]">Procurement Info</p>
                <p class="text-sm font-bold text-on-surface">
                    Purchase Date: <strong>{{ $batch->purchase_date ? $batch->purchase_date->format('d M Y') : '—' }}</strong>
                </p>
                <p class="text-on-surface-variant">
                    Supplier: <strong>{{ $batch->supplier_name ?: '—' }}</strong>
                </p>
                <p class="text-on-surface-variant font-mono">
                    Invoice: <strong>{{ $batch->invoice_number ? '#' . $batch->invoice_number : '—' }}</strong>
                </p>
            </div>

            <div class="space-y-2">
                <p class="text-on-surface-variant font-bold uppercase text-[10px]">Financial Summary</p>
                <p class="text-sm font-bold text-on-surface">
                    Total Amount Paid: <strong>₹{{ number_format($totalBatchValue, 2) }}</strong>
                </p>
                <p class="text-on-surface-variant">
                    Unit Purchase Rate: <strong>₹{{ number_format($unitRate, 2) }} per {{ $batch->unit }}</strong>
                </p>
                <p class="text-on-surface-variant">
                    Current Stock Value: <strong class="text-emerald-700">₹{{ number_format($currentValue, 2) }}</strong>
                </p>
            </div>
        </div>
    </div>

    <!-- Section: Fabric Bales & Rolls Inventory Breakdown -->
    @if($batch->bales->isNotEmpty() || optional($batch->rawMaterial?->category)->code === 'CAT-FAB' || optional($batch->rawMaterial?->category)->unit_type === 'length_based')
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
            <div class="p-4 border-b border-outline-variant/60 bg-surface-container-low/20 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="font-bold text-sm text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[18px]">inventory_2</span>
                        Fabric Bales & Individual Rolls Audit
                    </h2>
                    <p class="text-xs text-on-surface-variant mt-0.5">Tracking for individual bales and recorded roll lengths in this procurement batch.</p>
                </div>
                <div class="flex items-center gap-2 font-mono text-xs">
                    <span class="px-2.5 py-1 rounded-lg bg-primary/10 text-primary font-bold">
                        {{ $batch->bales->count() }} Bales Total
                    </span>
                    <span class="px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800 font-bold">
                        {{ $batch->bales->where('status', 'unopened')->count() }} Unopened
                    </span>
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 font-bold">
                        {{ $batch->bales->where('status', 'opened')->count() }} Opened
                    </span>
                </div>
            </div>

            <div class="p-6 space-y-6">
                @forelse($batch->bales as $bale)
                    <div class="bg-surface border border-outline-variant/60 rounded-xl p-5 shadow-xs space-y-4">
                        <div class="flex flex-wrap justify-between items-center gap-4 pb-3 border-b border-outline-variant/40">
                            <div class="flex items-center gap-3">
                                <span class="font-mono font-extrabold text-base text-primary">{{ $bale->bale_number }}</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase font-mono
                                    {{ $bale->status === 'unopened' ? 'bg-amber-100 text-amber-900 border border-amber-300' : ($bale->status === 'opened' ? 'bg-emerald-100 text-emerald-900 border border-emerald-300' : 'bg-slate-100 text-slate-700') }}">
                                    {{ strtoupper($bale->status) }}
                                </span>
                            </div>

                            <div class="flex items-center gap-4 text-xs font-mono">
                                <div>
                                    <span class="text-on-surface-variant text-[10px] uppercase font-bold block">Purchase Declared</span>
                                    <span class="font-bold text-on-surface">{{ number_format($bale->declared_length, 2) }} {{ $batch->unit }}</span>
                                </div>
                                <div>
                                    <span class="text-on-surface-variant text-[10px] uppercase font-bold block">Measured Total</span>
                                    <span class="font-bold text-primary">
                                        {{ $bale->actual_recorded_length ? number_format($bale->actual_recorded_length, 2) . ' ' . $batch->unit : '— (Unopened)' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-on-surface-variant text-[10px] uppercase font-bold block">Current Balance</span>
                                    <span class="font-extrabold text-emerald-600">
                                        {{ number_format($bale->current_balance_length, 2) }} {{ $batch->unit }}
                                    </span>
                                </div>

                                @if($bale->status === 'unopened')
                                    <button type="button" wire:click="triggerOpenBaleModal({{ $bale->id }})" class="bg-amber-600 hover:bg-amber-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-extrabold shadow-2xs transition-all active:scale-95 flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-[15px]">content_cut</span> Open Bale
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Individual Rolls Breakdown (If Opened) -->
                        @if($bale->rolls->isNotEmpty())
                            <div class="space-y-2 pt-1">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant block">
                                    Rolls Breakdown ({{ $bale->rolls->count() }} Rolls Recorded)
                                </span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                    @foreach($bale->rolls as $roll)
                                        <div class="p-3 bg-surface-container-lowest border border-outline-variant/50 rounded-xl space-y-1.5 shadow-2xs">
                                            <div class="flex justify-between items-center">
                                                <span class="font-mono font-bold text-xs text-primary">{{ $roll->roll_number }}</span>
                                                <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded {{ $roll->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                                    {{ $roll->status }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between text-xs font-mono">
                                                <span class="text-on-surface-variant text-[10px]">Initial:</span>
                                                <span class="font-semibold">{{ number_format($roll->initial_length, 2) }}m</span>
                                            </div>
                                            <div class="flex justify-between text-xs font-mono pt-1 border-t border-outline-variant/30 font-bold">
                                                <span class="text-on-surface-variant text-[10px]">Balance:</span>
                                                <span class="text-secondary">{{ number_format($roll->current_balance_length, 2) }}m</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @elseif($bale->status === 'unopened')
                            <p class="text-xs text-amber-800 bg-amber-50/50 p-3 rounded-lg border border-amber-200 font-medium flex items-center justify-between">
                                <span>Bale has not been opened yet. Number of rolls and individual roll lengths will be recorded when opened at the cutting stage or using the button above.</span>
                            </p>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-on-surface-variant text-center py-6">No bale records generated for this batch.</p>
                @endforelse
            </div>
        </div>
    @endif

    <!-- Section 1: Batch Lifecycle Audit Log -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-outline-variant/60 bg-surface-container-low/20 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-sm text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">history</span>
                    Batch Lifecycle Audit Logs
                </h2>
                <p class="text-xs text-on-surface-variant mt-0.5">Full movement log for creation, manual adjustments, and consumption deductions.</p>
            </div>
            <span class="text-xs font-mono font-bold text-primary bg-primary/10 px-2.5 py-1 rounded-lg">
                {{ $batch->logs->count() }} Entries
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-surface-container-low/40 border-b border-outline-variant/60 text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                        <th class="px-6 py-4">Timestamp</th>
                        <th class="px-6 py-4">User</th>
                        <th class="px-6 py-4">Action</th>
                        <th class="px-6 py-4 text-right">Quantity</th>
                        <th class="px-6 py-4">Related Production Order</th>
                        <th class="px-6 py-4">Notes / Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($batch->logs->sortByDesc('created_at') as $log)
                        <tr class="hover:bg-surface-container-low/20 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-on-surface-variant font-mono">
                                {{ $log->created_at->format('d M Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-on-surface">
                                {{ $log->user?->name ?? 'System' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase font-mono
                                    {{ strtolower($log->action) === 'created' ? 'bg-emerald-100 text-emerald-800' : (strtolower($log->action) === 'consumed' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap font-mono font-bold text-sm">
                                @if($log->quantity)
                                    {{ number_format($log->quantity, 2) }} <span class="text-xs text-on-surface-variant">{{ $batch->unit }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-secondary">
                                @if($log->related_production_batch_id)
                                    <a href="{{ route('admin.production.jobs.index') }}" wire:navigate class="underline hover:text-secondary/80">
                                        Batch Order #{{ $log->related_production_batch_id }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-on-surface-variant">
                                {{ $log->description ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                                No audit log entries recorded for this batch.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Detailed Stage Execution Consumption Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-outline-variant/60 bg-surface-container-low/20 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-sm text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[18px]">precision_manufacturing</span>
                    Production Job Consumption Breakdown
                </h2>
                <p class="text-xs text-on-surface-variant mt-0.5">Every job stage execution where material was deducted from this batch.</p>
            </div>
            <span class="text-xs font-mono font-bold text-secondary bg-secondary/10 px-2.5 py-1 rounded-lg">
                {{ $batch->consumptions->count() }} Records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-surface-container-low/40 border-b border-outline-variant/60 text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Production Job #</th>
                        <th class="px-6 py-4">Manufacturing Product</th>
                        <th class="px-6 py-4 text-right">Quantity Consumed</th>
                        <th class="px-6 py-4 text-right">Cost Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($batch->consumptions->sortByDesc('created_at') as $consumption)
                        @php
                            $consumedVal = floatval($consumption->quantity) * $unitRate;
                        @endphp
                        <tr class="hover:bg-surface-container-low/20 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-on-surface-variant font-mono">
                                {{ $consumption->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($consumption->job)
                                    <a href="{{ route('admin.production.jobs.show', ['id' => $consumption->job->id]) }}" wire:navigate class="font-bold text-xs text-primary hover:underline">
                                        Job #{{ $consumption->job->id }} – {{ $consumption->job->title ?? 'Untitled' }}
                                    </a>
                                @else
                                    <span class="text-xs text-on-surface-variant">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-on-surface">
                                {{ $consumption->job?->manufacturingProduct?->name ?: '—' }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap font-mono font-bold text-sm text-rose-600">
                                {{ number_format($consumption->quantity, 2) }} <span class="text-xs text-on-surface-variant">{{ $batch->unit }}</span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap font-mono font-bold text-sm text-emerald-700">
                                ₹{{ number_format($consumedVal, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-on-surface-variant">
                                No job stage consumption records linked to this batch yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Open Bale Modal Dialog -->
    @if($showOpenBaleModal && $activeBaleIdToOpen)
        @php
            $baleToOpen = \App\Models\InventoryBale::find($activeBaleIdToOpen);
        @endphp
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-surface border border-outline-variant/60 rounded-3xl p-6 sm:p-8 max-w-2xl w-full shadow-2xl space-y-6 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-start pb-4 border-b border-outline-variant/40">
                    <div>
                        <span class="text-[10px] font-black text-amber-800 uppercase tracking-widest bg-amber-100 px-2.5 py-1 rounded-md">Bale Opening & Roll Entry</span>
                        <h3 class="font-headline-sm text-headline-sm text-primary font-extrabold mt-1">Open {{ $baleToOpen?->bale_number }}</h3>
                        <p class="text-xs text-on-surface-variant">Recorded Purchase Declared Length: <strong class="text-primary">{{ $baleToOpen?->declared_length }}m</strong></p>
                    </div>
                    <button type="button" wire:click="$set('showOpenBaleModal', false)" class="text-outline hover:text-on-surface p-2 rounded-xl">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Number of Rolls in this Bale *</label>
                        <input type="number" min="1" max="50" wire:model.live="baleRollCount" placeholder="-- Enter number of rolls (e.g. 5) --" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-bold text-primary focus:ring-2 focus:ring-primary/20">
                        @error('baleRollCount') <p class="text-xs font-bold text-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if(!empty($baleRollCount) && count($baleRollLengths) > 0)
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Measured Length of Each Roll (Meters) *</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                @foreach($baleRollLengths as $i => $len)
                                    <div class="p-3 bg-surface-container-lowest border border-outline-variant/40 rounded-xl space-y-1">
                                        <label class="block text-[10px] font-extrabold text-on-surface-variant uppercase">Roll #{{ $i + 1 }}</label>
                                        <div class="relative">
                                            <input type="number" step="0.01" min="0.01" wire:model.live="baleRollLengths.{{ $i }}" placeholder="0.00" class="w-full bg-surface border border-outline-variant/60 rounded-lg pl-3 pr-8 py-2 text-xs font-bold text-primary">
                                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[9px] text-outline font-bold">m</span>
                                        </div>
                                        @error("baleRollLengths.{$i}") <p class="text-[10px] font-bold text-error mt-0.5">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @php
                            $measuredTotal = array_sum(array_map('floatval', array_filter($baleRollLengths, fn($v) => $v !== '' && $v !== null)));
                        @endphp
                        <div class="p-4 bg-primary/5 border border-primary/20 rounded-2xl flex justify-between items-center text-xs">
                            <span class="font-bold text-on-surface">Total Measured Rolls Length:</span>
                            <span class="font-black text-secondary text-base font-mono">{{ number_format($measuredTotal, 2) }}m</span>
                        </div>

                        @if($baleMismatchWarning)
                            <div class="p-4 bg-amber-500/10 border border-amber-500/30 text-amber-900 rounded-2xl text-xs font-medium space-y-1">
                                <div class="flex items-center gap-1 font-bold text-amber-800">
                                    <span class="material-symbols-outlined text-[16px]">warning</span> Discrepancy Notice
                                </div>
                                <p>{{ $baleMismatchWarning }}</p>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40">
                    <button type="button" wire:click="$set('showOpenBaleModal', false)" class="px-5 py-2.5 rounded-xl border border-outline-variant/60 text-xs font-bold text-on-surface hover:bg-surface-container">Cancel</button>
                    <button type="button" wire:click="submitOpenedBaleForm" class="px-6 py-2.5 rounded-xl bg-primary text-on-primary text-xs font-extrabold shadow-md hover:bg-primary-container transition-all" {{ empty($baleRollCount) ? 'disabled' : '' }}>Save & Open Bale</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Mismatch Confirmation Warning Modal Overlay -->
    @if($showMismatchConfirmationModal && $activeBaleIdToOpen)
        @php
            $baleToConfirm = \App\Models\InventoryBale::find($activeBaleIdToOpen);
            $sumRecorded = array_sum(array_map('floatval', array_filter($baleRollLengths, fn($v) => $v !== '' && $v !== null)));
            $declaredLen = (float) ($baleToConfirm?->declared_length ?? 0);
            $diffVal = round($sumRecorded - $declaredLen, 2);
            $signVal = $diffVal > 0 ? "+{$diffVal}" : "{$diffVal}";
        @endphp
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center z-[60] p-4">
            <div class="bg-surface border border-amber-500/40 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6">
                <div class="flex items-center gap-3 text-amber-700">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-2xl">warning</span>
                    </div>
                    <div>
                        <h4 class="font-headline-sm text-lg font-black text-amber-900">Length Discrepancy Warning</h4>
                        <p class="text-xs text-amber-800 font-medium">Bale {{ $baleToConfirm?->bale_number }} Roll Measurement Mismatch</p>
                    </div>
                </div>

                <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 text-xs space-y-2 text-amber-950">
                    <div class="flex justify-between font-medium">
                        <span>Declared Purchase Length:</span>
                        <span class="font-bold">{{ number_format($declaredLen, 2) }}m</span>
                    </div>
                    <div class="flex justify-between font-medium">
                        <span>Sum of Measured Rolls:</span>
                        <span class="font-extrabold text-amber-900">{{ number_format($sumRecorded, 2) }}m</span>
                    </div>
                    <div class="h-px bg-amber-200 my-1"></div>
                    <div class="flex justify-between font-extrabold text-sm text-amber-900">
                        <span>Net Difference:</span>
                        <span>{{ $signVal }}m</span>
                    </div>
                </div>

                <p class="text-xs text-on-surface-variant leading-relaxed">
                    The measured total roll length (<strong>{{ number_format($sumRecorded, 2) }}m</strong>) does not match the recorded purchase length (<strong>{{ number_format($declaredLen, 2) }}m</strong>).
                    <br><br>
                    This measured length of <strong>{{ number_format($sumRecorded, 2) }}m</strong> will be recorded as the actual genuine stock balance for material calculations. Do you want to proceed?
                </p>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button type="button" wire:click="$set('showMismatchConfirmationModal', false)" class="flex-1 px-4 py-3 rounded-xl border border-outline-variant/60 text-xs font-bold text-on-surface hover:bg-surface-container transition-all">
                        Go Back & Review Rolls
                    </button>
                    <button type="button" wire:click="saveOpenedBale" class="flex-1 px-4 py-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-extrabold shadow-md transition-all">
                        Insist & Save Measured ({{ number_format($sumRecorded, 2) }}m)
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
