<div>
    <!-- Page Header & Navigation -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.labor.index') }}" wire:navigate class="text-primary font-bold text-xs flex items-center gap-1 hover:underline">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Back to Labor Directory
                </a>
                <span class="text-outline text-xs font-bold">• Worker Audit Profile</span>
            </div>
            <div class="flex items-center gap-3">
                <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">{{ $labor->name }}</h2>
                <span class="px-3 py-1 font-mono font-bold text-xs rounded-xl uppercase tracking-wider bg-primary/10 text-primary border border-primary/20">
                    {{ $labor->code }}
                </span>
                @if($labor->status)
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-700 border border-emerald-500/30 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> ACTIVE
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-outline-variant/30 text-outline flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-outline"></span> INACTIVE
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            @if($labor->payment_method === 'monthly_salary')
                <div class="bg-amber-500/10 border border-amber-500/30 px-4 py-2 rounded-xl flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-800 text-[20px]">account_balance_wallet</span>
                    <div>
                        <span class="text-[9px] uppercase font-bold text-amber-800 tracking-wider block">Monthly Fixed Salary</span>
                        <span class="text-sm font-black text-amber-900">₹{{ number_format($labor->monthly_salary, 2) }} / mo</span>
                    </div>
                </div>
            @else
                <div class="bg-secondary/10 border border-secondary/20 px-4 py-2 rounded-xl flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary text-[20px]">payments</span>
                    <div>
                        <span class="text-[9px] uppercase font-bold text-secondary tracking-wider block">Payment Method</span>
                        <span class="text-sm font-black text-secondary">Piece-Rate Job Work</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Authorized Tasks Banner -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-4 shadow-xs mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-outline text-[20px]">build_circle</span>
            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Authorized Production Tasks:</span>
            <div class="flex flex-wrap gap-1.5 ml-2">
                @forelse($labor->tasks as $t)
                    <span class="px-2.5 py-1 bg-surface-container-high rounded-lg text-xs font-semibold text-primary">
                        {{ $t->name }}
                    </span>
                @empty
                    <span class="text-xs text-outline italic">No authorized tasks assigned yet.</span>
                @endforelse
            </div>
        </div>
        <div class="text-xs text-outline font-medium">
            Contact: <strong class="text-on-surface">{{ $labor->mobile_number ?: 'N/A' }}</strong>
        </div>
    </div>

    <!-- Date Range & Criteria Filter Bar -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-4 shadow-xs mb-6 space-y-3">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-2.5 border-b border-outline-variant/30">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]">filter_alt</span>
                <h3 class="font-bold text-xs text-primary uppercase tracking-wider">Audit Date Range & Criteria Filters</h3>
            </div>
            
            <!-- Quick Preset Buttons -->
            <div class="flex flex-wrap items-center gap-1 bg-surface-container-low p-1 rounded-xl border border-outline-variant/40">
                <button type="button" wire:click="setPresetFilter('this_month')" class="px-2.5 py-0.5 text-[11px] font-bold rounded-lg transition-all {{ $date_from === \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d') ? 'bg-primary text-white shadow-xs' : 'text-on-surface-variant hover:bg-surface' }}">
                    This Month
                </button>
                <button type="button" wire:click="setPresetFilter('last_30')" class="px-2.5 py-0.5 text-[11px] font-bold rounded-lg transition-all {{ $date_from === \Carbon\Carbon::now()->subDays(30)->format('Y-m-d') ? 'bg-primary text-white shadow-xs' : 'text-on-surface-variant hover:bg-surface' }}">
                    Last 30 Days
                </button>
                <button type="button" wire:click="setPresetFilter('this_year')" class="px-2.5 py-0.5 text-[11px] font-bold rounded-lg transition-all {{ $date_from === \Carbon\Carbon::now()->startOfYear()->format('Y-m-d') ? 'bg-primary text-white shadow-xs' : 'text-on-surface-variant hover:bg-surface' }}">
                    This Year
                </button>
                <button type="button" wire:click="setPresetFilter('all')" class="px-2.5 py-0.5 text-[11px] font-bold rounded-lg transition-all {{ empty($date_from) && empty($date_to) ? 'bg-primary text-white shadow-xs' : 'text-on-surface-variant hover:bg-surface' }}">
                    All Time
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end pt-1">
            <!-- 3/12 Col: Date Range (From & To Date) -->
            <div class="lg:col-span-3 grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">From Date</label>
                    <input type="date" wire:model.live="date_from" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-2.5 py-1.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">To Date</label>
                    <input type="date" wire:model.live="date_to" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-2.5 py-1.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
            </div>

            <!-- 3/12 Col: Batch & Task Stage Dropdowns -->
            <div class="lg:col-span-3 grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Filter Batch</label>
                    <select wire:model.live="batch_filter" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-2.5 py-1.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary truncate">
                        <option value="">-- All Batches --</option>
                        @foreach($availableBatches as $bCode)
                            <option value="{{ $bCode }}">{{ $bCode }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Filter Stage</label>
                    <select wire:model.live="task_filter" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-2.5 py-1.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary truncate">
                        <option value="">-- All Stages --</option>
                        @foreach($availableTasks as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- 6/12 Col: Search Keyword & Action Button -->
            <div class="lg:col-span-6 flex items-end gap-2">
                <div class="flex-1 relative">
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Search Keyword</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by Batch Code, Job Code, Product SKU..." class="w-full bg-surface border border-outline-variant/60 rounded-xl pl-9 pr-3 py-1.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>
                @if($date_from || $date_to || $batch_filter || $task_filter || $search)
                    <button type="button" wire:click="resetFilters" class="px-3 py-1.5 bg-surface-container-high text-error hover:bg-error-container/40 rounded-xl transition-all font-bold text-xs flex items-center gap-1 shrink-0 h-[34px]" title="Reset All Filters">
                        <span class="material-symbols-outlined text-[16px]">filter_alt_off</span>
                        <span>Reset</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Summary KPI Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Direct Wages Card -->
        <div class="bg-secondary-container/10 border border-secondary/20 p-5 rounded-2xl flex items-center justify-between shadow-xs">
            <div>
                <span class="text-[10px] uppercase font-bold text-secondary tracking-wider block">Direct Wages Paid</span>
                <span class="text-3xl font-black text-secondary block mt-1">₹{{ number_format($totalDirectWages, 2) }}</span>
                <span class="text-[10px] font-medium text-outline block mt-1">
                    @if($labor->payment_method === 'monthly_salary')
                        <span class="text-amber-800 font-bold">Fixed Monthly Salary Worker</span>
                    @else
                        Piece-Rate Job Work Outflow
                    @endif
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">payments</span>
            </div>
        </div>

        <!-- Job Production Value Card -->
        <div class="bg-amber-500/10 border border-amber-500/30 p-5 rounded-2xl flex items-center justify-between shadow-xs">
            <div>
                <span class="text-[10px] uppercase font-bold text-amber-800 tracking-wider block">Job Production Value</span>
                <span class="text-3xl font-black text-amber-900 block mt-1">₹{{ number_format($totalJobCostValue, 2) }}</span>
                <span class="text-[10px] font-medium text-amber-700 block mt-1">Valuation @ standard piece rates</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-900 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">calculate</span>
            </div>
        </div>

        <!-- Productivity / Salary Efficiency Ratio -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 p-5 rounded-2xl flex items-center justify-between shadow-xs">
            <div>
                @if($labor->payment_method === 'monthly_salary')
                    @php
                        $mSalary = (float)($labor->monthly_salary ?? 0);
                        $ratio = $mSalary > 0 ? round(($totalJobCostValue / $mSalary) * 100, 1) : 100;
                        $diff = $totalJobCostValue - $mSalary;
                    @endphp
                    <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">Salary Efficiency Ratio</span>
                    <span class="text-3xl font-black text-primary block mt-1">{{ number_format($ratio, 1) }}%</span>
                    <span class="text-[10px] font-bold block mt-1 {{ $diff >= 0 ? 'text-emerald-700' : 'text-error' }}">
                        {{ $diff >= 0 ? '+' : '' }}₹{{ number_format($diff, 2) }} vs Fixed Salary
                    </span>
                @else
                    @php
                        $avgRate = $totalPieces > 0 ? ($totalDirectWages / $totalPieces) : 0.0;
                    @endphp
                    <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">Avg Rate Per Piece</span>
                    <span class="text-3xl font-black text-primary block mt-1">₹{{ number_format($avgRate, 2) }}</span>
                    <span class="text-[10px] font-medium text-outline block mt-1">Effective piece rate earned</span>
                @endif
            </div>
            <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">trending_up</span>
            </div>
        </div>

        <!-- Volume & Batches Count Card -->
        <div class="bg-primary/5 border border-primary/20 p-5 rounded-2xl flex items-center justify-between shadow-xs">
            <div>
                <span class="text-[10px] uppercase font-bold text-primary tracking-wider block">Total Production Volume</span>
                <span class="text-3xl font-black text-primary block mt-1">{{ number_format($totalPieces) }} Pcs</span>
                <span class="text-[10px] font-bold text-on-surface-variant block mt-1">
                    {{ $totalBatchesCount }} Batch(es) • {{ $totalJobsCount }} Work Order(s)
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">engineering</span>
            </div>
        </div>
    </div>

    <!-- Batch Performance Breakdown Cards -->
    @if(count($batchBreakdown) > 0)
        <div class="mb-8 space-y-3">
            <h3 class="font-bold text-sm text-primary uppercase tracking-wider flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]">view_list</span>
                Production Batches Contribution Breakdown ({{ count($batchBreakdown) }} Batches)
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($batchBreakdown as $bData)
                    <div class="bg-surface-container-lowest border border-outline-variant/60 p-4 rounded-2xl shadow-xs hover:border-primary/50 transition-colors">
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-mono font-black text-primary text-sm">{{ $bData['batch_code'] }}</span>
                            <span class="text-[10px] font-bold text-outline uppercase">{{ $bData['jobs_count'] }} Job(s)</span>
                        </div>
                        <div class="space-y-1 my-3">
                            <div class="flex justify-between text-xs font-semibold">
                                <span class="text-on-surface-variant">Output Yield:</span>
                                <span class="font-bold text-primary">{{ number_format($bData['total_pieces']) }} Pcs</span>
                            </div>
                            <div class="flex justify-between text-xs font-semibold">
                                <span class="text-on-surface-variant">Job Cost Value:</span>
                                <span class="font-bold text-amber-800">₹{{ number_format($bData['total_valuation'], 2) }}</span>
                            </div>
                            <div class="flex justify-between text-xs font-semibold">
                                <span class="text-on-surface-variant">Direct Wage Paid:</span>
                                <span class="font-bold text-secondary">₹{{ number_format($bData['total_wages'], 2) }}</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.production.batches.ledger', $bData['batch_code']) }}" wire:navigate class="w-full text-center block py-1.5 bg-primary/10 text-primary hover:bg-primary hover:text-white rounded-xl text-xs font-bold transition-all">
                            View 360 Ledger →
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Detailed Itemized Activity & Wage Allocation Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 overflow-hidden shadow-xs mb-8">
        <div class="p-5 bg-surface-container-low border-b border-outline-variant/60 flex justify-between items-center">
            <div>
                <h3 class="font-headline-sm text-headline-sm text-primary font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">history</span>
                    Itemized Production Allocations & Wage Logs
                </h3>
                <p class="text-xs text-on-surface-variant font-medium mt-0.5">Chronological record of all stage activities, piece counts, standard valuations, and payouts.</p>
            </div>
            <span class="px-3 py-1 font-bold text-xs rounded-xl uppercase tracking-wider bg-primary/10 text-primary">
                {{ $allocations->total() }} Record(s) Found
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-body-md">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant/60 text-xs text-on-surface-variant uppercase tracking-wider">
                        <th class="px-4 py-3 font-bold">Date & Time</th>
                        <th class="px-4 py-3 font-bold">Batch & Work Order</th>
                        <th class="px-4 py-3 font-bold">Task Stage</th>
                        <th class="px-4 py-3 font-bold text-center">Product SKU</th>
                        <th class="px-4 py-3 font-bold text-center">Qty Processed</th>
                        <th class="px-4 py-3 font-bold text-center">Piece Rate</th>
                        <th class="px-4 py-3 font-bold text-right text-amber-900">Job Cost Value</th>
                        <th class="px-4 py-3 font-bold text-right">Direct Wage Paid</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40 text-xs">
                    @forelse($allocations as $alloc)
                        @php
                            $rate = (float) $alloc->piece_rate;
                            if ($rate <= 0 && $alloc->manufacturingProduct) {
                                $rate = (float) $alloc->manufacturingProduct->getStandardLaborRateForTask($alloc->task_id);
                            }
                            $valuation = round((float)$alloc->quantity_processed * $rate, 2);
                        @endphp
                        <tr class="hover:bg-surface-container/50 transition-colors">
                            <td class="px-4 py-3.5 text-on-surface-variant font-medium">
                                {{ $alloc->created_at ? $alloc->created_at->format('M d, Y · H:i A') : 'N/A' }}
                            </td>
                            <td class="px-4 py-3.5">
                                <a href="{{ route('admin.production.batches.ledger', $alloc->production_batch_id ?: $alloc->job_id) }}" wire:navigate class="font-mono font-bold text-primary hover:underline text-sm block">
                                    {{ $alloc->production_batch_id ?: $alloc->job_id }}
                                </a>
                                <span class="text-[10px] text-outline font-mono">Job: {{ $alloc->job_id }}</span>
                            </td>
                            <td class="px-4 py-3.5 font-bold text-on-surface">
                                {{ $alloc->productionJob?->task?->name ?? 'Stage' }}
                            </td>
                            <td class="px-4 py-3.5 text-center font-semibold text-on-surface-variant">
                                {{ $alloc->manufacturingProduct?->name ?? $alloc->productionJob?->manufacturingProduct?->name }} 
                                <span class="text-[10px] text-outline font-mono block">({{ $alloc->manufacturingProduct?->code ?? $alloc->productionJob?->manufacturingProduct?->code }})</span>
                            </td>
                            <td class="px-4 py-3.5 text-center font-black text-primary text-sm">
                                {{ number_format($alloc->quantity_processed) }} Pcs
                            </td>
                            <td class="px-4 py-3.5 text-center font-semibold text-outline">
                                ₹{{ number_format($rate, 2) }} / Pc
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-amber-900">
                                ₹{{ number_format($valuation, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-black text-secondary text-sm">
                                @if($labor->payment_method === 'monthly_salary')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-amber-500/10 text-amber-800 text-[11px] font-bold border border-amber-500/30">
                                        ₹0.00 <span class="font-normal text-[10px] text-amber-700">(Monthly Salary)</span>
                                    </span>
                                @else
                                    ₹{{ number_format((float)$alloc->calculated_wage, 2) }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-outline italic">
                                <span class="material-symbols-outlined text-4xl text-outline mb-2">engineering</span>
                                <p class="text-sm font-semibold text-on-surface-variant">No stage allocations or production activities logged for this worker matching the selected filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($allocations->hasPages())
            <div class="p-4 border-t border-outline-variant/40 bg-surface-container-low">
                {{ $allocations->links() }}
            </div>
        @endif
    </div>
</div>
