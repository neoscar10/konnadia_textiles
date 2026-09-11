<div>
    <!-- Header & Breadcrumbs -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <nav class="flex items-center gap-2 text-on-surface-variant mb-2">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="font-label-sm text-xs text-on-surface-variant hover:text-primary transition-all">Dashboard</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a href="{{ route('admin.production.jobs.index') }}" wire:navigate class="font-label-sm text-xs text-on-surface-variant hover:text-primary transition-all">Production Jobs Hub</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="font-label-sm text-xs text-primary font-bold">Shared Cutting Stage</span>
            </nav>
            <div class="flex items-center gap-3">
                <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Shared Fabric Cutting Stage Wizard</h2>
                @if(!empty($batchCode))
                    <span class="px-3 py-1 bg-primary/10 text-primary font-mono font-black rounded-xl text-xs flex items-center gap-1.5 border border-primary/20">
                        <span class="material-symbols-outlined text-[16px]">layers</span>
                        Batch {{ $batchCode }}
                    </span>
                @endif
            </div>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">First mandatory stage: select fabric rolls, allocate products &amp; patterns per roll, record cutting labor rates, and spawn initial production jobs.</p>
        </div>
    </div>

    <!-- Wizard Stepper Navigation (4 Steps) -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-4 mb-8 shadow-xs">
        <div class="flex items-center justify-between max-w-4xl mx-auto">
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
                    <p class="text-xs font-semibold text-on-surface">Fabric &amp; Roll Allocations</p>
                </div>
            </button>

            <div class="flex-1 h-0.5 bg-outline-variant/40 mx-3"></div>

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
                    <p class="text-xs font-semibold text-on-surface">Labor &amp; Rates</p>
                </div>
            </button>

            <div class="flex-1 h-0.5 bg-outline-variant/40 mx-3"></div>

            <!-- Step 3 -->
            <button
                type="button"
                wire:click="goToStep(3)"
                class="flex items-center gap-3 cursor-pointer group"
            >
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm transition-all
                    {{ $currentStep === 3 ? 'bg-primary text-on-primary shadow-md ring-4 ring-primary/20' : ($currentStep > 3 ? 'bg-secondary text-on-secondary' : 'bg-surface-container-high text-on-surface-variant') }}"
                >
                    @if($currentStep > 3)
                        <span class="material-symbols-outlined text-[20px]">check</span>
                    @else
                        3
                    @endif
                </div>
                <div class="text-left hidden sm:block">
                    <p class="text-xs font-extrabold {{ $currentStep === 3 ? 'text-primary' : 'text-on-surface-variant' }}">Step 3</p>
                    <p class="text-xs font-semibold text-on-surface">Output Items</p>
                </div>
            </button>

            <div class="flex-1 h-0.5 bg-outline-variant/40 mx-3"></div>

            <!-- Step 4 -->
            <button
                type="button"
                wire:click="goToStep(4)"
                class="flex items-center gap-3 cursor-pointer group"
            >
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm transition-all
                    {{ $currentStep === 4 ? 'bg-primary text-on-primary shadow-md ring-4 ring-primary/20' : 'bg-surface-container-high text-on-surface-variant' }}"
                >
                    4
                </div>
                <div class="text-left hidden sm:block">
                    <p class="text-xs font-extrabold {{ $currentStep === 4 ? 'text-primary' : 'text-on-surface-variant' }}">Step 4</p>
                    <p class="text-xs font-semibold text-on-surface">Review &amp; Confirm</p>
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

    <!-- STEP 1: Fabric Bales, Rolls & Per-Roll Product Allocations -->
    @if($currentStep === 1)
        <div class="space-y-6">
            <div class="flex justify-between items-center bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-5 shadow-xs">
                <div>
                    <h3 class="font-headline-sm text-base font-extrabold text-primary">Select Fabric Raw Material, Bales &amp; Cut Lengths</h3>
                    <p class="text-xs text-on-surface-variant mt-0.5">Select fabric rolls to cut, enter cut lengths, and allocate the product(s) &amp; pattern(s) to produce from each roll.</p>
                </div>
                <button type="button" wire:click="addFabricRow" class="inline-flex items-center gap-2 bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    Add Another Fabric
                </button>
            </div>

            @foreach($selectedFabrics as $fIdx => $fabRow)
                <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs relative space-y-6">
                    <div class="flex justify-between items-center pb-3 border-b border-outline-variant/40">
                        <span class="font-mono text-xs font-extrabold text-primary bg-primary/10 px-3 py-1 rounded-lg">
                            Fabric Item #{{ $fIdx + 1 }}
                        </span>
                        @if(count($selectedFabrics) > 1)
                            <button type="button" wire:click="removeFabricRow({{ $fIdx }})" class="text-error hover:bg-error-container/20 px-3 py-1 rounded-lg text-xs font-bold transition-colors cursor-pointer">
                                Remove Fabric
                            </button>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Stock Batch <span class="text-error">*</span></label>
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
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Bale <span class="text-error">*</span></label>
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

                    <!-- Bale Details & Active Rolls Section -->
                    @if(!empty($fabRow['inventory_bale_id']))
                        @php
                            $selectedBale = \App\Models\InventoryBale::with('activeRolls')->find($fabRow['inventory_bale_id']);
                        @endphp
                        @if($selectedBale)
                            <div class="bg-surface-container-low/40 rounded-xl p-5 border border-outline-variant/40">
                                @if($selectedBale->status === 'unopened')
                                    <!-- Unopened Bale Banner -->
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
                                            Open Bale &amp; Define Rolls
                                        </button>
                                    </div>

                                @else
                                    <!-- Opened Bale: List Active Rolls -->
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-2 text-secondary font-bold text-xs uppercase tracking-wider">
                                            <span class="material-symbols-outlined text-[18px]">view_week</span>
                                            <span>Active Rolls in {{ $selectedBale->bale_number }} (Bal: {{ $selectedBale->current_balance_length }}m)</span>
                                        </div>
                                        <span class="text-[11px] text-on-surface-variant font-semibold">Select rolls to cut and define products per roll</span>
                                    </div>

                                    <div class="space-y-6 w-full">
                                        @foreach($selectedBale->activeRolls as $roll)
                                            @php
                                                $isSelected = isset($fabRow['selected_rolls'][$roll->id]);
                                                $rollData = $fabRow['selected_rolls'][$roll->id] ?? null;
                                                $rollWidthText = null;
                                                if ($roll->fabricWidth) {
                                                    $rollWidthText = $roll->fabricWidth->name ? ($roll->fabricWidth->name . ' (' . $roll->fabricWidth->value . $roll->fabricWidth->unit . ')') : ($roll->fabricWidth->value . $roll->fabricWidth->unit);
                                                } elseif ($roll->rawMaterial && $roll->rawMaterial->standard_width) {
                                                    $rollWidthText = $roll->rawMaterial->standard_width . ($roll->rawMaterial->width_unit ?? '"');
                                                }
                                            @endphp
                                            <div class="w-full bg-surface-container-lowest border rounded-2xl p-5 transition-all {{ $isSelected ? 'border-primary ring-2 ring-primary/20 shadow-sm' : 'border-outline-variant/60' }}">
                                                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-outline-variant/40">
                                                    <div class="flex flex-wrap items-center gap-3">
                                                        <label class="flex items-center gap-2 cursor-pointer select-none">
                                                            <input
                                                                type="checkbox"
                                                                wire:click="toggleRollSelection({{ $fIdx }}, {{ $roll->id }})"
                                                                {{ $isSelected ? 'checked' : '' }}
                                                                class="rounded text-primary focus:ring-primary h-5 w-5"
                                                            />
                                                            <span class="font-mono font-extrabold text-sm text-primary px-2.5 py-1 bg-primary/10 rounded-lg">{{ $roll->roll_number }}</span>
                                                        </label>
                                                        @if($roll->design_number)
                                                            <span class="px-2.5 py-1 bg-surface-container text-on-surface-variant text-xs font-bold rounded-lg border border-outline-variant/60">Design: {{ $roll->design_number }}</span>
                                                        @endif
                                                        @if($rollWidthText)
                                                            <span class="px-2.5 py-1 bg-primary/10 text-primary text-xs font-bold rounded-lg border border-primary/20">Width: {{ $rollWidthText }}</span>
                                                        @endif
                                                    </div>
                                                    <span class="text-xs font-bold text-on-surface-variant">
                                                        Available Stock: <strong class="text-on-surface px-2 py-0.5 bg-surface-container rounded-lg font-mono">{{ $roll->current_balance_length }}m</strong> / {{ $roll->initial_length }}m
                                                    </span>
                                                </div>

                                                @if($isSelected && $rollData)
                                                    <div class="space-y-5 pt-4">
                                                        <!-- Cut Length Control Row -->
                                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-surface-container-low p-3.5 rounded-xl border border-outline-variant/40">
                                                            <div class="flex items-center gap-2 flex-1 max-w-md">
                                                                <label class="text-xs font-extrabold text-on-surface-variant uppercase tracking-wider whitespace-nowrap">Cut Length (m) *</label>
                                                                <input
                                                                    type="number"
                                                                    step="0.01"
                                                                    wire:model.live.debounce.300ms="selectedFabrics.{{ $fIdx }}.selected_rolls.{{ $roll->id }}.cut_length"
                                                                    max="{{ $roll->current_balance_length }}"
                                                                    placeholder="Length in meters..."
                                                                    class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-extrabold text-on-surface focus:border-primary focus:outline-none"
                                                                />
                                                            </div>
                                                            <button
                                                                type="button"
                                                                wire:click="setFullRollCut({{ $fIdx }}, {{ $roll->id }})"
                                                                class="px-4 py-2 bg-primary text-on-primary font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container transition-all flex items-center justify-center gap-1.5 shrink-0 active:scale-95 cursor-pointer"
                                                            >
                                                                <span class="material-symbols-outlined text-[16px]">content_cut</span>
                                                                Cut Full Roll ({{ $roll->current_balance_length }}m)
                                                            </button>
                                                        </div>

                                                        <!-- Per-Roll Products Allocation Repeater -->
                                                        <div class="bg-surface-container-low/60 p-4 rounded-xl border border-outline-variant/60 space-y-3">
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-xs font-black text-primary uppercase tracking-wider flex items-center gap-1.5">
                                                                    <span class="material-symbols-outlined text-[18px]">inventory</span>
                                                                    Manufacturing Products to Cut from {{ $roll->roll_number }}
                                                                </span>
                                                                <button
                                                                    type="button"
                                                                    wire:click="addProductToRoll({{ $fIdx }}, {{ $roll->id }})"
                                                                    class="text-xs font-bold text-primary hover:underline flex items-center gap-1 cursor-pointer"
                                                                >
                                                                    <span class="material-symbols-outlined text-sm">add_circle</span> + Add Product to Roll
                                                                </button>
                                                            </div>

                                                            <div class="space-y-3">
                                                                @foreach($rollData['products'] ?? [] as $pIdx => $pRow)
                                                                    @php
                                                                        $rProdId = $pRow['manufacturing_product_id'] ?? null;
                                                                        $rPatterns = $rProdId ? \App\Models\ManufacturingProductPattern::where('manufacturing_product_id', $rProdId)->get() : collect();
                                                                    @endphp
                                                                    <div wire:key="roll-prod-{{ $roll->id }}-{{ $pIdx }}" class="p-3 bg-surface border border-outline-variant/60 rounded-xl space-y-2">
                                                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                                                                            <div class="sm:col-span-5">
                                                                                <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-wider mb-1">PRODUCT *</label>
                                                                                <select wire:model.live="selectedFabrics.{{ $fIdx }}.selected_rolls.{{ $roll->id }}.products.{{ $pIdx }}.manufacturing_product_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                                                                    @foreach($manufacturingProducts as $mp)
                                                                                        <option value="{{ $mp->id }}">{{ $mp->name }} ({{ $mp->code }})</option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </div>

                                                                            <div class="sm:col-span-4">
                                                                                <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-wider mb-1">PATTERN *</label>
                                                                                <select wire:model.live="selectedFabrics.{{ $fIdx }}.selected_rolls.{{ $roll->id }}.products.{{ $pIdx }}.pattern_id" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                                                                    @forelse($rPatterns as $pat)
                                                                                        <option value="{{ $pat->id }}">{{ $pat->name }}{{ $pat->is_default ? ' (Default)' : '' }}</option>
                                                                                    @empty
                                                                                        <option value="">Standard Pattern</option>
                                                                                    @endforelse
                                                                                </select>
                                                                            </div>

                                                                            <div class="sm:col-span-3">
                                                                                <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-wider mb-1">QUANTITY (PCS) *</label>
                                                                                <div class="flex items-center gap-1.5">
                                                                                    <input type="number" min="1" wire:model.live.debounce.300ms="selectedFabrics.{{ $fIdx }}.selected_rolls.{{ $roll->id }}.products.{{ $pIdx }}.planned_quantity" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                                                                    @if(count($rollData['products'] ?? []) > 1)
                                                                                        <button type="button" wire:click="removeProductFromRoll({{ $fIdx }}, {{ $roll->id }}, {{ $pIdx }})" class="text-error hover:bg-error-container/20 p-1.5 rounded-lg transition-colors cursor-pointer shrink-0">
                                                                                            <span class="material-symbols-outlined text-base">delete</span>
                                                                                        </button>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>

                                                        <!-- Live Area Calculation & Wastage Breakdown for Roll -->
                                                        @php
                                                            $cLenVal = floatval($rollData['cut_length'] ?? 0);
                                                            $rLive = ($cLenVal > 0) ? $this->getRollCutBreakdown($roll->id, $cLenVal, $fabRow['raw_material_id'] ?? null, $rollData['products'] ?? []) : null;
                                                        @endphp
                                                        @if($rLive)
                                                            <div class="p-4 bg-primary/5 border rounded-xl text-xs space-y-3 {{ !empty($rLive['is_over_capacity']) ? 'border-error bg-error-container/10' : 'border-primary/20' }}">
                                                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pb-3 border-b border-primary/10">
                                                                    <div class="space-y-1">
                                                                        <p class="text-[10px] font-extrabold text-primary uppercase tracking-wider">Roll Cut Area</p>
                                                                        <div class="flex items-center gap-2">
                                                                            <span class="material-symbols-outlined text-primary text-lg">aspect_ratio</span>
                                                                            <span class="text-sm font-black text-on-surface">{{ $rLive['cut_area_m2'] }} m²</span>
                                                                        </div>
                                                                        <p class="text-[11px] text-on-surface-variant font-bold">Width: <span class="text-primary">{{ $rLive['roll_width_display'] }}</span></p>
                                                                    </div>

                                                                    <div class="space-y-1">
                                                                        <p class="text-[10px] font-extrabold text-emerald-700 uppercase tracking-wider">Allocated Products Area</p>
                                                                        <div class="flex items-center gap-2">
                                                                            <span class="material-symbols-outlined text-emerald-600 text-lg">fact_check</span>
                                                                            <span class="text-sm font-black {{ !empty($rLive['is_over_capacity']) ? 'text-error' : 'text-emerald-700' }}">{{ $rLive['used_area_m2'] }} m²</span>
                                                                        </div>
                                                                        <p class="text-[11px] text-on-surface-variant font-bold">Utilized: {{ $rLive['usage_percentage'] }}% &middot; Wastage: {{ $rLive['wastage_percentage'] }}%</p>
                                                                    </div>

                                                                    <div class="space-y-1">
                                                                        <p class="text-[10px] font-extrabold text-amber-800 uppercase tracking-wider">Fabric Wastage</p>
                                                                        <div class="flex items-center gap-2">
                                                                            <span class="material-symbols-outlined text-amber-600 text-lg">delete_sweep</span>
                                                                            <span class="text-sm font-black text-amber-900">{{ $rLive['wastage_area_m2'] }} m² ({{ $rLive['wastage_percentage'] }}%)</span>
                                                                        </div>
                                                                        <p class="text-[11px] text-amber-800 font-bold">Wastage Len: {{ $rLive['wastage_length'] }} m &middot; Cost: ₹{{ number_format($rLive['wastage_cost'], 2) }}</p>
                                                                    </div>

                                                                    <div class="space-y-1">
                                                                        <p class="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Total Cut Fabric Cost</p>
                                                                        <span class="text-sm font-black text-primary block">₹{{ number_format($rLive['total_fabric_cut_cost'], 2) }}</span>
                                                                        <span class="text-[10px] text-outline">Rate: {{ $cLenVal > 0 ? '₹' . number_format($rLive['total_fabric_cut_cost'] / $cLenVal, 2) . '/m' : '—' }}</span>
                                                                    </div>
                                                                </div>

                                                                @if(!empty($rLive['is_over_capacity']))
                                                                    <div class="bg-error-container/20 border border-error/40 text-error p-3 rounded-xl text-xs font-extrabold flex items-center gap-2">
                                                                        <span class="material-symbols-outlined text-lg">error</span>
                                                                        <span>Allocated product area ({{ $rLive['used_area_m2'] }} m²) exceeds roll cut area ({{ $rLive['cut_area_m2'] }} m²) by {{ $rLive['over_capacity_diff_m2'] }} m²! Please reduce product target quantities.</span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif
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
                    Proceed to Step 2 (Labor &amp; Rates)
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </div>
        </div>
    @endif

    <!-- STEP 2: Cutting Labor & Rates (per Product / Pattern allocation) -->
    @if($currentStep === 2)
        <div class="space-y-6">
            <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-6 shadow-xs space-y-6">
                <div class="flex justify-between items-center border-b border-outline-variant/40 pb-4">
                    <div>
                        <h3 class="font-headline-sm text-base font-extrabold text-primary">Cutting Labor Worker Assignment &amp; Piece Rates</h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Assign worker(s), specify quantities cut per worker, base rates, and bonus rates for each allocated product / pattern.</p>
                    </div>
                </div>

                <!-- Labor Worker Repeater per Product/Pattern Card -->
                <div class="space-y-6">
                    @foreach($laborAllocations as $pKey => $lGroup)
                        @php
                            $totalCutQty = intval($lGroup['total_cut_quantity'] ?? 0);
                            $workers = $lGroup['workers'] ?? [];
                            $assignedQty = array_sum(array_column($workers, 'quantity'));
                            $isOverAssigned = $assignedQty > $totalCutQty;
                            $isFullyAssigned = $assignedQty === $totalCutQty;
                        @endphp
                        <div wire:key="labor-group-{{ $pKey }}" class="bg-surface-container-low/40 rounded-2xl p-5 border border-outline-variant/60 space-y-4">
                            <!-- Product Header Bar -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-outline-variant/40">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-extrabold text-sm text-primary">{{ $lGroup['product_name'] }}</h4>
                                        <span class="px-2 py-0.5 bg-primary/10 text-primary text-[11px] font-bold rounded-lg border border-primary/20">
                                            Pattern: {{ $lGroup['pattern_name'] }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-on-surface-variant font-semibold mt-0.5">
                                        Total Cut Quantity across Rolls: <strong class="text-on-surface font-mono">{{ $totalCutQty }} Pcs</strong>
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <!-- Assigned Quantity Counter Badge -->
                                    <span class="px-3 py-1 rounded-xl text-xs font-mono font-black border {{ $isOverAssigned ? 'bg-error-container/20 text-error border-error/40' : ($isFullyAssigned ? 'bg-emerald-500/10 text-emerald-800 border-emerald-500/30' : 'bg-amber-500/10 text-amber-900 border-amber-500/30') }}">
                                        Assigned: {{ $assignedQty }} / {{ $totalCutQty }} Pcs
                                    </span>

                                    <!-- Add Worker Button -->
                                    <button
                                        type="button"
                                        wire:click="addWorkerToProduct('{{ $pKey }}')"
                                        class="px-3.5 py-1.5 bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1 shrink-0"
                                    >
                                        <span class="material-symbols-outlined text-[16px]">person_add</span>
                                        + Add Worker
                                    </button>
                                </div>
                            </div>

                            @error("laborAllocations.{$pKey}")
                                <div class="bg-error-container/20 border border-error/40 text-error p-2.5 rounded-xl text-xs font-bold flex items-center gap-2">
                                    <span class="material-symbols-outlined text-base">error</span>
                                    <span>{{ $message }}</span>
                                </div>
                            @enderror

                            <!-- Worker Allocation Repeater Rows -->
                            <div class="space-y-3">
                                @foreach($workers as $wIdx => $wRow)
                                    @php
                                        $baseRate = floatval($wRow['base_rate'] ?? 0);
                                        $bonusRate = floatval($wRow['bonus_rate'] ?? 0);
                                        $effRate = $baseRate + $bonusRate;
                                        $wQty = intval($wRow['quantity'] ?? 0);
                                        $subtotal = round($effRate * $wQty, 2);
                                    @endphp
                                    <div wire:key="worker-row-{{ $pKey }}-{{ $wIdx }}" class="p-3 bg-surface border border-outline-variant/60 rounded-xl grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                                        <!-- Cutting Worker -->
                                        <div class="sm:col-span-4">
                                            <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-wider mb-1">CUTTING WORKER *</label>
                                            <select
                                                wire:model="laborAllocations.{{ $pKey }}.workers.{{ $wIdx }}.labor_id"
                                                class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface"
                                            >
                                                <option value="">— Select Worker —</option>
                                                @foreach($labors as $l)
                                                    <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->worker_code }})</option>
                                                @endforeach
                                            </select>
                                            @error("laborAllocations.{$pKey}.workers.{$wIdx}.labor_id")
                                                <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <!-- Qty Worked Upon -->
                                        <div class="sm:col-span-3">
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="text-[10px] font-black text-on-surface-variant uppercase tracking-wider">WORKED QTY (PCS) *</label>
                                                <button
                                                    type="button"
                                                    wire:click="assignAllToWorker('{{ $pKey }}', {{ $wIdx }})"
                                                    class="text-[9px] font-extrabold text-primary hover:underline cursor-pointer"
                                                    title="Assign all {{ $totalCutQty }} Pcs to this worker"
                                                >
                                                    Assign All ({{ $totalCutQty }})
                                                </button>
                                            </div>
                                            <input
                                                type="number"
                                                min="1"
                                                max="{{ $totalCutQty }}"
                                                wire:model.live.debounce.300ms="laborAllocations.{{ $pKey }}.workers.{{ $wIdx }}.quantity"
                                                class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-extrabold text-on-surface"
                                            />
                                            @error("laborAllocations.{$pKey}.workers.{{ $wIdx }}.quantity")
                                                <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <!-- Base Rate -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-wider mb-1">BASE RATE (₹/PC)</label>
                                            <input
                                                type="number"
                                                step="0.5"
                                                wire:model.live.debounce.300ms="laborAllocations.{{ $pKey }}.workers.{{ $wIdx }}.base_rate"
                                                class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-right text-on-surface"
                                            />
                                        </div>

                                        <!-- Bonus Rate -->
                                        <div class="sm:col-span-2">
                                            <label class="block text-[10px] font-black text-amber-800 uppercase tracking-wider mb-1">BONUS RATE (₹/PC)</label>
                                            <input
                                                type="number"
                                                step="0.5"
                                                wire:model.live.debounce.300ms="laborAllocations.{{ $pKey }}.workers.{{ $wIdx }}.bonus_rate"
                                                class="w-full bg-amber-50/50 border border-amber-300 rounded-xl px-3 py-2 text-xs font-bold text-right text-amber-900 focus:border-amber-500"
                                            />
                                        </div>

                                        <!-- Subtotal & Delete -->
                                        <div class="sm:col-span-1 flex flex-col items-end justify-center">
                                            <span class="text-[9px] text-outline font-extrabold uppercase">Subtotal</span>
                                            <span class="text-xs font-extrabold text-primary">₹{{ number_format($subtotal, 2) }}</span>
                                            @if(count($workers) > 1)
                                                <button
                                                    type="button"
                                                    wire:click="removeWorkerFromProduct('{{ $pKey }}', {{ $wIdx }})"
                                                    class="text-error hover:bg-error-container/20 p-1 rounded-lg transition-colors cursor-pointer mt-1"
                                                    title="Remove worker"
                                                >
                                                    <span class="material-symbols-outlined text-base">delete</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-between pt-4">
                <button type="button" wire:click="goToStep(1)" class="px-6 py-3 border border-outline-variant/60 rounded-xl text-xs font-bold text-on-surface-variant hover:bg-surface-container-high transition-colors cursor-pointer">
                    Back to Step 1
                </button>
                <button type="button" wire:click="goToStep(3)" class="px-8 py-3.5 bg-primary text-on-primary font-extrabold text-xs rounded-xl shadow-md hover:bg-primary-container transition-all cursor-pointer flex items-center gap-2">
                    Proceed to Step 3 (Output Items)
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </div>
        </div>
    @endif

    <!-- STEP 3: Output Items Definition -->
    @if($currentStep === 3)
        <div class="space-y-6">
            <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-6 shadow-xs space-y-6">
                <div class="flex justify-between items-center border-b border-outline-variant/40 pb-4">
                    <div>
                        <h3 class="font-headline-sm text-base font-extrabold text-primary">Define Cutting Output Items &amp; Notes</h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Verify expected output quantities and remarks for each product/pattern to be spawned into production jobs.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    @foreach($outputItems as $oIdx => $out)
                        <div wire:key="output-item-{{ $oIdx }}" class="p-4 bg-surface-container-low/40 rounded-xl border border-outline-variant/40 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-bold text-on-surface text-sm">{{ $out['product_name'] }}</h4>
                                    <span class="text-xs text-outline font-semibold">Pattern: {{ $out['pattern_name'] }}</span>
                                </div>
                                <span class="px-3 py-1 bg-primary/10 text-primary font-mono font-black rounded-lg text-xs">
                                    {{ $out['expected_quantity'] }} Pcs Expected
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                <div class="md:col-span-4">
                                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Expected Output Quantity *</label>
                                    <input type="number" min="1" wire:model.live.debounce.300ms="outputItems.{{ $oIdx }}.expected_quantity" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface" />
                                </div>

                                <div class="md:col-span-8">
                                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Remarks / Cutting Output Note</label>
                                    <input type="text" wire:model="outputItems.{{ $oIdx }}.remarks" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface" placeholder="Remarks..." />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-between pt-4">
                <button type="button" wire:click="goToStep(2)" class="px-6 py-3 border border-outline-variant/60 rounded-xl text-xs font-bold text-on-surface-variant hover:bg-surface-container-high transition-colors cursor-pointer">
                    Back to Step 2
                </button>
                <button type="button" wire:click="goToStep(4)" class="px-8 py-3.5 bg-primary text-on-primary font-extrabold text-xs rounded-xl shadow-md hover:bg-primary-container transition-all cursor-pointer flex items-center gap-2">
                    Proceed to Step 4 (Review &amp; Confirm)
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </div>
        </div>
    @endif

    <!-- STEP 4: Cutting Stage Review & Confirm -->
    @if($currentStep === 4)
        <div class="space-y-6">
            <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs space-y-6">
                <div class="flex justify-between items-center border-b border-outline-variant/40 pb-4">
                    <div>
                        <h3 class="font-headline-sm text-base font-extrabold text-primary">Shared Cutting Stage Review &amp; Confirmation</h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Review full cutting metrics, allocated product items, labor wage totals, and spawned production jobs preview.</p>
                    </div>
                    @if(!empty($batchCode))
                        <span class="px-3.5 py-1.5 bg-primary/10 text-primary font-mono font-black text-sm rounded-xl border border-primary/20">
                            Batch {{ $batchCode }}
                        </span>
                    @endif
                </div>

                <!-- Consolidated Summary Cards -->
                @php
                    $cBreakdown = $this->fabricCuttingBreakdown;
                    $uniqueProds = $this->uniqueAllocatedProducts;
                    $totalPlannedOutputQty = array_sum(array_column($uniqueProds, 'total_quantity'));
                    $totalLaborWage = 0;
                    foreach ($this->laborAllocations as $lGroup) {
                        foreach ($lGroup['workers'] ?? [] as $w) {
                            $eff = floatval($w['base_rate'] ?? 0) + floatval($w['bonus_rate'] ?? 0);
                            $totalLaborWage += round($eff * intval($w['quantity'] ?? 0), 2);
                        }
                    }
                @endphp

                <div class="flex flex-wrap lg:flex-nowrap gap-3 text-xs">
                    <div class="flex-1 min-w-[150px] bg-surface-container-low p-4 rounded-xl border border-outline-variant/40">
                        <span class="text-on-surface-variant block text-[10px] font-bold uppercase">Total Cut Length &amp; Area</span>
                        <span class="text-xl font-black text-primary">{{ $cBreakdown['cut_area_m2'] ?? 0 }} m²</span>
                        <span class="text-xs text-on-surface-variant block font-bold mt-0.5">{{ $cBreakdown['total_cut_length'] ?? 0 }}m Fabric Cut</span>
                    </div>

                    <div class="flex-1 min-w-[150px] bg-surface-container-low p-4 rounded-xl border border-outline-variant/40">
                        <span class="text-on-surface-variant block text-[10px] font-bold uppercase">Utilized Product Area</span>
                        <span class="text-xl font-black text-emerald-700">{{ $cBreakdown['used_area_m2'] ?? 0 }} m²</span>
                        <span class="text-xs text-emerald-800 font-bold block mt-0.5">{{ $cBreakdown['usage_percentage'] ?? 0 }}% Utilized</span>
                    </div>

                    <div class="flex-1 min-w-[150px] bg-surface-container-low p-4 rounded-xl border border-outline-variant/40">
                        <span class="text-on-surface-variant block text-[10px] font-bold uppercase">Fabric Wastage</span>
                        <span class="text-xl font-black text-amber-800">{{ $cBreakdown['wastage_area_m2'] ?? 0 }} m²</span>
                        <span class="text-xs text-amber-900 font-bold block mt-0.5">{{ $cBreakdown['wastage_percentage'] ?? 0 }}% Wastage</span>
                    </div>

                    <div class="flex-1 min-w-[150px] bg-surface-container-low p-4 rounded-xl border border-outline-variant/40">
                        <span class="text-on-surface-variant block text-[10px] font-bold uppercase">Total Fabric Cost</span>
                        <span class="text-xl font-black text-on-surface">₹{{ number_format($cBreakdown['total_fabric_cut_cost'] ?? 0, 2) }}</span>
                        <span class="text-xs text-outline block font-semibold mt-0.5">Material Cost</span>
                    </div>

                    <div class="flex-1 min-w-[150px] bg-surface-container-low p-4 rounded-xl border border-outline-variant/40">
                        <span class="text-on-surface-variant block text-[10px] font-bold uppercase">Total Cutting Labor Wage</span>
                        <span class="text-xl font-black text-primary">₹{{ number_format($totalLaborWage, 2) }}</span>
                        <span class="text-xs text-outline block font-semibold mt-0.5">Piece Rate Total</span>
                    </div>
                </div>

                <!-- Spawned Production Jobs Preview -->
                <div class="space-y-3 pt-2">
                    <span class="text-xs font-black text-primary uppercase tracking-wider block">
                        Spawned Production Jobs Preview (Cutting Completed &rightarrow; Stitching In Progress)
                    </span>

                    <div class="bg-surface-container-low rounded-xl border border-outline-variant/60 overflow-hidden">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-surface-container-high border-b border-outline-variant/60 text-on-surface-variant uppercase font-bold text-[10px] tracking-wider">
                                    <th class="px-4 py-3">Product Name</th>
                                    <th class="px-4 py-3">Pattern</th>
                                    <th class="px-4 py-3 text-center">Target Qty</th>
                                    <th class="px-4 py-3 text-center">Stage 1 (Cutting)</th>
                                    <th class="px-4 py-3 text-center">Stage 2 (Next Step)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/40">
                                @foreach($uniqueProds as $item)
                                    <tr class="hover:bg-surface-container/40 font-semibold text-on-surface">
                                        <td class="px-4 py-3">
                                            <strong class="font-extrabold text-sm text-primary block">{{ $item['product_name'] }}</strong>
                                            <span class="text-[10px] text-outline font-mono">{{ $item['product_code'] }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-on-surface-variant font-bold">
                                            {{ $item['pattern_name'] }}
                                        </td>
                                        <td class="px-4 py-3 text-center font-black text-sm">
                                            {{ number_format($item['total_quantity']) }} Pcs
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 font-extrabold rounded-lg text-[11px] inline-flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                                COMPLETED
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2.5 py-1 bg-blue-100 text-blue-800 font-extrabold rounded-lg text-[11px] inline-flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[14px]">play_circle</span>
                                                IN PROGRESS
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between pt-4">
                <button type="button" wire:click="goToStep(3)" class="px-6 py-3 border border-outline-variant/60 rounded-xl text-xs font-bold text-on-surface-variant hover:bg-surface-container-high transition-colors cursor-pointer">
                    Back to Step 3
                </button>
                <button
                    type="button"
                    wire:click="submitCuttingStage"
                    class="px-8 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-sm rounded-xl shadow-lg transition-all active:scale-95 cursor-pointer flex items-center gap-2"
                >
                    <span class="material-symbols-outlined text-[20px]">task_alt</span>
                    Complete Shared Cutting Stage &amp; Create Production Jobs
                </button>
            </div>
        </div>
    @endif

    <!-- Unopened Bale Modal -->
    @if($showOpenBaleModal && $activeBaleIdToOpen)
        @php
            $modalBale = \App\Models\InventoryBale::with('batch.rawMaterial')->find($activeBaleIdToOpen);
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-surface rounded-2xl border border-outline-variant/60 shadow-2xl max-w-lg w-full p-6 space-y-5">
                <div class="flex justify-between items-center border-b border-outline-variant/40 pb-3">
                    <h3 class="font-bold text-base text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-600">lock_open</span>
                        Open Bale {{ $modalBale?->bale_number }} &amp; Define Rolls
                    </h3>
                    <button type="button" wire:click="$set('showOpenBaleModal', false)" class="text-on-surface-variant hover:text-on-surface">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <div class="space-y-4 text-xs font-body-md">
                    <p class="text-on-surface-variant">
                        Enter the number of rolls in this bale and the measured cut length for each roll. Recorded Purchase Length: <strong>{{ $modalBale?->declared_length }}m</strong>.
                    </p>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">Number of Rolls *</label>
                        <input type="number" min="1" max="50" wire:model.live="baleRollCount" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-2.5 text-xs font-bold text-on-surface">
                        @error('baleRollCount') <span class="text-error text-[11px] block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    @if(!empty($baleRollLengths))
                        <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                            @foreach($baleRollLengths as $i => $len)
                                <div class="p-3 bg-surface-container-low/50 rounded-xl border border-outline-variant/40 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-primary font-mono">Roll #{{ $i + 1 }}</span>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Length (Meters) *</label>
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="baleRollLengths.{{ $i }}" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                        @error("baleRollLengths.{$i}") <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($baleMismatchWarning))
                        <div class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl text-amber-900 text-xs font-semibold">
                            {{ $baleMismatchWarning }}
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-3 border-t border-outline-variant/40">
                    <button type="button" wire:click="$set('showOpenBaleModal', false)" class="px-4 py-2 rounded-xl text-xs font-bold text-on-surface-variant hover:bg-surface-container-high">Cancel</button>
                    <button type="button" wire:click="submitOpenedBaleForm" class="px-5 py-2.5 bg-primary text-on-primary font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container">
                        Save &amp; Open Bale
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
