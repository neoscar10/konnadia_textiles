<div class="space-y-6">
    <!-- Navigation Back Link -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.production.customized') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs font-bold text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            <span>Back to Customized Production Hub</span>
        </a>
    </div>

    <!-- Top Summary Card -->
    <div class="bg-surface-container-lowest p-6 rounded-3xl border border-outline-variant/60 shadow-sm space-y-4">
        <div>
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300">CUSTOMIZED WORK ORDER SPECIFICATIONS</div>
            <h1 class="text-2xl font-black tracking-tight text-on-surface mt-0.5">
                Custom Item: {{ $customOrder->item_description }}
            </h1>
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

    @if($customOrder->status === 'completed')
        <!-- Completed Hero Banner -->
        <div class="p-6 rounded-3xl bg-gradient-to-r from-emerald-950 via-teal-900 to-slate-900 text-white border border-emerald-500/30 shadow-xl flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-3xl">check_circle</span>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white">Customized Production Order Completed!</h3>
                    <p class="text-xs text-emerald-200/80 font-medium">
                        All dynamic task routings have been executed and {{ $customOrder->target_quantity }} Pcs finished goods are ready.
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.production.customized') }}" wire:navigate class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs rounded-xl shadow transition-all shrink-0">
                Return to Customized Production Hub →
            </a>
        </div>
    @endif

    <!-- Main Two-Column Layout -->
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
                            $isCurrentSelected = ($selectedStageId == $taskRow['id']);
                        @endphp
                        <div wire:click="selectStage({{ $taskRow['id'] }})" class="p-4 rounded-2xl border transition-all cursor-pointer relative {{ $isCurrentSelected ? 'bg-amber-500/10 border-amber-500/50 shadow-md' : 'bg-surface-container-low/40 border-outline-variant/60 hover:bg-surface-container-low' }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-extrabold text-on-surface">Task #{{ $idx + 1 }}</span>
                                <div class="flex items-center gap-1.5">
                                    @if($taskRow['status'] === 'completed')
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

                                    @if(count($dynamicTaskRows) > 1 && $taskRow['status'] !== 'completed')
                                        <button type="button" wire:click.stop="removeDynamicTaskStage({{ $idx }})" class="p-1 text-on-surface-variant/60 hover:text-red-500 rounded-lg hover:bg-red-500/10 transition-colors">
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

                                <!-- Final Stage Marking Toggle -->
                                <label class="flex items-center gap-2 text-xs font-bold text-on-surface cursor-pointer select-none">
                                    <input type="radio" name="finalStageRadio" wire:click="setFinalStage({{ $idx }})" {{ $taskRow['is_final_step'] ? 'checked' : '' }} class="w-3.5 h-3.5 text-amber-600 focus:ring-amber-500" />
                                    <span class="{{ $taskRow['is_final_step'] ? 'text-amber-800 dark:text-amber-300 font-extrabold' : 'text-on-surface-variant' }}">Final Stage</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" wire:click="addDynamicTaskStage" class="w-full py-2.5 px-4 bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-xs font-bold rounded-2xl border border-outline-variant/60 transition-all flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    <span>Add Dynamic Task Stage</span>
                </button>
            </div>
        </div>

        <!-- RIGHT COLUMN (lg:col-span-8): Selected Task Stage Execution Terminal Engine -->
        <div class="lg:col-span-8 space-y-4">
            @if($selectedStage)
                @php
                    $taskName = strtolower($selectedStage->task?->name ?? '');
                    $isCuttingStage = str_contains($taskName, 'cut');
                @endphp

                <div class="bg-surface-container-lowest p-6 rounded-3xl border border-outline-variant/60 shadow-sm space-y-6">
                    <!-- Terminal Header -->
                    <div class="flex items-center justify-between border-b border-outline-variant/60 pb-4">
                        <div>
                            <h2 class="text-xl font-black text-on-surface flex items-center gap-2">
                                {{ $selectedStage->sequence_number }}. {{ $selectedStage->task?->name }}
                            </h2>
                            <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                                Dynamic Task Stage Execution Engine
                            </p>
                        </div>
                        <div>
                            @if($selectedStage->status === 'completed')
                                <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border border-emerald-500/20">
                                    COMPLETED
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-amber-500/10 text-amber-800 dark:text-amber-300 border border-amber-500/20">
                                    IN PROGRESS
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Stage Step Wizard Navigation Tabs -->
                    <div class="flex items-center border-b border-outline-variant/60 gap-4 text-xs font-extrabold">
                        @if($isCuttingStage)
                            <button type="button" wire:click="setWizardStep(1)" class="pb-3 border-b-2 transition-colors {{ $wizardStep === 1 ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/60 hover:text-on-surface' }}">
                                1. Fabric Selection
                            </button>
                            <button type="button" wire:click="setWizardStep(2)" class="pb-3 border-b-2 transition-colors {{ $wizardStep === 2 ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/60 hover:text-on-surface' }}">
                                2. Labour & Bonus
                            </button>
                            <button type="button" wire:click="setWizardStep(3)" class="pb-3 border-b-2 transition-colors {{ $wizardStep === 3 ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/60 hover:text-on-surface' }}">
                                3. Review & Confirm
                            </button>
                        @else
                            <button type="button" wire:click="setWizardStep(1)" class="pb-3 border-b-2 transition-colors {{ $wizardStep === 1 ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/60 hover:text-on-surface' }}">
                                1. Labour & Bonus
                            </button>
                            <button type="button" wire:click="setWizardStep(2)" class="pb-3 border-b-2 transition-colors {{ $wizardStep === 2 ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant/60 hover:text-on-surface' }}">
                                2. Review & Confirm
                            </button>
                        @endif
                    </div>

                    <!-- STEP CONTENTS -->
                    @if($isCuttingStage)
                        <!-- CUTTING STAGE WIZARD -->
                        @if($wizardStep === 1)
                            <!-- Step 1: Fabric Selection & Roll Cut -->
                            <div class="space-y-4">
                                <h3 class="text-xs font-black uppercase tracking-wider text-on-surface">Step 1: Fabric Selection & Roll Cut</h3>

                                <div>
                                    <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">SELECT FABRIC INVENTORY BATCH *</label>
                                    <select wire:model.live="cuttingFabricBatchId" class="w-full px-3.5 py-2.5 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold">
                                        <option value="">Select Fabric Batch...</option>
                                        @foreach($fabricBatches as $fb)
                                            <option value="{{ $fb->id }}">{{ $fb->batch_number }} — {{ $fb->rawMaterial?->name }} (Available: {{ $fb->balance_quantity }} {{ $fb->unit }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Active Roll Cards -->
                                @forelse($cuttingBaleRows as $bIndex => $bRow)
                                    <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-3">
                                        <div class="font-bold text-xs text-on-surface">Bale {{ $bRow['bale_number'] }}</div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            @foreach($bRow['selected_rolls'] as $rId => $rData)
                                                <div wire:click="toggleRollSelection({{ $bIndex }}, {{ $rId }})" class="p-3 rounded-xl border cursor-pointer transition-all {{ !empty($rData['is_selected']) ? 'bg-primary/10 border-primary shadow-sm' : 'bg-surface-container-lowest border-outline-variant/60' }}">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="font-extrabold text-xs text-on-surface">Roll #{{ $rData['roll_number'] }}</span>
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-secondary-container/40 text-on-secondary-container">
                                                            Width: {{ $rData['width_display'] }}
                                                        </span>
                                                    </div>
                                                    <div class="text-[11px] text-on-surface-variant font-medium">
                                                        Length: {{ $rData['max_length'] }}m available
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-6 text-center text-xs text-on-surface-variant italic border rounded-2xl">
                                        No active rolls found for selected batch. Please choose a fabric batch above.
                                    </div>
                                @endforelse

                                <div class="pt-4 flex justify-end">
                                    <button type="button" wire:click="setWizardStep(2)" class="px-5 py-2 bg-primary text-on-primary text-xs font-bold rounded-xl shadow">
                                        Next: Labour & Bonus →
                                    </button>
                                </div>
                            </div>
                        @elseif($wizardStep === 2)
                            <!-- Step 2: Labour & Piece Quantities -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-xs font-black uppercase tracking-wider text-on-surface">Step 2: Labour Allocation & Piece Quantities</h3>
                                        <p class="text-[11px] text-on-surface-variant">Assign workers, piece quantity worked upon per worker, base rates, and bonus rates.</p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black bg-primary/10 text-primary border border-primary/20">
                                        Multi-Worker Allocation
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    @foreach($cuttingLaborAllocations as $idx => $alloc)
                                        <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-extrabold text-on-surface">Worker #{{ $idx + 1 }}</span>
                                                @if(count($cuttingLaborAllocations) > 1)
                                                    <button type="button" wire:click="removeCuttingWorkerRow({{ $idx }})" class="p-1 text-red-500 hover:bg-red-500/10 rounded-lg">
                                                        <span class="material-symbols-outlined text-[16px]">close</span>
                                                    </button>
                                                @endif
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                                <div class="sm:col-span-2">
                                                    <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">WORKER NAME *</label>
                                                    <select wire:model="cuttingLaborAllocations.{{ $idx }}.labor_id" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 font-semibold text-on-surface">
                                                        <option value="">Select Worker...</option>
                                                        @foreach($allLabors as $l)
                                                            <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->worker_type ?: 'Cutter' }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">QTY WORKED UPON (PCS) *</label>
                                                    <input type="number" min="1" wire:model="cuttingLaborAllocations.{{ $idx }}.quantity" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 font-mono font-bold text-on-surface" />
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">BASE RATE (₹)</label>
                                                    <input type="number" step="0.5" wire:model="cuttingLaborAllocations.{{ $idx }}.base_rate" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 font-mono font-bold text-on-surface" />
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" wire:click="addCuttingWorkerRow" class="px-3.5 py-1.5 bg-surface-container-high hover:bg-surface-container-highest text-primary text-xs font-bold rounded-xl flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">add</span>
                                    <span>Add Worker Allocation</span>
                                </button>

                                <div class="pt-4 flex items-center justify-between border-t border-outline-variant/60">
                                    <button type="button" wire:click="setWizardStep(1)" class="px-4 py-2 bg-surface-container-high text-xs font-bold rounded-xl">
                                        ← Back
                                    </button>
                                    <button type="button" wire:click="setWizardStep(3)" class="px-5 py-2 bg-primary text-on-primary text-xs font-bold rounded-xl shadow">
                                        Next: Review & Confirm →
                                    </button>
                                </div>
                            </div>
                        @else
                            <!-- Step 3: Review & Confirm -->
                            <div class="space-y-4">
                                <h3 class="text-xs font-black uppercase tracking-wider text-on-surface">Step 3: Review & Confirm Stage Execution</h3>

                                <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-2 text-xs">
                                    <div class="flex justify-between">
                                        <span class="font-bold text-on-surface-variant">Custom Item:</span>
                                        <span class="font-black text-on-surface">{{ $customOrder->item_description }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="font-bold text-on-surface-variant">Target Quantity:</span>
                                        <span class="font-mono font-black text-on-surface">{{ $customOrder->target_quantity }} Pcs</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="font-bold text-on-surface-variant">Active Task Stage:</span>
                                        <span class="font-extrabold text-primary">{{ $selectedStage->task?->name }}</span>
                                    </div>
                                </div>

                                <div class="pt-4 flex items-center justify-between border-t border-outline-variant/60">
                                    <button type="button" wire:click="setWizardStep(2)" class="px-4 py-2 bg-surface-container-high text-xs font-bold rounded-xl">
                                        ← Back
                                    </button>
                                    @if($selectedStage->status !== 'completed')
                                        <button type="button" wire:click="completeCurrentStage" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                            <span>Confirm & Complete Task Stage</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif

                    @else
                        <!-- NON-CUTTING STAGE WIZARD -->
                        @if($wizardStep === 1)
                            <!-- Step 1: Labour & Piece Quantities -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-xs font-black uppercase tracking-wider text-on-surface">Step 1: Labour Allocation & Piece Quantities</h3>
                                        <p class="text-[11px] text-on-surface-variant">Assign workers, piece quantity worked upon per worker, base rates, and bonus rates.</p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black bg-primary/10 text-primary border border-primary/20">
                                        Multi-Worker Allocation
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    @foreach($stageLaborRows as $idx => $alloc)
                                        <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs font-extrabold text-on-surface">Worker #{{ $idx + 1 }}</span>
                                                @if(count($stageLaborRows) > 1)
                                                    <button type="button" wire:click="removeNonCuttingWorkerRow({{ $idx }})" class="p-1 text-red-500 hover:bg-red-500/10 rounded-lg">
                                                        <span class="material-symbols-outlined text-[16px]">close</span>
                                                    </button>
                                                @endif
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                                <div class="sm:col-span-2">
                                                    <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">WORKER NAME *</label>
                                                    <select wire:model="stageLaborRows.{{ $idx }}.labor_id" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 font-semibold text-on-surface">
                                                        <option value="">Select Worker...</option>
                                                        @foreach($allLabors as $l)
                                                            <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->worker_type ?: 'Operator' }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">QTY WORKED UPON (PCS) *</label>
                                                    <input type="number" min="1" wire:model="stageLaborRows.{{ $idx }}.quantity" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 font-mono font-bold text-on-surface" />
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">BASE RATE (₹)</label>
                                                    <input type="number" step="0.5" wire:model="stageLaborRows.{{ $idx }}.base_rate" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 font-mono font-bold text-on-surface" />
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" wire:click="addNonCuttingWorkerRow" class="px-3.5 py-1.5 bg-surface-container-high hover:bg-surface-container-highest text-primary text-xs font-bold rounded-xl flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">add</span>
                                    <span>Add Worker Allocation</span>
                                </button>

                                <div class="pt-4 flex justify-end">
                                    <button type="button" wire:click="setWizardStep(2)" class="px-5 py-2 bg-primary text-on-primary text-xs font-bold rounded-xl shadow">
                                        Next: Review & Confirm →
                                    </button>
                                </div>
                            </div>
                        @else
                            <!-- Step 2: Review & Confirm -->
                            <div class="space-y-4">
                                <h3 class="text-xs font-black uppercase tracking-wider text-on-surface">Step 2: Review & Confirm Stage Execution</h3>

                                <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-2 text-xs">
                                    <div class="flex justify-between">
                                        <span class="font-bold text-on-surface-variant">Custom Item:</span>
                                        <span class="font-black text-on-surface">{{ $customOrder->item_description }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="font-bold text-on-surface-variant">Output Quantity:</span>
                                        <span class="font-mono font-black text-on-surface">{{ $stageOutputQty }} Pcs</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="font-bold text-on-surface-variant">Active Stage:</span>
                                        <span class="font-extrabold text-primary">{{ $selectedStage->task?->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="font-bold text-on-surface-variant">Designated Final Stage:</span>
                                        <span class="font-bold {{ $selectedStage->is_final_step ? 'text-emerald-600' : 'text-on-surface-variant' }}">
                                            {{ $selectedStage->is_final_step ? 'YES (Will complete custom order)' : 'NO' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="pt-4 flex items-center justify-between border-t border-outline-variant/60">
                                    <button type="button" wire:click="setWizardStep(1)" class="px-4 py-2 bg-surface-container-high text-xs font-bold rounded-xl">
                                        ← Back
                                    </button>
                                    @if($selectedStage->status !== 'completed')
                                        <button type="button" wire:click="completeCurrentStage" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl shadow flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                                            <span>Confirm & Complete Task Stage</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif

                    @endif
                </div>
            @endif
        </div>

    </div>
</div>
