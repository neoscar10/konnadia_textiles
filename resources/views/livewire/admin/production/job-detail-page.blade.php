<div>
    <!-- Back Navigation & Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                @if($job->production_batch_id)
                    <a href="{{ route('admin.production.batches.jobs', $job->production_batch_id) }}" wire:navigate class="text-primary font-bold text-xs flex items-center gap-1 hover:underline">
                        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                        Back to Batch {{ $job->production_batch_id }}
                    </a>
                @else
                    <a href="{{ route('admin.production.jobs.index') }}" wire:navigate class="text-primary font-bold text-xs flex items-center gap-1 hover:underline">
                        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                        Back to Production Jobs Hub
                    </a>
                @endif
                <span class="text-outline text-xs font-bold">• Work Order Detail</span>
            </div>
            <div class="flex items-center gap-3">
                <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">{{ $job->job_code }}</h2>
                @if($job->production_batch_id)
                    <span class="px-3 py-1 bg-surface-container-high text-on-surface font-mono font-bold text-xs rounded-xl">
                        Batch: {{ $job->production_batch_id }}
                    </span>
                @endif
                @if($job->batch?->parentBatch)
                    <span class="px-3 py-1 bg-amber-500/10 text-amber-700 font-mono font-bold text-xs rounded-xl border border-amber-500/30">
                        Child Batch of: {{ $job->batch->parentBatch->batch_code }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Status Action & Complete Job Button -->
        <div class="flex items-center gap-3">
            @if($job->status !== 'completed')
                <button type="button" wire:click="completeCurrentJob" class="flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-xs bg-secondary text-on-secondary shadow-md hover:bg-secondary-container transition-all active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">task_alt</span>
                    Complete Job & Progress Workflow
                </button>
            @endif

            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs shadow-xs transition-all uppercase tracking-wider {{ $job->status === 'completed' ? 'bg-secondary text-white' : ($job->status === 'in_progress' ? 'bg-primary text-white' : 'bg-surface-container-high text-on-surface-variant') }}">
                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                    {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                    <span class="material-symbols-outlined text-[18px]">expand_more</span>
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-56 bg-surface-container-lowest border border-outline-variant/60 rounded-2xl shadow-lg py-2 z-50">
                    <button type="button" wire:click="updateJobStatus('in_progress')" @click="open = false" class="w-full text-left px-4 py-2 text-xs font-bold text-primary hover:bg-surface-container-low flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary"></span> In Progress
                    </button>
                    <button type="button" wire:click="completeCurrentJob" @click="open = false" class="w-full text-left px-4 py-2 text-xs font-bold text-secondary hover:bg-surface-container-low flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-secondary"></span> Mark as Completed & Auto Progress
                    </button>
                    <button type="button" wire:click="updateJobStatus('pending')" @click="open = false" class="w-full text-left px-4 py-2 text-xs font-bold text-on-surface-variant hover:bg-surface-container-low flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-outline"></span> Pending
                    </button>
                    <button type="button" wire:click="updateJobStatus('cancelled')" @click="open = false" class="w-full text-left px-4 py-2 text-xs font-bold text-error hover:bg-error-container/30 flex items-center gap-2 border-t border-outline-variant/30 mt-1 pt-2">
                        <span class="w-2 h-2 rounded-full bg-error"></span> Cancel Job
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($errors->has('jobStatus'))
        <div class="bg-error-container/40 border border-error/30 text-error p-4 rounded-xl mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined text-error shrink-0">error</span>
            <p class="font-body-md text-body-md font-semibold">{{ $errors->first('jobStatus') }}</p>
        </div>
    @endif

    <!-- Job Summary KPI Banner -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 mb-6 shadow-xs">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
            <div class="md:col-span-2">
                <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Manufacturing Product</span>
                <h3 class="text-2xl font-black text-primary tracking-tight mt-0.5">
                    {{ $job->manufacturingProduct?->name ?? 'Unassigned Product' }}
                </h3>
                <p class="text-xs text-outline font-mono mt-1">Product Code: {{ $job->manufacturingProduct?->code ?? 'N/A' }} • Target Batch Qty: <span class="font-extrabold text-primary">{{ number_format($job->target_quantity) }} Pcs</span></p>
            </div>

            <div>
                <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider block">Overall Job Progress</span>
                <div class="mt-1">
                    <div class="flex justify-between items-center text-xs font-bold mb-1">
                        <span class="text-primary">{{ $job->completed_quantity }} / {{ $job->target_quantity }} Finished Units</span>
                        <span class="text-secondary font-black">{{ $job->progress_percentage }}%</span>
                    </div>
                    <div class="w-full bg-surface-container-high h-2.5 rounded-full overflow-hidden">
                        <div class="bg-primary h-full transition-all duration-500 rounded-full" style="width: {{ $job->progress_percentage }}%"></div>
                    </div>
                    <span class="text-[10px] text-outline font-medium block mt-1">Stage average output across {{ $allTasks->count() }} stages</span>
                </div>
            </div>

            <div class="text-right border-l border-outline-variant/30 pl-6 hidden md:block">
                <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Total Production Cost</span>
                <p class="text-2xl font-black text-secondary mt-0.5">
                    ₹{{ number_format((float)($job->allocations()->sum('calculated_wage') + $job->materialConsumptions()->sum('total_cost')), 2) }}
                </p>
                <span class="text-[11px] text-outline font-medium">Labor wages + Raw material cost</span>
            </div>
        </div>
    </div>

    <!-- MAIN TWO-COLUMN WORKSPACE LAYOUT (WIZARD LEFT, STAGE SIDEBAR RIGHT) -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
        <!-- LEFT COLUMN: STAGE OPERATIONS WIZARD (3 SPANS) -->
        <div class="lg:col-span-3 space-y-6 order-2 lg:order-last">


        <!-- INTERACTIVE WIZARD PROGRESS TRACKER HEADER -->
        @php
            $isCuttingStage = $selectedTask && ($selectedTask->name === 'Cutting' || $selectedTask->code === 'TSK-001');
            $hasMat = $this->hasMaterialStep;
            $maxSteps = $this->maxWizardSteps;
        @endphp

        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs mb-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4 pb-4 border-b border-outline-variant/40">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                        <span class="material-symbols-outlined text-[22px]">route</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-headline-sm text-headline-sm text-primary font-bold">
                                {{ $selectedTask ? $selectedTask->name : 'Stage' }} Operations Wizard
                            </h3>
                            @if($this->isSelectedStageCompleted)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-secondary/15 text-secondary border border-secondary/30">
                                    Completed
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-primary/15 text-primary border border-primary/30">
                                    Step {{ $wizardStep }} of {{ $maxSteps }}
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                            Follow the guided steps below to record worker allocations, output yields, and stage progression.
                        </p>
                    </div>
                </div>

                <!-- Navigation Controls -->
                <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end">
                    <button type="button" wire:click="previousWizardStep" @if($wizardStep <= 1) disabled @endif class="px-3.5 py-2 bg-surface-container-low border border-outline-variant/60 text-on-surface rounded-xl text-xs font-bold hover:bg-surface-container transition-all flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed">
                        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                        Back
                    </button>

                    <div class="text-xs font-bold text-outline font-mono">
                        Step {{ $wizardStep }} / {{ $maxSteps }}
                    </div>

                    <button type="button" wire:click="nextWizardStep" @if($wizardStep >= $maxSteps) disabled @endif class="px-3.5 py-2 bg-primary text-on-primary rounded-xl text-xs font-bold hover:bg-primary-container transition-all flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed shadow-xs">
                        Next
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </button>
                </div>
            </div>

            <!-- DYNAMIC ACTIVE-STEP SUB-TABS -->
            @if($isCuttingStage)
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide">
                    <button type="button" wire:click="setWizardStep(1)" class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all border flex items-center gap-2 shrink-0 {{ $wizardStep === 1 ? 'bg-primary text-on-primary border-primary shadow-sm' : 'bg-surface border-outline-variant/60 text-on-surface-variant hover:bg-surface-container' }}">
                        <span class="material-symbols-outlined text-[18px]">content_cut</span>
                        <span>1. Fabric & Roll Selection</span>
                    </button>
                    <button type="button" wire:click="setWizardStep(2)" class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all border flex items-center gap-2 shrink-0 {{ $wizardStep === 2 ? 'bg-primary text-on-primary border-primary shadow-sm' : 'bg-surface border-outline-variant/60 text-on-surface-variant hover:bg-surface-container' }}">
                        <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                        <span>2. Cut Yields & Labor</span>
                    </button>
                    <button type="button" wire:click="setWizardStep(3)" class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all border flex items-center gap-2 shrink-0 {{ $wizardStep === 3 ? 'bg-primary text-on-primary border-primary shadow-sm' : 'bg-surface border-outline-variant/60 text-on-surface-variant hover:bg-surface-container' }}">
                        <span class="material-symbols-outlined text-[18px]">payments</span>
                        <span>3. Cost Valuation & Save</span>
                    </button>
                </div>
            @else
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide">
                    @if($hasMat)
                        <button type="button" wire:click="setActiveStep('material')" class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all border flex items-center gap-2 shrink-0 {{ $activeStep === 'material' ? 'bg-primary text-on-primary border-primary shadow-sm' : 'bg-surface border-outline-variant/60 text-on-surface-variant hover:bg-surface-container' }}">
                            <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                            <span>1. Material Selection</span>
                        </button>
                    @endif

                    @php $workerStepNum = $hasMat ? 2 : 1; @endphp
                    <button type="button" wire:click="setActiveStep('workers')" class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all border flex items-center gap-2 shrink-0 {{ $activeStep === 'workers' ? 'bg-primary text-on-primary border-primary shadow-sm' : 'bg-surface border-outline-variant/60 text-on-surface-variant hover:bg-surface-container' }}">
                        <span class="material-symbols-outlined text-[18px]">group</span>
                        <span>{{ $workerStepNum }}. Workers & Allocation</span>
                    </button>

                    @php $outputStepNum = $hasMat ? 3 : 2; @endphp
                    <button type="button" wire:click="setActiveStep('output')" class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all border flex items-center gap-2 shrink-0 {{ $activeStep === 'output' ? 'bg-primary text-on-primary border-primary shadow-sm' : 'bg-surface border-outline-variant/60 text-on-surface-variant hover:bg-surface-container' }}">
                        <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                        <span>{{ $outputStepNum }}. Product Output Yields</span>
                    </button>

                    @php $wastageStepNum = $hasMat ? 4 : 3; @endphp
                    <button type="button" wire:click="setActiveStep('wastage')" class="px-4 py-2.5 rounded-xl font-bold text-xs transition-all border flex items-center gap-2 shrink-0 {{ $activeStep === 'wastage' ? 'bg-error text-on-error border-error shadow-sm' : 'bg-surface border-outline-variant/60 text-on-surface-variant hover:bg-surface-container' }}">
                        <span class="material-symbols-outlined text-[18px]">report_problem</span>
                        <span>{{ $wastageStepNum }}. Alterations</span>
                    </button>
                </div>
            @endif
        </div>



      @if($selectedTask && ($selectedTask->name === 'Cutting' || $selectedTask->code === 'TSK-001'))
        <!-- CUSTOM CUTTING SESSION TERMINAL UI -->
        <form wire:submit.prevent="saveCuttingSession" class="space-y-6 mb-8">
            <div class="grid grid-cols-12 gap-6">
                <!-- Left Side: Cutting Form Entry -->
                <div class="col-span-12 space-y-6">

                    @if($errors->any())
                        <div class="bg-error-container/40 border border-error/30 text-error p-4 rounded-xl mb-6">
                            <ul class="list-disc pl-5 text-xs font-semibold space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- STEP 1: FABRIC & ROLL SELECTION --}}
                    @if($wizardStep === 1)
                        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs space-y-6">
                            <div class="flex items-center gap-3 pb-4 border-b border-outline-variant/40">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                                    <span class="material-symbols-outlined text-[22px]">content_cut</span>
                                </div>
                                <div>
                                    <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Step 1: Fabric & Roll Selection</h3>
                                    <p class="text-xs text-on-surface-variant font-medium mt-0.5">Select fabric raw material, inventory batch, bale, and record roll cut lengths.</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Step 1: Select Fabric Raw Material -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">1. Select Fabric Material *</label>
                                        <select wire:model.live="cuttingFabricMaterialId" @if($this->isSelectedStageCompleted) disabled @endif class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary @if($this->isSelectedStageCompleted) opacity-75 bg-surface-container-low cursor-not-allowed @endif">
                                            <option value="">-- Select Fabric Material --</option>
                                            @foreach($this->fabricMaterialsList as $mat)
                                                <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->code }}) — Std Width: {{ $mat->standard_width ?: 60 }}in</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Step 2: Select Fabric Batch -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">2. Select Fabric Batch *</label>
                                        <select wire:model.live="cuttingFabricBatchId" @if($this->isSelectedStageCompleted || empty($cuttingFabricMaterialId)) disabled @endif class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary @if($this->isSelectedStageCompleted) opacity-75 bg-surface-container-low cursor-not-allowed @endif">
                                            <option value="">-- Choose Batch --</option>
                                            @foreach($this->batchesForSelectedFabric as $batch)
                                                <option value="{{ $batch->id }}">
                                                    {{ $batch->batch_number }} — Available Stock: {{ number_format($batch->balance_quantity, 2) }} {{ $batch->unit }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('cuttingFabricBatchId') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                @php
                                    $selectedBatch = !empty($this->cuttingFabricBatchId) ? \App\Models\InventoryBatch::find($this->cuttingFabricBatchId) : null;
                                @endphp
                                @if($selectedBatch)
                                    <div class="p-4 bg-secondary-container/20 text-on-secondary-container border border-secondary/20 rounded-xl flex flex-wrap justify-between items-center text-xs gap-3">
                                        <div>
                                            <span class="font-bold block text-[10px] uppercase text-on-surface-variant">Batch Reference</span>
                                            <span class="font-extrabold text-primary font-mono text-xs">{{ $selectedBatch->batch_number }}</span>
                                        </div>
                                        <div>
                                            <span class="font-bold block text-[10px] uppercase text-on-surface-variant">Available Batch Stock</span>
                                            <span class="font-extrabold text-secondary">{{ number_format($selectedBatch->balance_quantity, 2) }} {{ $selectedBatch->unit }}</span>
                                        </div>
                                        <div>
                                            <span class="font-bold block text-[10px] uppercase text-on-surface-variant">Purchase Cost Rate</span>
                                            <span class="font-extrabold text-primary">₹{{ number_format($selectedBatch->unit_cost, 2) }} / {{ $selectedBatch->unit }}</span>
                                        </div>
                                    </div>

                                    <!-- Multi-Bale & Roll Level Cutting Breakdown Card -->
                                    <div class="space-y-4 pt-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs font-extrabold text-primary uppercase tracking-wider flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-[18px]">inventory_2</span> 3. Select Bale(s) & Record Cut Length From Opened Rolls *
                                            </span>
                                            <button type="button" wire:click="addCuttingBaleRow" class="text-xs font-bold text-secondary hover:text-secondary-container bg-secondary/10 px-3 py-1.5 rounded-lg flex items-center gap-1 transition-all">
                                                <span class="material-symbols-outlined text-[16px]">add</span> Add Another Bale Row
                                            </button>
                                        </div>

                                        @foreach($cuttingBaleRows as $bIndex => $bRow)
                                            @php
                                                $rowBale = !empty($bRow['bale_id']) ? \App\Models\InventoryBale::with('rolls')->find($bRow['bale_id']) : null;
                                            @endphp
                                            <div class="p-4 bg-surface rounded-2xl border border-outline-variant/60 shadow-2xs space-y-3">
                                                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                                                    <div class="flex-1 w-full">
                                                        <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Bale / Lot Selection #{{ $bIndex + 1 }}</label>
                                                        <select wire:model.live="cuttingBaleRows.{{ $bIndex }}.bale_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                                                            <option value="">-- Choose Particular Bale --</option>
                                                            @foreach($this->balesForSelectedBatch as $bale)
                                                                <option value="{{ $bale->id }}">
                                                                    {{ $bale->bale_number }} [{{ strtoupper($bale->status) }}] — Bal: {{ number_format($bale->current_balance_length, 2) }}m (Decl: {{ $bale->declared_length }}m)
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    @if(count($cuttingBaleRows) > 1)
                                                        <button type="button" wire:click="removeCuttingBaleRow({{ $bIndex }})" class="p-2 text-error hover:bg-error-container/20 rounded-xl self-end transition-colors" title="Remove Bale Row">
                                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                                        </button>
                                                    @endif
                                                </div>

                                                @if($rowBale)
                                                    @if($rowBale->status === 'unopened')
                                                        <div class="p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl flex flex-wrap justify-between items-center text-xs gap-3">
                                                            <div class="space-y-0.5">
                                                                <div class="font-extrabold text-amber-900 flex items-center gap-1.5">
                                                                    <span class="material-symbols-outlined text-[18px]">lock</span> Unopened Bale Selected (Bale {{ $rowBale->bale_number }})
                                                                </div>
                                                                <p class="text-[11px] text-amber-800 font-medium">Bale has not been opened yet. Open bale to enter roll count and measured roll lengths before cutting.</p>
                                                            </div>
                                                            <button type="button" wire:click="triggerOpenBaleModal({{ $rowBale->id }})" class="bg-amber-600 hover:bg-amber-700 text-white font-extrabold px-4 py-2 rounded-xl text-xs shadow-xs transition-all flex items-center gap-1.5 active:scale-95 shrink-0">
                                                                <span class="material-symbols-outlined text-[16px]">lock_open</span> Open Bale & Record Rolls
                                                            </button>
                                                        </div>
                                                    @elseif($rowBale->status === 'opened')
                                                        <div class="p-4 bg-surface-container-lowest border border-outline-variant/40 rounded-xl space-y-4">
                                                            <div class="flex justify-between items-center pb-2 border-b border-outline-variant/30">
                                                                <span class="text-xs font-extrabold text-primary uppercase tracking-wider">
                                                                    Select Rolls in {{ $rowBale->bale_number }} (Remaining Bale Stock: {{ number_format($rowBale->current_balance_length, 2) }}m)
                                                                </span>
                                                            </div>

                                                            @php
                                                                $activeRollsList = !empty($bRow['selected_rolls']) ? $bRow['selected_rolls'] : [];
                                                                if (empty($activeRollsList) && $rowBale && $rowBale->status === 'opened' && $rowBale->activeRolls->isNotEmpty()) {
                                                                    foreach ($rowBale->activeRolls as $r) {
                                                                        $activeRollsList[$r->id] = [
                                                                            'roll_id' => $r->id,
                                                                            'roll_number' => $r->roll_number,
                                                                            'max_length' => (float) $r->current_balance_length,
                                                                            'is_selected' => false,
                                                                            'cut_length' => '',
                                                                            'wastage_length' => '0',
                                                                            'outputs' => [
                                                                                ['manufacturing_product_id' => $job->manufacturing_product_id ?? '', 'quantity' => '']
                                                                            ]
                                                                        ];
                                                                    }
                                                                }
                                                            @endphp

                                                            @if(!empty($activeRollsList))
                                                                <div class="space-y-4">
                                                                    @foreach($activeRollsList as $rId => $rData)
                                                                        <div class="p-4 bg-surface border border-outline-variant/50 rounded-2xl space-y-4 @if(!empty($rData['is_selected'])) ring-2 ring-primary/25 border-primary/50 @else opacity-90 @endif">
                                                                            <div class="flex justify-between items-center pb-2 border-b border-outline-variant/30">
                                                                                <label class="flex items-center gap-3 cursor-pointer">
                                                                                    <input type="checkbox" wire:model.live="cuttingBaleRows.{{ $bIndex }}.selected_rolls.{{ $rId }}.is_selected" @if($this->isSelectedStageCompleted) disabled @endif class="w-4 h-4 rounded text-primary border-outline-variant/60 focus:ring-primary/20">
                                                                                    <span class="font-extrabold text-sm text-primary">Roll #{{ $rData['roll_number'] }}</span>
                                                                                </label>
                                                                                <span class="text-[11px] font-bold text-secondary bg-secondary/15 px-3 py-1 rounded-xl">Available: {{ number_format($rData['max_length'], 2) }}m</span>
                                                                            </div>

                                                                            @if(!empty($rData['is_selected']))
                                                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                                                    <div>
                                                                                        <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Cut Length (Meters) *</label>
                                                                                        <div class="flex items-center gap-1.5">
                                                                                            <input type="number" step="0.01" min="0.01" max="{{ $rData['max_length'] }}" wire:model.live="cuttingBaleRows.{{ $bIndex }}.selected_rolls.{{ $rId }}.cut_length" @if($this->isSelectedStageCompleted) disabled @endif placeholder="0.00" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-primary focus:ring-2 focus:ring-primary/25">
                                                                                            @if(!$this->isSelectedStageCompleted)
                                                                                                <button type="button" wire:click="useFullRoll({{ $bIndex }}, {{ $rId }})" class="px-3 py-2 bg-primary/10 hover:bg-primary/20 text-primary font-black text-[10px] rounded-xl shrink-0 transition-all">
                                                                                                    Use All
                                                                                                </button>
                                                                                            @endif
                                                                                        </div>
                                                                                    </div>
                                                                                    <div>
                                                                                        <label class="block text-[10px] font-bold text-error uppercase tracking-wider mb-1.5">Roll Wastage Length *</label>
                                                                                        <input type="number" step="0.01" min="0" wire:model.live="cuttingBaleRows.{{ $bIndex }}.selected_rolls.{{ $rId }}.wastage_length" @if($this->isSelectedStageCompleted) disabled @endif placeholder="0.00" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-error focus:ring-2 focus:ring-error/25">
                                                                                    </div>
                                                                                </div>

                                                                                <!-- Product Output Grid for this Roll -->
                                                                                <div class="p-3 bg-surface-container-lowest rounded-xl border border-outline-variant/40 space-y-3 mt-2">
                                                                                    <div class="flex justify-between items-center pb-2 border-b border-outline-variant/30">
                                                                                        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Product Output on this Roll</span>
                                                                                        @if(!$this->isSelectedStageCompleted)
                                                                                            <button type="button" wire:click="addRollOutputRow({{ $bIndex }}, {{ $rId }})" class="text-[10px] font-black text-secondary hover:text-secondary-container bg-secondary/10 px-2.5 py-1 rounded-lg flex items-center gap-1">
                                                                                                <span class="material-symbols-outlined text-[12px]">add</span> Add SKU
                                                                                            </button>
                                                                                        @endif
                                                                                    </div>

                                                                                    <div class="space-y-2">
                                                                                        @foreach($rData['outputs'] ?? [] as $oIdx => $outItem)
                                                                                            <div class="flex items-center gap-3">
                                                                                                <div class="flex-1">
                                                                                                    <select wire:model.live="cuttingBaleRows.{{ $bIndex }}.selected_rolls.{{ $rId }}.outputs.{{ $oIdx }}.manufacturing_product_id" @if($this->isSelectedStageCompleted) disabled @endif class="w-full bg-surface border border-outline-variant/60 rounded-xl px-2.5 py-2 text-xs font-semibold text-on-surface focus:ring-1 focus:ring-primary">
                                                                                                        <option value="">-- Choose Product SKU --</option>
                                                                                                        @php
                                                                                                            $cuttingProductOptions = $job->manufacturingProduct ? collect([$job->manufacturingProduct]) : $allManufacturingProducts;
                                                                                                        @endphp
                                                                                                        @foreach($cuttingProductOptions as $prod)
                                                                                                            <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->code }})</option>
                                                                                                        @endforeach
                                                                                                    </select>
                                                                                                </div>
                                                                                                <div class="w-24">
                                                                                                    <input type="number" min="1" placeholder="Pcs" wire:model.live="cuttingBaleRows.{{ $bIndex }}.selected_rolls.{{ $rId }}.outputs.{{ $oIdx }}.quantity" @if($this->isSelectedStageCompleted) disabled @endif class="w-full bg-surface border border-outline-variant/60 rounded-xl px-2.5 py-2 text-xs font-bold text-center text-primary focus:ring-1 focus:ring-primary">
                                                                                                </div>
                                                                                                @if(!$this->isSelectedStageCompleted && count($rData['outputs'] ?? []) > 1)
                                                                                                    <button type="button" wire:click="removeRollOutputRow({{ $bIndex }}, {{ $rId }}, {{ $oIdx }})" class="p-1.5 text-error hover:bg-error-container/20 rounded-lg transition-colors shrink-0">
                                                                                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                                                                                    </button>
                                                                                                @endif
                                                                                            </div>
                                                                                        @endforeach
                                                                                    </div>
                                                                                </div>
                                                                            @else
                                                                                <p class="text-xs text-outline italic">Roll is currently ignored. Toggle the checkbox to record cut output for this roll.</p>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <p class="text-xs text-on-surface-variant italic">No active rolls available in this bale.</p>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Fabric Dimensions & Consumed -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                    <div>
                                        <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-2 flex items-center gap-1">
                                            Total Consumed Length * <span class="material-symbols-outlined text-[14px] text-primary" title="Auto-computed from selected roll cut lengths">lock</span>
                                        </label>
                                        <div class="relative">
                                            <input type="number" step="0.01" readonly wire:model.live="cuttingConsumedLength" placeholder="0.00" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl pl-4 pr-12 py-3 text-sm font-black text-primary cursor-not-allowed">
                                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-primary font-extrabold uppercase">
                                                {{ $selectedBatch ? $selectedBatch->unit : 'Meters' }}
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-outline font-medium mt-1">Auto-summed from selected roll cut lengths.</p>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-2 flex items-center gap-1">
                                            Fabric Width (Inches) * <span class="material-symbols-outlined text-[14px] text-outline" title="Auto-derived from Raw Material Master">lock</span>
                                        </label>
                                        <div class="relative">
                                            <input type="number" step="0.1" wire:model="cuttingFabricWidth" readonly class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-black text-on-surface cursor-not-allowed">
                                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-outline font-bold uppercase">Inches</span>
                                        </div>
                                        <p class="text-[10px] text-outline font-medium mt-1">Pre-defined in Raw Material Master.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 1 FOOTER NAVIGATION -->
                            <div class="flex justify-end pt-4 border-t border-outline-variant/40">
                                <button type="button" wire:click="setWizardStep(2)" class="bg-primary text-on-primary px-7 py-3.5 rounded-xl font-bold text-sm shadow-md hover:bg-primary-container transition-all flex items-center gap-2 active:scale-95 cursor-pointer">
                                    Next Step: Labor Assignments
                                    <span class="material-symbols-outlined text-lg">arrow_forward</span>
                                </button>
                            </div>
                        </div>
                        @endif

                    {{-- STEP 2: LABOR ALLOCATION --}}
                    @if($wizardStep === 2)
                        <div class="space-y-6">
                            <!-- Global Bulk Labor Allocation Card -->
                            <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="space-y-0.5">
                                    <h4 class="font-bold text-sm text-primary uppercase tracking-wider">Global Bulk Assignment</h4>
                                    <p class="text-[11px] text-on-surface-variant">Assign a worker to ALL selected rolls across all bales at once.</p>
                                </div>
                                <div class="flex items-center gap-3 w-full md:w-auto max-w-md">
                                    <select wire:model="allRollsBulkLabor" class="flex-1 bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                                        <option value="">-- Choose Worker for All Rolls --</option>
                                        @foreach($this->authorizedLabors as $lab)
                                            <option value="{{ $lab->id }}">{{ $lab->name }} ({{ $lab->labor_code }})</option>
                                        @endforeach
                                    </select>
                                    <button type="button" wire:click="applyAllRollsBulkLabor" class="bg-primary text-on-primary px-5 py-2 rounded-xl text-xs font-black shadow-xs hover:bg-primary-container transition-all">
                                        Apply Global
                                    </button>
                                </div>
                            </div>

                            <!-- Bale & Roll Labor Assignments -->
                            <div class="space-y-6">
                                @foreach($cuttingBaleRows as $bIndex => $bRow)
                                    @php
                                        $bale = !empty($bRow['bale_id']) ? \App\Models\InventoryBale::find($bRow['bale_id']) : null;
                                        $hasActiveRolls = false;
                                        foreach ($bRow['selected_rolls'] ?? [] as $r) {
                                            if (!empty($r['is_selected'])) {
                                                $hasActiveRolls = true;
                                            }
                                        }
                                    @endphp
                                    @if($bale && $hasActiveRolls)
                                        <div class="bg-surface rounded-2xl border border-outline-variant/60 shadow-2xs p-5 space-y-5">
                                            <!-- Bale Header with Bale-Level Bulk Allocation -->
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-outline-variant/40">
                                                <div class="space-y-0.5">
                                                    <h4 class="font-extrabold text-sm text-primary uppercase tracking-wider">Bale {{ $bale->bale_number }}</h4>
                                                    <p class="text-[11px] text-on-surface-variant">Allocations for active rolls under this bale.</p>
                                                </div>
                                                
                                                <div class="flex items-center gap-2 bg-surface-container-lowest p-2 rounded-xl border border-outline-variant/50 max-w-sm w-full sm:w-auto">
                                                    <select wire:model="baleBulkLabor.{{ $bale->id }}" class="flex-1 bg-surface border border-outline-variant/60 rounded-lg px-2.5 py-1.5 text-xs font-bold text-on-surface">
                                                        <option value="">-- Bulk Select Worker --</option>
                                                        @foreach($this->authorizedLabors as $lab)
                                                            <option value="{{ $lab->id }}">{{ $lab->name }} ({{ $lab->labor_code }})</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" wire:click="applyBaleBulkLabor({{ $bIndex }})" class="bg-secondary text-on-secondary px-3.5 py-1.5 rounded-lg text-xs font-black hover:bg-secondary-container transition-all">
                                                        Apply to Bale
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Rolls in this Bale -->
                                            <div class="space-y-5">
                                                @foreach($bRow['selected_rolls'] ?? [] as $rId => $rData)
                                                    @if(!empty($rData['is_selected']))
                                                        @php
                                                            $rollOutputs = [];
                                                            foreach ($rData['outputs'] ?? [] as $out) {
                                                                if (!empty($out['manufacturing_product_id']) && !empty($out['quantity'])) {
                                                                    $pId = (int)$out['manufacturing_product_id'];
                                                                    $rollOutputs[$pId] = ($rollOutputs[$pId] ?? 0) + (int)$out['quantity'];
                                                                }
                                                            }
                                                        @endphp
                                                        <div class="p-4 bg-surface-container-lowest border border-outline-variant/50 rounded-xl space-y-4">
                                                            <div class="flex justify-between items-center pb-2.5 border-b border-outline-variant/30">
                                                                <div>
                                                                    <span class="font-extrabold text-xs text-primary">Roll #{{ $rData['roll_number'] }} Labor Assignment</span>
                                                                    <div class="flex flex-wrap gap-2 mt-1">
                                                                        <span class="text-[9px] font-bold text-outline uppercase">Products Cut:</span>
                                                                        @if(!empty($rollOutputs))
                                                                            @foreach($rollOutputs as $pId => $qty)
                                                                                @php
                                                                                    $prod = \App\Models\ManufacturingProduct::find($pId);
                                                                                @endphp
                                                                                <span class="px-2 py-0.5 bg-primary/10 text-primary rounded-lg text-[9px] font-black">
                                                                                    {{ $prod ? $prod->name : "Product #{$pId}" }} x{{ $qty }} Pcs
                                                                                </span>
                                                                            @endforeach
                                                                        @else
                                                                            <span class="text-[9px] text-error font-black">No products defined on Step 1</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                @if(!$this->isSelectedStageCompleted)
                                                                    <button type="button" wire:click="addCuttingLaborRow({{ $rId }})" class="flex items-center gap-1 bg-primary text-on-primary px-2.5 py-1.5 rounded-lg text-[10px] font-bold shadow-xs hover:bg-primary-container transition-all">
                                                                        <span class="material-symbols-outlined text-[13px]">person_add</span> Add Cutter Worker
                                                                    </button>
                                                                @endif
                                                            </div>

                                                            <div class="space-y-3">
                                                                @php
                                                                    $allocs = $cuttingLaborAllocations[$rId] ?? [];
                                                                @endphp
                                                                @if(!empty($allocs))
                                                                    @foreach($allocs as $aIdx => $alloc)
                                                                        <div class="flex flex-col md:flex-row items-center gap-4 p-3 bg-surface rounded-xl border border-outline-variant/60 shadow-2xs">
                                                                            <!-- Worker Dropdown -->
                                                                            <div class="w-full md:w-1/3 min-w-[200px]">
                                                                                <label class="block text-[9px] font-bold text-on-surface-variant uppercase mb-1">Cutter Worker *</label>
                                                                                <select wire:model="cuttingLaborAllocations.{{ $rId }}.{{ $aIdx }}.labor_id" @if($this->isSelectedStageCompleted) disabled @endif class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-2.5 py-2 text-xs font-semibold text-on-surface">
                                                                                    <option value="">-- Select Factory Cutter --</option>
                                                                                    @foreach($this->authorizedLabors as $lab)
                                                                                        <option value="{{ $lab->id }}">{{ $lab->name }} ({{ $lab->labor_code }})</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>

                                                                            <!-- Product Dropdown -->
                                                                            <div class="w-full md:w-1/3 min-w-[200px]">
                                                                                <label class="block text-[9px] font-bold text-on-surface-variant uppercase mb-1">Target Product SKU *</label>
                                                                                <select wire:model="cuttingLaborAllocations.{{ $rId }}.{{ $aIdx }}.manufacturing_product_id" @if($this->isSelectedStageCompleted) disabled @endif class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-2.5 py-2 text-xs font-semibold text-on-surface">
                                                                                    <option value="">-- Choose Cut Product SKU --</option>
                                                                                    @foreach($rollOutputs as $pId => $qty)
                                                                                        @php
                                                                                            $prod = \App\Models\ManufacturingProduct::find($pId);
                                                                                        @endphp
                                                                                        <option value="{{ $pId }}">{{ $prod ? $prod->name : "Product #{$pId}" }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>

                                                                            <!-- Quantity / Use All Shortcut -->
                                                                            <div class="w-full md:w-36 flex flex-col">
                                                                                <label class="block text-[9px] font-bold text-on-surface-variant uppercase mb-1 text-center">Processed (Pcs) *</label>
                                                                                <div class="flex items-center gap-1.5">
                                                                                    <input type="number" min="1" wire:model="cuttingLaborAllocations.{{ $rId }}.{{ $aIdx }}.quantity" @if($this->isSelectedStageCompleted) disabled @endif class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-2.5 py-2 text-xs font-bold text-primary text-center">
                                                                                    @if(!$this->isSelectedStageCompleted)
                                                                                        <button type="button" wire:click="setLaborQuantityToMax({{ $rId }}, {{ $aIdx }})" class="px-2.5 py-2 bg-secondary/15 hover:bg-secondary/25 text-secondary font-black text-[9px] rounded-xl shrink-0 transition-all" title="Use All Target">
                                                                                            Max
                                                                                        </button>
                                                                                    @endif
                                                                                </div>
                                                                            </div>

                                                                            <!-- Delete -->
                                                                            @if(!$this->isSelectedStageCompleted)
                                                                                <button type="button" wire:click="removeCuttingLaborRow({{ $rId }}, {{ $aIdx }})" class="p-2 text-error hover:bg-error-container/20 rounded-xl transition-colors shrink-0 md:self-end">
                                                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                                                </button>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                @else
                                                                    <p class="text-xs text-outline italic">No cutter workers assigned yet for this roll. Assign a worker above or use bulk allocation.</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            @error('cuttingLaborAllocations')
                                <div class="p-4 bg-error-container/30 border border-error/40 text-error rounded-xl text-xs font-bold flex items-center gap-2.5 shadow-xs">
                                    <span class="material-symbols-outlined text-[20px] text-error shrink-0">error</span>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror

                            <!-- STEP 2 FOOTER NAVIGATION -->
                            <div class="flex justify-between items-center p-4 bg-surface rounded-2xl border border-outline-variant/60 shadow-xs">
                                <button type="button" wire:click="setWizardStep(1)" class="bg-surface-container-high text-on-surface border border-outline-variant/60 px-6 py-3.5 rounded-xl font-bold text-sm hover:bg-surface-container-highest transition-all flex items-center gap-2 shadow-xs cursor-pointer">
                                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                                    Back to Fabric Selection
                                </button>
                                <button type="button" wire:click="setWizardStep(3)" class="bg-primary text-on-primary px-7 py-3.5 rounded-xl font-bold text-sm shadow-md hover:bg-primary-container transition-all flex items-center gap-2 active:scale-95 cursor-pointer">
                                    Next Step: Cost Valuation
                                    <span class="material-symbols-outlined text-lg">arrow_forward</span>
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- STEP 3: COST VALUATION --}}
                    @if($wizardStep === 3)
                        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs space-y-6">
                            <div class="flex items-center gap-3 pb-4 border-b border-outline-variant/40">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                                    <span class="material-symbols-outlined text-[22px]">payments</span>
                                </div>
                                <div>
                                    <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Step 3: Cost Valuation Summary</h3>
                                    <p class="text-xs text-on-surface-variant font-medium mt-0.5">Review consolidated fabric cost, allocated wastage, cutter labor wages, and per-unit SKU cost rollups.</p>
                                </div>
                            </div>

                            @if($this->cuttingCostPreview)
                                <div class="space-y-4">
                                    <div class="p-4 bg-primary/5 text-primary border border-primary/20 rounded-xl space-y-2 text-xs">
                                        <div class="flex justify-between font-medium">
                                            <span>Total Consumed Fabric:</span>
                                            <span class="font-bold">₹{{ number_format($this->cuttingCostPreview['total_fabric_cost'], 2) }}</span>
                                        </div>
                                        <div class="flex justify-between font-medium text-error">
                                            <span>Total Allocated Wastage:</span>
                                            <span class="font-bold">+ ₹{{ number_format($this->cuttingCostPreview['total_wastage_cost'], 2) }}</span>
                                        </div>
                                        <div class="h-px bg-outline-variant/30 my-2"></div>
                                        <div class="flex justify-between font-extrabold text-sm">
                                            <span>Consolidated Valuation:</span>
                                            <span>₹{{ number_format($this->cuttingCostPreview['consolidated_fabric_valuation'], 2) }}</span>
                                        </div>
                                    </div>

                                    <div class="space-y-3 pt-2">
                                        <span class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Itemized Cost Allocation per Cut Product:</span>
                                        @foreach($this->cuttingCostPreview['preview_items'] as $item)
                                            <div class="p-4 bg-surface border border-outline-variant/40 rounded-xl text-xs space-y-2 shadow-2xs">
                                                <div class="flex justify-between font-bold text-on-surface text-sm truncate">
                                                    <span>{{ $item['product_name'] }}</span>
                                                    <span class="px-2.5 py-0.5 bg-primary/10 text-primary rounded-lg text-xs font-black">x{{ number_format($item['quantity']) }} Pcs</span>
                                                </div>
                                                <div class="flex justify-between text-on-surface-variant">
                                                    <span>Base Fabric Cost:</span>
                                                    <span>₹{{ number_format($item['base_cost'], 2) }}</span>
                                                </div>
                                                <div class="flex justify-between text-error/80">
                                                    <span>Wastage Cost (WA):</span>
                                                    <span>+ ₹{{ number_format($item['allocated_wastage'], 2) }}</span>
                                                </div>
                                                <div class="pt-1.5 mt-1 border-t border-outline-variant/30 flex justify-between font-bold text-primary">
                                                    <span>Total Material Cost:</span>
                                                    <span>₹{{ number_format($item['total_cost'], 2) }}</span>
                                                </div>
                                                <div class="text-[11px] text-secondary font-extrabold">
                                                    Yield Cost: ₹{{ number_format($item['cost_per_unit'], 2) }} / pc
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="p-8 text-center text-on-surface-variant bg-surface rounded-xl border border-outline-variant/40 italic text-xs flex flex-col items-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-outline opacity-40">calculate</span>
                                    Return to Step 1 & Step 2 to select fabric rolls and output quantities to display valuation preview.
                                </div>
                            @endif

                            <!-- STEP 3 CONFIRMATION BANNER -->
                            <div class="mt-6 pt-4 border-t border-outline-variant/30">
                                @if($this->isSelectedStageCompleted)
                                    <div class="p-4 bg-surface-container-low border border-outline-variant/60 rounded-2xl text-center text-xs text-on-surface-variant font-medium">
                                        This cutting stage is completed and locked from further entry. Recorded sessions and yields are archived in the audit log below.
                                    </div>
                                @else
                                    <div class="p-4 bg-primary/5 border border-primary/20 text-on-surface-variant rounded-2xl text-xs space-y-1.5 shadow-2xs">
                                        <p class="font-bold text-primary flex items-center gap-1.5">
                                            <span class="material-symbols-outlined text-[16px]">info</span>
                                            Confirmation & Progress Action
                                        </p>
                                        <p class="text-[11px] leading-relaxed text-on-surface-variant">
                                            Ensure all pieces, measurements, and fabric wastes are logged accurately. Saving this cutting session will deduct bale lengths from stock and allocate unit costs downstream.
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <!-- STEP 3 FOOTER NAVIGATION -->
                            <div class="flex justify-between items-center pt-4 border-t border-outline-variant/40">
                                <button type="button" wire:click="setWizardStep(2)" class="bg-surface-container-high text-on-surface border border-outline-variant/60 px-6 py-3.5 rounded-xl font-bold text-sm hover:bg-surface-container-highest transition-all flex items-center gap-2 shadow-xs cursor-pointer">
                                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                                    Back to Cut Yields & Labor
                                </button>
                                @if($this->isSelectedStageCompleted)
                                    <button type="button" disabled class="bg-outline-variant/40 text-on-surface-variant/60 px-7 py-3.5 rounded-xl font-bold text-sm cursor-not-allowed shadow-xs flex items-center gap-2">
                                        <span class="material-symbols-outlined text-lg">lock</span>
                                        Stage Completed (Locked)
                                    </button>
                                @else
                                    <button type="submit" class="bg-primary text-on-primary px-7 py-3.5 rounded-xl font-bold text-sm shadow-md hover:bg-primary-container transition-all flex items-center gap-2 active:scale-95 cursor-pointer">
                                        Save & Progress
                                        <span class="material-symbols-outlined text-lg">save</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>


            </div>
        </form>

        <!-- Full-Width: RECORDED CUTTING SESSIONS & FABRIC USAGE AUDIT TABLE -->
        <div class="mt-8 bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs space-y-4">
            <div class="flex justify-between items-center pb-4 border-b border-outline-variant/40">
                <div>
                    <h4 class="font-headline-sm text-headline-sm text-primary font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">inventory_2</span>
                        Recorded Cutting Sessions & Fabric Usage Log
                    </h4>
                    <p class="text-xs text-on-surface-variant mt-0.5">Audit log of all fabric roll deductions and cut piece outputs recorded for this job.</p>
                </div>
                <span class="px-3 py-1 bg-primary/10 text-primary rounded-xl font-bold text-xs">
                    Total Sessions: {{ $stageConsumptions->count() }}
                </span>
            </div>

            @if($stageConsumptions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse font-body-md">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/60 text-xs text-on-surface-variant uppercase tracking-wider">
                                <th class="px-4 py-3 font-bold">Date & Time</th>
                                <th class="px-4 py-3 font-bold">Fabric Batch</th>
                                <th class="px-4 py-3 font-bold text-center">Consumed Length</th>
                                <th class="px-4 py-3 font-bold text-center">Cut Outputs Produced</th>
                                <th class="px-4 py-3 font-bold text-right">Fabric Cost Valuation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @foreach($stageConsumptions as $consumption)
                                @php
                                    $matchedOutputs = $stageOutputs->where('inventory_batch_id', $consumption->inventory_batch_id);
                                @endphp
                                <tr class="hover:bg-surface-container/50 transition-colors">
                                    <td class="px-4 py-3 text-xs text-on-surface-variant font-medium">
                                        {{ $consumption->created_at ? $consumption->created_at->format('M d, Y · H:i A') : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-on-surface text-sm">{{ $consumption->inventoryBatch?->rawMaterial?->name ?? 'Fabric Material' }}</p>
                                        <span class="text-xs text-outline font-mono">Batch: {{ $consumption->inventoryBatch?->batch_number }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-black text-secondary text-sm">
                                        {{ number_format((float)$consumption->quantity_consumed, 2) }} <span class="text-xs font-normal text-outline">{{ $consumption->inventoryBatch?->unit ?? 'Meters' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($matchedOutputs->count() > 0)
                                            <div class="space-y-1">
                                                @foreach($matchedOutputs as $out)
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-primary/10 text-primary font-bold text-xs border border-primary/20">
                                                        {{ $out->quantity_produced }} Pcs · {{ $out->manufacturingProduct?->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-outline font-mono">Recorded</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-primary text-sm">
                                        ₹{{ number_format((float)$consumption->total_cost, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center bg-surface rounded-xl border border-outline-variant/40 space-y-2">
                    <span class="material-symbols-outlined text-4xl text-outline">content_cut</span>
                    <p class="text-sm font-bold text-on-surface-variant">No cutting sessions recorded yet for this job.</p>
                    <p class="text-xs text-outline">Select fabric material above and click "Save Cutting Session Output" to record cutting progress.</p>
                </div>
            @endif
        </div>
    @else
        <!-- STANDARD STAGE FORMS -->
        @if($hasMat && $wizardStep === 1)
        <!-- WIZARD STEP 1: MATERIAL CONSUMPTION & BOM -->
        @if($selectedTask && $selectedTask->consumes_raw_material && !$isTaskStitching)
        <!-- SECTION 1: RAW MATERIAL SELECTION & CONSUMPTION -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 sm:p-6 shadow-xs mb-8">
            <div class="bg-surface p-4 rounded-xl border border-outline-variant/60 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center font-bold shrink-0">
                        <span class="material-symbols-outlined">inventory_2</span>
                    </div>
                    <div>
                        <h3 class="font-headline-sm text-headline-sm text-primary font-bold">
                            Raw Material Selection: {{ $selectedTask ? $selectedTask->name : 'Stage' }}
                        </h3>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                            Select and consume raw material inventory batches allowed for stage <span class="font-bold text-primary">{{ $selectedTask ? $selectedTask->name : '' }}</span>.
                        </p>
                    </div>
                </div>

                @if(!$this->isSelectedStageCompleted)
                    <button type="button" wire:click="addMaterialRow" class="flex items-center gap-2 bg-secondary text-on-secondary px-4 py-2.5 rounded-xl font-label-md text-label-md hover:bg-secondary-container transition-all font-bold shadow-xs active:scale-95 shrink-0">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Add Material Row
                    </button>
                @endif
            </div>

            @if($errors->has('materialConsumptions'))
                <div class="bg-error-container/40 border border-error/30 text-error p-4 rounded-xl mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined text-error shrink-0">error</span>
                    <p class="font-body-md text-body-md font-semibold">{{ $errors->first('materialConsumptions') }}</p>
                </div>
            @endif

            <form wire:submit.prevent="saveMaterialConsumption" class="space-y-4">
                <div class="space-y-3">
                    @foreach($materialConsumptions as $index => $consumption)
                        @php
                            $selectedBatch = $availableBatches->firstWhere('id', $consumption['inventory_batch_id']);
                        @endphp

                        <div class="grid grid-cols-12 gap-3 sm:gap-4 items-center p-4 sm:p-5 bg-surface rounded-xl border border-outline-variant/60 shadow-xs hover:border-secondary/40 transition-all">
                            <div class="col-span-12 lg:col-span-6 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-secondary text-on-secondary font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Select Available Inventory Batch *</label>
                                    <select wire:model.live="materialConsumptions.{{ $index }}.inventory_batch_id" @if($this->isSelectedStageCompleted) disabled @endif class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all truncate @if($this->isSelectedStageCompleted) opacity-75 cursor-not-allowed @endif">
                                        <option value="">-- Select Material Batch for {{ $selectedTask ? $selectedTask->name : 'Stage' }} --</option>
                                        @foreach($availableBatches as $batch)
                                            <option value="{{ $batch->id }}">
                                                {{ $batch->rawMaterial?->name }} (Batch: {{ $batch->batch_number }}) — Avail: {{ number_format((float)$batch->balance_quantity, 2) }} {{ $batch->unit }} [Category: {{ $batch->rawMaterial?->category?->name ?? 'General' }}]
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("materialConsumptions.{$index}.inventory_batch_id") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>
                            </div>

                            <div class="col-span-12 sm:col-span-6 lg:col-span-3 flex items-center">
                                @if($selectedBatch)
                                    <div class="px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-2 border w-full bg-secondary-container/30 text-on-secondary-container border-secondary/20">
                                        <span class="material-symbols-outlined text-[20px] text-secondary shrink-0">inventory</span>
                                        <div class="truncate">
                                            <p class="font-bold text-[11px] uppercase tracking-wider">Stock: {{ number_format((float)$selectedBatch->balance_quantity, 2) }} {{ $selectedBatch->unit }}</p>
                                            <p class="text-[10px] opacity-80 font-medium">Unit Cost: ₹{{ number_format((float)$selectedBatch->unit_cost, 2) }} / {{ $selectedBatch->unit }}</p>
                                        </div>
                                    </div>
                                @else
                                    <div class="px-3.5 py-2 rounded-xl text-xs text-outline bg-surface-container-high/30 border border-outline-variant/30 italic w-full flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px] opacity-50">info</span>
                                        Select batch to view stock
                                    </div>
                                @endif
                            </div>

                            <div class="col-span-12 sm:col-span-6 lg:col-span-3 flex items-center justify-between gap-3">
                                <div class="flex-1">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Qty Consumed *</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" min="0.01" max="{{ $selectedBatch ? $selectedBatch->balance_quantity : 99999 }}" wire:model.live="materialConsumptions.{{ $index }}.quantity_consumed" @if($this->isSelectedStageCompleted) disabled @endif placeholder="0.00" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl pl-3.5 pr-14 py-2.5 text-sm font-black text-secondary text-center focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all @if($this->isSelectedStageCompleted) opacity-75 cursor-not-allowed @endif">
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-outline font-bold uppercase truncate max-w-[40px]">
                                            {{ $selectedBatch ? $selectedBatch->unit : 'Units' }}
                                        </span>
                                    </div>
                                    @error("materialConsumptions.{$index}.quantity_consumed") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>

                                @if(!$this->isSelectedStageCompleted && count($materialConsumptions) > 1)
                                    <button type="button" wire:click="removeMaterialRow({{ $index }})" class="p-2.5 text-error hover:bg-error-container/30 rounded-xl transition-colors shrink-0 mt-5" title="Remove Row">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40 mt-6">
                    @if($this->isSelectedStageCompleted)
                        <button type="button" disabled class="bg-outline-variant/40 text-on-surface-variant/60 px-8 py-3 rounded-xl font-label-md text-label-md font-bold cursor-not-allowed shadow-xs flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">lock</span>
                            Material Entry Locked
                        </button>
                    @else
                        <button type="submit" class="bg-secondary text-on-secondary px-8 py-3 rounded-xl font-label-md text-label-md font-bold hover:bg-secondary-container shadow-md transition-all active:scale-95 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">inventory</span>
                            Record Raw Material Consumption for {{ $selectedTask ? $selectedTask->name : 'Stage' }}
                        </button>
                    @endif
                </div>
            </form>

            <!-- 1. RECORDED RAW MATERIAL CONSUMPTION LOG -->
            <div class="mt-8 pt-6 border-t border-outline-variant/40">
                <h4 class="font-headline-sm text-headline-sm text-primary font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">inventory_2</span>
                    Material Consumption Log
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse font-body-md">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/60 text-xs text-on-surface-variant uppercase tracking-wider">
                                <th class="px-4 py-3 font-bold">Material & Batch</th>
                                <th class="px-4 py-3 font-bold text-center">Qty</th>
                                <th class="px-4 py-3 font-bold text-right">Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @forelse($stageConsumptions as $consumption)
                                <tr class="hover:bg-surface-container/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-on-surface text-sm">{{ $consumption->inventoryBatch?->rawMaterial?->name ?? 'Raw Material' }}</p>
                                        <span class="text-xs text-outline font-mono">Batch: {{ $consumption->inventoryBatch?->batch_number }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-black text-secondary text-sm">
                                        {{ number_format((float)$consumption->quantity_consumed, 2) }} <span class="text-xs font-normal text-outline">{{ $consumption->inventoryBatch?->unit }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-primary text-sm">
                                        ₹{{ number_format((float)$consumption->total_cost, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-on-surface-variant text-sm">No raw materials consumed yet for this stage.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif


        {{-- ═══ STITCHING COST POOL INFO BANNER (CAT-STITCH tasks) ═══ --}}
        @if($isTaskStitching)
            <div class="bg-tertiary-container/20 border border-tertiary/30 rounded-2xl p-5 mt-6 mb-8 flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-tertiary-container text-on-tertiary-container flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-[22px]">info</span>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-on-surface mb-1">Stitching Material — Cost Pool Accumulation</h3>
                    <p class="text-xs text-on-surface-variant leading-relaxed">
                        Stitching materials are not deducted per-unit during job execution. All stitching costs accumulate into the periodic Stitching Cost Pool for period-end allocation.
                    </p>
                </div>
            </div>
        @endif

        <!-- STEP 1 FOOTER NAVIGATION -->
        <div class="flex justify-between items-center p-4 bg-surface rounded-2xl border border-outline-variant/60 shadow-xs mb-8">
            <span class="text-xs font-bold text-on-surface-variant">Step 1 of 4: Material & Subsidiary BOM Entry</span>
            <button type="button" wire:click="setWizardStep(2)" class="bg-primary text-on-primary px-6 py-2.5 rounded-xl font-bold text-xs shadow-xs hover:bg-primary-container transition-all flex items-center gap-1.5 active:scale-95">
                Next Step: Record Workers & Allocations
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </button>
        </div>
        @endif

        @if((($hasMat && $wizardStep === 2) || (!$hasMat && $wizardStep === 1)) && $selectedTask)
        <!-- WIZARD WORKER ALLOCATION & LABOR MANAGEMENT -->
        <!-- SECTION 2: WORKER ALLOCATION & OUTPUT ENTRY -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 sm:p-6 shadow-xs mb-8">
            @if($precedingInfo)
                <div class="mb-5 p-4 rounded-xl border flex items-center justify-between {{ $precedingInfo['completed'] > $stageCompleted ? 'bg-primary/5 border-primary/20 text-primary' : 'bg-amber-500/10 border-amber-500/30 text-amber-900' }}">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[22px] shrink-0">account_tree</span>
                        <div>
                            <p class="font-bold text-xs">Sequential Dependency: Preceding Stage (<span class="underline">{{ $precedingInfo['task']->name }}</span>)</p>
                            <p class="text-xs opacity-90">Preceding Stage Output: <span class="font-extrabold">{{ number_format($precedingInfo['completed']) }} / {{ number_format($precedingInfo['target']) }} Pcs</span> • Current {{ $selectedTask?->name }} Output: <span class="font-extrabold">{{ number_format($stageCompleted) }} Pcs</span></p>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-[10px] uppercase tracking-wider font-bold block">Ready for {{ $selectedTask?->name }}</span>
                        <span class="text-base font-black">{{ number_format($stagePending) }} Pcs</span>
                    </div>
                </div>
            @endif

            <div class="bg-surface p-4 rounded-xl border border-outline-variant/60 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0">
                        <span class="material-symbols-outlined">engineering</span>
                    </div>
                    <div>
                        <h3 class="font-headline-sm text-headline-sm text-primary font-bold">
                            Stage Worker Execution & Labor Allocation: {{ $selectedTask ? $selectedTask->name : 'Select Stage' }}
                        </h3>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                            Assign authorized workers for stage <span class="font-bold text-primary">{{ $selectedTask ? $selectedTask->name : '' }}</span>.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right">
                        <span class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider block">Stage Progress</span>
                        <span class="text-base font-black text-primary">{{ number_format($stageCompleted) }} / {{ number_format($stageMaxAllowed) }} Pcs</span>
                    </div>

                    @if($stagePending > 0)
                        <div class="px-3.5 py-1.5 bg-amber-500/10 text-amber-700 border border-amber-500/30 rounded-xl text-xs font-extrabold flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            <span>{{ number_format($stagePending) }} Pcs Ready</span>
                        </div>
                    @else
                        <div class="px-3.5 py-1.5 bg-secondary-container text-on-secondary-container border border-secondary/30 rounded-xl text-xs font-extrabold flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">check_circle</span>
                            <span>Stage Completed</span>
                        </div>
                    @endif

                    @if(!$this->isSelectedStageCompleted)
                        <button type="button" wire:click="addLaborRow" class="flex items-center gap-2 bg-primary text-on-primary px-4 py-2.5 rounded-xl font-label-md text-label-md hover:bg-primary-container transition-all font-bold shadow-xs active:scale-95 ml-2">
                            <span class="material-symbols-outlined text-[18px]">person_add</span>
                            Add Worker Row
                        </button>
                    @endif
                </div>
            </div>


            @if($errors->has('laborAllocations'))
                <div class="bg-error-container/40 border border-error/30 text-error p-4 rounded-xl mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined text-error shrink-0">error</span>
                    <p class="font-body-md text-body-md font-semibold">{{ $errors->first('laborAllocations') }}</p>
                </div>
            @endif

            <form wire:submit.prevent="saveStageAllocations" class="space-y-4">
                <div class="space-y-3">
                    @foreach($laborAllocations as $index => $allocation)
                        @php
                            $selectedLabor = $authorizedLabors->firstWhere('id', $allocation['labor_id']);
                        @endphp

                        <div class="grid grid-cols-12 gap-3 sm:gap-4 items-center p-4 sm:p-5 bg-surface rounded-xl border border-outline-variant/60 shadow-xs hover:border-primary/40 transition-all">
                            <div class="col-span-12 lg:col-span-5 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary text-on-primary font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Select Authorized Worker *</label>
                                    <select wire:model.live="laborAllocations.{{ $index }}.labor_id" @if($this->isSelectedStageCompleted) disabled @endif class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all truncate @if($this->isSelectedStageCompleted) opacity-75 cursor-not-allowed @endif">
                                        <option value="">-- Choose Worker for {{ $selectedTask ? $selectedTask->name : 'Stage' }} --</option>
                                        @foreach($authorizedLabors as $labor)
                                            <option value="{{ $labor->id }}">
                                                {{ $labor->name }} ({{ $labor->code }}) — {{ $labor->payment_method === 'monthly_salary' ? 'Monthly Salaried' : 'Piece Rate' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("laborAllocations.{$index}.labor_id") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>
                            </div>

                            <div class="col-span-12 sm:col-span-6 lg:col-span-4">
                                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Product SKU *</label>
                                <select wire:model.live="laborAllocations.{{ $index }}.manufacturing_product_id" @if($job->manufacturing_product_id || $this->isSelectedStageCompleted) disabled @endif class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all truncate @if($job->manufacturing_product_id || $this->isSelectedStageCompleted) opacity-85 bg-surface-container-low cursor-not-allowed @endif">
                                    @php
                                        $stageProducts = $job->manufacturingProduct ? collect([$job->manufacturingProduct]) : $allManufacturingProducts;
                                    @endphp
                                    @foreach($stageProducts as $prod)
                                        <option value="{{ $prod->id }}">
                                            {{ $prod->name }} ({{ $prod->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-span-12 sm:col-span-6 lg:col-span-3 flex items-center justify-between gap-3">
                                <div class="flex-1">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Processed Qty *</label>
                                    <div class="relative flex gap-1.5 items-center">
                                        <div class="relative flex-1">
                                            <input type="number" min="1" wire:model.live="laborAllocations.{{ $index }}.quantity" @if($this->isSelectedStageCompleted) disabled @endif placeholder="0" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl pl-3.5 pr-12 py-2.5 text-sm font-black text-primary text-center focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @if($this->isSelectedStageCompleted) opacity-75 cursor-not-allowed @endif">
                                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[11px] text-outline font-bold uppercase">Pcs</span>
                                        </div>
                                        @if(!$this->isSelectedStageCompleted)
                                            <button type="button" wire:click="setAllLaborQuantity({{ $index }})" class="px-3 py-2.5 bg-primary/10 hover:bg-primary text-primary hover:text-on-primary text-[10px] font-extrabold uppercase rounded-xl transition-all shadow-xs shrink-0 h-[42px] flex items-center justify-center">
                                                All
                                            </button>
                                        @endif
                                    </div>
                                    @error("laborAllocations.{$index}.quantity") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>

                                @if(!$this->isSelectedStageCompleted && count($laborAllocations) > 1)
                                    <button type="button" wire:click="removeLaborRow({{ $index }})" class="p-2.5 text-error hover:bg-error-container/30 rounded-xl transition-colors shrink-0 mt-5" title="Remove Row">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40 mt-6">
                    @if($this->isSelectedStageCompleted)
                        <button type="button" disabled class="bg-outline-variant/40 text-on-surface-variant/60 px-8 py-3.5 rounded-xl font-label-md text-label-md font-bold cursor-not-allowed shadow-xs flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">lock</span>
                            Stage Completed & Locked
                        </button>
                    @else
                        <button type="submit" class="bg-primary text-on-primary px-8 py-3.5 rounded-xl font-label-md text-label-md font-bold hover:bg-primary-container shadow-md transition-all active:scale-95 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">task_alt</span>
                            Record Worker Output & Wages for {{ $selectedTask ? $selectedTask->name : 'Stage' }}
                        </button>
                    @endif
                </div>
            </form>

            <!-- 2. RECORDED WORKER STAGE OUTPUT LOG -->
            <div class="mt-8 pt-6 border-t border-outline-variant/40">
                <h4 class="font-headline-sm text-headline-sm text-primary font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">verified</span>
                    Worker Stage Output Log
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse font-body-md">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/60 text-xs text-on-surface-variant uppercase tracking-wider">
                                <th class="px-4 py-3 font-bold">Worker</th>
                                <th class="px-4 py-3 font-bold text-center">Output</th>
                                <th class="px-4 py-3 font-bold text-right">Wage</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @forelse($stageAllocations as $allocation)
                                <tr class="hover:bg-surface-container/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-on-surface text-sm">{{ $allocation->labor?->name ?? 'Unknown Worker' }}</p>
                                        <span class="text-xs text-outline">{{ $allocation->labor?->code }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-on-surface text-sm">
                                        {{ number_format($allocation->quantity_processed) }} <span class="text-xs font-normal text-outline">Pcs</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if(is_null($allocation->calculated_wage))
                                            <span class="text-xs text-outline italic">Salaried (Fixed)</span>
                                        @else
                                            <span class="font-bold text-secondary text-sm">₹{{ number_format($allocation->calculated_wage, 2) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-on-surface-variant text-sm">No worker allocations recorded yet for this stage.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- WORKER STEP FOOTER NAVIGATION -->
        <div class="flex justify-between items-center p-4 bg-surface rounded-2xl border border-outline-variant/60 shadow-xs mb-8">
            @if($hasMat)
                <button type="button" wire:click="setWizardStep(1)" class="bg-surface-container-low border border-outline-variant/60 text-on-surface px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-surface-container transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Back: Material Entry
                </button>
            @else
                <span class="text-xs font-bold text-on-surface-variant">Step 1 of {{ $maxSteps }}: Record Workers</span>
            @endif
            <button type="button" wire:click="setWizardStep({{ $hasMat ? 3 : 2 }})" class="bg-primary text-on-primary px-6 py-2.5 rounded-xl font-bold text-xs shadow-xs hover:bg-primary-container transition-all flex items-center gap-1.5 active:scale-95">
                Next Step: Record Product Output Yield
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </button>
        </div>
        @endif

        @if((($hasMat && $wizardStep === 3) || (!$hasMat && $wizardStep === 2)) && $selectedTask)
        <!-- WIZARD PRODUCT OUTPUT YIELD & PRODUCTION LOSS -->
        <!-- SECTION 3: MULTI-PRODUCT PRODUCTION OUTPUT RECORDING -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 sm:p-6 shadow-xs mb-8">
            <div class="bg-surface p-4 rounded-xl border border-outline-variant/60 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0">
                        <span class="material-symbols-outlined">precision_manufacturing</span>
                    </div>
                    <div>
                        <h3 class="font-headline-sm text-headline-sm text-primary font-bold">
                            Production Product Output Entry: {{ $selectedTask ? $selectedTask->name : 'Stage' }}
                        </h3>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                            Record quantities of manufacturing products produced from this stage activity (supports multiple product yields).
                        </p>
                    </div>
                </div>

                @if(!$this->isSelectedStageCompleted)
                    <button type="button" wire:click="addOutputRow" class="flex items-center gap-2 bg-primary text-on-primary px-4 py-2.5 rounded-xl font-label-md text-label-md hover:bg-primary-container transition-all font-bold shadow-xs active:scale-95 shrink-0">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Add Product Output Row
                    </button>
                @endif
            </div>

            @if($errors->has('productionOutputs'))
                <div class="bg-error-container/40 border border-error/30 text-error p-4 rounded-xl mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined text-error shrink-0">error</span>
                    <p class="font-body-md text-body-md font-semibold">{{ $errors->first('productionOutputs') }}</p>
                </div>
            @endif

            <form wire:submit.prevent="saveProductionOutput" class="space-y-4">
                <div class="space-y-3">
                    @foreach($productionOutputs as $index => $output)
                        <div class="grid grid-cols-12 gap-3 sm:gap-4 items-center p-4 sm:p-5 bg-surface rounded-xl border border-outline-variant/60 shadow-xs hover:border-primary/40 transition-all">
                            <div class="col-span-12 lg:col-span-8 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary text-on-primary font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Manufacturing Product Output *</label>
                                    @php
                                        $outputProdOptions = $job->manufacturingProduct ? collect([$job->manufacturingProduct]) : $allManufacturingProducts;
                                        $isFixedProduct = (bool) $job->manufacturing_product_id;
                                    @endphp
                                    <select wire:model.live="productionOutputs.{{ $index }}.manufacturing_product_id" @if($isFixedProduct || $this->isSelectedStageCompleted) disabled @endif class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-sm font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all truncate @if($isFixedProduct || $this->isSelectedStageCompleted) opacity-85 bg-surface-container-low cursor-not-allowed @endif">
                                        @foreach($outputProdOptions as $prod)
                                            <option value="{{ $prod->id }}">
                                                {{ $prod->name }} ({{ $prod->code }}) — Standard Wage: ₹{{ number_format((float)$prod->standard_labor_rate, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("productionOutputs.{$index}.manufacturing_product_id") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>
                            </div>

                            <div class="col-span-12 lg:col-span-4 flex items-center justify-between gap-3">
                                <div class="flex-1">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Quantity Produced *</label>
                                    <div class="relative">
                                        <input type="number" min="1" wire:model.live="productionOutputs.{{ $index }}.quantity_produced" @if($this->isSelectedStageCompleted) disabled @endif placeholder="0" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl pl-3.5 pr-14 py-2.5 text-sm font-black text-primary text-center focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all @if($this->isSelectedStageCompleted) opacity-75 cursor-not-allowed @endif">
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-outline font-bold uppercase">Pcs</span>
                                    </div>
                                    @error("productionOutputs.{$index}.quantity_produced") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>

                                @if(!$this->isSelectedStageCompleted && count($productionOutputs) > 1)
                                    <button type="button" wire:click="removeOutputRow({{ $index }})" class="p-2.5 text-error hover:bg-error-container/30 rounded-xl transition-colors shrink-0 mt-5" title="Remove Row">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40 mt-6">
                    @if($this->isSelectedStageCompleted)
                        <button type="button" disabled class="bg-outline-variant/40 text-on-surface-variant/60 px-8 py-3 rounded-xl font-label-md text-label-md font-bold cursor-not-allowed shadow-xs flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">lock</span>
                            Output Entry Locked
                        </button>
                    @else
                        <button type="submit" class="bg-primary text-on-primary px-8 py-3 rounded-xl font-label-md text-label-md font-bold hover:bg-primary-container shadow-md transition-all active:scale-95 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">precision_manufacturing</span>
                            Record Product Output for {{ $selectedTask ? $selectedTask->name : 'Stage' }}
                        </button>
                    @endif
                </div>
            </form>

            <!-- 3. RECORDED PRODUCT OUTPUT LOG -->
            <div class="mt-8 pt-6 border-t border-outline-variant/40">
                <h4 class="font-headline-sm text-headline-sm text-primary font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">precision_manufacturing</span>
                    Product Output Yield Log
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse font-body-md">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/60 text-xs text-on-surface-variant uppercase tracking-wider">
                                <th class="px-4 py-3 font-bold">Product Yield</th>
                                <th class="px-4 py-3 font-bold text-center">Qty</th>
                                <th class="px-4 py-3 font-bold text-right">Logged At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @forelse($stageOutputs as $output)
                                <tr class="hover:bg-surface-container/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-on-surface text-sm">{{ $output->manufacturingProduct?->name ?? 'Product' }}</p>
                                        <span class="text-xs text-outline font-mono">{{ $output->manufacturingProduct?->code }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-black text-primary text-sm">
                                        {{ number_format($output->quantity_produced) }} <span class="text-xs font-normal text-outline">Pcs</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-xs font-mono text-outline whitespace-nowrap">
                                        {{ $output->created_at ? $output->created_at->format('d M, h:i A') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-on-surface-variant text-sm">No product outputs recorded yet for this stage.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- OUTPUT STEP FOOTER NAVIGATION -->
        <div class="mt-8 flex justify-between items-center p-4 bg-surface rounded-2xl border border-outline-variant/60 shadow-xs mb-8">
            <button type="button" wire:click="setWizardStep({{ $hasMat ? 2 : 1 }})" class="bg-surface-container-low border border-outline-variant/60 text-on-surface px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-surface-container transition-all flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                Back: Record Workers
            </button>
            <button type="button" wire:click="setWizardStep({{ $hasMat ? 4 : 3 }})" class="bg-primary text-on-primary px-6 py-2.5 rounded-xl font-bold text-xs shadow-xs hover:bg-primary-container transition-all flex items-center gap-1.5 active:scale-95">
                Next Step: Alterations
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </button>
        </div>
        @endif

        @if((($hasMat && $wizardStep === 4) || (!$hasMat && $wizardStep === 3)) && $selectedTask)
            @php
                $varInfo = $this->stageVarianceInfo;
            @endphp
            
            @if($varInfo['has_shortfall'] || count($stageWastages) > 0)
        <!-- SECTION 4: WASTAGE & PRODUCTION LOSS RECORDING -->
        <div class="bg-error-container/10 border border-outline-variant/60 rounded-2xl p-5 sm:p-6 shadow-xs mb-8">
            <div class="bg-surface p-4 rounded-xl border border-error/20 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-error/10 text-error flex items-center justify-center font-bold shrink-0">
                        <span class="material-symbols-outlined">report_problem</span>
                    </div>
                    <div>
                        <h3 class="font-headline-sm text-headline-sm text-error font-bold">
                            Production Loss & Wastage Entry: {{ $selectedTask ? $selectedTask->name : 'Stage' }}
                        </h3>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                            Record raw material fabric scraps or damaged units (permanently deducted from downstream stage processing).
                        </p>
                    </div>
                </div>

                @if(!$this->isSelectedStageCompleted)
                    <button type="button" wire:click="addWastageRow" class="flex items-center gap-2 bg-error text-on-error px-4 py-2.5 rounded-xl font-label-md text-label-md hover:bg-error-container hover:text-error transition-all font-bold shadow-xs active:scale-95 shrink-0">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Add Wastage Row
                    </button>
                @endif
            </div>

            @if($errors->has('wastageRecords'))
                <div class="bg-error-container/40 border border-error/30 text-error p-4 rounded-xl mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined text-error shrink-0">error</span>
                    <p class="font-body-md text-body-md font-semibold">{{ $errors->first('wastageRecords') }}</p>
                </div>
            @endif

            <form wire:submit.prevent="saveJobWastage" class="space-y-4">
                <div class="space-y-3">
                    @foreach($wastageRecords as $index => $wastage)
                        <div class="grid grid-cols-12 gap-3 sm:gap-4 items-center p-4 sm:p-5 bg-surface rounded-xl border border-error/20 shadow-xs hover:border-error/50 transition-all">
                            <div class="col-span-12 lg:col-span-5 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-error text-on-error font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <label class="block text-[11px] font-bold text-error uppercase tracking-wider mb-1">Product (If Applicable)</label>
                                    <select wire:model.live="wastageRecords.{{ $index }}.manufacturing_product_id" @if($this->isSelectedStageCompleted) disabled @endif class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-error/20 focus:border-error transition-all truncate @if($this->isSelectedStageCompleted) opacity-75 cursor-not-allowed @endif">
                                        <option value="">-- None / General Raw Fabric Scraps --</option>
                                        @foreach($allManufacturingProducts as $prod)
                                            <option value="{{ $prod->id }}">
                                                {{ $prod->name }} ({{ $prod->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                                <label class="block text-[11px] font-bold text-error uppercase tracking-wider mb-1">Qty Wasted *</label>
                                <div class="relative">
                                    <input type="number" step="0.01" min="0.01" wire:model.live="wastageRecords.{{ $index }}.quantity_wasted" @if($this->isSelectedStageCompleted) disabled @endif placeholder="0.00" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl pl-3.5 pr-14 py-2.5 text-sm font-black text-error text-center focus:ring-2 focus:ring-error/20 focus:border-error transition-all @if($this->isSelectedStageCompleted) opacity-75 cursor-not-allowed @endif">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-error font-bold uppercase">Units</span>
                                </div>
                                @error("wastageRecords.{$index}.quantity_wasted") 
                                    <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                @enderror
                            </div>

                            <div class="col-span-12 sm:col-span-6 lg:col-span-4 flex items-center justify-between gap-3">
                                <div class="flex-1">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Wastage Reason (Optional)</label>
                                    <input type="text" wire:model.live="wastageRecords.{{ $index }}.reason" @if($this->isSelectedStageCompleted) disabled @endif placeholder="e.g. Scraps, Ruined Stitching" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-error/20 focus:border-error transition-all @if($this->isSelectedStageCompleted) opacity-75 cursor-not-allowed @endif">
                                </div>

                                @if(!$this->isSelectedStageCompleted && count($wastageRecords) > 1)
                                    <button type="button" wire:click="removeWastageRow({{ $index }})" class="p-2.5 text-error hover:bg-error-container/30 rounded-xl transition-colors shrink-0 mt-5" title="Remove Row">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(!$this->isSelectedStageCompleted)
                    <div class="flex justify-end gap-3 pt-4 border-t border-error/20 mt-6">
                        <button type="submit" class="bg-error text-on-error px-8 py-3 rounded-xl font-label-md text-label-md font-bold hover:bg-error-container hover:text-error shadow-md transition-all active:scale-95 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">report_problem</span>
                            Record Wastage & Production Loss Log
                        </button>
                    </div>
                @endif
            </form>

            <!-- 4. RECORDED WASTAGE & PRODUCTION LOSS LOG -->
            <div class="mt-8 pt-6 border-t border-error/20">
                <h4 class="font-headline-sm text-headline-sm text-error font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-error">report_problem</span>
                    Production Loss & Wastage Log
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse font-body-md">
                        <thead>
                            <tr class="bg-error-container/20 border-b border-outline-variant/60 text-xs text-error uppercase tracking-wider">
                                <th class="px-4 py-3 font-bold">Product / Scraps</th>
                                <th class="px-4 py-3 font-bold text-center">Wasted Qty</th>
                                <th class="px-4 py-3 font-bold text-right">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/40">
                            @forelse($stageWastages as $wastage)
                                <tr class="hover:bg-error-container/10 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-on-surface text-sm">{{ $wastage->manufacturingProduct?->name ?? 'General Fabric Scraps' }}</p>
                                        <span class="text-xs text-outline font-mono">{{ $wastage->task?->name }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center font-black text-error text-sm">
                                        {{ number_format((float)$wastage->quantity_wasted, 2) }} <span class="text-xs font-normal text-outline">Units</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-xs font-medium text-on-surface-variant">
                                        {{ $wastage->reason }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-on-surface-variant text-sm">No production loss or wastage logged for this stage.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

            @if($varInfo['has_shortfall'])
                <!-- VARIANCE ALERT CARD -->
                <div class="mt-8 bg-amber-500/10 border border-amber-500/30 rounded-2xl p-6 shadow-xs text-amber-950 space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-900 flex items-center justify-center font-bold shrink-0">
                            <span class="material-symbols-outlined text-[28px]">warning</span>
                        </div>
                        <div class="space-y-1">
                            <h4 class="font-headline-sm text-headline-sm font-bold text-amber-950">Output Quantity Shortfall Detected!</h4>
                            <p class="text-xs text-amber-900 leading-relaxed">
                                Stage input quantity entering <strong>{{ $selectedTask?->name }}</strong> was <strong>{{ number_format($varInfo['input_qty']) }} Pcs</strong>, but recorded product output is <strong>{{ number_format($varInfo['output_qty']) }} Pcs</strong> (Shortfall: <strong>{{ number_format($varInfo['shortfall_qty']) }} Pcs</strong>).
                            </p>
                            <p class="text-xs font-semibold text-amber-900">
                                Were these remaining <strong>{{ number_format($varInfo['shortfall_qty']) }} Pcs</strong> converted or altered into another target product (e.g. bedsheet cut pieces converted into pillow covers)?
                            </p>
                        </div>
                    </div>

                    <!-- ALTERATION CONVERTER FORM -->
                    <div class="bg-surface-container-lowest border border-amber-500/30 rounded-xl p-5 space-y-4 text-on-surface">
                        <h5 class="font-bold text-xs uppercase tracking-wider text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-600">published_with_changes</span>
                            Record Product Alteration & Spawn Child Production Batch
                        </h5>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Target Converted Product *</label>
                                <select wire:model.live="alterationRecords.0.target_product_id" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                                    <option value="">-- Select Target Product (e.g. Pillow Covers) --</option>
                                    @foreach($allManufacturingProducts as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
                                    @endforeach
                                </select>
                                @error('alterationRecords.0.target_product_id')
                                    <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Quantity Converted (Pcs) *</label>
                                <input type="number" min="1" max="{{ $varInfo['shortfall_qty'] }}" wire:model.live="alterationRecords.0.target_quantity" placeholder="{{ $varInfo['shortfall_qty'] }}" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-black text-primary text-center focus:ring-2 focus:ring-primary/20">
                                @error('alterationRecords.0.target_quantity')
                                    <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="button" wire:click="saveJobAlteration" class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-6 py-2.5 rounded-xl text-xs shadow-xs transition-all flex items-center gap-2 active:scale-95">
                                <span class="material-symbols-outlined text-[18px]">alt_route</span>
                                Record Alteration & Start Cutting Stage for New Product
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <!-- ALTERATION LOG TABLE -->
            @if(count($jobAlterations) > 0)
                <div class="mt-8 bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 sm:p-6 shadow-xs">
                    <h4 class="font-headline-sm text-headline-sm text-amber-800 font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-700">alt_route</span>
                        Recorded Product Alterations & Linked Child Batches
                    </h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse font-body-md">
                            <thead>
                                <tr class="bg-amber-500/10 border-b border-outline-variant/60 text-xs text-amber-900 uppercase tracking-wider">
                                    <th class="px-4 py-3 font-bold">Source Product & Qty</th>
                                    <th class="px-4 py-3 font-bold">Target Product Yield</th>
                                    <th class="px-4 py-3 font-bold text-center">Generated Child Batch</th>
                                    <th class="px-4 py-3 font-bold text-right">Logged At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/40">
                                @foreach($jobAlterations as $alt)
                                    <tr class="hover:bg-surface-container/50 transition-colors">
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-on-surface text-sm">{{ $alt->sourceProduct?->name ?? 'Source Product' }}</p>
                                            <span class="text-xs font-black text-amber-800">{{ number_format($alt->source_quantity) }} Pcs</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-primary text-sm">{{ $alt->targetProduct?->name ?? 'Target Product' }}</p>
                                            <span class="text-xs font-black text-secondary">{{ number_format($alt->target_quantity) }} Pcs</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($alt->childBatch)
                                                <span class="px-3 py-1 bg-amber-500/20 text-amber-800 font-mono font-black text-xs rounded-xl border border-amber-500/30">
                                                    {{ $alt->childBatch->batch_code }}
                                                </span>
                                            @else
                                                <span class="text-xs text-outline font-mono">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right text-xs font-mono text-outline whitespace-nowrap">
                                            {{ $alt->created_at ? $alt->created_at->format('d M, h:i A') : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- STAGE WORKFLOW PROGRESSION ACTION CARD -->
            <div class="mt-8 p-6 bg-surface-container-lowest border border-secondary/40 rounded-2xl shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-secondary/15 text-secondary flex items-center justify-center font-bold shrink-0">
                            <span class="material-symbols-outlined text-[26px]">task_alt</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm text-primary">Stage Progression & Target Completion</h4>
                            <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                                Once all worker outputs and product yields are logged, click below to mark this stage 100% completed and progress to the next task in the workflow.
                            </p>
                        </div>
                    </div>

                    @if($this->isSelectedStageCompleted)
                        <button type="button" wire:click="completeStageAndProgress" class="bg-emerald-600 text-white hover:bg-emerald-700 px-8 py-3.5 rounded-xl font-bold text-xs shadow-md transition-all active:scale-95 flex items-center gap-2 shrink-0">
                            <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                            Go to Next Work Step
                        </button>
                    @else
                        <button type="button" wire:click="completeStageAndProgress" class="bg-emerald-600 text-white hover:bg-emerald-700 px-8 py-3.5 rounded-xl font-bold text-xs shadow-md transition-all active:scale-95 flex items-center gap-2 shrink-0">
                            <span class="material-symbols-outlined text-[20px]">task_alt</span>
                            Complete Stage & Progress Workflow
                        </button>
                    @endif
                </div>
            </div>

            <!-- WASTAGE STEP FOOTER NAVIGATION -->
            <div class="mt-8 flex justify-between items-center p-4 bg-surface rounded-2xl border border-outline-variant/60 shadow-xs mb-8">
                <button type="button" wire:click="setWizardStep({{ $hasMat ? 3 : 2 }})" class="bg-surface-container-low border border-outline-variant/60 text-on-surface px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-surface-container transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Back: Product Output
                </button>
                <span class="text-xs font-bold text-on-surface-variant">Step {{ $maxSteps }} of {{ $maxSteps }}: Wastage & Stage Completion</span>
            </div>
        @endif
        @endif
        </div>

        <!-- RIGHT COLUMN: STAGE NAVIGATION SIDEBAR (1 SPAN) -->
        <div class="lg:col-span-1 space-y-4 order-1 lg:order-first">
            <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 shadow-xs sticky top-6">
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-outline-variant/40">
                    <span class="material-symbols-outlined text-primary text-[20px]">layers</span>
                    <h4 class="font-headline-sm text-sm font-extrabold text-primary uppercase tracking-wider">Production Stages</h4>
                </div>

                <div class="space-y-3">
                    @php
                        $visibleStageExecutions = (!$job->manufacturing_product_id)
                            ? $job->stageExecutions->where('sequence_number', 1)
                            : $job->stageExecutions;
                    @endphp
                    @foreach($visibleStageExecutions as $idx => $stageExec)
                        @php
                            $task = $stageExec->task;
                            $stageOutputSum = $stageExec->completed_quantity;
                            $stageMax = (int)$stageExec->target_quantity;
                            $stagePending = $stageExec->pending_quantity;
                            $isSelected = ($selectedTaskId == $task?->id);
                        @endphp
                        <button type="button" wire:click="selectTask({{ $task?->id }})" class="w-full text-left p-3.5 rounded-xl border transition-all flex flex-col gap-2 relative overflow-hidden group {{ $isSelected ? 'bg-primary text-on-primary border-primary shadow-md' : 'bg-surface-container-low text-on-surface border-outline-variant/60 hover:bg-surface-container-high hover:border-primary/40' }}">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-xs tracking-tight flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black {{ $isSelected ? 'bg-white/20 text-white' : 'bg-primary/10 text-primary' }}">
                                        {{ $idx + 1 }}
                                    </span>
                                    {{ $task?->name }}
                                </span>
                                @if($stageExec->status === 'completed')
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider {{ $isSelected ? 'bg-white/20 text-white' : 'bg-secondary/15 text-secondary' }}">
                                        Completed
                                    </span>
                                @elseif($isSelected)
                                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between text-[11px] {{ $isSelected ? 'text-white/80' : 'text-on-surface-variant' }}">
                                <span class="font-medium">Yield Output</span>
                                <span class="font-mono font-bold">{{ number_format($stageOutputSum) }} / {{ number_format($stageMax) }} Pcs</span>
                            </div>

                            <div class="w-full bg-black/10 rounded-full h-1.5 overflow-hidden">
                                <div class="h-full rounded-full transition-all {{ $isSelected ? 'bg-white' : 'bg-primary' }}" style="width: {{ min(100, $stageMax > 0 ? ($stageOutputSum / $stageMax) * 100 : 0) }}%"></div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Final Task Completion Modal -->
    @if($showFinalCompletionModal)
        <div class="fixed inset-0 bg-on-surface/50 backdrop-blur-xs flex items-center justify-center z-50 transition-all duration-300">
            <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/60 shadow-2xl p-8 max-w-md w-full mx-4 text-center transform scale-100 transition-all">
                <!-- Checkmark Icon -->
                <div class="w-20 h-20 bg-secondary-container text-on-secondary-container rounded-full flex items-center justify-center mx-auto mb-6 shadow-xs">
                    <span class="material-symbols-outlined text-4xl" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                </div>
                
                <!-- Badge & Title -->
                <span class="px-4 py-1.5 bg-secondary-container/60 text-on-secondary-container rounded-full text-xs font-black uppercase tracking-wider inline-block mb-3 border border-secondary/20">
                    Final Task Completion - Batch Finished
                </span>
                
                <h3 class="font-headline-md text-headline-md text-primary font-extrabold tracking-tight mb-2">
                    Manufacturing Completed!
                </h3>
                
                <p class="font-body-md text-on-surface-variant text-sm mb-8 leading-relaxed">
                    Final production task <strong class="text-on-surface">[{{ $job->task->name }}]</strong> has been completed. This batch (<strong>{{ $job->production_batch_code ?? $job->production_batch_id }}</strong>) is now ready for Finished Goods Conversion.
                </p>
                
                <!-- CTAs -->
                <div class="space-y-3">
                    <a href="{{ route('factory.batches.convert', $job->production_batch_db_id) }}" wire:navigate class="w-full inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-container text-on-primary font-bold py-3.5 px-6 rounded-xl transition-all shadow-md active:scale-95 text-sm">
                        <span class="material-symbols-outlined text-[18px]">autofps_select</span>
                        Proceed to Finished Goods Conversion
                    </a>
                    
                    <button type="button" wire:click="$set('showFinalCompletionModal', false)" class="w-full inline-flex justify-center text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low font-bold py-2.5 rounded-xl transition-all text-xs">
                        Keep Reviewing Job Detail
                    </button>
                </div>
            </div>
        </div>
    @endif

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

