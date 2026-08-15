<div>
    <!-- Back Navigation & Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.production.batches.jobs', $batch->batch_code) }}" wire:navigate class="text-primary font-bold text-xs flex items-center gap-1 hover:underline">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Back to Batch Jobs
                </a>
                <span class="text-outline text-xs font-bold">• 360-Degree Cost & Batch Ledger</span>
            </div>
            <div class="flex items-center gap-3">
                <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">{{ $batch->batch_code }}</h2>
                <span class="px-3 py-1 font-bold text-xs rounded-xl uppercase tracking-wider {{ $batch->status === 'Completed' ? 'bg-secondary text-white' : 'bg-primary text-white' }}">
                    {{ $batch->status }}
                </span>
                @if($batch->parentBatch)
                    <a href="{{ route('admin.production.batches.ledger', $batch->parentBatch->id) }}" wire:navigate class="px-3 py-1 bg-amber-500/10 text-amber-700 font-mono font-bold text-xs rounded-xl border border-amber-500/30 hover:underline">
                        Child Batch of: {{ $batch->parentBatch->batch_code }}
                    </a>
                @endif
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <div class="flex items-center gap-2 bg-surface border border-outline-variant/60 rounded-xl px-3 py-1.5 shadow-xs">
                <span class="material-symbols-outlined text-on-surface-variant text-[18px]">filter_list</span>
                <select wire:model.live="selectedJobId" class="bg-transparent border-none text-xs font-bold text-primary focus:ring-0 p-0 cursor-pointer outline-none">
                    <option value="">Overall Batch Ledger</option>
                    @foreach($batch->jobs as $j)
                        <option value="{{ $j->id }}">{{ $j->job_code }} - {{ $j->manufacturingProduct?->name ?? 'Product' }} ({{ $j->task?->name ?? 'Stage' }})</option>
                    @endforeach
                </select>
            </div>

            <button type="button" wire:click="evaluateBatchCompletion" class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-xs bg-primary text-on-primary shadow-xs hover:bg-primary-container transition-all active:scale-95">
                <span class="material-symbols-outlined text-[18px]">verified</span>
                Recalculate & Evaluate
            </button>
        </div>
    </div>

    <!-- Conversion Readiness Banner -->
    @if($batch->isReadyForConversion())
        <div class="bg-secondary-container/20 border border-secondary/40 rounded-2xl p-6 mb-6 shadow-xs flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-secondary/15 flex items-center justify-center shrink-0 mt-0.5">
                    <span class="material-symbols-outlined text-secondary text-2xl">verified</span>
                </div>
                <div>
                    <h4 class="font-extrabold text-secondary text-sm">Manufacturing Complete — Ready for Conversion</h4>
                    <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                        All routing stages have completed successfully. Convert these completed WIP items to finished goods storefront stock.
                    </p>
                </div>
            </div>
            <a href="{{ route('factory.batches.convert', $batch->id) }}" wire:navigate class="inline-flex items-center gap-2 bg-secondary hover:bg-secondary-container text-on-secondary px-6 py-3 rounded-xl font-bold text-xs shadow-md transition-all active:scale-95">
                <span class="material-symbols-outlined text-[18px]">autofps_select</span>
                Convert to Finished Goods
            </a>
        </div>
    @elseif($batch->is_converted)
        <div class="bg-primary/5 border border-primary/20 rounded-2xl p-5 mb-6 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-primary text-2xl">check_circle</span>
            </div>
            <div>
                <h4 class="font-extrabold text-primary text-sm">Converted to Storefront Stock</h4>
                <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                    This production batch has been successfully finalized and converted into finished goods.
                </p>
            </div>
        </div>
    @endif

    <!-- TOP FINANCIAL METRIC SUMMARY CARDS (STITCH DESIGN) -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        <!-- Card 1: Total Manufacturing Cost -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-[11px] font-extrabold text-primary uppercase tracking-wider">Total Manufacturing Cost</span>
                    <span class="material-symbols-outlined text-secondary text-[22px]">payments</span>
                </div>
                <h3 class="text-2xl font-black text-secondary tracking-tight">
                    ₹{{ number_format($costSummary['total_manufacturing_cost'], 2) }}
                </h3>
            </div>
            <div class="pt-3 border-t border-outline-variant/30 mt-3 text-[11px] text-outline font-medium">
                <span>Materials + Labor + Wastage</span>
            </div>
        </div>

        <!-- Card 2: Average Cost per Finished Unit -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-[11px] font-extrabold text-primary uppercase tracking-wider">Avg Cost / Unit</span>
                    <span class="material-symbols-outlined text-primary text-[22px]">calculate</span>
                </div>
                <h3 class="text-2xl font-black text-primary tracking-tight">
                    ₹{{ number_format($costSummary['average_cost_per_unit'], 2) }}
                </h3>
            </div>
            <div class="pt-3 border-t border-outline-variant/30 mt-3 text-[11px] text-outline font-medium">
                <span>Per Finished Unit ({{ number_format($costSummary['finished_units']) }} Pcs)</span>
            </div>
        </div>

        <!-- Card 3: Raw Material Cost -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider">Raw Material Cost</span>
                    <span class="material-symbols-outlined text-secondary text-[22px]">inventory_2</span>
                </div>
                <h3 class="text-xl font-black text-primary tracking-tight">
                    ₹{{ number_format($costSummary['total_material_cost'], 2) }}
                </h3>
            </div>
            <div class="pt-3 border-t border-outline-variant/30 mt-3 text-[10px] text-outline font-medium truncate">
                <span>Fabric: <strong>₹{{ number_format($costSummary['fabric_cost'], 2) }}</strong></span>
            </div>
        </div>

        <!-- Card 4: Labor Wages Cost -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider">Total Labor Wages</span>
                    <span class="material-symbols-outlined text-primary text-[22px]">engineering</span>
                </div>
                <h3 class="text-xl font-black text-primary tracking-tight">
                    ₹{{ number_format($costSummary['total_labor_cost'], 2) }}
                </h3>
            </div>
            <div class="pt-3 border-t border-outline-variant/30 mt-3 text-[10px] text-outline font-medium">
                <span>Piece-Rate Stage Allocations</span>
            </div>
        </div>

        <!-- Card 5: Production Loss & Wastage Cost -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-[11px] font-extrabold text-error uppercase tracking-wider">Wastage Loss Cost</span>
                    <span class="material-symbols-outlined text-error text-[22px]">report_problem</span>
                </div>
                <h3 class="text-xl font-black text-error tracking-tight">
                    ₹{{ number_format($costSummary['total_wastage_cost'], 2) }}
                </h3>
            </div>
            <div class="pt-3 border-t border-outline-variant/30 mt-3 text-[10px] text-error/80 font-medium">
                <span>Weighted Fabric Loss</span>
            </div>
        </div>
    </div>

    <!-- COST BREAKDOWN TABS & TABLES (MATERIALS, LABOR, WASTAGE) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- 1. RAW MATERIAL COSTS BY CATEGORY -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs">
            <h4 class="font-headline-sm text-headline-sm text-primary font-bold mb-3 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">inventory_2</span>
                    Material Costs by Category
                </span>
                <span class="text-xs font-black text-secondary">₹{{ number_format($costSummary['total_material_cost'], 2) }}</span>
            </h4>
            <div class="space-y-3 mb-4">
                <div class="p-3 rounded-xl bg-surface border border-outline-variant/40 flex justify-between items-center text-xs">
                    <div>
                        <p class="font-bold text-primary">Fabric Material</p>
                        <p class="text-[10px] text-outline">Main Body Fabric & Textile</p>
                    </div>
                    <span class="font-black text-on-surface text-sm">₹{{ number_format($costSummary['fabric_cost'], 2) }}</span>
                </div>

                <div class="p-3 rounded-xl bg-surface border border-outline-variant/40 flex justify-between items-center text-xs">
                    <div>
                        <p class="font-bold text-primary">Subsidiary Raw Materials</p>
                        <p class="text-[10px] text-outline">Threads, Zippers, Trimmings, Buttons</p>
                    </div>
                    <span class="font-black text-on-surface text-sm">₹{{ number_format($costSummary['subsidiary_cost'], 2) }}</span>
                </div>

                <div class="p-3 rounded-xl bg-surface border border-outline-variant/40 flex justify-between items-center text-xs">
                    <div>
                        <p class="font-bold text-primary">Packaging Materials</p>
                        <p class="text-[10px] text-outline">Poly Bags, Cartons, Tags & Labels</p>
                    </div>
                    <span class="font-black text-on-surface text-sm">₹{{ number_format($costSummary['packaging_cost'], 2) }}</span>
                </div>

                <div class="p-3 rounded-xl bg-surface border border-outline-variant/40 flex justify-between items-center text-xs">
                    <div>
                        <p class="font-bold text-primary">General Overheads / Consumables</p>
                        <p class="text-[10px] text-outline">Machine oil, cleaners, factory consumables</p>
                    </div>
                    <span class="font-black text-on-surface text-sm">₹{{ number_format($costSummary['overhead_cost'], 2) }}</span>
                </div>
            </div>

            <!-- Itemized Overhead Breakdown -->
            @php
                $overheadAllocations = DB::table('overhead_cost_allocations')
                    ->where('production_batch_id', $batch->id)
                    ->join('raw_materials', 'overhead_cost_allocations.raw_material_id', '=', 'raw_materials.id')
                    ->select('raw_materials.name', 'overhead_cost_allocations.allocated_quantity', 'overhead_cost_allocations.allocated_cost')
                    ->get();
            @endphp
            @if($overheadAllocations->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-outline-variant/30">
                    <h5 class="text-[11px] font-extrabold text-primary uppercase tracking-wider mb-2">Overhead Itemization</h5>
                    <div class="space-y-1.5 max-h-[120px] overflow-y-auto pr-1">
                        @foreach($overheadAllocations as $alloc)
                            <div class="flex justify-between items-center text-[10px] text-on-surface-variant">
                                <span>{{ $alloc->name }} ({{ number_format($alloc->allocated_quantity, 2) }})</span>
                                <span class="font-bold">₹{{ number_format($alloc->allocated_cost, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- 2. STAGE PIECE-RATE LABOR WAGES -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs">
            <h4 class="font-headline-sm text-headline-sm text-primary font-bold mb-3 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">engineering</span>
                    Piece-Rate Labor Wages
                </span>
                <span class="text-xs font-black text-primary">₹{{ number_format($costSummary['total_labor_cost'], 2) }}</span>
            </h4>
            <div class="overflow-y-auto max-h-[220px] space-y-2 pr-1">
                @forelse($costSummary['labor_details']['allocations'] as $alloc)
                    <div class="p-3 rounded-xl bg-surface border border-outline-variant/40 flex justify-between items-center text-xs">
                        <div>
                            <p class="font-bold text-on-surface">{{ $alloc->labor?->name }} ({{ $alloc->labor?->code }})</p>
                            <p class="text-[10px] text-outline">Stage: {{ $alloc->task?->name }} • {{ number_format($alloc->quantity_processed) }} Pcs</p>
                        </div>
                        <span class="font-black text-secondary text-sm">₹{{ number_format((float)$alloc->calculated_wage, 2) }}</span>
                    </div>
                @empty
                    <p class="text-xs text-outline italic text-center py-6">No labor wage allocations recorded yet.</p>
                @endforelse
            </div>
        </div>

        <!-- 3. FABRIC WASTAGE WEIGHTED ALLOCATION -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs">
            <h4 class="font-headline-sm text-headline-sm text-error font-bold mb-3 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-error">report_problem</span>
                    Fabric Wastage Weighted Cost
                </span>
                <span class="text-xs font-black text-error">₹{{ number_format($costSummary['total_wastage_cost'], 2) }}</span>
            </h4>
            <div class="overflow-y-auto max-h-[220px] space-y-2 pr-1">
                @forelse($costSummary['wastage_details']['wastage_log'] as $wLog)
                    <div class="p-3 rounded-xl bg-error-container/10 border border-error/20 flex justify-between items-center text-xs">
                        <div>
                            <p class="font-bold text-error">{{ $wLog['product_name'] }}</p>
                            <p class="text-[10px] text-on-surface-variant">Stage: {{ $wLog['task_name'] }} • Qty: {{ number_format($wLog['quantity_wasted'], 2) }}</p>
                        </div>
                        <span class="font-black text-error text-sm">₹{{ number_format($wLog['total_cost'], 2) }}</span>
                    </div>
                @empty
                    <p class="text-xs text-outline italic text-center py-6">No production loss or fabric wastage logged.</p>
                @endforelse
            </div>
        </div>
    </div>

    @if(empty($selectedJobId))
    <!-- LINKED JOBS STAGE EXECUTION AUDIT TABLE -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 sm:p-6 shadow-xs mb-8">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h3 class="font-headline-sm text-headline-sm text-primary font-bold">
                    Linked Production Jobs & Stage Workflow
                </h3>
                <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                    Sequential manufacturing stage jobs belonging to Batch <span class="font-bold text-primary">{{ $batch->batch_code }}</span>.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-body-md">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant/60 text-xs text-on-surface-variant uppercase tracking-wider">
                        <th class="px-4 py-3 font-bold">Job Code</th>
                        <th class="px-4 py-3 font-bold">Stage Task</th>
                        <th class="px-4 py-3 font-bold text-center">Status</th>
                        <th class="px-4 py-3 font-bold text-center">Target Qty</th>
                        <th class="px-4 py-3 font-bold text-center">Output Processed</th>
                        <th class="px-4 py-3 font-bold text-right">Labor Wages</th>
                        <th class="px-4 py-3 font-bold text-right">Material Cost</th>
                        <th class="px-4 py-3 font-bold text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($batch->jobs as $job)
                        @php
                            $jobWageCost = (float) $job->allocations()->sum('calculated_wage');
                            $jobMatCost = (float) $job->materialConsumptions()->sum('total_cost');
                            $jobOutput = (int) $job->allocations()->where('task_id', $job->task_id)->sum('quantity_processed');
                        @endphp
                        <tr class="hover:bg-surface-container/50 transition-colors">
                            <td class="px-4 py-3.5 font-mono font-bold text-primary text-sm">
                                {{ $job->job_code }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-bold text-on-surface text-sm">{{ $job->task?->name ?? 'Stage Task' }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $job->status === 'completed' ? 'bg-secondary/10 text-secondary' : ($job->status === 'in_progress' ? 'bg-primary/10 text-primary' : 'bg-surface-container-high text-on-surface-variant') }}">
                                    {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center font-bold text-on-surface text-sm">
                                {{ number_format($job->target_quantity) }} Pcs
                            </td>
                            <td class="px-4 py-3.5 text-center font-black text-primary text-sm">
                                {{ number_format($jobOutput) }} Pcs
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-secondary text-sm">
                                ₹{{ number_format($jobWageCost, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-primary text-sm">
                                ₹{{ number_format($jobMatCost, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-center space-x-2">
                                <a href="#" wire:click.prevent="$set('selectedJobId', '{{ $job->id }}')" class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 hover:underline">
                                    <span class="material-symbols-outlined text-[16px]">receipt_long</span>
                                    <span>Ledger</span>
                                </a>
                                <a href="{{ route('admin.production.jobs.show', $job->id) }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                                    <span>Terminal</span>
                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-on-surface-variant text-sm">
                                No production jobs created yet for this batch.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- LINKED CHILD PRODUCTION BATCHES SECTION (DERIVED FROM ALTERATIONS) -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 sm:p-6 shadow-xs">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h3 class="font-headline-sm text-headline-sm text-amber-800 font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-600">alt_route</span>
                    Linked Child Production Batches (Derived from Alterations)
                </h3>
                <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                    Independent child production batches created when items in Batch <span class="font-bold text-primary">{{ $batch->batch_code }}</span> were converted to new target SKUs.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-body-md">
                <thead>
                    <tr class="bg-amber-500/10 border-b border-outline-variant/60 text-xs text-amber-900 uppercase tracking-wider">
                        <th class="px-4 py-3 font-bold">Child Batch Code</th>
                        <th class="px-4 py-3 font-bold">Target Manufacturing Product</th>
                        <th class="px-4 py-3 font-bold text-center">Status</th>
                        <th class="px-4 py-3 font-bold text-center">Planned Qty</th>
                        <th class="px-4 py-3 font-bold text-right">View Child 360 Ledger</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($batch->childBatches as $child)
                        <tr class="hover:bg-amber-500/5 transition-colors">
                            <td class="px-4 py-3.5">
                                <span class="px-3 py-1 bg-amber-500/20 text-amber-900 font-mono font-black text-xs rounded-xl border border-amber-500/30">
                                    {{ $child->batch_code }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="font-bold text-primary text-sm">{{ $child->manufacturingProduct?->name }}</p>
                                <span class="text-xs text-outline font-mono">{{ $child->manufacturingProduct?->code }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-800">
                                    {{ $child->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center font-black text-amber-800 text-sm">
                                {{ number_format($child->planned_quantity) }} Pcs
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('admin.production.batches.ledger', $child->id) }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-bold text-amber-800 hover:underline">
                                    <span>Child 360 Ledger</span>
                                    <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-on-surface-variant text-sm">
                                No child alteration batches have been derived from this batch yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
