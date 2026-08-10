<div>
    <!-- Back Navigation & Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.production.jobs.index') }}" wire:navigate class="text-primary font-bold text-xs flex items-center gap-1 hover:underline">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Back to Production Jobs Hub
                </a>
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

    <!-- STAGE SELECTION TABS -->
    <div class="mb-6">
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-hide">
            <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider shrink-0 mr-2">Production Stage:</span>
            @foreach($job->stageExecutions as $idx => $stageExec)
                @php
                    $task = $stageExec->task;
                    $stageOutputSum = $stageExec->completed_quantity;
                    $stageMax = $stageExec->target_quantity > 0 ? $stageExec->target_quantity : (int)$job->target_quantity;
                    $stagePending = $stageExec->pending_quantity;
                    $isSelected = ($selectedTaskId == $task?->id);
                @endphp
                <button type="button" wire:click="selectTask({{ $task?->id }})" class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs transition-all shrink-0 border {{ $isSelected ? 'bg-primary text-on-primary border-primary shadow-sm' : 'bg-surface-container-lowest text-on-surface-variant border-outline-variant/60 hover:bg-surface-container-high' }}">
                    <span>P{{ $idx + 1 }}: {{ $task?->name }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] {{ $isSelected ? 'bg-white text-primary font-black' : ($stagePending > 0 ? 'bg-amber-500/10 text-amber-700 font-bold' : 'bg-secondary/10 text-secondary font-bold') }}">
                        {{ number_format($stageOutputSum) }} / {{ number_format($stageMax) }} Pcs
                        @if($stagePending > 0)
                            • {{ number_format($stagePending) }} Ready
                        @else
                            • Completed
                        @endif
                    </span>
                </button>
            @endforeach

        </div>
      @if($selectedTask && ($selectedTask->name === 'Cutting' || $selectedTask->code === 'TSK-001'))
        <!-- CUSTOM CUTTING SESSION TERMINAL UI -->
        <form wire:submit.prevent="saveCuttingSession" class="space-y-6 mb-8">
            <div class="grid grid-cols-12 gap-6">
                <!-- Left Side: Cutting Form Entry -->
                <div class="col-span-12 xl:col-span-8 space-y-6">
                    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-outline-variant/40">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                                <span class="material-symbols-outlined text-[22px]">content_cut</span>
                            </div>
                            <div>
                                <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Cutting Operations Terminal</h3>
                                <p class="text-xs text-on-surface-variant font-medium mt-0.5">Select fabric inventory, record dimensions, consumed length, wastage, and cut yields.</p>
                            </div>
                        </div>

                        @if($errors->any())
                            <div class="bg-error-container/40 border border-error/30 text-error p-4 rounded-xl mb-6">
                                <ul class="list-disc pl-5 text-xs font-semibold space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="space-y-4 mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Step 1: Select Fabric Raw Material -->
                                <div>
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">1. Select Fabric Material *</label>
                                    <select wire:model.live="cuttingFabricMaterialId" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <option value="">-- Select Fabric Material --</option>
                                        @foreach($this->fabricMaterialsList as $mat)
                                            <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->code }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Step 2: Select Fabric Batch -->
                                <div>
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">2. Select Fabric Batch *</label>
                                    <select wire:model.live="cuttingFabricBatchId" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary" {{ empty($cuttingFabricMaterialId) ? 'disabled' : '' }}>
                                        <option value="">-- Choose Batch --</option>
                                        @foreach($this->batchesForSelectedFabric as $batch)
                                            <option value="{{ $batch->id }}">
                                                {{ $batch->batch_number }} — Stock: {{ number_format($batch->balance_quantity, 2) }} {{ $batch->unit }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('cuttingFabricBatchId') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                                </div>

                                <!-- Step 3: Select Particular Bale / Lot -->
                                <div>
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">3. Select Bale / Lot</label>
                                    <select wire:model.live="cuttingFabricBaleId" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary" {{ empty($cuttingFabricBatchId) ? 'disabled' : '' }}>
                                        <option value="">-- Select Particular Bale --</option>
                                        @foreach($this->balesForSelectedBatch as $bale)
                                            <option value="{{ $bale->id }}">
                                                {{ $bale->bale_number }} [{{ strtoupper($bale->status) }}] — Bal: {{ number_format($bale->current_balance_length, 2) }}m (Decl: {{ $bale->declared_length }}m)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @php
                                $selectedBatch = !empty($this->cuttingFabricBatchId) ? \App\Models\InventoryBatch::find($this->cuttingFabricBatchId) : null;
                                $selectedBale = !empty($this->cuttingFabricBaleId) ? \App\Models\InventoryBale::find($this->cuttingFabricBaleId) : null;
                            @endphp
                            @if($selectedBatch)
                                <div class="p-4 bg-secondary-container/20 text-on-secondary-container border border-secondary/20 rounded-xl flex flex-wrap justify-between items-center text-xs gap-3">
                                    <div>
                                        <span class="font-bold block text-[10px] uppercase text-on-surface-variant">Batch Reference</span>
                                        <span class="font-extrabold text-primary font-mono text-xs">{{ $selectedBatch->batch_number }}</span>
                                    </div>
                                    <div>
                                        <span class="font-bold block text-[10px] uppercase text-on-surface-variant">Available Stock</span>
                                        <span class="font-extrabold text-secondary">{{ number_format($selectedBatch->balance_quantity, 2) }} {{ $selectedBatch->unit }}</span>
                                    </div>
                                    <div>
                                        <span class="font-bold block text-[10px] uppercase text-on-surface-variant">Purchase Rate</span>
                                        <span class="font-extrabold text-primary">₹{{ number_format($selectedBatch->unit_cost, 2) }} / {{ $selectedBatch->unit }}</span>
                                    </div>
                                    @if($selectedBale)
                                        <div class="border-l border-secondary/20 pl-3">
                                            <span class="font-bold block text-[10px] uppercase text-amber-800">Selected Bale</span>
                                            <span class="font-extrabold text-amber-900 font-mono text-xs">{{ $selectedBale->bale_number }} (Bal: {{ number_format($selectedBale->current_balance_length, 2) }}m)</span>
                                            <span class="inline-block text-[9px] font-black uppercase px-2 py-0.5 rounded ml-1 {{ $selectedBale->status === 'unopened' ? 'bg-amber-200 text-amber-900' : 'bg-emerald-100 text-emerald-800' }}">
                                                {{ $selectedBale->status }}
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                @if($selectedBale && $selectedBale->status === 'unopened')
                                    <div class="p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl flex flex-wrap justify-between items-center text-xs gap-3">
                                        <div class="space-y-0.5">
                                            <div class="font-extrabold text-amber-900 flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-[18px]">inventory_2</span> Unopened Bale Selected (Declared Length: {{ $selectedBale->declared_length }}m)
                                            </div>
                                            <p class="text-[11px] text-amber-800 font-medium">Bale has not been opened yet. Open bale to enter the number of rolls and measured roll lengths for material calculation.</p>
                                        </div>
                                        <button type="button" wire:click="triggerOpenBaleModal({{ $selectedBale->id }})" class="bg-amber-600 hover:bg-amber-700 text-white font-extrabold px-4 py-2 rounded-xl text-xs shadow-xs transition-all flex items-center gap-1.5 active:scale-95 shrink-0">
                                            <span class="material-symbols-outlined text-[16px]">content_cut</span> Open Bale & Record Rolls
                                        </button>
                                    </div>
                                @endif
                            @endif
                        </div>

                            <!-- Fabric Dimensions & Consumed -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Consumed Length *</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" min="0.01" wire:model.live="cuttingConsumedLength" placeholder="0.00" class="w-full bg-surface border border-outline-variant/60 rounded-xl pl-4 pr-12 py-3 text-sm font-bold text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-outline font-bold uppercase">
                                            {{ $selectedBatch ? $selectedBatch->unit : 'Units' }}
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-2">Fabric Width (Inches) *</label>
                                    <input type="number" step="0.1" wire:model.live="cuttingFabricWidth" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>

                                <div class="col-span-2">
                                    <label class="block text-[11px] font-bold text-error uppercase tracking-wider mb-2">Fabric Wastage Length *</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" min="0" wire:model.live="cuttingWastageLength" placeholder="0.00" class="w-full bg-surface border border-error/30 rounded-xl pl-4 pr-12 py-3 text-sm font-bold text-error focus:ring-2 focus:ring-error/20 focus:border-error">
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-error font-bold uppercase">
                                            {{ $selectedBatch ? $selectedBatch->unit : 'Units' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <!-- Cut Piece Output Grid Card -->
                    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs space-y-4">
                        <div class="flex justify-between items-center pb-4 border-b border-outline-variant/40">
                            <h4 class="font-headline-sm text-headline-sm text-primary font-bold">Cut Piece Output Grid</h4>
                            <button type="button" wire:click="addCuttingOutputRow" class="flex items-center gap-1 bg-secondary text-on-secondary px-4 py-2 rounded-xl text-xs font-bold shadow-xs hover:bg-secondary-container transition-all active:scale-95">
                                <span class="material-symbols-outlined text-[16px]">add</span> Add Row
                            </button>
                        </div>

                        <div class="space-y-4">
                            @foreach($cuttingOutputs as $index => $output)
                                <div class="flex flex-wrap md:flex-nowrap items-center gap-4 p-4 bg-surface rounded-xl border border-outline-variant/60 shadow-xs relative">
                                    <!-- Product Select -->
                                    <div class="flex-1 min-w-[180px]">
                                        <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Target Product *</label>
                                        <select wire:model.live="cuttingOutputs.{{ $index }}.manufacturing_product_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                            <option value="">-- Choose SKU --</option>
                                            @foreach($allManufacturingProducts as $prod)
                                                <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->code }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Dimensions Width -->
                                    <div class="w-32">
                                        <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Width *</label>
                                        <div class="flex gap-1">
                                            <input type="number" step="0.01" wire:model.live="cuttingOutputs.{{ $index }}.width" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-2 py-2 text-xs font-bold text-center focus:ring-1 focus:ring-primary">
                                            <select wire:model.live="cuttingOutputs.{{ $index }}.width_unit" class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-1 py-2 text-[10px] font-bold">
                                                <option value="inch">in</option>
                                                <option value="cm">cm</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Dimensions Length -->
                                    <div class="w-32">
                                        <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Length *</label>
                                        <div class="flex gap-1">
                                            <input type="number" step="0.01" wire:model.live="cuttingOutputs.{{ $index }}.length" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-2 py-2 text-xs font-bold text-center focus:ring-1 focus:ring-primary">
                                            <select wire:model.live="cuttingOutputs.{{ $index }}.length_unit" class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-1 py-2 text-[10px] font-bold">
                                                <option value="meter">m</option>
                                                <option value="yard">yd</option>
                                                <option value="inch">in</option>
                                                <option value="cm">cm</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Quantity -->
                                    <div class="w-24">
                                        <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Quantity *</label>
                                        <input type="number" min="1" wire:model.live="cuttingOutputs.{{ $index }}.quantity" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-black text-primary text-center focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                    </div>

                                    <!-- Delete button -->
                                    @if(count($cuttingOutputs) > 1)
                                        <button type="button" wire:click="removeCuttingOutputRow({{ $index }})" class="p-2 text-error hover:bg-error-container/30 rounded-xl transition-colors shrink-0 self-end mb-1" title="Remove Row">
                                            <span class="material-symbols-outlined text-[20px]">delete</span>
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Right Side: Real-time Cost Preview Summary -->
                <div class="col-span-12 xl:col-span-4 space-y-6">
                    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs space-y-4">
                        <h4 class="font-headline-sm text-headline-sm text-primary font-bold flex items-center gap-2">
                            <span class="material-symbols-outlined">payments</span> Cost Valuation
                        </h4>

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
                                        <span class="text-secondary font-black">₹{{ number_format($this->cuttingCostPreview['total_fabric_cost'], 2) }}</span>
                                    </div>
                                </div>

                                <div class="space-y-3 pt-2">
                                    <span class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Itemized Cost Allocation:</span>
                                    @foreach($this->cuttingCostPreview['preview_items'] as $item)
                                        <div class="p-3 bg-surface border border-outline-variant/40 rounded-xl text-xs space-y-1.5 shadow-2xs">
                                            <div class="flex justify-between font-bold text-on-surface text-sm truncate">
                                                <span>{{ $item['product_name'] }}</span>
                                                <span>x{{ $item['quantity'] }}</span>
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
                                            <div class="text-[10px] text-outline font-semibold">
                                                Yield Cost: ₹{{ number_format($item['cost_per_unit'], 2) }} / pc
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="p-8 text-center text-on-surface-variant bg-surface rounded-xl border border-outline-variant/40 italic text-xs flex flex-col items-center gap-2">
                                <span class="material-symbols-outlined text-4xl text-outline opacity-40">calculate</span>
                                Enter fabric roll selection and consumed quantity to display live cost rollup preview.
                            </div>
                        @endif
                    </div>

                    <!-- Complete Stage Action Button -->
                    <div class="bg-surface-container-low border border-outline-variant/60 rounded-2xl p-6 shadow-xs text-center space-y-3">
                        <p class="text-xs text-on-surface-variant leading-relaxed">Ensure all pieces, measurements, and fabric wastes are logged accurately. Saving this cutting session will deduct bale lengths from stock and allocate unit costs downstream.</p>
                        <button type="submit" class="w-full bg-primary text-on-primary py-4 rounded-xl font-bold text-base hover:bg-primary-container shadow-md transition-all active:scale-95 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">save</span>
                            Save Cutting Session Output
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @else
        <!-- STANDARD STAGE FORMS -->
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

                <button type="button" wire:click="addMaterialRow" class="flex items-center gap-2 bg-secondary text-on-secondary px-4 py-2.5 rounded-xl font-label-md text-label-md hover:bg-secondary-container transition-all font-bold shadow-xs active:scale-95 shrink-0">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Add Material Row
                </button>
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
                                    <select wire:model.live="materialConsumptions.{{ $index }}.inventory_batch_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all truncate">
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
                                        <input type="number" step="0.01" min="0.01" max="{{ $selectedBatch ? $selectedBatch->balance_quantity : 99999 }}" wire:model.live="materialConsumptions.{{ $index }}.quantity_consumed" placeholder="0.00" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl pl-3.5 pr-14 py-2.5 text-sm font-black text-secondary text-center focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all">
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-outline font-bold uppercase truncate max-w-[40px]">
                                            {{ $selectedBatch ? $selectedBatch->unit : 'Units' }}
                                        </span>
                                    </div>
                                    @error("materialConsumptions.{$index}.quantity_consumed") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>

                                @if(count($materialConsumptions) > 1)
                                    <button type="button" wire:click="removeMaterialRow({{ $index }})" class="p-2.5 text-error hover:bg-error-container/30 rounded-xl transition-colors shrink-0 mt-5" title="Remove Row">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40 mt-6">
                    <button type="submit" class="bg-secondary text-on-secondary px-8 py-3 rounded-xl font-label-md text-label-md font-bold hover:bg-secondary-container shadow-md transition-all active:scale-95 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">inventory</span>
                        Record Raw Material Consumption for {{ $selectedTask ? $selectedTask->name : 'Stage' }}
                    </button>
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

        {{-- ═══ SUBSIDIARY BOM CONSUMPTION PANEL (CAT-SUB tasks only) ═══ --}}
        @if($isTaskSubsidiary && count($subsidiaryConsumptions) > 0)
            <div class="bg-surface-container-lowest border border-secondary/30 rounded-2xl p-5 sm:p-6 shadow-xs mb-8">
                <div class="flex items-center gap-3 mb-5 pb-4 border-b border-outline-variant/40">
                    <div class="w-10 h-10 rounded-xl bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold shrink-0">
                        <span class="material-symbols-outlined text-[22px]">inventory_2</span>
                    </div>
                    <div>
                        <h3 class="font-headline-sm text-headline-sm text-secondary font-bold">Subsidiary Material BOM Consumption</h3>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">Pre-filled from product BOM. Select batch and confirm actual quantity consumed. <strong>No wastage is applied.</strong></p>
                    </div>
                </div>

                @error('subsidiaryConsumptions')
                    <div class="bg-error-container/40 border border-error/30 text-error p-4 rounded-xl mb-4 flex items-center gap-3">
                        <span class="material-symbols-outlined text-error shrink-0">error</span>
                        <p class="font-semibold text-sm">{{ $message }}</p>
                    </div>
                @enderror

                <form wire:submit.prevent="saveSubsidiaryConsumption" class="space-y-3">
                    {{-- Column headers --}}
                    <div class="grid grid-cols-12 gap-3 px-1 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">
                        <div class="col-span-3">Material</div>
                        <div class="col-span-2 text-center">Unit</div>
                        <div class="col-span-2 text-center">Expected Qty</div>
                        <div class="col-span-3">Inventory Batch</div>
                        <div class="col-span-2 text-center">Actual Consumed</div>
                    </div>

                    @foreach($subsidiaryConsumptions as $idx => $row)
                        <div class="grid grid-cols-12 gap-3 items-center p-3.5 bg-surface rounded-xl border border-outline-variant/50 hover:border-secondary/40 transition-colors">
                            {{-- Material Name --}}
                            <div class="col-span-3">
                                <p class="font-bold text-sm truncate">{{ $row['bom_material_name'] }}</p>
                            </div>

                            {{-- Auto-derived unit badge --}}
                            <div class="col-span-2 flex justify-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-secondary-container/60 text-on-secondary-container font-bold text-xs border border-secondary/20">
                                    {{ $row['unit'] }}
                                </span>
                            </div>

                            {{-- Expected quantity (read-only reference) --}}
                            <div class="col-span-2 text-center">
                                <span class="font-black text-sm text-primary">{{ number_format($row['expected_quantity'], 4) }}</span>
                            </div>

                            {{-- Batch selector (only CAT-SUB batches for this material) --}}
                            <div class="col-span-3">
                                <select
                                    wire:model.live="subsidiaryConsumptions.{{ $idx }}.inventory_batch_id"
                                    class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-semibold text-on-surface focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all"
                                >
                                    <option value="">-- Select Batch --</option>
                                    @foreach($availableBatches->where('raw_material_id', $row['bom_raw_material_id']) as $batch)
                                        <option value="{{ $batch->id }}">
                                            {{ $batch->batch_number }} — {{ number_format($batch->balance_quantity, 2) }} {{ $batch->unit }}
                                        </option>
                                    @endforeach
                                </select>
                                @error("subsidiaryConsumptions.{$idx}.inventory_batch_id")
                                    <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Actual consumed --}}
                            <div class="col-span-2">
                                <input
                                    type="number"
                                    step="0.0001"
                                    min="0.0001"
                                    wire:model.live="subsidiaryConsumptions.{{ $idx }}.actual_consumed"
                                    class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-black text-secondary text-center focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all"
                                />
                                @error("subsidiaryConsumptions.{$idx}.actual_consumed")
                                    <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    @endforeach

                    <div class="flex justify-end pt-4 border-t border-outline-variant/40 mt-4">
                        <button type="submit" class="bg-secondary text-on-secondary px-8 py-3 rounded-xl font-bold text-sm hover:bg-secondary-container shadow-md transition-all active:scale-95 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">inventory</span>
                            Record Subsidiary Consumption (No Wastage)
                        </button>
            </div>
        @endif
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

        @if(optional($selectedTask)->is_labor_required)
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
                            Assign authorized workers via <span class="font-bold text-primary">Individual Split</span> or <span class="font-bold text-secondary">Bulk 100% Allocation</span> for stage <span class="font-bold text-primary">{{ $selectedTask ? $selectedTask->name : '' }}</span>.
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

                    <button type="button" wire:click="addLaborRow" class="flex items-center gap-2 bg-primary text-on-primary px-4 py-2.5 rounded-xl font-label-md text-label-md hover:bg-primary-container transition-all font-bold shadow-xs active:scale-95 ml-2">
                        <span class="material-symbols-outlined text-[18px]">person_add</span>
                        Add Worker Row
                    </button>
                </div>
            </div>

            <!-- BULK ALLOCATION CONTROL BAR -->
            @php
                $prodId = $job->manufacturing_product_id ?? '';
            @endphp
            <div class="bg-secondary-container/20 border border-secondary/30 rounded-xl p-4 mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="w-9 h-9 rounded-xl bg-secondary text-on-secondary flex items-center justify-center font-bold shrink-0">
                        <span class="material-symbols-outlined text-[20px]">bolt</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-xs text-primary uppercase tracking-wider">Bulk 100% Worker Allocation</h4>
                        <p class="text-[11px] text-on-surface-variant font-medium">Assign full stage pending quantity ({{ number_format($stagePending) }} Pcs) to a single laborer in one action.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <select wire:model.live="bulkLaborSelections.{{ $prodId }}" class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2 text-xs font-semibold text-on-surface focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all shrink-0 min-w-[200px]">
                        <option value="">-- Choose Worker for Bulk Assign --</option>
                        @foreach($authorizedLabors as $labor)
                            <option value="{{ $labor->id }}">
                                {{ $labor->name }} ({{ $labor->code }})
                            </option>
                        @endforeach
                    </select>

                    <button type="button" wire:click="bulkAllocate('{{ $prodId }}')" @if($stagePending <= 0) disabled @endif class="bg-secondary text-on-secondary px-5 py-2 rounded-xl text-xs font-bold hover:bg-secondary-container transition-all shadow-xs active:scale-95 shrink-0 flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="material-symbols-outlined text-[16px]">bolt</span>
                        Bulk Assign 100%
                    </button>
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
                                    <select wire:model.live="laborAllocations.{{ $index }}.labor_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all truncate">
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
                                <select wire:model.live="laborAllocations.{{ $index }}.manufacturing_product_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all truncate">
                                    @foreach($allManufacturingProducts as $prod)
                                        <option value="{{ $prod->id }}">
                                            {{ $prod->name }} ({{ $prod->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-span-12 sm:col-span-6 lg:col-span-3 flex items-center justify-between gap-3">
                                <div class="flex-1">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Processed Qty *</label>
                                    <div class="relative">
                                        <input type="number" min="1" max="{{ $stagePending }}" wire:model.live="laborAllocations.{{ $index }}.quantity" placeholder="0" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl pl-3.5 pr-12 py-2.5 text-sm font-black text-primary text-center focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[11px] text-outline font-bold uppercase">Pcs</span>
                                    </div>
                                    @error("laborAllocations.{$index}.quantity") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>

                                @if(count($laborAllocations) > 1)
                                    <button type="button" wire:click="removeLaborRow({{ $index }})" class="p-2.5 text-error hover:bg-error-container/30 rounded-xl transition-colors shrink-0 mt-5" title="Remove Row">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40 mt-6">
                    <button type="submit" @if($stagePending <= 0) disabled @endif class="bg-primary text-on-primary px-8 py-3.5 rounded-xl font-label-md text-label-md font-bold hover:bg-primary-container shadow-md transition-all active:scale-95 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="material-symbols-outlined text-[20px]">task_alt</span>
                        Record Worker Output & Wages for {{ $selectedTask ? $selectedTask->name : 'Stage' }}
                    </button>
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
        @endif

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

                <button type="button" wire:click="addOutputRow" class="flex items-center gap-2 bg-primary text-on-primary px-4 py-2.5 rounded-xl font-label-md text-label-md hover:bg-primary-container transition-all font-bold shadow-xs active:scale-95 shrink-0">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Add Product Output Row
                </button>
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
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Select Manufacturing Product Output *</label>
                                    <select wire:model.live="productionOutputs.{{ $index }}.manufacturing_product_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all truncate">
                                        <option value="">-- Select Manufacturing Product --</option>
                                        @foreach($allManufacturingProducts as $prod)
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
                                        <input type="number" min="1" wire:model.live="productionOutputs.{{ $index }}.quantity_produced" placeholder="0" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl pl-3.5 pr-14 py-2.5 text-sm font-black text-primary text-center focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                        <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-outline font-bold uppercase">Pcs</span>
                                    </div>
                                    @error("productionOutputs.{$index}.quantity_produced") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>

                                @if(count($productionOutputs) > 1)
                                    <button type="button" wire:click="removeOutputRow({{ $index }})" class="p-2.5 text-error hover:bg-error-container/30 rounded-xl transition-colors shrink-0 mt-5" title="Remove Row">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40 mt-6">
                    <button type="submit" class="bg-primary text-on-primary px-8 py-3 rounded-xl font-label-md text-label-md font-bold hover:bg-primary-container shadow-md transition-all active:scale-95 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">precision_manufacturing</span>
                        Record Product Output for {{ $selectedTask ? $selectedTask->name : 'Stage' }}
                    </button>
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

                <button type="button" wire:click="addWastageRow" class="flex items-center gap-2 bg-error text-on-error px-4 py-2.5 rounded-xl font-label-md text-label-md hover:bg-error-container hover:text-error transition-all font-bold shadow-xs active:scale-95 shrink-0">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Add Wastage Row
                </button>
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
                                    <select wire:model.live="wastageRecords.{{ $index }}.manufacturing_product_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-error/20 focus:border-error transition-all truncate">
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
                                    <input type="number" step="0.01" min="0.01" wire:model.live="wastageRecords.{{ $index }}.quantity_wasted" placeholder="0.00" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl pl-3.5 pr-14 py-2.5 text-sm font-black text-error text-center focus:ring-2 focus:ring-error/20 focus:border-error transition-all">
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[10px] text-error font-bold uppercase">Units</span>
                                </div>
                                @error("wastageRecords.{$index}.quantity_wasted") 
                                    <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                @enderror
                            </div>

                            <div class="col-span-12 sm:col-span-6 lg:col-span-4 flex items-center justify-between gap-3">
                                <div class="flex-1">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Wastage Reason (Optional)</label>
                                    <input type="text" wire:model.live="wastageRecords.{{ $index }}.reason" placeholder="e.g. Scraps, Ruined Stitching" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-error/20 focus:border-error transition-all">
                                </div>

                                @if(count($wastageRecords) > 1)
                                    <button type="button" wire:click="removeWastageRow({{ $index }})" class="p-2.5 text-error hover:bg-error-container/30 rounded-xl transition-colors shrink-0 mt-5" title="Remove Row">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-error/20 mt-6">
                    <button type="submit" class="bg-error text-on-error px-8 py-3 rounded-xl font-label-md text-label-md font-bold hover:bg-error-container hover:text-error shadow-md transition-all active:scale-95 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">report_problem</span>
                        Record Wastage & Production Loss Log
                    </button>
                </div>
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

        <!-- SECTION 5: ALTERATION MANAGEMENT & CHILD BATCH GENERATION -->
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 sm:p-6 shadow-xs mb-8">
            <div class="bg-surface p-4 rounded-xl border border-outline-variant/60 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-700 flex items-center justify-center font-bold shrink-0">
                        <span class="material-symbols-outlined">alt_route</span>
                    </div>
                    <div>
                        <h3 class="font-headline-sm text-headline-sm text-primary font-bold">
                            Alteration Management & Child Batch Generation
                        </h3>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                            Convert partially processed items into a new target product SKU and automatically generate a linked <span class="font-bold text-amber-800 underline">Child Production Batch</span>.
                        </p>
                    </div>
                </div>

                <button type="button" wire:click="addAlterationRow" class="flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-xl font-label-md text-label-md hover:bg-amber-700 transition-all font-bold shadow-xs active:scale-95 shrink-0">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Add Alteration Row
                </button>
            </div>

            @if($errors->has('alterationRecords'))
                <div class="bg-error-container/40 border border-error/30 text-error p-4 rounded-xl mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined text-error shrink-0">error</span>
                    <p class="font-body-md text-body-md font-semibold">{{ $errors->first('alterationRecords') }}</p>
                </div>
            @endif

            <form wire:submit.prevent="saveJobAlteration" class="space-y-4">
                <div class="space-y-3">
                    @foreach($alterationRecords as $index => $alt)
                        <div class="grid grid-cols-12 gap-3 sm:gap-4 items-center p-4 sm:p-5 bg-surface rounded-xl border border-outline-variant/60 shadow-xs hover:border-amber-500/40 transition-all">
                            <div class="col-span-12 lg:col-span-5 grid grid-cols-12 gap-2">
                                <div class="col-span-8">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Source Product *</label>
                                    <select wire:model.live="alterationRecords.{{ $index }}.source_product_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-semibold text-on-surface focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all truncate">
                                        <option value="">-- Source Product --</option>
                                        @foreach($allManufacturingProducts as $prod)
                                            <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->code }})</option>
                                        @endforeach
                                    </select>
                                    @error("alterationRecords.{$index}.source_product_id") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>
                                <div class="col-span-4">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Source Qty *</label>
                                    <input type="number" min="1" wire:model.live="alterationRecords.{{ $index }}.source_quantity" placeholder="0" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-black text-amber-800 text-center focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all">
                                    @error("alterationRecords.{$index}.source_quantity") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>
                            </div>

                            <div class="hidden lg:flex col-span-1 justify-center items-center">
                                <span class="material-symbols-outlined text-amber-600 text-[24px]">arrow_forward</span>
                            </div>

                            <div class="col-span-12 lg:col-span-6 grid grid-cols-12 gap-2 items-center">
                                <div class="col-span-7">
                                    <label class="block text-[11px] font-bold text-secondary uppercase tracking-wider mb-1">Target Product (New SKU) *</label>
                                    <select wire:model.live="alterationRecords.{{ $index }}.target_product_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-semibold text-on-surface focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all truncate">
                                        <option value="">-- Target Product SKU --</option>
                                        @foreach($allManufacturingProducts as $prod)
                                            <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->code }})</option>
                                        @endforeach
                                    </select>
                                    @error("alterationRecords.{$index}.target_product_id") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>
                                <div class="col-span-4">
                                    <label class="block text-[11px] font-bold text-secondary uppercase tracking-wider mb-1">Target Qty *</label>
                                    <input type="number" min="1" wire:model.live="alterationRecords.{{ $index }}.target_quantity" placeholder="0" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-black text-secondary text-center focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all">
                                    @error("alterationRecords.{$index}.target_quantity") 
                                        <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> 
                                    @enderror
                                </div>
                                <div class="col-span-1 text-right">
                                    @if(count($alterationRecords) > 1)
                                        <button type="button" wire:click="removeAlterationRow({{ $index }})" class="p-2 text-error hover:bg-error-container/30 rounded-xl transition-colors shrink-0 mt-4" title="Remove Row">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40 mt-6">
                    <button type="submit" class="bg-amber-600 text-white px-8 py-3 rounded-xl font-label-md text-label-md font-bold hover:bg-amber-700 shadow-md transition-all active:scale-95 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">alt_route</span>
                        Record Alteration & Generate Child Production Batch
                    </button>
                </div>
            </form>

            <!-- 5. RECORDED ALTERATIONS & GENERATED CHILD BATCHES LOG -->
            <div class="mt-8 pt-6 border-t border-outline-variant/40">
                <h4 class="font-headline-sm text-headline-sm text-primary font-bold mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-600">alt_route</span>
                    Alterations & Linked Child Production Batches Log
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
                            @forelse($jobAlterations as $alt)
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
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-on-surface-variant text-sm">No alterations recorded for this job.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
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

