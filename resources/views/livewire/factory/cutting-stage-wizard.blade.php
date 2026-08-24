<div>
    <!-- Header & Breadcrumbs -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <nav class="flex items-center gap-2 text-on-surface-variant mb-2">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="font-label-sm text-xs text-on-surface-variant hover:text-primary transition-all">Dashboard</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a href="{{ route('factory.tasks.index') }}" wire:navigate class="font-label-sm text-xs text-on-surface-variant hover:text-primary transition-all">Factory Floor</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="font-label-sm text-xs text-primary font-bold">Mandatory Cutting Stage</span>
            </nav>
            <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Mandatory Cutting Stage Wizard</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">First mandatory step before initiating production jobs: cut fabric bales & rolls, record cutting labor, and generate target multi-product jobs.</p>
        </div>
    </div>

    <!-- Wizard Stepper Navigation -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-4 mb-8 shadow-xs">
        <div class="flex items-center justify-between max-w-3xl mx-auto">
            <!-- Step 1 -->
            <button
                type="button"
                wire:click="goToStep(1)"
                class="flex items-center gap-3 cursor-pointer group"
            >
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm transition-all
                    {{ $currentStep === 1 ? 'bg-primary text-on-primary shadow-md ring-4 ring-primary/20' : ($currentStep > 1 ? 'bg-secondary text-on-secondary' : 'bg-surface-container-high text-on-surface-variant') }}"
                >
                    @if($currentStep > 1)
                        <span class="material-symbols-outlined text-[20px]">check</span>
                    @else
                        1
                    @endif
                </div>
                <div class="text-left hidden sm:block">
                    <p class="text-xs font-extrabold {{ $currentStep === 1 ? 'text-primary' : 'text-on-surface-variant' }}">Step 1</p>
                    <p class="text-xs font-semibold text-on-surface">Fabric Bales & Rolls</p>
                </div>
            </button>

            <div class="flex-1 h-0.5 bg-outline-variant/40 mx-4"></div>

            <!-- Step 2 -->
            <button
                type="button"
                wire:click="goToStep(2)"
                class="flex items-center gap-3 cursor-pointer group"
            >
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm transition-all
                    {{ $currentStep === 2 ? 'bg-primary text-on-primary shadow-md ring-4 ring-primary/20' : ($currentStep > 2 ? 'bg-secondary text-on-secondary' : 'bg-surface-container-high text-on-surface-variant') }}"
                >
                    @if($currentStep > 2)
                        <span class="material-symbols-outlined text-[20px]">check</span>
                    @else
                        2
                    @endif
                </div>
                <div class="text-left hidden sm:block">
                    <p class="text-xs font-extrabold {{ $currentStep === 2 ? 'text-primary' : 'text-on-surface-variant' }}">Step 2</p>
                    <p class="text-xs font-semibold text-on-surface">Cutting Labor & Rates</p>
                </div>
            </button>

            <div class="flex-1 h-0.5 bg-outline-variant/40 mx-4"></div>

            <!-- Step 3 -->
            <button
                type="button"
                wire:click="goToStep(3)"
                class="flex items-center gap-3 cursor-pointer group"
            >
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm transition-all
                    {{ $currentStep === 3 ? 'bg-primary text-on-primary shadow-md ring-4 ring-primary/20' : 'bg-surface-container-high text-on-surface-variant' }}"
                >
                    3
                </div>
                <div class="text-left hidden sm:block">
                    <p class="text-xs font-extrabold {{ $currentStep === 3 ? 'text-primary' : 'text-on-surface-variant' }}">Step 3</p>
                    <p class="text-xs font-semibold text-on-surface">Multi-Job Creation</p>
                </div>
            </button>
        </div>
    </div>

    <!-- Error Summary Header -->
    @if($errors->has('step1_rolls'))
        <div class="bg-error-container/20 border border-error/40 text-error rounded-xl p-4 mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined text-[20px]">warning</span>
            <p class="text-xs font-bold">{{ $errors->first('step1_rolls') }}</p>
        </div>
    @endif

    <!-- STEP 1: Fabric Bales & Rolls Selection -->
    @if($currentStep === 1)
        <div class="space-y-6">
            <div class="flex justify-between items-center bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-5 shadow-xs">
                <div>
                    <h3 class="font-headline-sm text-base font-extrabold text-primary">Select Fabric Materials, Bales & Rolls</h3>
                    <p class="text-xs text-on-surface-variant mt-0.5">Pick fabric bales to cut from. Unopened bales must define roll counts and lengths before cutting.</p>
                </div>
                <button type="button" wire:click="addFabricRow" class="inline-flex items-center gap-2 bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 px-4 py-2 rounded-xl text-xs font-bold transition-all">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    Add Another Fabric
                </button>
            </div>

            @foreach($selectedFabrics as $fIdx => $fabRow)
                <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs relative">
                    <div class="flex justify-between items-center mb-5 pb-3 border-b border-outline-variant/40">
                        <span class="font-mono text-xs font-extrabold text-primary bg-primary/10 px-3 py-1 rounded-lg">
                            Fabric Item #{{ $fIdx + 1 }}
                        </span>
                        @if(count($selectedFabrics) > 1)
                            <button type="button" wire:click="removeFabricRow({{ $fIdx }})" class="text-error hover:bg-error-container/20 px-3 py-1 rounded-lg text-xs font-bold transition-colors">
                                Remove Item
                            </button>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <!-- Select Fabric Raw Material -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Fabric Material <span class="text-error">*</span></label>
                            <select
                                wire:model.live="selectedFabrics.{{ $fIdx }}.raw_material_id"
                                class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-body-md focus:border-primary focus:outline-none"
                            >
                                <option value="">— Select Fabric Material —</option>
                                @foreach($fabricMaterials as $fMat)
                                    <option value="{{ $fMat->id }}">{{ $fMat->name }} ({{ $fMat->code }})</option>
                                @endforeach
                            </select>
                            @error("selectedFabrics.{$fIdx}.raw_material_id")
                                <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Select Inventory Batch -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Inventory Batch <span class="text-error">*</span></label>
                            @php
                                $batches = !empty($fabRow['raw_material_id']) 
                                    ? \App\Models\InventoryBatch::where('raw_material_id', $fabRow['raw_material_id'])->where('balance_quantity', '>', 0)->orderBy('id', 'desc')->get()
                                    : collect();
                            @endphp
                            <select
                                wire:model.live="selectedFabrics.{{ $fIdx }}.inventory_batch_id"
                                class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-body-md focus:border-primary focus:outline-none"
                                {{ empty($fabRow['raw_material_id']) ? 'disabled' : '' }}
                            >
                                <option value="">— Select Batch —</option>
                                @foreach($batches as $b)
                                    <option value="{{ $b->id }}">{{ $b->batch_number }} (Bal: {{ $b->balance_quantity }} {{ $b->unit }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Select Fabric Bale -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Fabric Bale <span class="text-error">*</span></label>
                            @php
                                $bales = collect();
                                if (!empty($fabRow['inventory_batch_id'])) {
                                    $bObj = \App\Models\InventoryBatch::find($fabRow['inventory_batch_id']);
                                    if ($bObj) {
                                        if ($bObj->bales()->count() === 0 && (float)$bObj->balance_quantity > 0) {
                                            $bObj->createBales(1, (float)$bObj->balance_quantity);
                                        }
                                        $bales = \App\Models\InventoryBale::where('inventory_batch_id', $bObj->id)->where('status', '!=', 'depleted')->get();
                                    }
                                }
                            @endphp
                            <select
                                wire:model.live="selectedFabrics.{{ $fIdx }}.inventory_bale_id"
                                class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-body-md focus:border-primary focus:outline-none"
                                {{ empty($fabRow['inventory_batch_id']) ? 'disabled' : '' }}
                            >
                                <option value="">— Select Bale —</option>
                                @foreach($bales as $bale)
                                    <option value="{{ $bale->id }}">
                                        {{ $bale->bale_number }} [{{ strtoupper($bale->status) }}] — Bal: {{ $bale->current_balance_length }}m (Decl: {{ $bale->declared_length }}m)
                                    </option>
                                @endforeach
                            </select>
                            @error("selectedFabrics.{$fIdx}.inventory_bale_id")
                                <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Bale Details & Rolls Selection Section -->
                    @if(!empty($fabRow['inventory_bale_id']))
                        @php
                            $selectedBale = \App\Models\InventoryBale::with('activeRolls')->find($fabRow['inventory_bale_id']);
                        @endphp
                        @if($selectedBale)
                            <div class="bg-surface-container-low/40 rounded-xl p-5 border border-outline-variant/40">
                                <!-- Case 1: Unopened Bale -->
                                @if($selectedBale->status === 'unopened')
                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-amber-500/10 border border-amber-500/30 rounded-xl p-4">
                                        <div class="flex items-center gap-3">
                                            <span class="material-symbols-outlined text-amber-600 text-[24px]">lock</span>
                                            <div>
                                                <h4 class="text-xs font-bold text-amber-900 uppercase tracking-wider">Unopened Bale Detected</h4>
                                                <p class="text-xs text-amber-800/80 mt-0.5">
                                                    {{ $selectedBale->bale_number }} has not been opened yet. Recorded Purchase Length: <strong>{{ $selectedBale->declared_length }} Meters</strong>.
                                                </p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="triggerOpenBaleModal({{ $selectedBale->id }})"
                                            class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-sm transition-all active:scale-95 whitespace-nowrap cursor-pointer"
                                        >
                                            <span class="material-symbols-outlined text-[16px] align-middle mr-1">lock_open</span>
                                            Open Bale & Define Rolls
                                        </button>
                                    </div>

                                <!-- Case 2: Opened Bale (List Active Rolls) -->
                                @else
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-2 text-secondary font-bold text-xs uppercase tracking-wider">
                                            <span class="material-symbols-outlined text-[18px]">view_week</span>
                                            <span>Active Rolls in {{ $selectedBale->bale_number }} (Bal: {{ $selectedBale->current_balance_length }}m)</span>
                                        </div>
                                        <span class="text-[11px] text-on-surface-variant font-semibold">Select rolls to cut</span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                        @foreach($selectedBale->activeRolls as $roll)
                                            @php
                                                $isSelected = isset($fabRow['selected_rolls'][$roll->id]);
                                                $rollData = $fabRow['selected_rolls'][$roll->id] ?? null;
                                            @endphp
                                            <div class="bg-surface-container-lowest border rounded-xl p-4 transition-all {{ $isSelected ? 'border-primary ring-2 ring-primary/20 shadow-sm' : 'border-outline-variant/60' }}">
                                                <div class="flex justify-between items-center mb-3">
                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                        <input
                                                            type="checkbox"
                                                            wire:click="toggleRollSelection({{ $fIdx }}, {{ $roll->id }})"
                                                            {{ $isSelected ? 'checked' : '' }}
                                                            class="rounded text-primary focus:ring-primary h-4 w-4"
                                                        />
                                                        <span class="font-mono font-extrabold text-xs text-primary">{{ $roll->roll_number }}</span>
                                                    </label>
                                                    <span class="text-[11px] font-bold text-on-surface-variant">
                                                        Bal: <strong>{{ $roll->current_balance_length }}m</strong> / {{ $roll->initial_length }}m
                                                    </span>
                                                </div>

                                                @if($isSelected)
                                                    <div class="space-y-2 pt-2 border-t border-outline-variant/30">
                                                        <div class="flex justify-between items-center">
                                                            <label class="text-[10px] font-bold text-on-surface-variant uppercase">Length to Cut (m)</label>
                                                            <button
                                                                type="button"
                                                                wire:click="setFullRollCut({{ $fIdx }}, {{ $roll->id }})"
                                                                class="text-[10px] font-extrabold text-primary hover:underline cursor-pointer"
                                                            >
                                                                Cut Full Roll ({{ $roll->current_balance_length }}m)
                                                            </button>
                                                        </div>
                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            wire:model.live="selectedFabrics.{{ $fIdx }}.selected_rolls.{{ $roll->id }}.cut_length"
                                                            max="{{ $roll->current_balance_length }}"
                                                            class="w-full bg-surface border border-outline-variant/60 rounded-lg px-3 py-1.5 text-xs font-bold text-right focus:border-primary focus:outline-none"
                                                        />
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach

            <!-- Action Button -->
            <div class="flex justify-end pt-4">
                <button
                    type="button"
                    wire:click="goToStep(2)"
                    class="px-8 py-3.5 bg-primary text-on-primary font-extrabold text-xs rounded-xl shadow-md hover:bg-primary-container transition-all active:scale-95 cursor-pointer flex items-center gap-2"
                >
                    Proceed to Step 2 (Labor & Rates)
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </div>
        </div>
    @endif

    <!-- STEP 2: Cutting Labor & Overhead Rates -->
    @if($currentStep === 2)
        <div class="space-y-6">
            <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-6 shadow-xs space-y-6">
                <div class="flex justify-between items-center border-b border-outline-variant/40 pb-4">
                    <div>
                        <h3 class="font-headline-sm text-base font-extrabold text-primary">Cutting Labor & Task Allocation</h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Assign supervisor and labor workers for the cutting process.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Cutting Supervisor <span class="text-error">*</span></label>
                        <select wire:model="supervisor_id" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-body-md focus:border-primary focus:outline-none">
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Cutting Process Task <span class="text-error">*</span></label>
                        <select wire:model="cutting_task_id" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-body-md focus:border-primary focus:outline-none">
                            @foreach($tasks as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Labor Worker Rows -->
                <div class="pt-4">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Labor Workers Allocation</label>
                        <button type="button" wire:click="addLaborRow" class="text-xs font-bold text-primary hover:underline flex items-center gap-1 cursor-pointer">
                            <span class="material-symbols-outlined text-[16px]">add</span> Add Labor Worker
                        </button>
                    </div>

                    @foreach($laborAllocations as $lIdx => $lRow)
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-surface-container-low/30 rounded-xl p-4 mb-3 border border-outline-variant/30 items-center">
                            <div>
                                <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Worker</label>
                                <select wire:model="laborAllocations.{{ $lIdx }}.labor_id" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-body-md">
                                    <option value="">— Select Worker —</option>
                                    @foreach($labors as $l)
                                        <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->worker_code }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Cutting Rate (₹)</label>
                                <input type="number" step="0.01" wire:model.live="laborAllocations.{{ $lIdx }}.rate" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-right" />
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Hours / Pieces</label>
                                <input type="number" step="0.01" wire:model.live="laborAllocations.{{ $lIdx }}.hours_or_pcs" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-right" />
                            </div>

                            <div class="flex items-center justify-between pt-4 md:pt-0">
                                <div>
                                    <p class="text-[10px] text-on-surface-variant font-bold uppercase">Wage Total</p>
                                    <p class="text-sm font-extrabold text-primary">₹{{ number_format(floatval($lRow['rate'] ?? 0) * floatval($lRow['hours_or_pcs'] ?? 0), 2) }}</p>
                                </div>
                                <button type="button" wire:click="removeLaborRow({{ $lIdx }})" class="text-error hover:bg-error-container/20 p-2 rounded-lg transition-colors cursor-pointer">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between pt-4">
                <button type="button" wire:click="goToStep(1)" class="px-6 py-3 border border-outline-variant/60 rounded-xl text-xs font-bold text-on-surface-variant hover:bg-surface-container-high transition-colors">
                    Back to Step 1
                </button>
                <button type="button" wire:click="goToStep(3)" class="px-8 py-3.5 bg-primary text-on-primary font-extrabold text-xs rounded-xl shadow-md hover:bg-primary-container transition-all cursor-pointer flex items-center gap-2">
                    Proceed to Step 3 (Target Products)
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </div>
        </div>
    @endif

    <!-- STEP 3: Target Output Manufacturing Products & Multi-Job Generator -->
    @if($currentStep === 3)
        <div class="space-y-6">
            <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-6 shadow-xs space-y-6">
                <div class="flex justify-between items-center border-b border-outline-variant/40 pb-4">
                    <div>
                        <h3 class="font-headline-sm text-base font-extrabold text-primary">Target Output Manufacturing Products & Multi-Job Generator</h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Specify products to produce from this cutting run. Separate production jobs will be created for each product.</p>
                    </div>
                    <button type="button" wire:click="addTargetProductRow" class="inline-flex items-center gap-2 bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">add</span> Add Target Product
                    </button>
                </div>

                @php
                    $cBreakdown = $this->fabricCuttingBreakdown;
                @endphp
                @if(!empty($cBreakdown))
                    <div class="bg-surface-container-low/60 border rounded-2xl p-5 shadow-xs border-outline-variant/60">
                        <div class="flex items-center justify-between mb-3 border-b border-outline-variant/30 pb-2">
                            <span class="text-xs font-bold text-primary uppercase tracking-wider flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[18px]">aspect_ratio</span>
                                Fabric Area & Auto-Calculated Wastage Summary
                            </span>
                            <span class="text-xs font-extrabold px-2.5 py-0.5 rounded-full {{ $cBreakdown['is_over_capacity'] ? 'bg-error/10 text-error' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $cBreakdown['usage_percentage'] }}% Area Utilized
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs font-semibold">
                            <div class="bg-surface-container-lowest p-3 rounded-xl border border-outline-variant/30">
                                <span class="text-on-surface-variant block text-[10px] uppercase font-bold">Total Cut Area</span>
                                <span class="text-base font-extrabold text-primary">{{ $cBreakdown['cut_area_base'] }} m²</span>
                            </div>
                            <div class="bg-surface-container-lowest p-3 rounded-xl border border-outline-variant/30">
                                <span class="text-on-surface-variant block text-[10px] uppercase font-bold">Used Area by Products</span>
                                <span class="text-base font-extrabold {{ $cBreakdown['is_over_capacity'] ? 'text-error' : 'text-emerald-700' }}">{{ $cBreakdown['used_area_base'] }} m²</span>
                            </div>
                            <div class="bg-surface-container-lowest p-3 rounded-xl border border-outline-variant/30">
                                <span class="text-on-surface-variant block text-[10px] uppercase font-bold">Auto-Calculated Wastage</span>
                                <span class="text-base font-extrabold text-error">{{ $cBreakdown['wastage_length'] }} {{ $cBreakdown['unit_name'] ?? 'Meters' }}</span>
                                <span class="text-[10px] text-on-surface-variant block">({{ $cBreakdown['remaining_area_base'] }} m² remaining)</span>
                            </div>
                        </div>

                        @if($cBreakdown['is_over_capacity'])
                            <div class="mt-3 bg-error-container/20 border border-error/40 text-error rounded-xl p-3 text-xs font-bold flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">error</span>
                                <span>Required product fabric area exceeds cut fabric area by {{ $cBreakdown['over_capacity_diff_base'] }} m²! Please reduce target quantities.</span>
                            </div>
                        @endif
                    </div>
                @endif

                @foreach($targetProducts as $tIdx => $tpRow)
                    @php
                        $mpId = $tpRow['manufacturing_product_id'] ?? null;
                        $maxPcs = $mpId && isset($cBreakdown['max_quantities'][$mpId]) ? $cBreakdown['max_quantities'][$mpId] : null;
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-surface-container-low/30 rounded-2xl p-5 border border-outline-variant/40 items-center">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Target Manufacturing Product <span class="text-error">*</span></label>
                            <select wire:model.live="targetProducts.{{ $tIdx }}.manufacturing_product_id" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-body-md focus:border-primary focus:outline-none">
                                <option value="">— Select Target Product —</option>
                                @foreach($manufacturingProducts as $mProd)
                                    <option value="{{ $mProd->id }}">{{ $mProd->name }} ({{ $mProd->code }})</option>
                                @endforeach
                            </select>
                            @error("targetProducts.{$tIdx}.manufacturing_product_id")
                                <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Planned Qty <span class="text-error">*</span></label>
                            <input type="number" min="1" @if($maxPcs !== null && $maxPcs > 0) max="{{ $maxPcs }}" @endif wire:model.live="targetProducts.{{ $tIdx }}.planned_quantity" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-bold text-right" />
                            @if($maxPcs !== null)
                                <span class="block text-[10px] font-bold text-right text-on-surface-variant/80 mt-1">Max allowed: {{ $maxPcs }} Pcs</span>
                            @endif
                            @error("targetProducts.{$tIdx}.planned_quantity")
                                <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between pt-4 md:pt-0">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Priority</label>
                                <select wire:model="targetProducts.{{ $tIdx }}.priority" class="bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold">
                                    <option value="Normal">Normal</option>
                                    <option value="High">High</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
                            @if(count($targetProducts) > 1)
                                <button type="button" wire:click="removeTargetProductRow({{ $tIdx }})" class="text-error hover:bg-error-container/20 p-2 rounded-lg transition-colors cursor-pointer">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Action Buttons & Final Submission -->
            <div class="flex justify-between items-center bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-md">
                <button type="button" wire:click="goToStep(2)" class="px-6 py-3 border border-outline-variant/60 rounded-xl text-xs font-bold text-on-surface-variant hover:bg-surface-container-high transition-colors">
                    Back to Step 2
                </button>

                <button
                    type="button"
                    wire:click="submitCuttingStage"
                    class="px-8 py-4 bg-primary hover:bg-primary-container text-on-primary font-black text-sm rounded-xl shadow-lg transition-all active:scale-95 cursor-pointer flex items-center gap-3"
                >
                    <span class="material-symbols-outlined text-[22px]">content_cut</span>
                    Complete Cutting Stage & Launch Production Jobs
                </button>
            </div>
        </div>
    @endif

    <!-- MODAL: Open Unopened Bale & Record Roll Lengths -->
    @if($showOpenBaleModal)
        @php
            $baleToOpen = $activeBaleIdToOpen ? \App\Models\InventoryBale::find($activeBaleIdToOpen) : null;
        @endphp
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-outline-variant/40 flex justify-between items-center bg-amber-500/10">
                    <div class="flex items-center gap-2 text-amber-900 font-extrabold text-sm">
                        <span class="material-symbols-outlined text-[20px]">lock_open</span>
                        <span>Open Unopened Bale & Define Rolls</span>
                    </div>
                    <button type="button" wire:click="$set('showOpenBaleModal', false)" class="text-on-surface-variant hover:bg-surface-container-high p-1 rounded-lg">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Number of Rolls in Bale <span class="text-error">*</span></label>
                        <input
                            type="number"
                            min="1"
                            max="50"
                            wire:model.live="baleRollCount"
                            placeholder="-- Enter number of rolls (e.g. 5) --"
                            class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2.5 text-sm font-bold"
                        />
                        @error('baleRollCount') <p class="text-xs font-bold text-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if(!empty($baleRollCount) && count($baleRollLengths) > 0)
                        <div class="space-y-3 pt-2">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Individual Roll Lengths (Meters) *</label>
                            <div class="grid grid-cols-2 gap-3">
                                @foreach($baleRollLengths as $rIdx => $rLen)
                                    <div>
                                        <label class="text-[10px] font-bold text-on-surface-variant uppercase">Roll {{ $rIdx + 1 }} Length</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            wire:model.live="baleRollLengths.{{ $rIdx }}"
                                            placeholder="0.00"
                                            class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-right"
                                        />
                                        @error("baleRollLengths.{$rIdx}") <p class="text-[10px] font-bold text-error mt-0.5">{{ $message }}</p> @enderror
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
                            <div class="bg-amber-500/10 border border-amber-500/30 text-amber-900 rounded-xl p-4 text-xs font-semibold flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">warning</span>
                                <span>{{ $baleMismatchWarning }}</span>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="px-6 py-4 border-t border-outline-variant/40 flex justify-end gap-3 bg-surface-container-low/30">
                    <button type="button" wire:click="$set('showOpenBaleModal', false)" class="px-5 py-2.5 border border-outline-variant/60 rounded-xl text-xs font-bold text-on-surface-variant">
                        Cancel
                    </button>
                    <button type="button" wire:click="submitOpenedBaleForm" class="px-6 py-2.5 bg-primary text-on-primary font-bold text-xs rounded-xl shadow-sm hover:bg-primary-container transition-all" {{ empty($baleRollCount) ? 'disabled' : '' }}>
                        Save & Open Bale
                    </button>
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
