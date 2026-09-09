<div>
    <!-- Header Section -->
    <div class="mb-8">
        <p class="font-label-md text-label-md font-bold uppercase tracking-wider text-amber-800 mb-1">
            FACTORY ADMINISTRATION · SCRAP AUDIT
        </p>
        <h2 class="font-headline-lg text-headline-lg text-on-surface font-extrabold flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-[32px]" data-icon="delete_sweep">delete_sweep</span>
            Wastage & Scrap Log
        </h2>
        <p class="font-body-md text-body-md text-on-surface-variant mt-1">
            Trace unaccounted item losses and scrap generated during final production batch completions.
        </p>
    </div>

    <!-- Top KPI Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Card 1: TOTAL WASTAGE LOGGED -->
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6 shadow-2xs space-y-2">
            <h3 class="font-label-md text-label-md font-bold uppercase tracking-wider text-on-surface-variant">
                Total Wastage Logged
            </h3>
            <div class="font-mono font-headline-lg text-headline-lg font-extrabold text-on-surface">
                {{ number_format($totalWastageQty, 0) }} <span class="font-sans font-body-md text-body-md font-normal text-on-surface-variant">Pcs</span>
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant">
                Across all production runs
            </p>
        </div>

        <!-- Card 2: LOSS INCIDENTS RECORDED -->
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6 shadow-2xs space-y-2">
            <h3 class="font-label-md text-label-md font-bold uppercase tracking-wider text-on-surface-variant">
                Loss Incidents Recorded
            </h3>
            <div class="font-mono font-headline-lg text-headline-lg font-extrabold text-on-surface">
                {{ number_format($lossIncidentsCount) }}
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant">
                Production batches impacted
            </p>
        </div>

        <!-- Card 3: AVG LOSS RATE -->
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6 shadow-2xs space-y-2">
            <h3 class="font-label-md text-label-md font-bold uppercase tracking-wider text-on-surface-variant">
                Avg Loss Rate
            </h3>
            <div class="font-mono font-headline-lg text-headline-lg font-extrabold text-amber-600">
                {{ number_format($avgLossRate, 1) }}%
            </div>
            <p class="font-body-sm text-body-sm text-on-surface-variant">
                Of batch targets
            </p>
        </div>
    </div>

    <!-- Main Table Container Card -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant p-6 shadow-2xs space-y-6">
        <!-- Title & Filter Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h3 class="font-label-md text-label-md font-bold uppercase tracking-wider text-on-surface">
                Recorded Wastage & Loss Incidents
            </h3>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Search Box -->
                <div class="relative min-w-[240px]">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-on-surface-variant text-[20px]">search</span>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search" 
                        class="w-full pl-9 pr-4 py-2 bg-surface border border-outline-variant rounded-lg font-body-sm text-body-sm focus:ring-1 focus:ring-primary"
                        placeholder="Search ID, Batch, Product, Reason..."
                    />
                </div>

                <!-- Stage Filter -->
                <select wire:model.live="selectedTask" class="bg-surface border border-outline-variant rounded-lg font-body-sm text-body-sm px-3.5 py-2 focus:ring-1 focus:ring-primary">
                    <option value="">All Stages</option>
                    @foreach($tasks as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-xl border border-outline-variant">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant">
                        <th class="px-5 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Wastage ID</th>
                        <th class="px-5 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Source Batch</th>
                        <th class="px-5 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Manufacturing Product</th>
                        <th class="px-5 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-center">Wastage Qty</th>
                        <th class="px-5 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Stage Lost</th>
                        <th class="px-5 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Logged Date</th>
                        <th class="px-5 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Notes / Cause</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($wastages as $wItem)
                        <tr class="hover:bg-surface-container transition-colors" wire:key="wastage-{{ $wItem->id }}">
                            <td class="px-5 py-4 font-mono font-bold font-body-sm text-body-sm text-on-surface">
                                WST-{{ $wItem->created_at->format('Y') }}-{{ str_pad((string) $wItem->id, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-5 py-4 font-mono font-bold font-body-sm text-body-sm text-primary">
                                {{ $wItem->productionJob?->production_batch_id ?: ($wItem->productionJob?->batch?->batch_code ?: $wItem->job_code) }}
                            </td>
                            <td class="px-5 py-4 font-body-md text-body-md font-semibold text-on-surface">
                                {{ $wItem->manufacturingProduct?->name ?: ($wItem->productionJob?->manufacturingProduct?->name ?: 'N/A') }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="px-3 py-1 rounded-full font-mono font-bold font-label-sm text-label-sm bg-amber-500/10 text-amber-900 border border-amber-500/30">
                                    {{ number_format($wItem->quantity_wasted, 0) }} Pcs
                                </span>
                            </td>
                            <td class="px-5 py-4 font-body-sm text-body-sm text-on-surface font-medium">
                                {{ $wItem->task?->name ?: 'Production Stage' }}
                            </td>
                            <td class="px-5 py-4 font-mono font-body-sm text-body-sm text-on-surface-variant">
                                {{ $wItem->created_at->format('Y-m-d') }}
                            </td>
                            <td class="px-5 py-4 font-body-sm text-body-sm text-on-surface-variant max-w-xs truncate">
                                {{ $wItem->reason ?: 'Unaccounted scrap during final batch completion' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center font-body-md text-body-md text-on-surface-variant italic">
                                No recorded wastage or loss incidents found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($wastages->hasPages())
            <div class="pt-2">
                {{ $wastages->links() }}
            </div>
        @endif
    </div>
</div>
