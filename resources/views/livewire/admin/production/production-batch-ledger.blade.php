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

    <!-- Tab Navigation -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden mb-6 flex overflow-x-auto">
        <button type="button" wire:click="$set('activeTab', 'financials')" class="px-6 py-4 font-label-md whitespace-nowrap font-bold text-sm flex-1 {{ $activeTab === 'financials' ? 'text-primary border-b-2 border-primary bg-surface-container-low/50' : 'text-on-surface-variant hover:text-primary transition-colors' }}">
            <span class="flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">payments</span>
                1. Financial Rollup
            </span>
        </button>
        <button type="button" wire:click="$set('activeTab', 'workers')" class="px-6 py-4 font-label-md whitespace-nowrap font-bold text-sm flex-1 {{ $activeTab === 'workers' ? 'text-secondary border-b-2 border-secondary bg-surface-container-low/50' : 'text-on-surface-variant hover:text-secondary transition-colors' }}">
            <span class="flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">engineering</span>
                2. Worker Earning Audit
            </span>
        </button>
        <button type="button" wire:click="$set('activeTab', 'rolls')" class="px-6 py-4 font-label-md whitespace-nowrap font-bold text-sm flex-1 {{ $activeTab === 'rolls' ? 'text-tertiary border-b-2 border-tertiary bg-surface-container-low/50' : 'text-on-surface-variant hover:text-tertiary transition-colors' }}">
            <span class="flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">texture</span>
                3. Roll Cost & Ledger
            </span>
        </button>
        <button type="button" wire:click="$set('activeTab', 'wastage')" class="px-6 py-4 font-label-md whitespace-nowrap font-bold text-sm flex-1 {{ $activeTab === 'wastage' ? 'text-error border-b-2 border-error bg-surface-container-low/50' : 'text-on-surface-variant hover:text-error transition-colors' }}">
            <span class="flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">report_problem</span>
                4. Wastage Allocation
            </span>
        </button>
    </div>

    <!-- Tab 1: Financials -->
    @if($activeTab === 'financials')
    <div class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- RAW MATERIAL COSTS BY CATEGORY -->
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

            <!-- STAGE PIECE-RATE LABOR WAGES -->
            <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs">
                <h4 class="font-headline-sm text-headline-sm text-primary font-bold mb-3 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">engineering</span>
                        Piece-Rate Labor Wages
                    </span>
                    <span class="text-xs font-black text-primary">₹{{ number_format($costSummary['total_labor_cost'], 2) }}</span>
                </h4>
                <div class="overflow-y-auto max-h-[280px] space-y-2 pr-1">
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

            <!-- FABRIC WASTAGE WEIGHTED ALLOCATION -->
            <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs">
                <h4 class="font-headline-sm text-headline-sm text-error font-bold mb-3 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-error">report_problem</span>
                        Fabric Wastage Weighted Cost
                    </span>
                    <span class="text-xs font-black text-error">₹{{ number_format($costSummary['total_wastage_cost'], 2) }}</span>
                </h4>
                <div class="overflow-y-auto max-h-[280px] space-y-2 pr-1">
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
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 sm:p-6 shadow-xs">
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
                                <td class="px-4 py-3.5 text-center space-x-2 whitespace-nowrap">
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
    @endif

    <!-- Tab 2: Worker Earning Audit -->
    @if($activeTab === 'workers')
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-outline-variant/30">
            <div>
                <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Worker Earnings & Performance Audit</h3>
                <p class="text-xs text-on-surface-variant font-medium mt-0.5">Filter by factory worker to review their stage allocations and wages earned on this batch.</p>
            </div>
            
            <div class="flex items-center gap-2 bg-surface border border-outline-variant/60 rounded-xl px-3 py-1.5 shadow-xs w-full sm:w-auto">
                <span class="material-symbols-outlined text-on-surface-variant text-[18px]">person</span>
                <select wire:model.live="filterWorkerId" class="bg-transparent border-none text-xs font-bold text-primary focus:ring-0 p-0 cursor-pointer outline-none w-full sm:w-64">
                    <option value="">-- Select Worker --</option>
                    @foreach($batchWorkers as $worker)
                        <option value="{{ $worker->id }}">{{ $worker->name }} ({{ $worker->code }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($workerData)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Earning Card -->
                <div class="bg-secondary-container/10 border border-secondary/20 p-5 rounded-2xl flex items-center justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-secondary tracking-widest block">Total Wages Earned</span>
                        <span class="text-3xl font-black text-secondary block mt-1">₹{{ number_format($workerData['total_earnings'], 2) }}</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">payments</span>
                    </div>
                </div>

                <!-- Performance Card -->
                <div class="bg-primary/5 border border-primary/20 p-5 rounded-2xl flex items-center justify-between">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-primary tracking-widest block">Total Pieces Processed</span>
                        <span class="text-3xl font-black text-primary block mt-1">{{ number_format($workerData['total_pieces']) }} Pcs</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-2xl">engineering</span>
                    </div>
                </div>
            </div>

            <!-- Detail Allocations Table -->
            <div class="pt-4">
                <h4 class="font-bold text-sm text-primary mb-3">Itemized Allocations & Wages</h4>
                <div class="overflow-x-auto border border-outline-variant/60 rounded-xl">
                    <table class="w-full text-left border-collapse font-body-md">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/60 text-xs text-on-surface-variant uppercase tracking-wider">
                                <th class="px-4 py-3 font-bold">Job Stage</th>
                                <th class="px-4 py-3 font-bold text-center">Product SKU</th>
                                <th class="px-4 py-3 font-bold text-center">Quantity Processed</th>
                                <th class="px-4 py-3 font-bold text-right">Wage Calculation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @foreach($workerData['allocations'] as $alloc)
                                <tr class="hover:bg-surface-container/50 transition-colors">
                                    <td class="px-4 py-3.5">
                                        <p class="font-bold text-on-surface text-sm">{{ $alloc->productionJob?->task?->name ?? 'Stage' }}</p>
                                        <span class="text-xs text-outline font-mono">Job: {{ $alloc->productionJob?->job_code }}</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-center font-semibold text-xs text-on-surface-variant">
                                        {{ $alloc->productionJob?->manufacturingProduct?->name }} ({{ $alloc->productionJob?->manufacturingProduct?->code }})
                                    </td>
                                    <td class="px-4 py-3.5 text-center font-bold text-sm text-primary">
                                        {{ number_format($alloc->quantity_processed) }} Pcs
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-black text-secondary text-sm">
                                        @if(is_null($alloc->calculated_wage))
                                            <span class="text-xs text-outline italic font-normal">Salaried (Fixed)</span>
                                        @else
                                            ₹{{ number_format($alloc->calculated_wage, 2) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="p-12 text-center border-2 border-dashed border-outline-variant/60 rounded-2xl">
                <span class="material-symbols-outlined text-4xl text-outline mb-2">engineering</span>
                <p class="text-sm font-semibold text-on-surface-variant">Please select a worker to load their earning metrics and details.</p>
            </div>
        @endif
    </div>
    @endif

    <!-- Tab 3: Roll Cost & Ledger -->
    @if($activeTab === 'rolls')
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-outline-variant/30">
            <div>
                <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Roll-Level Product Costing Ledger</h3>
                <p class="text-xs text-on-surface-variant font-medium mt-0.5">Filter by fabric roll to audit exact material yields, labor allocations, and wastage costs linked to that roll.</p>
            </div>
            
            <div class="flex items-center gap-2 bg-surface border border-outline-variant/60 rounded-xl px-3 py-1.5 shadow-xs w-full sm:w-auto">
                <span class="material-symbols-outlined text-on-surface-variant text-[18px]">texture</span>
                <select wire:model.live="filterRollId" class="bg-transparent border-none text-xs font-bold text-primary focus:ring-0 p-0 cursor-pointer outline-none w-full sm:w-64">
                    <option value="">-- Select Fabric Roll --</option>
                    @foreach($batchRolls as $r)
                        <option value="{{ $r->id }}">Bale: {{ $r->bale?->bale_number }} · Roll: {{ $r->roll_number }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Master All Rolls Audit Table -->
        <div class="pt-2">
            <div class="flex justify-between items-center mb-3">
                <h4 class="font-bold text-sm text-primary uppercase tracking-wider">All Fabric Rolls Summary Audit ({{ count($allRollsSummary) }} Rolls Used)</h4>
                @if(!empty($filterRollId))
                    <button type="button" wire:click="$set('filterRollId', '')" class="text-xs font-bold text-secondary hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">clear</span> Show All Rolls
                    </button>
                @endif
            </div>
            <div class="overflow-x-auto border border-outline-variant/60 rounded-xl bg-surface-container-lowest shadow-xs">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant/60 text-[10px] text-on-surface-variant uppercase font-bold">
                            <th class="px-4 py-3">Bale & Roll #</th>
                            <th class="px-4 py-3 text-center">Fabric Consumed</th>
                            <th class="px-4 py-3 text-center">Output Yield</th>
                            <th class="px-4 py-3 text-center text-error">Wastage Qty / Roll</th>
                            <th class="px-4 py-3 text-right text-error">Wastage Cost / Roll</th>
                            <th class="px-4 py-3 text-right">Total Roll Cost</th>
                            <th class="px-4 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @forelse($allRollsSummary as $rSum)
                            <tr class="hover:bg-surface-container/40 transition-colors {{ (int)$filterRollId === (int)$rSum['roll_id'] ? 'bg-primary/10 font-bold' : '' }}">
                                <td class="px-4 py-3">
                                    <span class="font-extrabold text-on-surface text-sm">Roll #{{ $rSum['roll_number'] }}</span>
                                    <span class="text-[10px] text-outline font-mono block">Bale: {{ $rSum['bale_number'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-secondary">
                                    {{ number_format($rSum['consumed_qty'], 2) }}
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-primary">
                                    {{ number_format($rSum['produced_qty']) }} Pcs
                                </td>
                                <td class="px-4 py-3 text-center font-bold text-error">
                                    {{ number_format($rSum['wasted_qty'], 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-black text-error">
                                    ₹{{ number_format($rSum['wastage_cost'], 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-black text-primary">
                                    ₹{{ number_format($rSum['total_roll_cost'] ?? $rSum['total_cost'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" wire:click="$set('filterRollId', {{ $rSum['roll_id'] }})" class="px-3 py-1 bg-primary/10 text-primary hover:bg-primary hover:text-white rounded-lg text-[11px] font-bold transition-all">
                                        Inspect →
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-outline italic">No fabric rolls linked or recorded for this batch.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($rollData)
            <!-- Roll KPI Cards -->
            <div class="pt-4 border-t border-outline-variant/30">
                <h4 class="font-bold text-sm text-primary uppercase tracking-wider mb-3">
                    Detailed Roll Inspection — Roll #{{ $rollData['roll']->roll_number }} (Bale: {{ $rollData['roll']->bale?->bale_number }})
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-surface-container-low border border-outline-variant/40 p-4 rounded-xl">
                        <span class="text-[10px] font-bold text-outline uppercase block">Material Cost</span>
                        <span class="text-xl font-extrabold text-primary block mt-0.5">₹{{ number_format($rollData['material_cost'], 2) }}</span>
                        <span class="text-[9px] text-on-surface-variant font-medium block mt-1">Consumed: {{ number_format($rollData['consumptions']->sum('quantity_consumed'), 2) }} units</span>
                    </div>
                    <div class="bg-surface-container-low border border-outline-variant/40 p-4 rounded-xl">
                        <span class="text-[10px] font-bold text-outline uppercase block">Labor Wages</span>
                        <span class="text-xl font-extrabold text-secondary block mt-0.5">₹{{ number_format($rollData['labor_cost'], 2) }}</span>
                        <span class="text-[9px] text-on-surface-variant font-medium block mt-1">Piece-rate wage allocations</span>
                    </div>
                    <div class="bg-surface-container-low border border-outline-variant/40 p-4 rounded-xl">
                        <span class="text-[10px] font-bold text-outline uppercase block">Roll Wastage Cost</span>
                        <span class="text-xl font-extrabold text-error block mt-0.5">₹{{ number_format($rollData['wastage_cost'], 2) }}</span>
                        <span class="text-[9px] text-error/85 font-medium block mt-1">Wasted: {{ number_format($rollData['wastages']->sum('quantity_wasted'), 2) }} units</span>
                    </div>
                    <div class="bg-primary/10 border border-primary/20 p-4 rounded-xl">
                        <span class="text-[10px] font-bold text-primary uppercase block">Total Cost / Produced</span>
                        <span class="text-xl font-black text-primary block mt-0.5">₹{{ number_format($rollData['total_cost'], 2) }}</span>
                        <span class="text-[10px] text-on-surface-variant font-bold block mt-1">Yield: {{ number_format($rollData['total_produced']) }} Pcs</span>
                    </div>
                </div>

                <!-- Roll Outputs, Consumptions and Wastages Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Products Produced -->
                    <div>
                        <h4 class="font-bold text-xs text-primary uppercase tracking-wider mb-2">Products Produced From Roll</h4>
                        <div class="overflow-x-auto border border-outline-variant/60 rounded-xl bg-surface-container-lowest">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-surface-container-low border-b border-outline-variant/60 text-[10px] text-on-surface-variant uppercase font-bold">
                                        <th class="px-3 py-2">Product Name</th>
                                        <th class="px-3 py-2 text-center">Qty Produced</th>
                                        <th class="px-3 py-2 text-right">Calculated Unit Cost</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/40">
                                    @forelse($rollData['outputs'] as $out)
                                        @php
                                            $proportion = $rollData['total_produced'] > 0 ? ($out->quantity_produced / $rollData['total_produced']) : 0;
                                            $apportionedCost = $rollData['total_cost'] * $proportion;
                                            $unitCost = $out->quantity_produced > 0 ? ($apportionedCost / $out->quantity_produced) : 0;
                                        @endphp
                                        <tr class="hover:bg-surface-container/30">
                                            <td class="px-3 py-2.5 font-bold">{{ $out->manufacturingProduct?->name }}</td>
                                            <td class="px-3 py-2.5 text-center font-extrabold text-primary">{{ number_format($out->quantity_produced) }} Pcs</td>
                                            <td class="px-3 py-2.5 text-right font-black text-secondary">₹{{ number_format($unitCost, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="px-3 py-6 text-center italic text-outline">No products recorded as produced from this roll yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Material Consumptions linked to Roll -->
                    <div>
                        <h4 class="font-bold text-xs text-primary uppercase tracking-wider mb-2">Fabric / Material Consumption</h4>
                        <div class="overflow-x-auto border border-outline-variant/60 rounded-xl bg-surface-container-lowest">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-surface-container-low border-b border-outline-variant/60 text-[10px] text-on-surface-variant uppercase font-bold">
                                        <th class="px-3 py-2">Material / Batch</th>
                                        <th class="px-3 py-2 text-center">Qty Consumed</th>
                                        <th class="px-3 py-2 text-right">Total Cost</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/40">
                                    @forelse($rollData['consumptions'] as $con)
                                        <tr class="hover:bg-surface-container/30">
                                            <td class="px-3 py-2.5 font-bold">
                                                {{ $con->inventoryBatch?->rawMaterial?->name }}
                                                <span class="text-[10px] text-outline block">Batch: {{ $con->inventoryBatch?->batch_number }}</span>
                                            </td>
                                            <td class="px-3 py-2.5 text-center font-extrabold">{{ number_format($con->quantity_consumed, 2) }} {{ $con->inventoryBatch?->unit }}</td>
                                            <td class="px-3 py-2.5 text-right font-black text-primary">₹{{ number_format($con->total_cost, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="px-3 py-6 text-center italic text-outline">No materials logged for this roll.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Roll Specific Wastages -->
                    <div>
                        <h4 class="font-bold text-xs text-error uppercase tracking-wider mb-2">Wastage Log for Selected Roll</h4>
                        <div class="overflow-x-auto border border-outline-variant/60 rounded-xl bg-surface-container-lowest">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-surface-container-low border-b border-outline-variant/60 text-[10px] text-on-surface-variant uppercase font-bold">
                                        <th class="px-3 py-2">Reason / Stage</th>
                                        <th class="px-3 py-2 text-center">Qty Wasted</th>
                                        <th class="px-3 py-2 text-right">Wastage Cost</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/40">
                                    @forelse($rollData['wastages'] as $w)
                                        @php
                                            $rRate = $rollData['roll']->bale?->unit_cost ?: 150.00;
                                            $rWCost = (float)$w->quantity_wasted * (float)$rRate;
                                        @endphp
                                        <tr class="hover:bg-error-container/10">
                                            <td class="px-3 py-2.5">
                                                <p class="font-bold text-on-surface">{{ $w->reason ?: 'Roll Cutting Scrap' }}</p>
                                                <span class="text-[10px] text-outline block">Stage: {{ $w->task?->name ?? 'Cutting' }}</span>
                                            </td>
                                            <td class="px-3 py-2.5 text-center font-extrabold text-error">{{ number_format($w->quantity_wasted, 2) }}</td>
                                            <td class="px-3 py-2.5 text-right font-black text-error">₹{{ number_format($rWCost, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="px-3 py-6 text-center italic text-outline">No wastage recorded for this roll.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    @endif

    <!-- Tab 4: Wastage Allocation -->
    @if($activeTab === 'wastage')
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs space-y-6">
        <div>
            <h3 class="font-headline-sm text-headline-sm text-error font-bold flex items-center gap-2">
                <span class="material-symbols-outlined text-error">report_problem</span>
                Fabric Wastage Allocation & Sharing Breakdowns
            </h3>
            <p class="text-xs text-on-surface-variant font-medium mt-0.5">Fabric scraps and alterations recorded during cutting are loaded as financial losses and pro-rated across the final completed output products.</p>
        </div>

        <!-- Sharing Rollup Card -->
        @php
            $finishedUnits = max(1, $costSummary['finished_units']);
            $wastageSharedPerUnit = $costSummary['total_wastage_cost'] / $finishedUnits;
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-error-container/10 border border-error/20 p-5 rounded-2xl">
                <span class="text-[10px] uppercase font-bold text-error tracking-wider block">Total Batch Wastage Loss</span>
                <span class="text-2xl font-black text-error block mt-1">₹{{ number_format($costSummary['total_wastage_cost'], 2) }}</span>
            </div>
            <div class="bg-surface-container-low border border-outline-variant/60 p-5 rounded-2xl">
                <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">Total Finished Units</span>
                <span class="text-2xl font-black text-primary block mt-1">{{ number_format($finishedUnits) }} Pcs</span>
            </div>
            <div class="bg-secondary-container/10 border border-secondary/20 p-5 rounded-2xl">
                <span class="text-[10px] uppercase font-bold text-secondary tracking-wider block">Wastage Shared Per Unit</span>
                <span class="text-2xl font-black text-secondary block mt-1">+₹{{ number_format($wastageSharedPerUnit, 2) }}</span>
                <span class="text-[9px] text-outline block mt-0.5">Added to the manufacturing cost of each product</span>
            </div>
        </div>

        <!-- Wastage Items Log -->
        <div class="pt-4">
            <h4 class="font-bold text-sm text-primary mb-3">Itemized Production Loss & Wastage Log</h4>
            <div class="overflow-x-auto border border-outline-variant/60 rounded-xl">
                <table class="w-full text-left border-collapse font-body-md">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant/60 text-xs text-on-surface-variant uppercase tracking-wider">
                            <th class="px-4 py-3 font-bold">Wastage Description & Stage</th>
                            <th class="px-4 py-3 font-bold text-center">Bale / Roll</th>
                            <th class="px-4 py-3 font-bold text-center">Quantity Wasted</th>
                            <th class="px-4 py-3 font-bold text-right">Calculated Loss</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/40">
                        @forelse($wastageLog as $w)
                            <tr class="hover:bg-error-container/5 transition-colors">
                                <td class="px-4 py-3.5">
                                    <p class="font-bold text-on-surface text-sm">{{ $w->reason ?: ($w->manufacturingProduct?->name ? 'Defective Piece - ' . $w->manufacturingProduct->name : 'General Fabric Scraps') }}</p>
                                    <div class="flex items-center gap-2 text-xs text-outline font-mono mt-0.5">
                                        <span>Stage: {{ $w->task?->name ?? 'Production' }}</span>
                                        @if($w->manufacturingProduct)
                                            <span>• Product: {{ $w->manufacturingProduct->name }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-center text-xs font-semibold text-on-surface-variant">
                                    @if($w->inventoryBaleRoll)
                                        Bale: {{ $w->inventoryBaleRoll->bale?->bale_number }} · Roll: {{ $w->inventoryBaleRoll->roll_number }}
                                    @else
                                        -- General --
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center font-bold text-error text-sm">
                                    {{ number_format((float)$w->quantity_wasted, 2) }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-black text-error text-sm">
                                    @php
                                        $rate = $w->inventoryBaleRoll?->bale?->unit_cost ?: ($batch->materialConsumptions->where('inventoryBatch.rawMaterial.category.code', 'CAT-FAB')->avg('unit_cost') ?: 150.00);
                                        $loss = (float)$w->quantity_wasted * (float)$rate;
                                    @endphp
                                    ₹{{ number_format($loss, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-on-surface-variant text-sm italic">
                                    No fabric wastage logs recorded for this production batch.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
</div>
