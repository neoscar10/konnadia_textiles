<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Top Header & Breadcrumb -->
    <div>
        @php
            $batchCode = $job->production_batch_id ?? $job->batch?->batch_code;
        @endphp
        @if($batchCode)
            <a href="{{ route('admin.production.batches.jobs', $batchCode) }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:underline mb-2">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Back to Batch {{ $batchCode }} Jobs</span>
            </a>
        @else
            <a href="{{ route('admin.production.jobs.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:underline mb-2">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Back to Production Jobs Hub</span>
            </a>
        @endif
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-black text-on-surface tracking-tight font-display font-mono">
                        {{ $job->batch?->batch_code ?? $job->job_code }}
                    </h1>
                    <span class="px-3 py-1 bg-amber-500/10 text-amber-800 border border-amber-500/30 rounded-full font-black text-[10px] uppercase tracking-wider">
                        {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                    </span>
                </div>
                <p class="text-xs text-on-surface-variant font-medium mt-1">
                    Manufacturing Product: <span class="font-bold text-on-surface">{{ $job->manufacturingProduct?->name ?? 'Standard Item' }}</span>
                    @if($job->pattern)
                        · Pattern: <span class="font-bold text-primary">{{ $job->pattern->name }}</span>
                    @endif
                    @if($job->batch?->factorySupervisor)
                        · Supervisor: <span class="font-bold text-on-surface">{{ $job->batch->factorySupervisor->name }}</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Main Terminal Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT PANEL: TASK SEQUENCE (col-span-4) -->
        <div class="lg:col-span-4 space-y-4">
            <div class="p-5 bg-surface rounded-2xl border border-outline-variant/60 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-on-surface-variant">Task Sequence</h3>
                    <span class="text-[10px] font-bold text-outline uppercase tracking-wider border border-outline-variant/60 px-2 py-0.5 rounded-full">
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
                             class="p-4 rounded-xl border transition-all cursor-pointer relative {{ $isCurrent ? 'bg-amber-500/5 border-amber-500/60 ring-2 ring-amber-500/20 shadow-sm' : ($isDone ? 'bg-surface-container-low/60 border-outline-variant/40 hover:bg-surface-container-low' : 'bg-surface border-outline-variant/60 hover:bg-surface-container-lowest') }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-extrabold text-sm text-on-surface">
                                            {{ $idx + 1 }}. {{ $stg->task?->name ?? 'Stage' }}
                                        </span>
                                        @if($isFinal)
                                            <span class="text-[9px] font-bold text-outline">Final Task</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-outline font-medium mt-1">
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
                                    @elseif($isDone)
                                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 font-extrabold text-[10px] uppercase">
                                            Completed
                                        </span>
                                    @elseif($stg->status === 'in_progress')
                                        <span class="px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-800 border border-amber-500/30 font-extrabold text-[10px] uppercase">
                                            In Progress
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full bg-surface-container text-outline font-bold text-[10px] uppercase">
                                            Pending
                                        </span>
                                    @endif

                                    <!-- Skip Stage Toggle for Non-Mandatory Intermediate Tasks -->
                                    @if($idx > 0 && !$isDone)
                                        <button type="button" 
                                                wire:click.stop="toggleSkipStage({{ $stg->id }})" 
                                                class="inline-flex items-center gap-1 text-[10px] font-extrabold text-outline hover:text-rose-600 px-2 py-0.5 bg-surface-container-low border border-outline-variant/60 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-[14px]">remove_circle_outline</span>
                                            <span>Skip</span>
                                        </button>
                                    @elseif($idx === 0)
                                        <span class="text-[9px] font-extrabold uppercase tracking-wider text-on-surface bg-on-surface/5 px-2 py-0.5 rounded">
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
            @if($activeStage)
                @php
                    $isFinalTask = $this->isFinalStage($activeStage);
                @endphp
                
                <!-- Stage Terminal Card Header & Tabs -->
                <div class="bg-surface rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
                    <div class="p-6 border-b border-outline-variant/60 bg-surface-container-low flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-extrabold text-on-surface">
                                    {{ $activeStage->sequence_number }}. {{ $activeStage->task?->name }}
                                </h2>
                                @if($isFinalTask)
                                    <span class="px-2.5 py-0.5 bg-amber-500/10 text-amber-800 border border-amber-500/30 text-[10px] font-extrabold uppercase rounded-full">
                                        Final Task
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-on-surface-variant mt-0.5">
                                Stage Target: <span class="font-bold text-on-surface">{{ $activeStage->target_quantity }} Pcs</span>
                            </p>
                        </div>

                        <!-- Status Badge -->
                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider {{ $activeStage->status === 'completed' ? 'bg-emerald-500/10 text-emerald-700 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-800 border border-amber-500/30' }}">
                            {{ ucfirst(str_replace('_', ' ', $activeStage->status)) }}
                        </span>
                    </div>

                    <!-- Step Navigation Sub-Tabs -->
                    <div class="flex items-center border-b border-outline-variant/60 bg-surface px-6 overflow-x-auto">
                        <button type="button" 
                                wire:click="$set('activeStep', 1)" 
                                class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 {{ $activeStep === 1 ? 'border-primary text-primary font-black' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
                            1. Labour & Bonus Rate
                        </button>
                        <button type="button" 
                                wire:click="$set('activeStep', 2)" 
                                class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 {{ $activeStep === 2 ? 'border-primary text-primary font-black' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
                            2. Output Items
                        </button>
                        @if($isFinalTask)
                            <button type="button" 
                                    wire:click="$set('activeStep', 3)" 
                                    class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 flex items-center gap-1.5 {{ $activeStep === 3 ? 'border-primary text-primary font-black' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
                                <span>3. Wastage & Alteration</span>
                                <span class="px-1.5 py-0.5 text-[9px] bg-amber-500/10 text-amber-800 border border-amber-500/30 rounded font-black uppercase">Final Task</span>
                            </button>
                            <button type="button" 
                                    wire:click="$set('activeStep', 4)" 
                                    class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 {{ $activeStep === 4 ? 'border-primary text-primary font-black' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
                                4. Review & Confirm
                            </button>
                        @else
                            <button type="button" 
                                    wire:click="$set('activeStep', 3)" 
                                    class="py-3.5 px-4 font-bold text-xs border-b-2 transition-all shrink-0 {{ $activeStep === 3 ? 'border-primary text-primary font-black' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
                                3. Review & Confirm
                            </button>
                        @endif
                    </div>
                </div>

                <!-- STEP 1: LABOUR DEFINITION & BONUS RATE -->
                @if($activeStep === 1)
                    <div class="bg-surface rounded-2xl border border-outline-variant/60 p-6 shadow-xs space-y-6">
                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-extrabold text-on-surface">Step 1: Labour Definition & Quantity Worked Upon</h3>
                                <span class="px-3 py-1 bg-[#001229] text-white rounded-full text-[10px] font-black uppercase tracking-wider">
                                    Multi-Worker Wage Allocation
                                </span>
                            </div>
                            <p class="text-xs text-on-surface-variant mt-1">
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
                                <div class="p-4 bg-surface-container-low border border-outline-variant/60 rounded-xl space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                                        <!-- Worker Select -->
                                        <div class="sm:col-span-4">
                                            <label class="block text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider mb-1">
                                                Worker #{{ $index + 1 }} *
                                            </label>
                                            <select wire:model.live="laborRows.{{ $index }}.labor_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                                <option value="">— Select Worker —</option>
                                                @foreach($labors as $l)
                                                    <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->worker_code ?? 'W' }})</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Qty Worked Upon -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider mb-1">
                                                QTY WORKED (PCS) *
                                            </label>
                                            <input type="number" min="1" wire:model.live="laborRows.{{ $index }}.processed_qty" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                        </div>

                                        <!-- Base Rate -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider mb-1">
                                                BASE RATE (₹ / PC)
                                            </label>
                                            <input type="number" step="0.5" wire:model.live="laborRows.{{ $index }}.base_rate" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                        </div>

                                        <!-- Bonus Rate -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider mb-1">
                                                BONUS RATE (₹ / PC)
                                            </label>
                                            <input type="number" step="0.5" wire:model.live="laborRows.{{ $index }}.bonus_rate" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                        </div>

                                        <!-- Remove Row Button -->
                                        <div class="sm:col-span-2 flex justify-end">
                                            @if(count($laborRows) > 1)
                                                <button type="button" wire:click="removeLaborRow({{ $index }})" class="p-2 text-rose-600 hover:bg-rose-500/10 rounded-xl transition-colors">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Rate Breakdown Footer Bar -->
                                    <div class="pt-2 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold">
                                        <span class="text-on-surface-variant">
                                            EFFECTIVE TOTAL RATE: <span class="text-primary font-black">₹{{ number_format($effRate, 2) }} / Pc</span>
                                        </span>
                                        <span class="text-on-surface">
                                            SUBTOTAL WAGE: <span class="text-emerald-700 font-extrabold text-sm">₹{{ number_format($subtotal, 2) }}</span>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Add Worker Button -->
                        <button type="button" wire:click="addLaborRow" class="inline-flex items-center gap-1.5 px-4 py-2 bg-surface-container-low border border-outline-variant/60 text-on-surface hover:bg-surface-container font-extrabold text-xs rounded-xl transition-colors">
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
                        <div class="p-4 bg-surface-container-low border border-outline-variant/60 rounded-xl space-y-1">
                            <div class="flex items-center gap-2 text-xs font-bold text-on-surface">
                                <span>Total Allotted Labor Quantity: <span class="font-extrabold text-primary">{{ $totalQtyProcessed }} Pcs</span></span>
                                @if($totalQtyProcessed == $activeStage->target_quantity)
                                    <span class="text-emerald-700 font-extrabold text-[11px]">(✓ Matches Batch Target {{ $activeStage->target_quantity }} Pcs)</span>
                                @endif
                            </div>
                            <div class="text-xs font-extrabold text-on-surface-variant">
                                Total Stage Wages (All {{ count($laborRows) }} Worker{{ count($laborRows) > 1 ? 's' : '' }}): <span class="text-emerald-700 text-sm font-black">₹{{ number_format($totalWages, 2) }}</span>
                            </div>
                        </div>

                        <!-- Next Navigation -->
                        <div class="pt-4 border-t border-outline-variant/40 flex justify-end">
                            <button type="button" wire:click="$set('activeStep', 2)" class="px-6 py-2.5 bg-[#001229] hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition-all shadow-sm flex items-center gap-2">
                                <span>Next Step: Output Items</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- STEP 2: OUTPUT ITEMS RECORDED -->
                @if($activeStep === 2)
                    <div class="bg-surface rounded-2xl border border-outline-variant/60 p-6 shadow-xs space-y-6">
                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-extrabold text-on-surface">Step 2: Output Items Recorded</h3>
                                <span class="px-3 py-1 bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 rounded-full text-[10px] font-black uppercase">
                                    Stage Output
                                </span>
                            </div>
                            <p class="text-xs text-on-surface-variant mt-1">
                                Record the actual quantity of finished pieces produced in this stage.
                            </p>
                        </div>

                        <!-- Output Quantity Input -->
                        <div class="p-5 bg-surface-container-low border border-outline-variant/60 rounded-xl space-y-3">
                            <label class="block text-xs font-extrabold text-on-surface-variant uppercase tracking-wider">
                                SUCCESSFULLY PRODUCED OUTPUT QUANTITY (PCS) *
                            </label>
                            <input type="number" min="0" wire:model.live="producedQty" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-4 py-3 text-base font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
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
                        <div class="pt-4 border-t border-outline-variant/40 flex items-center justify-between">
                            <button type="button" wire:click="$set('activeStep', 1)" class="px-5 py-2.5 bg-surface-container-low border border-outline-variant/60 text-on-surface font-bold text-xs rounded-xl">
                                ← Back to Labour
                            </button>
                            @if($isFinalTask)
                                <button type="button" wire:click="$set('activeStep', 3)" class="px-6 py-2.5 bg-[#001229] hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition-all shadow-sm flex items-center gap-2">
                                    <span>Next Step: Wastage & Alteration</span>
                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </button>
                            @else
                                <button type="button" wire:click="$set('activeStep', 3)" class="px-6 py-2.5 bg-[#001229] hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition-all shadow-sm flex items-center gap-2">
                                    <span>Next Step: Review & Confirm</span>
                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- STEP 3 (FINAL TASK ONLY): FINAL BATCH RECONCILIATION - ALTERATION & WASTAGE -->
                @if($isFinalTask && $activeStep === 3)
                    <div class="bg-surface rounded-2xl border border-outline-variant/60 p-6 shadow-xs space-y-6">
                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-extrabold text-on-surface">STEP 3: FINAL BATCH RECONCILIATION — ALTERATION & WASTAGE</h3>
                                <span class="px-3 py-1 bg-amber-500/10 text-amber-800 border border-amber-500/30 rounded-full text-[10px] font-black uppercase">
                                    Final Task Only
                                </span>
                            </div>
                            <p class="text-xs text-on-surface-variant mt-1">
                                Reconcile batch discrepancy, alteration units, and unsalvageable wastage before final batch completion.
                            </p>
                        </div>

                        <!-- Batch Production Discrepancy Breakdown Card -->
                        <div class="p-5 bg-amber-500/10 border border-amber-500/30 rounded-xl space-y-2 text-amber-950">
                            <h4 class="font-extrabold text-xs uppercase tracking-wider text-amber-900">Batch Production Discrepancy Breakdown:</h4>
                            <div class="flex flex-wrap items-center gap-4 text-xs font-bold">
                                <span>Initial Batch Started Qty: <span class="font-extrabold">{{ $job->target_quantity }} Pcs</span></span>
                                <span>·</span>
                                <span>Final Task Output: <span class="font-extrabold">{{ $producedQty }} Pcs</span></span>
                                <span>·</span>
                                @php
                                    $discrepancy = max(0, $job->target_quantity - $producedQty);
                                @endphp
                                <span>Unaccounted Loss / Missing Items: <span class="font-extrabold text-rose-700">{{ $discrepancy }} Pcs</span></span>
                            </div>
                        </div>

                        <!-- Alteration Units & Target Product Mapping -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-extrabold uppercase tracking-wider text-on-surface-variant">
                                    Alteration Units & Target Product Mapping
                                </h4>
                                <button type="button" wire:click="addAlterationRow" class="text-xs font-extrabold text-primary hover:underline">
                                    + Add Alteration Item
                                </button>
                            </div>

                            <div class="space-y-3">
                                @foreach($alterationRows as $aIdx => $aRow)
                                    <div class="p-4 bg-surface-container-low border border-outline-variant/60 rounded-xl flex flex-col sm:flex-row items-center gap-3">
                                        <div class="w-full sm:w-1/3">
                                            <label class="block text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider mb-1">ALTERED QTY (PCS)</label>
                                            <input type="number" min="1" wire:model.live="alterationRows.{{ $aIdx }}.altered_qty" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                        </div>
                                        <div class="w-full sm:w-2/3">
                                            <label class="block text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider mb-1">TARGET MANUFACTURING ITEM CONVERTED TO</label>
                                            <select wire:model.live="alterationRows.{{ $aIdx }}.target_product_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                                @foreach($allProducts as $ap)
                                                    <option value="{{ $ap->id }}">{{ $ap->name }} ({{ $ap->code }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @if(count($alterationRows) > 1)
                                            <button type="button" wire:click="removeAlterationRow({{ $aIdx }})" class="p-2 text-rose-600 hover:bg-rose-500/10 rounded-xl transition-colors shrink-0 self-end sm:self-center">
                                                <span class="material-symbols-outlined text-[18px]">close</span>
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Unsalvageable Wastage Entry -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider mb-1">UNSALVAGEABLE WASTAGE QUANTITY (PCS)</label>
                                <input type="number" step="0.5" wire:model="wastageQty" placeholder="e.g. 2.0" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface">
                            </div>
                            <div>
                                <label class="block text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider mb-1">REASON / REMARKS</label>
                                <input type="text" wire:model="remarks" placeholder="e.g. Fabric tear during final ironing" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface">
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="pt-4 border-t border-outline-variant/40 flex items-center justify-between">
                            <button type="button" wire:click="$set('activeStep', 2)" class="px-5 py-2.5 bg-surface-container-low border border-outline-variant/60 text-on-surface font-bold text-xs rounded-xl">
                                ← Back to Output Items
                            </button>
                            <button type="button" wire:click="$set('activeStep', 4)" class="px-6 py-2.5 bg-[#001229] hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition-all shadow-sm flex items-center gap-2">
                                <span>Next Step: Review & Confirm</span>
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- STEP REVIEW & CONFIRM -->
                @if(($isFinalTask && $activeStep === 4) || (!$isFinalTask && $activeStep === 3))
                    <div class="bg-surface rounded-2xl border border-outline-variant/60 p-6 shadow-xs space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-extrabold text-on-surface">REVIEW & CONFIRM STAGE COMPLETION</h3>
                                <p class="text-xs text-on-surface-variant mt-1">
                                    Verify labor allocation summary, worker wages, and recorded output yield before confirming stage completion.
                                </p>
                            </div>
                            <span class="px-3 py-1 bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 rounded-full text-[10px] font-black uppercase">
                                {{ $isFinalTask ? 'Final Stage Verification' : 'Stage Verification' }}
                            </span>
                        </div>

                        <!-- Worker Wage Summary Table Card -->
                        <div class="p-5 bg-surface-container-low border border-outline-variant/60 rounded-xl space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold uppercase tracking-wider text-on-surface-variant">
                                    Task Stage: <span class="text-on-surface font-black">{{ $activeStage->sequence_number }}. {{ $activeStage->task?->name }}</span>
                                </span>
                                <span class="px-2.5 py-0.5 bg-[#001229] text-white rounded text-[10px] font-black uppercase">
                                    {{ count($laborRows) }} Assigned Worker{{ count($laborRows) > 1 ? 's' : '' }}
                                </span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="border-b border-outline-variant/60 text-[10px] text-on-surface-variant uppercase tracking-wider font-extrabold">
                                            <th class="py-2.5 px-3">WORKER & CATEGORY</th>
                                            <th class="py-2.5 px-3 text-center">QTY WORKED</th>
                                            <th class="py-2.5 px-3 text-right">BASE RATE</th>
                                            <th class="py-2.5 px-3 text-right">BONUS RATE</th>
                                            <th class="py-2.5 px-3 text-right">EFFECTIVE RATE</th>
                                            <th class="py-2.5 px-3 text-right">SUBTOTAL WAGE</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant/40 font-bold text-on-surface">
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
                                                    <div class="font-extrabold text-on-surface">{{ $lObj?->name ?? 'Unassigned Worker' }}</div>
                                                    <div class="text-[10px] text-outline font-medium">{{ $lObj?->trade ?? $activeStage->task?->name ?? 'Laborer' }}</div>
                                                </td>
                                                <td class="py-3 px-3 text-center font-extrabold">{{ $qtyWorked }} Pcs</td>
                                                <td class="py-3 px-3 text-right font-mono">₹{{ number_format($baseRate, 2) }}</td>
                                                <td class="py-3 px-3 text-right font-mono text-emerald-700">+ ₹{{ number_format($bonusRate, 2) }}</td>
                                                <td class="py-3 px-3 text-right font-mono text-primary font-black">₹{{ number_format($effectiveRate, 2) }}</td>
                                                <td class="py-3 px-3 text-right font-mono text-emerald-800 font-extrabold text-sm">₹{{ number_format($subtotal, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t-2 border-outline-variant/60 font-black text-xs bg-surface-container-lowest">
                                            <td class="py-3 px-3 uppercase tracking-wider text-on-surface-variant">Total / Overall</td>
                                            <td class="py-3 px-3 text-center text-primary font-extrabold">{{ $totalWorkedSum }} Pcs</td>
                                            <td colspan="3" class="py-3 px-3 text-right text-on-surface-variant font-extrabold">Total Stage Labor Wage:</td>
                                            <td class="py-3 px-3 text-right text-emerald-800 text-sm font-black font-mono">₹{{ number_format($totalWagesSum, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs font-bold">
                                <span class="text-on-surface-variant">Recorded Stage Output Qty:</span>
                                <span class="text-emerald-700 font-black text-sm">{{ $producedQty }} Pcs</span>
                            </div>
                        </div>

                        <!-- Action Bar -->
                        <div class="pt-4 border-t border-outline-variant/40 flex items-center justify-between">
                            <button type="button" wire:click="$set('activeStep', {{ $isFinalTask ? 3 : 2 }})" class="px-5 py-2.5 bg-surface-container-low border border-outline-variant/60 text-on-surface font-bold text-xs rounded-xl hover:bg-surface-container transition-colors">
                                ← Back
                            </button>
                            <button type="button" wire:click="completeActiveStage" class="px-8 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl transition-all shadow-md active:scale-95 flex items-center gap-2 cursor-pointer">
                                <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                <span>Confirm & Advance Stage</span>
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
