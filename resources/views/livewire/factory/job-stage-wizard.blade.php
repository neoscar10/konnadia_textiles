<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Top Header & Breadcrumb -->
    <div>
        @php
            $batchCode = $job->production_batch_id ?? $job->batch?->batch_code;
        @endphp
        @if($batchCode)
            <a href="{{ route('admin.production.batches.jobs', $batchCode) }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-700 hover:text-amber-900 mb-2 transition-colors">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Back to Batch {{ $batchCode }} Jobs</span>
            </a>
        @else
            <a href="{{ route('admin.production.jobs.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-700 hover:text-amber-900 mb-2 transition-colors">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Back to Production Jobs Hub</span>
            </a>
        @endif
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight font-display font-mono">
                        {{ $job->batch?->batch_code ?? $job->job_code }}
                    </h1>
                    <span class="px-3 py-1 bg-amber-500/10 text-amber-800 border border-amber-500/30 rounded-full font-black text-[10px] uppercase tracking-wider">
                        {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 font-medium mt-1">
                    Manufacturing Product: <span class="font-bold text-slate-900">{{ $job->manufacturingProduct?->name ?? 'Standard Item' }}</span>
                    @if($job->pattern)
                        · Pattern: <span class="font-bold text-amber-700">{{ $job->pattern->name }}</span>
                    @endif
                    @if($job->batch?->factorySupervisor)
                        · Supervisor: <span class="font-bold text-slate-900">{{ $job->batch->factorySupervisor->name }}</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Main Terminal Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT PANEL: TASK SEQUENCE (col-span-4) -->
        <div class="lg:col-span-4 space-y-4">
            <div class="p-5 bg-white rounded-2xl border border-gray-200/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700">Task Sequence</h3>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider border border-slate-200 px-2 py-0.5 rounded-full">
                        Click Task to Edit
                    </span>
                </div>

                <div class="space-y-3">
                    @foreach($stageExecutions as $idx => $stg)
                        @php
                            $isCurrent = $activeStage && $activeStage->id === $stg->id;
                            $isDone = $stg->status === 'completed';
                            $isFinal = $loop->last;
                        @endphp
                        <div wire:click="selectStage({{ $stg->id }})" 
                             class="p-4 rounded-xl border transition-all cursor-pointer relative {{ $isCurrent ? 'bg-amber-500/5 border-amber-500/60 ring-2 ring-amber-500/20 shadow-sm' : ($isDone ? 'bg-slate-50/80 border-slate-200/80 hover:bg-slate-100' : 'bg-white border-slate-200 hover:bg-slate-50') }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-extrabold text-sm text-slate-900">
                                            {{ $idx + 1 }}. {{ $stg->task?->name ?? 'Stage' }}
                                        </span>
                                        @if($isFinal)
                                            <span class="text-[9px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">Final Task</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-500 font-medium mt-1">
                                        @if($isDone)
                                            {{ $stg->completed_quantity ?? $stg->target_quantity }} / {{ $job->target_quantity }} Pcs
                                        @else
                                            — / {{ $stg->target_quantity }} Pcs
                                        @endif
                                    </p>
                                </div>

                                <div class="flex flex-col items-end gap-2 shrink-0">
                                    @if($stg->is_skipped)
                                        <span class="px-2.5 py-0.5 rounded-full bg-slate-200 text-slate-700 font-extrabold text-[10px] uppercase">
                                            Skipped
                                        </span>
                                        <!-- UNSKIP BUTTON -->
                                        <button type="button" 
                                                wire:click.stop="unskipStage({{ $stg->id }})" 
                                                class="inline-flex items-center gap-1 text-[10px] font-extrabold text-amber-800 hover:text-amber-950 px-2 py-0.5 bg-amber-100 border border-amber-300 rounded-lg transition-colors shadow-2xs">
                                            <span class="material-symbols-outlined text-[13px]">undo</span>
                                            <span>Unskip</span>
                                        </button>
                                    @elseif($isDone)
                                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 font-extrabold text-[10px] uppercase">
                                            Completed
                                        </span>
                                    @elseif($stg->status === 'in_progress')
                                        <span class="px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-800 border border-amber-500/30 font-extrabold text-[10px] uppercase">
                                            In Progress
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-500 font-bold text-[10px] uppercase">
                                            Pending
                                        </span>
                                    @endif

                                    <!-- SKIP BUTTON FOR NON-MANDATORY INTERMEDIATE TASKS -->
                                    @if($idx > 0 && !$isDone && !$stg->is_skipped)
                                        <button type="button" 
                                                wire:click.stop="toggleSkipStage({{ $stg->id }})" 
                                                class="inline-flex items-center gap-1 text-[10px] font-extrabold text-slate-600 hover:text-rose-600 px-2 py-0.5 bg-slate-100 border border-slate-200 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-[14px]">remove_circle_outline</span>
                                            <span>Skip</span>
                                        </button>
                                    @elseif($idx === 0 && !$stg->is_skipped)
                                        <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                            Mandatory
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- RIGHT MAIN PANEL: ACTIVE TASK TERMINAL (col-span-8) -->
        <div class="lg:col-span-8 space-y-6">
            @if($this->isJobFullyCompleted())
                <!-- JOB COMPLETION SUMMARY TERMINAL VIEW -->
                @php
                    $batchCode = $job->production_batch_id ?? $job->batch?->batch_code;
                @endphp
                <div class="bg-white rounded-2xl border border-gray-200/80 p-6 sm:p-8 shadow-sm space-y-8">
                    <!-- Hero Banner -->
                    <div class="p-6 text-white rounded-2xl shadow-md border border-emerald-800 space-y-4" style="background: linear-gradient(135deg, #064e3b 0%, #042f2e 50%, #0f172a 100%);">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-inner" style="background-color: rgba(16, 185, 129, 0.2); border: 1px solid rgba(52, 211, 153, 0.4);">
                                    <span class="material-symbols-outlined text-3xl" style="color: #6ee7b7;">task_alt</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-2xl font-black font-display tracking-tight text-white">Job {{ $job->job_code }} Completed!</h2>
                                        <span class="px-3 py-1 rounded-full font-black text-[10px] uppercase tracking-wider" style="background-color: rgba(52, 211, 153, 0.2); color: #a7f3d0; border: 1px solid rgba(52, 211, 153, 0.3);">
                                            All Stages Completed
                                        </span>
                                    </div>
                                    <p class="text-xs mt-1 font-medium" style="color: #a7f3d0;">
                                        Manufacturing Product: <span class="font-bold text-white">{{ $job->manufacturingProduct?->name ?? 'Standard Item' }}</span>
                                        @if($job->pattern)
                                            · Pattern: <span class="font-bold" style="color: #fde047;">{{ $job->pattern->name }}</span>
                                        @endif
                                        @if($batchCode)
                                            · Production Batch: <span class="font-bold text-white">{{ $batchCode }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($batchCode)
                                <a href="{{ route('admin.production.batches.jobs', $batchCode) }}" wire:navigate class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-xl shadow-md transition-all inline-flex items-center gap-2 shrink-0">
                                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                                    <span>Back to Batch {{ $batchCode }} Jobs</span>
                                </a>
                            @else
                                <a href="{{ route('admin.production.jobs.index') }}" wire:navigate class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-xl shadow-md transition-all inline-flex items-center gap-2 shrink-0">
                                    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                                    <span>Back to Production Jobs Hub</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- KPI Performance Summary Grid (4 Cards) -->
                    @php
                        $finalOutputQty = $job->productOutputs->sum('quantity_produced') ?: $job->target_quantity;
                        $targetQty = max(1, $job->target_quantity);
                        $yieldPercent = round(($finalOutputQty / $targetQty) * 100, 1);

                        $totalMetersConsumed = round($job->materialConsumptions->sum('quantity_consumed'), 2);
                        $totalFabricCost = round($job->materialConsumptions->sum('total_cost'), 2);

                        $totalWorkersCount = $job->allocations->pluck('labor_id')->unique()->filter()->count();
                        $totalLaborWages = round($job->allocations->sum('calculated_wage'), 2);

                        $scrapQty = $job->wastages->where('wastage_type', 'scrap')->sum('quantity_wasted');
                        $damageQty = $job->wastages->where('wastage_type', 'damage')->sum('quantity_wasted');
                        $alterationsCount = $job->alterations->count();
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- 1. Produced Yield -->
                        <div class="p-5 bg-emerald-50/80 border border-emerald-200 rounded-2xl space-y-1">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800">Final Produced Yield</span>
                            <div class="text-2xl font-black text-emerald-950 font-mono">{{ $finalOutputQty }} / {{ $targetQty }} Pcs</div>
                            <div class="text-xs font-bold text-emerald-700">Yield Completion Rate: {{ $yieldPercent }}%</div>
                        </div>

                        <!-- 2. Fabric Consumed -->
                        <div class="p-5 bg-amber-50/80 border border-amber-200 rounded-2xl space-y-1">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-900">Total Fabric Consumed</span>
                            <div class="text-2xl font-black text-amber-950 font-mono">{{ $totalMetersConsumed }} m</div>
                            <div class="text-xs font-bold text-amber-800">Fabric Cost: ₹{{ number_format($totalFabricCost, 2) }}</div>
                        </div>

                        <!-- 3. Labor & Wages Paid -->
                        <div class="p-5 bg-blue-50/80 border border-blue-200 rounded-2xl space-y-1">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-900">Total Worker Wages Paid</span>
                            <div class="text-2xl font-black text-blue-950 font-mono">₹{{ number_format($totalLaborWages, 2) }}</div>
                            <div class="text-xs font-bold text-blue-800">{{ $totalWorkersCount }} Worker{{ $totalWorkersCount > 1 ? 's' : '' }} Allocated</div>
                        </div>

                        <!-- 4. Wastage & Discrepancy -->
                        <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl space-y-1">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-700">Discrepancy &amp; Wastage</span>
                            <div class="text-lg font-black text-slate-900 font-mono">{{ $scrapQty }} Scrap · {{ $damageQty }} Damaged</div>
                            <div class="text-xs font-bold text-amber-800">{{ $alterationsCount }} Alteration Batch(es)</div>
                        </div>
                    </div>

                    <!-- Stage-by-Stage Production Summary Timeline -->
                    <div class="p-6 bg-slate-50 border border-slate-200 rounded-2xl space-y-4">
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900">Stage-by-Stage Production Breakdown</h3>
                        <div class="space-y-3">
                            @foreach($stageExecutions as $stgIdx => $stg)
                                @php
                                    $stgAllocations = $job->allocations->where('task_id', $stg->task_id);
                                    $stgWages = $stgAllocations->sum('calculated_wage');
                                    $stgOutput = $job->productOutputs->where('task_id', $stg->task_id)->sum('quantity_produced');
                                @endphp
                                <div class="p-4 bg-white rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-extrabold text-sm text-slate-900">{{ $stgIdx + 1 }}. {{ $stg->task?->name }}</span>
                                            @if($stg->is_skipped)
                                                <span class="px-2 py-0.5 bg-slate-200 text-slate-700 text-[10px] font-extrabold rounded-full uppercase">Skipped</span>
                                            @else
                                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-extrabold rounded-full uppercase">Completed</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1 font-medium">
                                            Target: <span class="font-bold text-slate-800">{{ $stg->target_quantity }} Pcs</span>
                                            · Workers Assigned: <span class="font-bold text-slate-800">{{ $stgAllocations->pluck('labor_id')->unique()->count() }}</span>
                                            · Stage Wages: <span class="font-bold text-emerald-700">₹{{ number_format($stgWages, 2) }}</span>
                                        </p>
                                    </div>

                                    <div class="text-right">
                                        <span class="text-xs font-extrabold text-slate-700 block">Stage Output</span>
                                        <span class="text-sm font-black text-emerald-800 font-mono">{{ $stgOutput ?: $stg->target_quantity }} Pcs</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Navigation Action Buttons Bar -->
                    <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                        @if($batchCode)
                            <a href="{{ route('admin.production.batches.jobs', $batchCode) }}" wire:navigate class="w-full sm:w-auto px-8 py-3.5 bg-amber-600 hover:bg-amber-700 text-white font-black text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                                <span>Back to Production Batch {{ $batchCode }} Jobs List</span>
                            </a>
                        @else
                            <a href="{{ route('admin.production.jobs.index') }}" wire:navigate class="w-full sm:w-auto px-8 py-3.5 bg-amber-600 hover:bg-amber-700 text-white font-black text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                                <span>Back to Production Jobs Hub</span>
                            </a>
                        @endif

                        <a href="{{ route('admin.production.finished-goods') }}" wire:navigate class="w-full sm:w-auto px-6 py-3.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                            <span>Proceed to Finished Goods Conversion Hub</span>
                        </a>
                    </div>
                </div>
            @elseif($activeStage)
                @php
                    $isFinalTask = $this->isFinalStage($activeStage);
                    $isCutting = $this->isCuttingStage($activeStage);
                @endphp
                <!-- Stage Terminal Card Header & Tabs -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-extrabold text-slate-900">
                                    {{ $activeStage->sequence_number }}. {{ $activeStage->task?->name }}
                                </h2>
                                @if($isFinalTask)
                                    <span class="px-2.5 py-0.5 bg-amber-500/10 text-amber-800 border border-amber-500/30 text-[10px] font-extrabold uppercase rounded-full">
                                        Final Task
                                    </span>
                                @endif
                                @if($isCutting)
                                    <span class="px-2.5 py-0.5 bg-slate-900 text-white text-[10px] font-extrabold uppercase rounded-full">
                                        Fabric Cutting Stage
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Stage Target: <span class="font-bold text-slate-900">{{ $activeStage->target_quantity }} Pcs</span>
                            </p>
                        </div>

                        <!-- Status Badge -->
                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider {{ $activeStage->status === 'completed' ? 'bg-emerald-500/10 text-emerald-700 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-800 border border-amber-500/30' }}">
                            {{ ucfirst(str_replace('_', ' ', $activeStage->status)) }}
                        </span>
                    </div>

                    <!-- Step Navigation Sub-Tabs -->
                    <div class="flex items-center border-b border-gray-100 bg-white px-6 overflow-x-auto">
                        @if($isCutting)
                            <button type="button" 
                                    wire:click="$set('activeStep', 1)" 
                                    class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 {{ $activeStep === 1 ? 'border-amber-600 text-amber-800 font-black' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                                1. Fabric Selection &amp; Consumption
                            </button>
                            <button type="button" 
                                    wire:click="$set('activeStep', 2)" 
                                    class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 {{ $activeStep === 2 ? 'border-amber-600 text-amber-800 font-black' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                                2. Labour &amp; Bonus Rate
                            </button>
                            <button type="button" 
                                    wire:click="$set('activeStep', 3)" 
                                    class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 {{ $activeStep === 3 ? 'border-amber-600 text-amber-800 font-black' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                                3. Output Items
                            </button>
                            @if($isFinalTask)
                                <button type="button" 
                                        wire:click="$set('activeStep', 4)" 
                                        class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 flex items-center gap-1.5 {{ $activeStep === 4 ? 'border-amber-600 text-amber-800 font-black' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                                    <span>4. Wastage &amp; Alteration</span>
                                    <span class="px-1.5 py-0.5 text-[9px] bg-amber-500/10 text-amber-800 border border-amber-500/30 rounded font-black uppercase">Final</span>
                                </button>
                                <button type="button" 
                                        wire:click="$set('activeStep', 5)" 
                                        class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 {{ $activeStep === 5 ? 'border-amber-600 text-amber-800 font-black' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                                    5. Review &amp; Confirm
                                </button>
                            @else
                                <button type="button" 
                                        wire:click="$set('activeStep', 4)" 
                                        class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 {{ $activeStep === 4 ? 'border-amber-600 text-amber-800 font-black' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                                    4. Review &amp; Confirm
                                </button>
                            @endif
                        @else
                            <button type="button" 
                                    wire:click="$set('activeStep', 1)" 
                                    class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 {{ $activeStep === 1 ? 'border-amber-600 text-amber-800 font-black' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                                1. Labour &amp; Bonus Rate
                            </button>
                            <button type="button" 
                                    wire:click="$set('activeStep', 2)" 
                                    class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 {{ $activeStep === 2 ? 'border-amber-600 text-amber-800 font-black' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                                2. Output Items
                            </button>
                            @if($isFinalTask)
                                <button type="button" 
                                        wire:click="$set('activeStep', 3)" 
                                        class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 flex items-center gap-1.5 {{ $activeStep === 3 ? 'border-amber-600 text-amber-800 font-black' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                                    <span>3. Wastage &amp; Alteration</span>
                                    <span class="px-1.5 py-0.5 text-[9px] bg-amber-500/10 text-amber-800 border border-amber-500/30 rounded font-black uppercase">Final</span>
                                </button>
                                <button type="button" 
                                        wire:click="$set('activeStep', 4)" 
                                        class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 {{ $activeStep === 4 ? 'border-amber-600 text-amber-800 font-black' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                                    4. Review &amp; Confirm
                                </button>
                            @else
                                <button type="button" 
                                        wire:click="$set('activeStep', 3)" 
                                        class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 {{ $activeStep === 3 ? 'border-amber-600 text-amber-800 font-black' : 'border-transparent text-slate-500 hover:text-slate-900' }}">
                                    3. Review &amp; Confirm
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- STEP 1: FABRIC SELECTION & CONSUMPTION (CUTTING STAGE ONLY) -->
                @if($isCutting && $activeStep === 1)
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm space-y-6">
                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-extrabold text-slate-900">Step 1: Fabric Selection, Width &amp; Bale Cutting</h3>
                                <span class="px-3 py-1 bg-slate-900 text-white rounded-full text-[10px] font-black uppercase tracking-wider">
                                    Raw Material Consumption
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Select fabric raw material, specify standard fabric width, select bale/rolls, open unopened bales, and record fabric cut lengths.
                            </p>
                        </div>

                        <!-- Already Consumed Fabric Summary Card -->
                        @if($job->materialConsumptions->isNotEmpty())
                            <div class="p-4 bg-amber-50/80 border border-amber-200 rounded-xl space-y-2">
                                <div class="text-xs font-extrabold text-amber-900 uppercase tracking-wider">Previously Recorded Fabric Consumptions on this Job:</div>
                                <div class="space-y-1.5">
                                    @foreach($job->materialConsumptions as $mc)
                                        <div class="flex items-center justify-between text-xs font-semibold text-amber-900 bg-white p-2.5 rounded-lg border border-amber-200">
                                            <div>
                                                <span class="font-extrabold text-slate-900">{{ $mc->inventoryBatch?->rawMaterial?->name }}</span>
                                                <span class="text-slate-500 text-[11px] block">
                                                    Bale {{ $mc->inventoryBaleRoll?->bale?->bale_number ?? 'Bale' }} · Roll {{ $mc->inventoryBaleRoll?->roll_number ?? 'Roll' }}
                                                </span>
                                            </div>
                                            <div class="text-right">
                                                <span class="font-black text-amber-800">{{ $mc->quantity_consumed }}m</span>
                                                <span class="text-slate-500 text-[11px] block font-mono">₹{{ number_format($mc->total_cost, 2) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Fabric Selection Repeater Rows -->
                        <div class="space-y-5">
                            @foreach($selectedFabrics as $fIdx => $fab)
                                @php
                                    $rawMaterial = !empty($fab['raw_material_id']) ? $fabricMaterials->firstWhere('id', $fab['raw_material_id']) : null;
                                    $availableBatches = $rawMaterial ? \App\Models\InventoryBatch::where('raw_material_id', $rawMaterial->id)->where('balance_quantity', '>', 0)->get() : collect();
                                    $selectedBatch = !empty($fab['inventory_batch_id']) ? $availableBatches->firstWhere('id', $fab['inventory_batch_id']) : null;
                                    $availableBales = $selectedBatch ? \App\Models\InventoryBale::where('inventory_batch_id', $selectedBatch->id)->where('status', '!=', 'depleted')->get() : collect();
                                    $selectedBale = !empty($fab['inventory_bale_id']) ? $availableBales->firstWhere('id', $fab['inventory_bale_id']) : null;
                                @endphp
                                <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                                        <!-- Fabric Raw Material Dropdown -->
                                        <div class="sm:col-span-5">
                                            <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Fabric Material *</label>
                                            <select wire:model.live="selectedFabrics.{{ $fIdx }}.raw_material_id" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500">
                                                <option value="">-- Select Fabric Raw Material --</option>
                                                @foreach($fabricMaterials as $fm)
                                                    <option value="{{ $fm->id }}">{{ $fm->name }} ({{ $fm->code }})</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Inventory Batch Dropdown -->
                                        <div class="sm:col-span-4">
                                            <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Stock Batch *</label>
                                            <select wire:model.live="selectedFabrics.{{ $fIdx }}.inventory_batch_id" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500">
                                                <option value="">-- Select Batch --</option>
                                                @foreach($availableBatches as $b)
                                                    <option value="{{ $b->id }}">Batch #{{ $b->batch_number }} (Bal: {{ $b->balance_quantity }}m)</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Bale Selection Dropdown -->
                                        <div class="sm:col-span-3">
                                            <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Bale *</label>
                                            <select wire:model.live="selectedFabrics.{{ $fIdx }}.inventory_bale_id" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500">
                                                <option value="">-- Select Bale --</option>
                                                @foreach($availableBales as $bale)
                                                    <option value="{{ $bale->id }}">Bale {{ $bale->bale_number }} ({{ ucfirst($bale->status) }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Bale State & Rolls Cutting Controls -->
                                    @if($selectedBale)
                                        @if($selectedBale->status === 'unopened')
                                            <!-- Unopened Bale Action Alert -->
                                            <div class="p-4 bg-amber-50 border border-amber-300 rounded-xl flex items-center justify-between gap-4">
                                                <div>
                                                    <div class="font-extrabold text-xs text-amber-900">Bale {{ $selectedBale->bale_number }} is currently UNOPENED</div>
                                                    <p class="text-[11px] text-amber-800 mt-0.5">Declared Purchase Length: {{ $selectedBale->declared_length }}m. Please open the bale and enter actual roll measurements before cutting.</p>
                                                </div>
                                                <button type="button" wire:click="triggerOpenBaleModal({{ $selectedBale->id }})" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded-xl shadow-xs shrink-0 flex items-center gap-1.5">
                                                    <span class="material-symbols-outlined text-[16px]">lock_open</span>
                                                    <span>Open Bale to Measure Rolls</span>
                                                </button>
                                            </div>
                                        @elseif($selectedBale->status === 'opened')
                                            <!-- Opened Bale Active Rolls Table (Filtered by Raw Material) -->
                                            @php
                                                $activeRolls = $selectedBale->rolls()
                                                    ->with(['fabricWidth', 'rawMaterial'])
                                                    ->where('status', 'active')
                                                    ->where('current_balance_length', '>', 0)
                                                    ->when(!empty($fab['raw_material_id']), function($q) use ($fab) {
                                                        $q->where(function($query) use ($fab) {
                                                            $query->where('raw_material_id', $fab['raw_material_id'])
                                                                  ->orWhereNull('raw_material_id');
                                                        });
                                                    })
                                                    ->get();
                                            @endphp
                                            <div class="space-y-3 pt-2 border-t border-slate-200">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-800">
                                                        Active Rolls in Bale {{ $selectedBale->bale_number }} (Matching {{ $rawMaterial?->name ?? 'Fabric' }}: {{ $activeRolls->count() }})
                                                    </span>
                                                    <span class="text-[11px] text-slate-500 font-bold">
                                                        Bale Balance: {{ $selectedBale->current_balance_length }}m
                                                    </span>
                                                </div>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                                    @forelse($activeRolls as $roll)
                                                        @php
                                                            $isRollSelected = isset($fab['selected_rolls'][$roll->id]);
                                                            $currentCut = $fab['selected_rolls'][$roll->id]['cut_length'] ?? $roll->current_balance_length;
                                                            $rollWidthText = null;
                                                            if ($roll->fabricWidth) {
                                                                $rollWidthText = $roll->fabricWidth->name ? ($roll->fabricWidth->name . ' (' . $roll->fabricWidth->value . $roll->fabricWidth->unit . ')') : ($roll->fabricWidth->value . $roll->fabricWidth->unit);
                                                            } elseif ($roll->rawMaterial && $roll->rawMaterial->standard_width) {
                                                                $rollWidthText = $roll->rawMaterial->standard_width . ($roll->rawMaterial->width_unit ?? '"');
                                                            } elseif ($rawMaterial && $rawMaterial->standard_width) {
                                                                $rollWidthText = $rawMaterial->standard_width . ($rawMaterial->width_unit ?? '"');
                                                            }
                                                        @endphp
                                                        <div class="p-3 rounded-xl border transition-all {{ $isRollSelected ? 'bg-amber-500/10 border-amber-500/50 shadow-2xs' : 'bg-white border-slate-200' }}">
                                                            <div class="flex items-center justify-between mb-1.5">
                                                                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                                                                    <input type="checkbox" wire:click="toggleRollSelection({{ $fIdx }}, {{ $roll->id }})" @checked($isRollSelected) class="rounded text-amber-600 focus:ring-amber-500">
                                                                    <span class="font-extrabold text-xs text-slate-900">{{ $roll->roll_number }}</span>
                                                                </label>
                                                                <span class="text-[11px] font-bold text-slate-500">Bal: {{ $roll->current_balance_length }}m</span>
                                                            </div>

                                                            <div class="flex flex-wrap items-center gap-1 mb-2">
                                                                @if($roll->design_number)
                                                                    <span class="px-1.5 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-extrabold rounded">Design: {{ $roll->design_number }}</span>
                                                                @endif
                                                                @if($rollWidthText)
                                                                    <span class="px-1.5 py-0.5 bg-amber-100 text-amber-900 text-[10px] font-extrabold rounded">Width: {{ $rollWidthText }}</span>
                                                                @endif
                                                            </div>

                                                            @if($isRollSelected)
                                                                <div class="space-y-2 pt-1">
                                                                    <div class="flex items-center gap-2">
                                                                        <input type="number" step="0.01" max="{{ $roll->current_balance_length }}" wire:model.live.debounce.500ms="selectedFabrics.{{ $fIdx }}.selected_rolls.{{ $roll->id }}.cut_length" placeholder="Cut Length (m)" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900">
                                                                        <button type="button" wire:click="setFullRollCut({{ $fIdx }}, {{ $roll->id }})" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-[10px] rounded-md shrink-0">
                                                                            Full Roll
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @empty
                                                        <div class="col-span-full p-4 bg-slate-50 border border-dashed border-slate-200 rounded-xl text-center text-xs text-slate-500 font-medium">
                                                            No active rolls available for {{ $rawMaterial?->name ?? 'this fabric' }} in Bale {{ $selectedBale->bale_number }}.
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Fabric Actions Footer Bar -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <button type="button" wire:click="addFabricRow" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs rounded-xl transition-all inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px]">add</span>
                                <span>+ Add Another Fabric</span>
                            </button>

                            <button type="button" wire:click="recordFabricConsumption" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all inline-flex items-center gap-2">
                                <span class="material-symbols-outlined text-[16px]">content_cut</span>
                                <span>Record Fabric Cut Consumption</span>
                            </button>
                        </div>

                        <!-- Step Navigation -->
                        <div class="pt-4 border-t border-slate-200 flex justify-end">
                            <button type="button" wire:click="$set('activeStep', 2)" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition-all shadow-sm flex items-center gap-2">
                                <span>Next Step: Labour &amp; Bonus Rate</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- STEP 1/2: LABOUR DEFINITION & BONUS RATE -->
                @if((!$isCutting && $activeStep === 1) || ($isCutting && $activeStep === 2))
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm space-y-6">
                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-extrabold text-slate-900">Step {{ $isCutting ? '2' : '1' }}: Labour Definition &amp; Quantity Worked Upon</h3>
                                <span class="px-3 py-1 bg-slate-900 text-white rounded-full text-[10px] font-black uppercase tracking-wider">
                                    Multi-Worker Wage Allocation
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Assign single or multiple workers, specify item quantities worked upon, base rates, and bonus rates.
                            </p>
                        </div>

                        <!-- Worker Rows Table -->
                        <div class="space-y-4">
                            @foreach($laborRows as $index => $row)
                                @php
                                    $bRate = floatval($row['base_rate'] ?? 0);
                                    $bnRate = floatval($row['bonus_rate'] ?? 0);
                                    $effRate = $bRate + $bnRate;
                                    $pQty = intval($row['processed_qty'] ?? 0);
                                    $subtotal = round($effRate * $pQty, 2);
                                @endphp
                                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                                        <!-- Worker Select -->
                                        <div class="sm:col-span-4">
                                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider mb-1">
                                                Worker #{{ $index + 1 }} *
                                            </label>
                                            <select wire:model.live="laborRows.{{ $index }}.labor_id" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500">
                                                <option value="">— Select Worker —</option>
                                                @foreach($labors as $l)
                                                    <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->worker_code ?? 'W' }})</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Qty Worked Upon -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider mb-1">
                                                QTY WORKED (PCS) *
                                            </label>
                                            <input type="number" min="1" wire:model.live="laborRows.{{ $index }}.processed_qty" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                        </div>

                                        <!-- Base Rate -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider mb-1">
                                                BASE RATE (₹ / PC)
                                            </label>
                                            <input type="number" step="0.5" wire:model.live="laborRows.{{ $index }}.base_rate" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                        </div>

                                        <!-- Bonus Rate -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider mb-1">
                                                BONUS RATE (₹ / PC)
                                            </label>
                                            <input type="number" step="0.5" wire:model.live="laborRows.{{ $index }}.bonus_rate" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                        </div>

                                        <!-- Remove Row Button -->
                                        <div class="sm:col-span-2 flex justify-end">
                                            @if(count($laborRows) > 1)
                                                <button type="button" wire:click="removeLaborRow({{ $index }})" class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition-colors">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Rate Breakdown Footer Bar -->
                                    <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between text-xs font-bold">
                                        <span class="text-slate-600">
                                            EFFECTIVE TOTAL RATE: <span class="text-amber-800 font-black">₹{{ number_format($effRate, 2) }} / Pc</span>
                                        </span>
                                        <span class="text-slate-900">
                                            SUBTOTAL WAGE: <span class="text-emerald-700 font-extrabold text-sm">₹{{ number_format($subtotal, 2) }}</span>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Add Worker Button -->
                        <button type="button" wire:click="addLaborRow" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 border border-slate-200 text-slate-900 hover:bg-slate-200 font-extrabold text-xs rounded-xl transition-colors">
                            <span class="material-symbols-outlined text-[16px]">add</span>
                            <span>+ Add Laborer / Worker</span>
                        </button>

                        <!-- Total Summary Footer Card -->
                        @php
                            $totalQtyProcessed = array_sum(array_column($laborRows, 'processed_qty'));
                            $totalWages = 0;
                            foreach($laborRows as $lr) {
                                $eff = floatval($lr['base_rate'] ?? 0) + floatval($lr['bonus_rate'] ?? 0);
                                $totalWages += round($eff * intval($lr['processed_qty'] ?? 0), 2);
                            }
                        @endphp
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                            <div class="flex items-center gap-2 text-xs font-bold text-slate-900">
                                <span>Total Allotted Labor Quantity: <span class="font-extrabold text-amber-800">{{ $totalQtyProcessed }} Pcs</span></span>
                                @if($totalQtyProcessed == $activeStage->target_quantity)
                                    <span class="text-emerald-700 font-extrabold text-[11px]">(✓ Matches Batch Target {{ $activeStage->target_quantity }} Pcs)</span>
                                @endif
                            </div>
                            <div class="text-xs font-extrabold text-slate-600">
                                Total Stage Wages (All {{ count($laborRows) }} Worker{{ count($laborRows) > 1 ? 's' : '' }}): <span class="text-emerald-700 text-sm font-black">₹{{ number_format($totalWages, 2) }}</span>
                            </div>
                        </div>

                        <!-- Next Navigation -->
                        <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
                            @if($isCutting)
                                <button type="button" wire:click="$set('activeStep', 1)" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-800 font-bold text-xs rounded-xl hover:bg-slate-50">
                                    ← Back to Fabric Selection
                                </button>
                            @else
                                <div></div>
                            @endif

                            <button type="button" wire:click="$set('activeStep', {{ $isCutting ? 3 : 2 }})" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition-all shadow-sm flex items-center gap-2">
                                <span>Next Step: Output Items</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- STEP 2/3: OUTPUT ITEMS RECORDED -->
                @if((!$isCutting && $activeStep === 2) || ($isCutting && $activeStep === 3))
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm space-y-6">
                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-extrabold text-slate-900">Step {{ $isCutting ? '3' : '2' }}: Output Items Recorded</h3>
                                <span class="px-3 py-1 bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 rounded-full text-[10px] font-black uppercase">
                                    Stage Output
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Record the actual quantity of finished pieces produced in this stage.
                            </p>
                        </div>

                        <!-- Output Quantity Input -->
                        <div class="p-5 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                                SUCCESSFULLY PRODUCED OUTPUT QUANTITY (PCS) *
                            </label>
                            <input type="number" min="0" wire:model.live.debounce.500ms="producedQty" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-base font-bold text-slate-900 focus:outline-none focus:border-amber-500">
                        </div>

                        <!-- Completion Status Card -->
                        @php
                            $target = max(1, $activeStage->target_quantity);
                            $rate = round(($producedQty / $target) * 100, 1);
                        @endphp
                        <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl space-y-1 text-emerald-900">
                            <div class="font-extrabold text-sm">Recorded Stage Output: {{ $producedQty }} Pcs</div>
                            <div class="text-xs font-semibold text-emerald-800">
                                Target Batch Qty: {{ $activeStage->target_quantity }} Pcs · Completion Rate: {{ $rate }}%
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
                            <button type="button" wire:click="$set('activeStep', {{ $isCutting ? 2 : 1 }})" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-800 font-bold text-xs rounded-xl hover:bg-slate-50">
                                ← Back to Labour
                            </button>
                            @if($isFinalTask)
                                <button type="button" wire:click="$set('activeStep', {{ $isCutting ? 4 : 3 }})" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition-all shadow-sm flex items-center gap-2">
                                    <span>Next Step: Wastage &amp; Alteration</span>
                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </button>
                            @else
                                <button type="button" wire:click="$set('activeStep', {{ $isCutting ? 4 : 3 }})" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition-all shadow-sm flex items-center gap-2">
                                    <span>Next Step: Review &amp; Confirm</span>
                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- STEP 3/4 (FINAL TASK ONLY): FINAL BATCH RECONCILIATION - ALTERATION, SCRAP & DAMAGE -->
                @if($isFinalTask && (($isCutting && $activeStep === 4) || (!$isCutting && $activeStep === 3)))
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm space-y-6">
                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-extrabold text-slate-900">STEP {{ $isCutting ? '4' : '3' }}: FINAL BATCH RECONCILIATION — ALTERATION, SCRAP &amp; DAMAGE</h3>
                                <span class="px-3 py-1 bg-amber-500/10 text-amber-800 border border-amber-500/30 rounded-full text-[10px] font-black uppercase">
                                    Final Task Only
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                Reconcile batch discrepancy, alteration units (spawns new alteration job), scrap output, and damaged output before final batch completion.
                            </p>
                        </div>

                        <!-- Batch Production Discrepancy Breakdown Card -->
                        <div class="p-5 bg-amber-50 border border-amber-200 rounded-xl space-y-2 text-amber-950">
                            <h4 class="font-extrabold text-xs uppercase tracking-wider text-amber-900">Batch Production Discrepancy Breakdown:</h4>
                            <div class="flex flex-wrap items-center gap-4 text-xs font-bold">
                                <span>Initial Batch Target: <span class="font-extrabold">{{ $job->target_quantity }} Pcs</span></span>
                                <span>·</span>
                                <span>Final Task Output: <span class="font-extrabold text-emerald-800">{{ $producedQty }} Pcs</span></span>
                                <span>·</span>
                                @php
                                    $discrepancy = max(0, $job->target_quantity - $producedQty);
                                @endphp
                                <span>Non-Good / Discrepancy Items: <span class="font-extrabold text-rose-700">{{ $discrepancy }} Pcs</span></span>
                            </div>
                        </div>

                        <!-- 1. Alteration Units & Target Product/Pattern Mapping -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <div>
                                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">
                                        1. Alteration Units (Spawns New Alteration Production Job)
                                    </h4>
                                    <p class="text-[11px] text-slate-500">Products sent for alteration will instantiate a new Production Job inside the batch for the chosen target product and pattern.</p>
                                </div>
                                <button type="button" wire:click="addAlterationRow" class="px-3 py-1 bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-900 font-bold text-xs rounded-lg transition-all">
                                    ＋ Add Alteration Item
                                </button>
                            </div>

                            <div class="space-y-3">
                                @foreach($alterationRows as $aIdx => $aRow)
                                    @php
                                        $selectedTargetProdId = $aRow['target_product_id'] ?? null;
                                        $targetProdObj = $selectedTargetProdId ? $allProducts->firstWhere('id', $selectedTargetProdId) : null;
                                        $targetPatterns = $targetProdObj ? $targetProdObj->patterns : collect();
                                    @endphp
                                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                                        <div class="sm:col-span-3">
                                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider mb-1">ALTERED QTY (PCS) *</label>
                                            <input type="number" min="1" wire:model.live="alterationRows.{{ $aIdx }}.altered_qty" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                        </div>
                                        <div class="sm:col-span-4">
                                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider mb-1">TARGET PRODUCT *</label>
                                            <select wire:model.live="alterationRows.{{ $aIdx }}.target_product_id" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                                @foreach($allProducts as $ap)
                                                    <option value="{{ $ap->id }}">{{ $ap->name }} ({{ $ap->code }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="sm:col-span-4">
                                            <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider mb-1">TARGET PATTERN *</label>
                                            <select wire:model="alterationRows.{{ $aIdx }}.target_pattern_id" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                                <option value="">-- Select Pattern --</option>
                                                @foreach($targetPatterns as $pat)
                                                    <option value="{{ $pat->id }}">{{ $pat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="sm:col-span-1 flex justify-end">
                                            @if(count($alterationRows) > 1)
                                                <button type="button" wire:click="removeAlterationRow({{ $aIdx }})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                                    ✕
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 2. Scrap vs. Damage Wastage Categorization -->
                        <div class="space-y-4 pt-2 border-t border-slate-200">
                            <div>
                                <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">
                                    2. Non-Good Output Categorization: Scrap vs. Damage
                                </h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Distinguish between partially damaged items that can still be sold as scrap (or converted into smaller items) versus completely unsalvageable damaged loss.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Scrap Section Card -->
                                <div class="p-4 bg-amber-50/60 border border-amber-200 rounded-xl space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-extrabold uppercase text-amber-900 flex items-center gap-1">
                                            <span>♻️</span> Scrap Output (Partially Damaged / Resold)
                                        </span>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider mb-1">SCRAP QUANTITY (PCS)</label>
                                        <input type="number" step="0.5" wire:model="scrapQty" placeholder="e.g. 2.0" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider mb-1">SCRAP ACTION / REASON NOTE</label>
                                        <input type="text" wire:model="scrapNotes" placeholder="e.g. Minor flaw, convert bedsheet to pillowcases or sell as scrap" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                    </div>
                                </div>

                                <!-- Damage Section Card -->
                                <div class="p-4 bg-rose-50/60 border border-rose-200 rounded-xl space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-extrabold uppercase text-rose-900 flex items-center gap-1">
                                            <span>⚠️</span> Damaged Output (Completely Unusable Loss)
                                        </span>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider mb-1">DAMAGED QUANTITY (PCS)</label>
                                        <input type="number" step="0.5" wire:model="damageQty" placeholder="e.g. 1.0" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider mb-1">DAMAGE REASON / REJECT NOTE</label>
                                        <input type="text" wire:model="damageNotes" placeholder="e.g. Severe fabric tear during stitching" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- General Remarks -->
                        <div>
                            <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Additional Reconciliation Remarks</label>
                            <input type="text" wire:model="remarks" placeholder="Optional notes for final job completion" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900">
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
                            <button type="button" wire:click="$set('activeStep', {{ $isCutting ? 3 : 2 }})" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-800 font-bold text-xs rounded-xl hover:bg-slate-50">
                                ← Back to Output Items
                            </button>
                            <button type="button" wire:click="$set('activeStep', {{ $isCutting ? 5 : 4 }})" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition-all shadow-sm flex items-center gap-2">
                                <span>Next Step: Review &amp; Confirm</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- STEP REVIEW & CONFIRM -->
                @if(($isFinalTask && (($isCutting && $activeStep === 5) || (!$isCutting && $activeStep === 4))) || (!$isFinalTask && (($isCutting && $activeStep === 4) || (!$isCutting && $activeStep === 3))))
                    <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-sm space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900">REVIEW &amp; CONFIRM STAGE COMPLETION</h3>
                                <p class="text-xs text-slate-500 mt-1">
                                    Verify labor allocation summary, worker wages, recorded output yield, and wastage/alterations before confirming stage completion.
                                </p>
                            </div>
                            <span class="px-3 py-1 bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 rounded-full text-[10px] font-black uppercase">
                                {{ $isFinalTask ? 'Final Stage Verification' : 'Stage Verification' }}
                            </span>
                        </div>

                        <!-- Worker Wage Summary Table Card -->
                        <div class="p-5 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-700">
                                    Task Stage: <span class="text-slate-900 font-black">{{ $activeStage->sequence_number }}. {{ $activeStage->task?->name }}</span>
                                </span>
                                <span class="px-2.5 py-0.5 bg-slate-900 text-white rounded text-[10px] font-black uppercase">
                                    {{ count($laborRows) }} Assigned Worker{{ count($laborRows) > 1 ? 's' : '' }}
                                </span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="border-b border-slate-200 text-[10px] text-slate-500 uppercase tracking-wider font-extrabold">
                                            <th class="py-2.5 px-3">WORKER &amp; CATEGORY</th>
                                            <th class="py-2.5 px-3 text-center">QTY WORKED</th>
                                            <th class="py-2.5 px-3 text-right">BASE RATE</th>
                                            <th class="py-2.5 px-3 text-right">BONUS RATE</th>
                                            <th class="py-2.5 px-3 text-right">EFFECTIVE RATE</th>
                                            <th class="py-2.5 px-3 text-right">SUBTOTAL WAGE</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 font-bold text-slate-900">
                                        @php
                                            $totalWorkedSum = 0;
                                            $totalWagesSum = 0;
                                        @endphp
                                        @foreach($laborRows as $lr)
                                            @php
                                                $lObj = $labors->firstWhere('id', $lr['labor_id']);
                                                $qtyWorked = intval($lr['processed_qty'] ?? 0);
                                                $baseRate = floatval($lr['base_rate'] ?? 0);
                                                $bonusRate = floatval($lr['bonus_rate'] ?? 0);
                                                $effectiveRate = $baseRate + $bonusRate;
                                                $subtotal = round($effectiveRate * $qtyWorked, 2);
                                                $totalWorkedSum += $qtyWorked;
                                                $totalWagesSum += $subtotal;
                                            @endphp
                                            <tr>
                                                <td class="py-3 px-3">
                                                    <div class="font-extrabold text-slate-900">{{ $lObj?->name ?? 'Unassigned Worker' }}</div>
                                                    <div class="text-[10px] text-slate-500 font-medium">{{ $lObj?->trade ?? $activeStage->task?->name ?? 'Laborer' }}</div>
                                                </td>
                                                <td class="py-3 px-3 text-center font-extrabold">{{ $qtyWorked }} Pcs</td>
                                                <td class="py-3 px-3 text-right font-mono">₹{{ number_format($baseRate, 2) }}</td>
                                                <td class="py-3 px-3 text-right font-mono text-emerald-700">+ ₹{{ number_format($bonusRate, 2) }}</td>
                                                <td class="py-3 px-3 text-right font-mono text-amber-800 font-black">₹{{ number_format($effectiveRate, 2) }}</td>
                                                <td class="py-3 px-3 text-right font-mono text-emerald-800 font-extrabold text-sm">₹{{ number_format($subtotal, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t-2 border-slate-200 font-black text-xs bg-white">
                                            <td class="py-3 px-3 uppercase tracking-wider text-slate-600">Total / Overall</td>
                                            <td class="py-3 px-3 text-center text-amber-800 font-extrabold">{{ $totalWorkedSum }} Pcs</td>
                                            <td colspan="3" class="py-3 px-3 text-right text-slate-600 font-extrabold">Total Stage Labor Wage:</td>
                                            <td class="py-3 px-3 text-right text-emerald-800 text-sm font-black font-mono">₹{{ number_format($totalWagesSum, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="pt-3 border-t border-slate-200 flex items-center justify-between text-xs font-bold">
                                <span class="text-slate-600">Recorded Stage Output Qty:</span>
                                <span class="text-emerald-700 font-black text-sm">{{ $producedQty }} Pcs</span>
                            </div>

                            @if($isFinalTask)
                                <div class="pt-3 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs font-semibold">
                                    <div class="p-2.5 bg-white rounded-lg border border-slate-200">
                                        <span class="text-slate-500 text-[11px] block">Alteration Items:</span>
                                        <span class="font-extrabold text-amber-800">{{ count($alterationRows) }} Item(s)</span>
                                    </div>
                                    <div class="p-2.5 bg-white rounded-lg border border-slate-200">
                                        <span class="text-slate-500 text-[11px] block">Scrap Quantity:</span>
                                        <span class="font-extrabold text-amber-800">{{ $scrapQty }} Pcs</span>
                                    </div>
                                    <div class="p-2.5 bg-white rounded-lg border border-slate-200">
                                        <span class="text-slate-500 text-[11px] block">Damaged Quantity:</span>
                                        <span class="font-extrabold text-rose-700">{{ $damageQty }} Pcs</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Action Bar -->
                        <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
                            @php
                                $prevStepNum = $isFinalTask ? ($isCutting ? 4 : 3) : ($isCutting ? 3 : 2);
                            @endphp
                            <button type="button" wire:click="$set('activeStep', {{ $prevStepNum }})" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-800 font-bold text-xs rounded-xl hover:bg-slate-50">
                                ← Back
                            </button>
                            <button type="button" wire:click="completeActiveStage" class="px-8 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl transition-all shadow-md active:scale-95 flex items-center gap-2 cursor-pointer">
                                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                <span>Confirm &amp; Advance Stage</span>
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- UNOPENED BALE MEASUREMENT MODAL -->
    @if($showOpenBaleModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-3xl w-full p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-600">lock_open</span>
                        <span>Measure &amp; Open Bale — Itemized Roll Breakdown</span>
                    </h3>
                    <button type="button" wire:click="$set('showOpenBaleModal', false)" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Number of Rolls in Bale *</label>
                        <input type="number" min="1" max="50" wire:model.live="baleRollCount" placeholder="e.g. 5" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900">
                        @error('baleRollCount') <span class="text-xs text-rose-600 font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    @if(count($baleRollLengths) > 0)
                        <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                            <span class="text-xs font-extrabold uppercase text-slate-700 block">Roll Specifications &amp; Measured Lengths *</span>
                            <div class="space-y-3">
                                @foreach($baleRollLengths as $i => $len)
                                    @php
                                        $selectedRollMatId = $baleRollMaterials[$i] ?? null;
                                        $selectedRollMat = $selectedRollMatId ? $fabricMaterials->firstWhere('id', $selectedRollMatId) : null;
                                    @endphp
                                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-black text-slate-900">Roll #{{ $i + 1 }}</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
                                            <!-- Fabric Material Select -->
                                            <div class="sm:col-span-4">
                                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Fabric Material *</label>
                                                <select wire:model.live="baleRollMaterials.{{ $i }}" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900">
                                                    <option value="">-- Select Material --</option>
                                                    @php
                                                        $materialsForBale = !empty($baleAllowedMaterials) ? $baleAllowedMaterials : $fabricMaterials;
                                                    @endphp
                                                    @foreach($materialsForBale as $fm)
                                                        <option value="{{ is_array($fm) ? $fm['id'] : $fm->id }}">{{ is_array($fm) ? ($fm['name'] . ($fm['code'] ? ' (' . $fm['code'] . ')' : '')) : ($fm->name . ' (' . $fm->code . ')') }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- Fabric Width Select -->
                                            <div class="sm:col-span-3">
                                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Standard Width</label>
                                                <select wire:model="baleRollWidths.{{ $i }}" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900">
                                                    <option value="">Default Width</option>
                                                    @if($selectedRollMat && $selectedRollMat->fabricWidths->isNotEmpty())
                                                        @foreach($selectedRollMat->fabricWidths as $fw)
                                                            <option value="{{ $fw->id }}">{{ $fw->name }} ({{ $fw->value }}{{ $fw->unit }})</option>
                                                        @endforeach
                                                    @else
                                                        @foreach($fabricWidths as $fw)
                                                            <option value="{{ $fw->id }}">{{ $fw->name }} ({{ $fw->value }}{{ $fw->unit }})</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>

                                            <!-- Design ID -->
                                            <div class="sm:col-span-3">
                                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Design ID / Number</label>
                                                <input type="text" wire:model="baleRollDesignNumbers.{{ $i }}" placeholder="e.g. D-101" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900">
                                            </div>

                                            <!-- Measured Length -->
                                            <div class="sm:col-span-2">
                                                <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Length (m) *</label>
                                                <input type="number" step="0.01" wire:model.live="baleRollLengths.{{ $i }}" placeholder="0.00" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-bold text-slate-900">
                                                @error("baleRollLengths.{$i}") <span class="text-[10px] text-rose-600 block mt-0.5">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($baleMismatchWarning)
                        <div class="p-3 bg-amber-50 border border-amber-300 rounded-xl text-xs text-amber-900 font-semibold">
                            {{ $baleMismatchWarning }}
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" wire:click="$set('showOpenBaleModal', false)" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 font-bold text-xs rounded-xl">
                        Cancel
                    </button>
                    <button type="button" wire:click="submitOpenedBaleForm" class="px-6 py-2 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded-xl shadow-xs">
                        Confirm &amp; Save Opened Bale
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
