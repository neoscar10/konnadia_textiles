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
</div>
