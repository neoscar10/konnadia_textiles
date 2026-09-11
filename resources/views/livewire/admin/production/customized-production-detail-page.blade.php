<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Top Header & Navigation Back Link -->
    <div class="space-y-3">
        <a href="{{ route('admin.production.customized') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-800 dark:text-amber-300 hover:text-amber-900 transition-colors">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Back to Customized Production Hub</span>
        </a>

        <!-- Specification Card -->
        <div class="bg-surface-container-lowest p-6 rounded-3xl border border-outline-variant/60 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="text-[11px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300">CUSTOMIZED WORK ORDER SPECIFICATIONS</div>
                    <h1 class="text-2xl font-black tracking-tight text-on-surface mt-0.5 font-mono">
                        {{ $customOrder->custom_order_id }} — {{ $customOrder->item_description }}
                    </h1>
                </div>
                <div>
                    <span class="px-3.5 py-1.5 rounded-full text-xs font-black uppercase tracking-wider shadow-xs {{ $customOrder->status === 'completed' ? 'bg-emerald-500/10 text-emerald-800 border border-emerald-500/30' : 'bg-amber-500/10 text-amber-800 border border-amber-500/30' }}">
                        {{ ucfirst(str_replace('_', ' ', $customOrder->status)) }}
                    </span>
                </div>
            </div>

            <!-- Specification Badges Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/50">
                <div>
                    <div class="text-[10px] font-bold text-on-surface-variant/70 uppercase">Fabric Material:</div>
                    <div class="text-xs font-black text-on-surface mt-0.5">
                        {{ $customOrder->fabric_name ?: ($customOrder->rawMaterial?->name ?? 'Custom Fabric') }}
                    </div>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-on-surface-variant/70 uppercase">Custom Dimensions:</div>
                    <div class="text-xs font-mono font-black text-on-surface mt-0.5">
                        {{ $customOrder->dimensions_formatted }}
                    </div>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-on-surface-variant/70 uppercase">Target Quantity:</div>
                    <div class="text-xs font-mono font-black text-on-surface mt-0.5">
                        {{ $customOrder->target_quantity }} Pcs
                    </div>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-on-surface-variant/70 uppercase">Special Notes:</div>
                    <div class="text-xs font-medium text-on-surface mt-0.5 truncate">
                        {{ $customOrder->notes ?: 'None specified' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN (lg:col-span-4): Dynamic Tasks On-The-Fly Panel -->
        <div class="lg:col-span-4 space-y-4">
            <div class="bg-surface-container-lowest p-5 rounded-3xl border border-outline-variant/60 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-on-surface">DYNAMIC TASKS</h3>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest bg-amber-500/20 text-amber-800 dark:text-amber-300">
                        ON-THE-FLY
                    </span>
                </div>

                <div class="space-y-3">
                    @foreach($dynamicTaskRows as $idx => $taskRow)
                        @php
                            $isCurrentSelected = ($activeStage && $activeStage->id == $taskRow['id']);
                        @endphp
                        <div wire:click="selectStage({{ $taskRow['id'] }})" 
                             class="p-4 rounded-2xl border transition-all cursor-pointer relative {{ $isCurrentSelected ? 'bg-amber-500/10 border-amber-500/60 ring-2 ring-amber-500/20 shadow-md' : 'bg-surface-container-low/40 border-outline-variant/60 hover:bg-surface-container-low' }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-extrabold text-on-surface">Task #{{ $idx + 1 }}</span>
                                <div class="flex items-center gap-1.5">
                                    @if(!empty($taskRow['is_skipped']))
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 border border-slate-300">
                                            SKIPPED
                                        </span>
                                        <button type="button" wire:click.stop="unskipStage({{ $taskRow['id'] }})" class="inline-flex items-center gap-1 text-[10px] font-extrabold text-amber-800 dark:text-amber-300 hover:text-amber-950 px-2 py-0.5 bg-amber-500/10 border border-amber-500/30 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-[13px]">undo</span>
                                            <span>Unskip</span>
                                        </button>
                                    @elseif($taskRow['status'] === 'completed')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border border-emerald-500/20">
                                            COMPLETED
                                        </span>
                                    @elseif($taskRow['status'] === 'in_progress')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-800 dark:text-amber-300 border border-amber-500/20">
                                            IN PROGRESS
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20">
                                            PENDING
                                        </span>
                                    @endif

                                    @if(empty($taskRow['is_skipped']) && $idx > 0 && $taskRow['status'] !== 'completed')
                                        <button type="button" wire:click.stop="toggleSkipStage({{ $taskRow['id'] }})" class="inline-flex items-center gap-1 text-[10px] font-extrabold text-on-surface-variant hover:text-rose-600 px-2 py-0.5 bg-surface-container-high border border-outline-variant/60 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-[14px]">remove_circle_outline</span>
                                            <span>Skip</span>
                                        </button>
                                    @elseif($idx === 0)
                                        <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 bg-surface-container-high px-2 py-0.5 rounded border border-outline-variant/60">
                                            MANDATORY
                                        </span>
                                    @endif

                                    @if(count($dynamicTaskRows) > 1 && $taskRow['status'] !== 'completed')
                                        <button type="button" wire:click.stop="removeDynamicTaskStage({{ $idx }})" class="p-1 text-on-surface-variant/60 hover:text-red-500 rounded-lg hover:bg-red-500/10 transition-colors" title="Delete Task Stage">
                                            <span class="material-symbols-outlined text-[16px]">close</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Task Selection Input -->
                            <div class="space-y-2" @click.stop>
                                <select wire:model.change="dynamicTaskRows.{{ $idx }}.task_id" wire:change="syncDynamicTaskRowsToDatabase" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-amber-500/30 font-bold text-on-surface">
                                    @foreach($allTasks as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>

                                <!-- Final Stage Radio Toggle -->
                                <label class="flex items-center gap-2 text-xs font-bold text-on-surface cursor-pointer select-none">
                                    <input type="radio" name="finalStageRadio" wire:click="setFinalStage({{ $idx }})" {{ $taskRow['is_final_step'] ? 'checked' : '' }} class="w-3.5 h-3.5 text-amber-600 focus:ring-amber-500" />
                                    <span class="{{ $taskRow['is_final_step'] ? 'text-amber-800 dark:text-amber-300 font-extrabold' : 'text-on-surface-variant' }}">Final Stage</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" wire:click="addDynamicTaskStage" class="w-full py-2.5 px-4 bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-xs font-bold rounded-2xl border border-outline-variant/60 transition-all flex items-center justify-center gap-1.5 shadow-xs">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    <span>Add Dynamic Task Stage</span>
                </button>
            </div>
        </div>

        <!-- RIGHT COLUMN (lg:col-span-8): Stage Terminal & Execution Engine -->
        <div class="lg:col-span-8 space-y-6">
            @if($this->isJobFullyCompleted())
                <!-- JOB COMPLETION SUMMARY TERMINAL VIEW -->
                <div class="bg-surface-container-lowest p-6 rounded-3xl border border-outline-variant/60 shadow-sm space-y-6">
                    <!-- Hero Banner -->
                    <div class="p-6 text-white rounded-3xl shadow-md border border-emerald-800 space-y-4" style="background: linear-gradient(135deg, #064e3b 0%, #042f2e 50%, #0f172a 100%);">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-inner" style="background-color: rgba(16, 185, 129, 0.2); border: 1px solid rgba(52, 211, 153, 0.4);">
                                    <span class="material-symbols-outlined text-3xl" style="color: #6ee7b7;">task_alt</span>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-black font-display tracking-tight text-white">Custom Order Completed!</h2>
                                    <p class="text-xs mt-1 font-medium" style="color: #a7f3d0;">
                                        All dynamic task routings have been executed for <span class="font-bold text-white">{{ $customOrder->item_description }}</span> ({{ $customOrder->target_quantity }} Pcs).
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('admin.production.customized') }}" wire:navigate class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-xl shadow transition-all shrink-0">
                                Return to Customized Production Hub →
                            </a>
                        </div>
                    </div>

                    <!-- Performance Breakdown Cards -->
                    @php
                        $finalOutputQty = $job->final_produced_yield;
                        $totalMetersConsumed = round($job->materialConsumptions->sum('quantity_consumed'), 2);
                        $totalFabricCost = round($job->materialConsumptions->sum('total_cost'), 2);
                        $totalWorkersCount = $job->allocations->pluck('labor_id')->unique()->filter()->count();
                        $totalLaborWages = round($job->allocations->sum('calculated_wage'), 2);
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl space-y-1">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Final Produced Quantity</span>
                            <div class="text-xl font-black text-emerald-950 dark:text-emerald-100 font-mono">{{ $finalOutputQty }} Pcs</div>
                        </div>
                        <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl space-y-1">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300">Total Fabric Consumed</span>
                            <div class="text-xl font-black text-amber-950 dark:text-amber-100 font-mono">{{ $totalMetersConsumed }} m</div>
                            <div class="text-xs font-bold text-amber-700">Cost: ₹{{ number_format($totalFabricCost, 2) }}</div>
                        </div>
                        <div class="p-4 bg-blue-500/10 border border-blue-500/20 rounded-2xl space-y-1">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-800 dark:text-blue-300">Worker Wages Paid</span>
                            <div class="text-xl font-black text-blue-950 dark:text-blue-100 font-mono">₹{{ number_format($totalLaborWages, 2) }}</div>
                            <div class="text-xs font-bold text-blue-700">{{ $totalWorkersCount }} Worker(s)</div>
                        </div>
                    </div>
                </div>

            @elseif($activeStage)
                @php
                    $isFinalTask = $this->isFinalStage($activeStage);
                    $isCutting = $this->isCuttingStage($activeStage);
                @endphp
                <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/60 shadow-sm overflow-hidden space-y-6 p-6">
                    <!-- Stage Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-outline-variant/60 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-black text-on-surface">
                                    {{ $activeStage->sequence_number }}. {{ $activeStage->task?->name }}
                                </h2>
                                @if($isFinalTask)
                                    <span class="px-2.5 py-0.5 bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-500/30 text-[10px] font-black uppercase rounded-full">
                                        Final Stage
                                    </span>
                                @endif
                                @if($isCutting)
                                    <span class="px-2.5 py-0.5 bg-slate-900 text-white text-[10px] font-black uppercase rounded-full">
                                        Fabric Cutting Stage
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                                Dynamic Task Stage Execution Engine
                            </p>
                        </div>
                        <div>
                            <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider {{ $activeStage->status === 'completed' ? 'bg-emerald-500/10 text-emerald-800 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-800 border border-amber-500/30' }}">
                                {{ ucfirst(str_replace('_', ' ', $activeStage->status)) }}
                            </span>
                        </div>
                    </div>

                    <!-- Step Sub-Navigation Tabs -->
                    <div class="flex items-center border-b border-outline-variant/60 gap-2 overflow-x-auto text-xs font-extrabold pb-3">
                        @if($isCutting)
                            <button type="button" wire:click="goToStep(1)" class="px-3 py-1.5 rounded-xl transition-all shrink-0 {{ $activeStep === 1 ? 'bg-primary text-on-primary font-black shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                1. Fabric Selection &amp; Consumption
                            </button>
                            <button type="button" wire:click="goToStep(2)" class="px-3 py-1.5 rounded-xl transition-all shrink-0 {{ $activeStep === 2 ? 'bg-primary text-on-primary font-black shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                2. Labour &amp; Bonus Rate
                            </button>
                            <button type="button" wire:click="goToStep(3)" class="px-3 py-1.5 rounded-xl transition-all shrink-0 {{ $activeStep === 3 ? 'bg-primary text-on-primary font-black shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                3. Output Items
                            </button>
                            @if($isFinalTask)
                                <button type="button" wire:click="goToStep(4)" class="px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center gap-1 {{ $activeStep === 4 ? 'bg-primary text-on-primary font-black shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                    <span>4. Wastage &amp; Alteration</span>
                                    <span class="px-1 py-0.2 text-[9px] bg-amber-500/20 text-amber-300 rounded font-black">FINAL</span>
                                </button>
                                <button type="button" wire:click="goToStep(5)" class="px-3 py-1.5 rounded-xl transition-all shrink-0 {{ $activeStep === 5 ? 'bg-primary text-on-primary font-black shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                    5. Review &amp; Confirm
                                </button>
                            @else
                                <button type="button" wire:click="goToStep(4)" class="px-3 py-1.5 rounded-xl transition-all shrink-0 {{ $activeStep === 4 ? 'bg-primary text-on-primary font-black shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                    4. Review &amp; Confirm
                                </button>
                            @endif
                        @else
                            <button type="button" wire:click="goToStep(1)" class="px-3 py-1.5 rounded-xl transition-all shrink-0 {{ $activeStep === 1 ? 'bg-primary text-on-primary font-black shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                1. Labour &amp; Bonus Rate
                            </button>
                            <button type="button" wire:click="goToStep(2)" class="px-3 py-1.5 rounded-xl transition-all shrink-0 {{ $activeStep === 2 ? 'bg-primary text-on-primary font-black shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                2. Output Items
                            </button>
                            @if($isFinalTask)
                                <button type="button" wire:click="goToStep(3)" class="px-3 py-1.5 rounded-xl transition-all shrink-0 flex items-center gap-1 {{ $activeStep === 3 ? 'bg-primary text-on-primary font-black shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                    <span>3. Wastage &amp; Alteration</span>
                                    <span class="px-1 py-0.2 text-[9px] bg-amber-500/20 text-amber-300 rounded font-black">FINAL</span>
                                </button>
                                <button type="button" wire:click="goToStep(4)" class="px-3 py-1.5 rounded-xl transition-all shrink-0 {{ $activeStep === 4 ? 'bg-primary text-on-primary font-black shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                    4. Review &amp; Confirm
                                </button>
                            @else
                                <button type="button" wire:click="goToStep(3)" class="px-3 py-1.5 rounded-xl transition-all shrink-0 {{ $activeStep === 3 ? 'bg-primary text-on-primary font-black shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                    3. Review &amp; Confirm
                                </button>
                            @endif
                        @endif
                    </div>

                    <!-- STEP CONTENTS -->
                    <!-- 1. FABRIC SELECTION & CONSUMPTION (CUTTING STAGE) -->
                    @if($isCutting && $activeStep === 1)
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-black uppercase tracking-wider text-on-surface">Step 1: Fabric Selection, Width &amp; Bale Cutting</h3>
                                    <p class="text-xs text-on-surface-variant">Select fabric raw material, specify standard fabric width, select bale/rolls, open unopened bales, and record fabric cut lengths.</p>
                                </div>
                                <span class="px-3 py-1 bg-slate-900 text-white rounded-full text-[10px] font-black uppercase tracking-wider">
                                    RAW MATERIAL CONSUMPTION
                                </span>
                            </div>

                            <!-- Previously Recorded Fabric Consumptions -->
                            @if($job->materialConsumptions->isNotEmpty())
                                <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl space-y-2">
                                    <div class="text-xs font-extrabold text-amber-800 dark:text-amber-300 uppercase tracking-wider">Previously Recorded Fabric Consumptions on this Job:</div>
                                    <div class="space-y-1.5">
                                        @foreach($job->materialConsumptions as $mc)
                                            <div class="flex items-center justify-between text-xs font-semibold bg-surface-container-lowest p-2.5 rounded-xl border border-outline-variant/60">
                                                <div>
                                                    <span class="font-extrabold text-on-surface">{{ $mc->inventoryBatch?->rawMaterial?->name }}</span>
                                                    <span class="text-on-surface-variant text-[11px] block">
                                                        Bale {{ $mc->inventoryBaleRoll?->bale?->bale_number ?? 'Bale' }} · Roll {{ $mc->inventoryBaleRoll?->roll_number ?? 'Roll' }}
                                                    </span>
                                                </div>
                                                <div class="text-right">
                                                    <span class="font-black text-amber-800 dark:text-amber-300">{{ $mc->quantity_consumed }}m</span>
                                                    <span class="text-on-surface-variant text-[11px] block font-mono">₹{{ number_format($mc->total_cost, 2) }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @php
                                $cBreakdown = $this->fabricCuttingBreakdown;
                            @endphp
                            @if(!empty($cBreakdown) && !empty($cBreakdown['cut_area_base']))
                                <div class="bg-surface-container-low/60 border rounded-2xl p-4 mb-4 shadow-xs border-outline-variant/60">
                                    <div class="flex items-center justify-between mb-3 border-b border-outline-variant/30 pb-2">
                                        <span class="text-xs font-bold text-primary uppercase tracking-wider flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[18px]">aspect_ratio</span>
                                            Fabric Area & Auto-Calculated Wastage Summary
                                        </span>
                                        <span class="text-xs font-extrabold px-2.5 py-0.5 rounded-full {{ $cBreakdown['is_over_capacity'] ? 'bg-error/10 text-error' : 'bg-emerald-100 text-emerald-800' }}">
                                            {{ $cBreakdown['usage_percentage'] }}% Area Utilized
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs font-semibold">
                                        <div class="bg-surface-container-lowest p-2.5 rounded-xl border border-outline-variant/30">
                                            <span class="text-on-surface-variant block text-[10px] uppercase font-bold">Total Cut Area & Length</span>
                                            <span class="text-sm font-extrabold text-primary">{{ $cBreakdown['cut_area_base'] }} m²</span>
                                            <span class="text-[10px] text-on-surface-variant block font-medium">Cut: {{ $cBreakdown['total_cut_length'] ?? 0 }}m</span>
                                        </div>
                                        <div class="bg-surface-container-lowest p-2.5 rounded-xl border border-outline-variant/30">
                                            <span class="text-on-surface-variant block text-[10px] uppercase font-bold">Used Area by Order</span>
                                            <span class="text-sm font-extrabold {{ $cBreakdown['is_over_capacity'] ? 'text-error' : 'text-emerald-700' }}">{{ $cBreakdown['used_area_base'] }} m²</span>
                                            <span class="text-[10px] text-on-surface-variant block font-medium">Remaining: {{ $cBreakdown['remaining_area_base'] }} m²</span>
                                        </div>
                                        <div class="bg-surface-container-lowest p-2.5 rounded-xl border border-outline-variant/30">
                                            <span class="text-on-surface-variant block text-[10px] uppercase font-bold">Cutting Wastage</span>
                                            <span class="text-sm font-extrabold text-error">{{ $cBreakdown['wastage_length'] }} {{ $cBreakdown['unit_name'] ?? 'Meters' }}</span>
                                            <span class="text-[10px] text-error font-medium block">Auto-Calculated</span>
                                        </div>
                                        <div class="bg-surface-container-lowest p-2.5 rounded-xl border border-outline-variant/30">
                                            <span class="text-on-surface-variant block text-[10px] uppercase font-bold">Total Wastage Cost</span>
                                            <span class="text-sm font-extrabold text-error">₹{{ number_format($cBreakdown['total_wastage_cost'] ?? 0, 2) }}</span>
                                            <span class="text-[10px] text-on-surface-variant block font-medium">Allocated to Job Output</span>
                                        </div>
                                    </div>

                                    @if($cBreakdown['is_over_capacity'])
                                        <div class="mt-2.5 bg-error-container/20 border border-error/40 text-error rounded-xl p-2.5 text-xs font-bold flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[16px]">error</span>
                                            <span>Required custom order fabric area exceeds cut fabric area by {{ $cBreakdown['over_capacity_diff_base'] }} m²! Please increase cut roll lengths.</span>
                                        </div>
                                    @endif
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
                                    <div class="p-5 bg-surface-container-low/60 border border-outline-variant/60 rounded-2xl space-y-4">
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                                            <!-- Fabric Material -->
                                            <div class="sm:col-span-4">
                                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">FABRIC MATERIAL *</label>
                                                <select wire:model.live="selectedFabrics.{{ $fIdx }}.raw_material_id" class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs font-bold text-on-surface focus:outline-none">
                                                    <option value="">-- Select Fabric Raw Material --</option>
                                                    @foreach($fabricMaterials as $fm)
                                                        <option value="{{ $fm->id }}">{{ $fm->name }} ({{ $fm->code }})</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- Stock Batch -->
                                            <div class="sm:col-span-4">
                                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">STOCK BATCH *</label>
                                                <select wire:model.live="selectedFabrics.{{ $fIdx }}.inventory_batch_id" class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs font-bold text-on-surface focus:outline-none">
                                                    <option value="">-- Select Batch --</option>
                                                    @foreach($availableBatches as $b)
                                                        <option value="{{ $b->id }}">{{ $b->batch_number }} (Bal: {{ $b->balance_quantity }} {{ $b->unit }})</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- Bale Dropdown -->
                                            <div class="sm:col-span-4">
                                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">BALE *</label>
                                                <select wire:model.live="selectedFabrics.{{ $fIdx }}.inventory_bale_id" class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant/60 rounded-xl text-xs font-bold text-on-surface focus:outline-none">
                                                    <option value="">-- Select Bale --</option>
                                                    @foreach($availableBales as $bale)
                                                        <option value="{{ $bale->id }}">Bale {{ $bale->bale_number }} ({{ $bale->status === 'opened' ? 'Opened - ' . $bale->rolls()->count() . ' rolls' : 'Unopened - ' . $bale->declared_length . 'm' }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Bale Rolls Breakdown / Open Bale Actions -->
                                        @if($selectedBale)
                                            <div class="pt-3 border-t border-outline-variant/60 space-y-3">
                                                @if($selectedBale->status === 'unopened')
                                                    <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-xl flex items-center justify-between gap-4">
                                                        <div>
                                                            <div class="text-xs font-black text-amber-800 dark:text-amber-300">Bale {{ $selectedBale->bale_number }} is Unopened</div>
                                                            <div class="text-[11px] text-on-surface-variant">Declared Length: {{ $selectedBale->declared_length }}m. Click to open and record roll count & measured lengths.</div>
                                                        </div>
                                                        <button type="button" wire:click="triggerOpenBaleModal({{ $selectedBale->id }})" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded-xl shadow-xs shrink-0">
                                                            Open Bale &amp; Measure Rolls →
                                                        </button>
                                                    </div>
                                                @else
                                                    <!-- Opened Bale Rolls Selection -->
                                                    <div class="space-y-3 w-full">
                                                        <div class="text-xs font-extrabold text-on-surface">Available Rolls in Bale {{ $selectedBale->bale_number }}:</div>
                                                        <div class="space-y-4 w-full">
                                                            @foreach($selectedBale->rolls as $roll)
                                                                @php
                                                                    $isSelected = isset($fab['selected_rolls'][$roll->id]);
                                                                    $rData = $fab['selected_rolls'][$roll->id] ?? null;
                                                                    $rollWidthText = null;
                                                                    if ($roll->fabricWidth) {
                                                                        $rollWidthText = $roll->fabricWidth->name ? ($roll->fabricWidth->name . ' (' . $roll->fabricWidth->value . $roll->fabricWidth->unit . ')') : ($roll->fabricWidth->value . $roll->fabricWidth->unit);
                                                                    } elseif ($roll->rawMaterial && $roll->rawMaterial->standard_width) {
                                                                        $rollWidthText = $roll->rawMaterial->standard_width . ($roll->rawMaterial->width_unit ?? '"');
                                                                    }
                                                                @endphp
                                                                <div class="w-full p-4 rounded-2xl border transition-all {{ $isSelected ? 'bg-primary/5 border-primary shadow-xs ring-1 ring-primary/20' : 'bg-surface-container-lowest border-outline-variant/60' }}">
                                                                    <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-outline-variant/40">
                                                                        <div class="flex flex-wrap items-center gap-3">
                                                                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                                                                <input type="checkbox" wire:click="toggleRollSelection({{ $fIdx }}, {{ $roll->id }})" {{ $isSelected ? 'checked' : '' }} class="rounded text-primary focus:ring-primary h-5 w-5" />
                                                                                <span class="font-mono font-extrabold text-sm text-primary px-2.5 py-1 bg-primary/10 rounded-lg">Roll #{{ $roll->roll_number }}</span>
                                                                            </label>
                                                                            @if($roll->design_number)
                                                                                <span class="px-2.5 py-1 bg-surface-container text-on-surface-variant text-xs font-bold rounded-lg border border-outline-variant/60">Design: {{ $roll->design_number }}</span>
                                                                            @endif
                                                                            @if($rollWidthText)
                                                                                <span class="px-2.5 py-1 bg-primary/10 text-primary text-xs font-bold rounded-lg border border-primary/20">Width: {{ $rollWidthText }}</span>
                                                                            @endif
                                                                        </div>
                                                                        <span class="text-xs font-bold text-on-surface-variant">
                                                                            Available Balance: <strong class="text-on-surface px-2 py-0.5 bg-surface-container rounded-lg font-mono">{{ $roll->current_balance_length }}m</strong>
                                                                        </span>
                                                                    </div>

                                                                    @if($isSelected)
                                                                        <div class="space-y-4 pt-3">
                                                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-surface-container-low p-3 rounded-xl border border-outline-variant/40">
                                                                                <div class="flex items-center gap-2 flex-1 max-w-md">
                                                                                    <label class="text-xs font-extrabold text-on-surface-variant uppercase tracking-wider whitespace-nowrap">Cut Length (m):</label>
                                                                                    <input type="number" step="0.1" max="{{ $roll->current_balance_length }}" wire:model.live.debounce.300ms="selectedFabrics.{{ $fIdx }}.selected_rolls.{{ $roll->id }}.cut_length" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-extrabold text-on-surface focus:border-primary focus:outline-none" placeholder="Enter meters to cut..." />
                                                                                </div>
                                                                                <button type="button" wire:click="setFullRollCut({{ $fIdx }}, {{ $roll->id }})" class="px-4 py-2 bg-primary text-on-primary font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container transition-all flex items-center justify-center gap-1.5 shrink-0 active:scale-95">
                                                                                    <span class="material-symbols-outlined text-[16px]">content_cut</span>
                                                                                    Cut Full Roll ({{ $roll->current_balance_length }}m)
                                                                                </button>
                                                                            </div>

                                                                            @php
                                                                                $cLenVal = floatval($rData['cut_length'] ?? 0);
                                                                            @endphp
                                                                            @if($cLenVal > 0)
                                                                                @php
                                                                                    $rLive = $this->getRollCutBreakdown($roll->id, $cLenVal, $fab['raw_material_id'] ?? null);
                                                                                @endphp
                                                                                @if($rLive)
                                                                                    <div class="p-4 bg-primary/5 border border-primary/20 rounded-xl text-xs space-y-3 shadow-2xs">
                                                                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pb-3 border-b border-primary/10">
                                                                                            <div class="space-y-1">
                                                                                                <p class="text-[10px] font-extrabold text-primary uppercase tracking-wider">Cut Area &amp; Dimensions</p>
                                                                                                <div class="flex items-center gap-2">
                                                                                                    <span class="material-symbols-outlined text-primary text-lg">aspect_ratio</span>
                                                                                                    <span class="text-sm font-black text-on-surface">{{ $rLive['cut_area_m2'] }} m²</span>
                                                                                                </div>
                                                                                                <p class="text-[11px] text-on-surface-variant font-bold">Standard Width: <span class="text-primary">{{ $rLive['roll_width_display'] }}</span></p>
                                                                                                <p class="text-[11px] text-on-surface-variant">Cut Length: <span class="font-bold text-on-surface">{{ $cLenVal }}m</span></p>
                                                                                            </div>

                                                                                            <div class="space-y-1">
                                                                                                <p class="text-[10px] font-extrabold text-emerald-700 uppercase tracking-wider">Est. Production Yield</p>
                                                                                                <div class="flex items-center gap-2">
                                                                                                    <span class="material-symbols-outlined text-emerald-600 text-lg">check_box</span>
                                                                                                    <span class="text-sm font-black text-emerald-700">{{ $rLive['est_yield_pieces'] }} Pcs</span>
                                                                                                </div>
                                                                                                <p class="text-[11px] text-on-surface-variant font-bold">Product: <span class="text-on-surface font-extrabold">{{ $rLive['product_name'] }}</span></p>
                                                                                                <p class="text-[11px] text-on-surface-variant font-semibold">Pattern Length Req: <span class="font-bold text-on-surface">{{ $rLive['piece_req_length'] }}m / pc</span></p>
                                                                                            </div>

                                                                                            <div class="space-y-1">
                                                                                                <p class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Job Target &amp; Allocation</p>
                                                                                                @if($rLive['target_qty'] > 0)
                                                                                                    <p class="text-[11px] text-on-surface-variant font-semibold">Job Target: <span class="font-bold text-on-surface">{{ $rLive['target_qty'] }} Pcs Req</span> ({{ $rLive['target_req_length'] }}m / {{ $rLive['target_req_area_m2'] }} m²)</p>
                                                                                                    @if($rLive['wastage_length'] > 0)
                                                                                                        <p class="text-[11px] text-amber-800 font-bold">Wastage Length: <span>{{ $rLive['wastage_length'] }}m (₹{{ number_format($rLive['wastage_cost'], 2) }})</span></p>
                                                                                                    @elseif($rLive['shortfall_pieces'] > 0)
                                                                                                        <p class="text-[11px] text-error font-bold">Shortfall for Target: <span>{{ $rLive['shortfall_pieces'] }} Pcs short</span></p>
                                                                                                    @endif
                                                                                                @endif
                                                                                            </div>
                                                                                        </div>

                                                                                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs pt-1">
                                                                                            <div class="flex items-center gap-1.5 text-emerald-800 font-extrabold bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-200">
                                                                                                <span class="material-symbols-outlined text-[16px]">verified</span>
                                                                                                <span>Yield covers {{ $rLive['target_qty'] }} Pcs target + {{ $rLive['surplus_pieces'] }} surplus Pcs</span>
                                                                                            </div>
                                                                                            <span class="text-[11px] text-on-surface-variant font-bold">Live calculation based on standard width &amp; cut pattern area</span>
                                                                                        </div>
                                                                                    </div>
                                                                                @endif
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex items-center justify-between pt-2">
                                <button type="button" wire:click="addFabricRow" class="px-3.5 py-2 bg-surface-container-high text-on-surface text-xs font-bold rounded-xl flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">add</span>
                                    <span>+ Add Another Fabric</span>
                                </button>
                                <button type="button" wire:click="recordFabricConsumption" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded-xl shadow-xs">
                                    Record Fabric Cut Consumption
                                </button>
                            </div>

                            <div class="pt-4 flex justify-end border-t border-outline-variant/60">
                                <button type="button" wire:click="goToStep(2)" class="px-5 py-2 bg-primary text-on-primary text-xs font-bold rounded-xl shadow-xs">
                                    Next Step: Labour &amp; Bonus Rate →
                                </button>
                            </div>
                        </div>
                    @elseif($activeStep === ($isCutting ? 2 : 1))
                        <!-- LABOUR & BONUS RATE STEP -->
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-black uppercase tracking-wider text-on-surface">Labour Allocation &amp; Piece Quantities</h3>
                                    <p class="text-xs text-on-surface-variant">Assign workers, piece quantity worked upon per worker, base rates, and bonus rates.</p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-[10px] font-black bg-primary/10 text-primary border border-primary/20">
                                    MULTI-WORKER ALLOCATION
                                </span>
                            </div>

                            @error('laborRows')
                                <div class="p-3 bg-rose-500/10 border border-rose-500/20 text-rose-700 rounded-xl text-xs font-bold mb-3">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="space-y-4">
                                @foreach($laborRows as $idx => $alloc)
                                    <div class="p-5 bg-surface-container-low/60 border border-outline-variant/60 rounded-2xl space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-extrabold text-on-surface">Worker #{{ $idx + 1 }}</span>
                                            @if(count($laborRows) > 1)
                                                <button type="button" wire:click="removeLaborRow({{ $idx }})" class="p-1 text-red-500 hover:bg-red-500/10 rounded-lg">
                                                    <span class="material-symbols-outlined text-[16px]">close</span>
                                                </button>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                            <div class="sm:col-span-2">
                                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">WORKER NAME *</label>
                                                <select wire:model.live="laborRows.{{ $idx }}.labor_id" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 font-bold text-on-surface">
                                                    <option value="">Select Worker...</option>
                                                    @foreach($labors as $l)
                                                        <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->worker_type ?: 'Operator' }})</option>
                                                    @endforeach
                                                </select>
                                                @error("laborRows.{$idx}.labor_id")
                                                    <span class="text-xs font-bold text-rose-600 block mt-1">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">QTY WORKED UPON (PCS) *</label>
                                                <input type="number" min="1" wire:model.live="laborRows.{{ $idx }}.processed_qty" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 font-mono font-bold text-on-surface" />
                                                @error("laborRows.{$idx}.processed_qty")
                                                    <span class="text-xs font-bold text-rose-600 block mt-1">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">BASE RATE (₹)</label>
                                                <input type="number" step="0.5" wire:model.live="laborRows.{{ $idx }}.base_rate" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 font-mono font-bold text-on-surface" />
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <button type="button" wire:click="addLaborRow" class="px-3.5 py-2 bg-surface-container-high hover:bg-surface-container-highest text-primary text-xs font-bold rounded-xl flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">add</span>
                                <span>+ Add Worker Allocation</span>
                            </button>

                            <div class="pt-4 flex items-center justify-between border-t border-outline-variant/60">
                                <button type="button" wire:click="goToStep({{ $isCutting ? 1 : 1 }})" class="px-4 py-2 bg-surface-container-high text-xs font-bold rounded-xl">
                                    ← Back
                                </button>
                                <button type="button" wire:click="goToStep({{ $isCutting ? 3 : 2 }})" class="px-5 py-2 bg-primary text-on-primary text-xs font-bold rounded-xl shadow-xs">
                                    Next Step: Output Items →
                                </button>
                            </div>
                        </div>
                    @elseif($activeStep === ($isCutting ? 3 : 2))
                        <!-- OUTPUT ITEMS STEP -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-wider text-on-surface">Step: Production Output Items</h3>
                                <p class="text-xs text-on-surface-variant">Verify pieces produced in this stage execution.</p>
                            </div>

                            <div class="p-5 bg-surface-container-low/60 border border-outline-variant/60 rounded-2xl space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">STAGE TARGET QUANTITY (PCS)</label>
                                        <input type="number" readonly value="{{ $activeStage->target_quantity }}" class="w-full px-3 py-2 bg-surface-container-high text-xs rounded-xl border border-outline-variant/60 font-mono font-bold text-on-surface-variant" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">QUANTITY PRODUCED (PCS) *</label>
                                        <input type="number" min="0" wire:model.live.debounce.500ms="producedQty" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 font-mono font-bold text-on-surface" />
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 flex items-center justify-between border-t border-outline-variant/60">
                                <button type="button" wire:click="goToStep({{ $isCutting ? 2 : 1 }})" class="px-4 py-2 bg-surface-container-high text-xs font-bold rounded-xl">
                                    ← Back
                                </button>
                                <button type="button" wire:click="goToStep({{ $isCutting ? ($isFinalTask ? 4 : 4) : ($isFinalTask ? 3 : 3) }})" class="px-5 py-2 bg-primary text-on-primary text-xs font-bold rounded-xl shadow-xs">
                                    Next Step →
                                </button>
                            </div>
                        </div>
                    @elseif($isFinalTask && $activeStep === ($isCutting ? 4 : 3))
                        <!-- WASTAGE & ALTERATION STEP (FINAL STAGE ONLY) -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-wider text-on-surface">Step: Final Task Wastage &amp; Reconciliation</h3>
                                <p class="text-xs text-on-surface-variant">Record scrap, damage, and alterations before completing custom work order.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl space-y-3">
                                    <label class="block text-xs font-black uppercase text-amber-800 dark:text-amber-300">SCRAP QUANTITY (PCS)</label>
                                    <input type="number" min="0" wire:model.live.debounce.500ms="scrapQty" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 font-mono font-bold text-on-surface" />
                                    <input type="text" wire:model.live.debounce.500ms="scrapNotes" placeholder="Reason for scrap..." class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 text-on-surface" />
                                </div>
                                <div class="p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl space-y-3">
                                    <label class="block text-xs font-black uppercase text-rose-800 dark:text-rose-300">DAMAGE QUANTITY (PCS)</label>
                                    <input type="number" min="0" wire:model.live.debounce.500ms="damageQty" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 font-mono font-bold text-on-surface" />
                                    <input type="text" wire:model.live.debounce.500ms="damageNotes" placeholder="Reason for damage..." class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 text-on-surface" />
                                </div>
                            </div>

                            <div class="pt-4 flex items-center justify-between border-t border-outline-variant/60">
                                <button type="button" wire:click="goToStep({{ $isCutting ? 3 : 2 }})" class="px-4 py-2 bg-surface-container-high text-xs font-bold rounded-xl">
                                    ← Back
                                </button>
                                <button type="button" wire:click="goToStep({{ $isCutting ? 5 : 4 }})" class="px-5 py-2 bg-primary text-on-primary text-xs font-bold rounded-xl shadow-xs">
                                    Next Step: Review &amp; Confirm →
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- REVIEW & CONFIRM STEP -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-wider text-on-surface">Step: Review &amp; Confirm Stage Execution</h3>
                                <p class="text-xs text-on-surface-variant">Review stage summary and complete execution.</p>
                            </div>

                            <div class="p-5 bg-surface-container-low/60 border border-outline-variant/60 rounded-2xl space-y-3 text-xs">
                                <div class="flex justify-between border-b border-outline-variant/40 pb-2">
                                    <span class="font-bold text-on-surface-variant">Custom Item:</span>
                                    <span class="font-black text-on-surface">{{ $customOrder->item_description }}</span>
                                </div>
                                <div class="flex justify-between border-b border-outline-variant/40 pb-2">
                                    <span class="font-bold text-on-surface-variant">Target Quantity:</span>
                                    <span class="font-mono font-black text-on-surface">{{ $customOrder->target_quantity }} Pcs</span>
                                </div>
                                <div class="flex justify-between border-b border-outline-variant/40 pb-2">
                                    <span class="font-bold text-on-surface-variant">Active Task Stage:</span>
                                    <span class="font-extrabold text-primary">{{ $activeStage->task?->name }}</span>
                                </div>
                                <div class="flex justify-between border-b border-outline-variant/40 pb-2">
                                    <span class="font-bold text-on-surface-variant">Designated Final Stage:</span>
                                    <span class="font-bold {{ $isFinalTask ? 'text-emerald-600' : 'text-on-surface-variant' }}">
                                        {{ $isFinalTask ? 'YES (Will complete custom order)' : 'NO' }}
                                    </span>
                                </div>
                            </div>

                            <div class="pt-4 flex items-center justify-between border-t border-outline-variant/60">
                                <button type="button" wire:click="goToStep({{ $isCutting ? ($isFinalTask ? 4 : 3) : ($isFinalTask ? 3 : 2) }})" class="px-4 py-2 bg-surface-container-high text-xs font-bold rounded-xl">
                                    ← Back
                                </button>
                                @if($activeStage->status !== 'completed')
                                    <button type="button" wire:click="completeActiveStage" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl shadow-md flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                        <span>Confirm &amp; Complete Task Stage</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- MODAL: OPEN UNOPENED BALE & MEASURE ROLLS -->
    @if($showOpenBaleModal)
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4 overflow-y-auto">
            <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/60 p-6 max-w-xl w-full shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-outline-variant/60 pb-3">
                    <h3 class="text-base font-black text-on-surface">Open Bale &amp; Measure Rolls</h3>
                    <button type="button" wire:click="$set('showOpenBaleModal', false)" class="p-1 text-on-surface-variant hover:text-on-surface rounded-lg">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-extrabold uppercase text-on-surface-variant mb-1">NUMBER OF ROLLS IN BALE *</label>
                        <input type="number" min="1" max="50" wire:model.live.debounce.300ms="baleRollCount" placeholder="e.g. 5" class="w-full px-3 py-2 bg-surface-container-lowest border border-outline-variant/60 rounded-xl font-bold text-on-surface" />
                        @error('baleRollCount') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if(!empty($baleRollLengths))
                        <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                            @foreach($baleRollLengths as $i => $len)
                                <div class="p-3 bg-surface-container-low/60 border border-outline-variant/60 rounded-xl grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] font-bold text-on-surface-variant uppercase">Roll #{{ $i + 1 }} Measured Length (m) *</label>
                                        <input type="number" step="0.1" wire:model.live.debounce.300ms="baleRollLengths.{{ $i }}" class="w-full px-2.5 py-1.5 bg-surface-container-lowest border border-outline-variant/60 rounded-lg font-mono font-bold" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-on-surface-variant uppercase">Material Override</label>
                                        <select wire:model.live="baleRollMaterials.{{ $i }}" class="w-full px-2.5 py-1.5 bg-surface-container-lowest border border-outline-variant/60 rounded-lg text-xs font-semibold">
                                            @foreach($baleAllowedMaterials as $mat)
                                                <option value="{{ $mat['id'] }}">{{ $mat['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($baleMismatchWarning)
                        <div class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl text-amber-800 dark:text-amber-300 font-medium text-[11px]">
                            {{ $baleMismatchWarning }}
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-outline-variant/60">
                    <button type="button" wire:click="$set('showOpenBaleModal', false)" class="px-4 py-2 bg-surface-container-high text-xs font-bold rounded-xl">
                        Cancel
                    </button>
                    <button type="button" wire:click="submitOpenedBaleForm" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs">
                        Save Opened Bale Rolls →
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
