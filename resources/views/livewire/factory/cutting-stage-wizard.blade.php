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
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">First mandatory stage: select fabric rolls, allocate products &amp; patterns per roll, and spawn initial production jobs.</p>
        </div>
    </div>

    <!-- Wizard Stepper Navigation (2 Steps) -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-4 mb-8 shadow-xs">
        <div class="flex items-center justify-between max-w-2xl mx-auto">
            <!-- Step 1 -->
            <button
                type="button"
                wire:click="goToStep(1)"
                class="flex items-center gap-3 cursor-pointer group"
            >
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm transition-all
                    {{ $currentStep === 1 ? 'bg-primary text-on-primary shadow-md ring-4 ring-primary/20' : 'bg-secondary text-on-secondary' }}"
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

            <div class="flex-1 h-0.5 bg-outline-variant/40 mx-6"></div>

            <!-- Step 2 -->
            <button
                type="button"
                wire:click="goToStep(2)"
                class="flex items-center gap-3 cursor-pointer group"
            >
                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm transition-all
                    {{ $currentStep === 2 ? 'bg-primary text-on-primary shadow-md ring-4 ring-primary/20' : 'bg-surface-container-high text-on-surface-variant' }}"
                >
                    2
                </div>
                <div class="text-left hidden sm:block">
                    <p class="text-xs font-extrabold {{ $currentStep === 2 ? 'text-primary' : 'text-on-surface-variant' }}">Step 2</p>
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
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-outline-variant/40">
                        <div class="flex items-center gap-3 flex-wrap flex-1">
                            <span class="font-mono text-xs font-extrabold text-primary bg-primary/10 px-3 py-1.5 rounded-lg shrink-0">
                                Fabric Item #{{ $fIdx + 1 }}
                            </span>

                            <!-- Compact Quick Search Bar beside Fabric Item #X -->
                            @php
                                $rowSearch = $fabRow['search'] ?? '';
                                $searchResults = !empty($rowSearch) ? $this->getMatchingSearchResults($rowSearch) : collect();
                            @endphp
                            <div x-data="{ open: true }" class="relative w-full sm:w-72 md:w-80">
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        wire:model.live.debounce.250ms="selectedFabrics.{{ $fIdx }}.search" 
                                        @focus="open = true" 
                                        placeholder="Search Bale # or Batch #..." 
                                        class="w-full bg-surface border border-outline-variant/60 rounded-xl pl-9 pr-7 py-1.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary shadow-xs"
                                    />
                                    <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-base">search</span>
                                    @if(!empty($rowSearch))
                                        <button type="button" wire:click="$set('selectedFabrics.{{ $fIdx }}.search', '')" class="absolute right-2 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface">
                                            <span class="material-symbols-outlined text-xs">close</span>
                                        </button>
                                    @endif
                                </div>

                                @if(!empty($rowSearch) && count($searchResults) > 0)
                                    <div x-show="open" @click.outside="open = false" class="absolute z-50 left-0 right-0 mt-1 bg-surface border border-outline-variant/60 rounded-xl shadow-xl overflow-hidden divide-y divide-outline-variant/30 font-body-md text-xs max-h-56 overflow-y-auto">
                                        @foreach($searchResults as $res)
                                            <div 
                                                wire:click="selectSearchedBaleOrBatch({{ $res['bale_id'] ? $res['bale_id'] : 'null' }}, {{ $res['batch_id'] ? $res['batch_id'] : 'null' }}, {{ $fIdx }})"
                                                @click="open = false"
                                                class="p-2.5 hover:bg-primary/10 cursor-pointer flex items-center justify-between transition-colors"
                                            >
                                                <div class="flex items-center gap-2 truncate">
                                                    <span class="material-symbols-outlined text-primary text-sm shrink-0">{{ $res['type'] === 'bale' ? 'view_week' : 'inventory_2' }}</span>
                                                    <div class="truncate">
                                                        <p class="font-extrabold text-on-surface text-xs leading-tight truncate">{{ $res['title'] }}</p>
                                                        <p class="text-[10px] text-on-surface-variant font-semibold truncate">{{ $res['subtitle'] }}</p>
                                                    </div>
                                                </div>
                                                <span class="px-2 py-0.5 bg-primary text-on-primary font-bold text-[10px] rounded-md shrink-0 ml-1">Select &rarr;</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if(count($selectedFabrics) > 1)
                            <button type="button" wire:click="removeFabricRow({{ $fIdx }})" class="text-error hover:bg-error-container/20 px-3 py-1.5 rounded-xl text-xs font-bold transition-colors cursor-pointer shrink-0">
                                Remove Fabric
                            </button>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- 1. Select Inventory Batch (Stock Batch) -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Stock Batch <span class="text-error">*</span></label>
                            @php
                                $rawMatId = $fabRow['raw_material_id'] ?? null;
                                $batchQuery = \App\Models\InventoryBatch::with('rawMaterial')->where('balance_quantity', '>', 0);
                                if (!empty($rawMatId)) {
                                    $batchQuery->where('raw_material_id', $rawMatId);
                                }
                                $batches = $batchQuery->orderBy('id', 'desc')->get();
                            @endphp
                            <select
                                wire:model.live="selectedFabrics.{{ $fIdx }}.inventory_batch_id"
                                class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-body-md focus:border-primary focus:outline-none"
                            >
                                <option value="">— Select Batch —</option>
                                @foreach($batches as $b)
                                    <option value="{{ $b->id }}">
                                        {{ $b->batch_number }} @if(empty($rawMatId) && $b->rawMaterial) ({{ $b->rawMaterial->name }}) @endif (Bal: {{ $b->balance_quantity }} {{ $b->unit }})
                                    </option>
                                @endforeach
                            </select>
                            @error("selectedFabrics.{$fIdx}.inventory_batch_id")
                                <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 2. Select Fabric Bale (Bale) -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Bale <span class="text-error">*</span></label>
                            @php
                                $batchId = $fabRow['inventory_batch_id'] ?? null;
                                $bales = collect();
                                if (!empty($batchId)) {
                                    $bObj = \App\Models\InventoryBatch::find($batchId);
                                    if ($bObj) {
                                        if ($bObj->bales()->count() === 0 && (float)$bObj->balance_quantity > 0) {
                                            $bObj->createBales(1, (float)$bObj->balance_quantity);
                                        }
                                        $bales = \App\Models\InventoryBale::where('inventory_batch_id', $bObj->id)->where('status', '!=', 'depleted')->get();
                                    }
                                } elseif (!empty($rawMatId)) {
                                    $bales = \App\Models\InventoryBale::whereHas('batch', fn($q) => $q->where('raw_material_id', $rawMatId))->where('status', '!=', 'depleted')->get();
                                } else {
                                    $bales = \App\Models\InventoryBale::with('batch.rawMaterial')->where('status', '!=', 'depleted')->take(30)->get();
                                }
                            @endphp
                            <select
                                wire:model.live="selectedFabrics.{{ $fIdx }}.inventory_bale_id"
                                class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-body-md focus:border-primary focus:outline-none"
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

                        <!-- 3. Select Fabric Raw Material (Fabric Material) -->
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
                    </div>

                    <!-- Bale Details & Active Rolls Section -->
                    @if(!empty($fabRow['inventory_bale_id']))
                        @php
                            $selectedBale = \App\Models\InventoryBale::with('activeRolls.fabricWidth.unitModel')->find($fabRow['inventory_bale_id']);
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
                                                        @if($isSelected && isset($rollData['selected_unit_id']))
                                                            @php
                                                                $availableUnitsHeader = $this->getAvailableUnitsForMaterial($fabRow['raw_material_id'] ?? $roll->raw_material_id ?: $roll->bale?->batch?->raw_material_id);
                                                                $headerUnitModel = $availableUnitsHeader->firstWhere('id', $rollData['selected_unit_id']);
                                                                $headerUnitCode = $headerUnitModel ? $headerUnitModel->short_code : 'm';
                                                                $maxInHeaderUnit = $this->convertLengthFromMeters((float)$roll->current_balance_length, $rollData['selected_unit_id']);
                                                                $maxInHeaderUnitDisp = (round($maxInHeaderUnit, 4) == round($maxInHeaderUnit, 0)) ? round($maxInHeaderUnit, 0) : round($maxInHeaderUnit, 2);
                                                            @endphp
                                                            Available Stock: <strong class="text-on-surface px-2 py-0.5 bg-surface-container rounded-lg font-mono">{{ $maxInHeaderUnitDisp }}{{ $headerUnitCode }}</strong>
                                                            @if($headerUnitCode !== 'm' && $headerUnitCode !== 'M')
                                                                <span class="text-[11px] text-on-surface-variant font-mono">({{ $roll->current_balance_length }}m)</span>
                                                            @endif
                                                            / {{ $roll->initial_length }}m
                                                        @else
                                                            Available Stock: <strong class="text-on-surface px-2 py-0.5 bg-surface-container rounded-lg font-mono">{{ $roll->current_balance_length }}m</strong> / {{ $roll->initial_length }}m
                                                        @endif
                                                    </span>
                                                </div>

                                                 @if($isSelected && $rollData)
                                                     @php
                                                         $availableUnits = $this->getAvailableUnitsForMaterial($fabRow['raw_material_id'] ?? $roll->raw_material_id ?: $roll->bale?->batch?->raw_material_id);
                                                         $selUnitId = $rollData['selected_unit_id'] ?? null;
                                                         $selUnitModel = $selUnitId ? $availableUnits->firstWhere('id', $selUnitId) : null;
                                                         $selUnitCode = $selUnitModel ? $selUnitModel->short_code : 'm';
                                                         $selUnitName = $selUnitModel ? $selUnitModel->name : 'Meters';

                                                         $maxMeters = (float) $roll->current_balance_length;
                                                         $maxInSelectedUnit = $this->convertLengthFromMeters($maxMeters, $selUnitId);
                                                         $maxInSelectedUnitDisplay = (round($maxInSelectedUnit, 4) == round($maxInSelectedUnit, 0)) ? round($maxInSelectedUnit, 0) : round($maxInSelectedUnit, 2);
                                                     @endphp
                                                     <div class="space-y-5 pt-4">
                                                         <!-- Cut Length Control Row -->
                                                         <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-surface-container-low p-3.5 rounded-xl border border-outline-variant/40">
                                                             <div class="flex items-center gap-3 flex-1 max-w-xl">
                                                                 <div class="flex items-center gap-2">
                                                                     <label class="text-xs font-extrabold text-on-surface-variant uppercase tracking-wider whitespace-nowrap">Cut Length *</label>
                                                                     <!-- Unit Selector Dropdown -->
                                                                     <select
                                                                         wire:model.live="selectedFabrics.{{ $fIdx }}.selected_rolls.{{ $roll->id }}.selected_unit_id"
                                                                         class="bg-surface border border-outline-variant/60 rounded-lg px-2.5 py-1.5 text-xs font-extrabold text-primary focus:border-primary focus:outline-none"
                                                                     >
                                                                         @foreach($availableUnits as $u)
                                                                             <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->short_code }})</option>
                                                                         @endforeach
                                                                     </select>
                                                                 </div>

                                                                 <div class="relative w-full">
                                                                     <input
                                                                         type="number"
                                                                         step="0.01"
                                                                         wire:model.live.debounce.150ms="selectedFabrics.{{ $fIdx }}.selected_rolls.{{ $roll->id }}.cut_length_input"
                                                                         max="{{ round($maxInSelectedUnit, 2) }}"
                                                                         placeholder="Length in {{ strtolower($selUnitName) }}..."
                                                                         class="w-full bg-surface border border-outline-variant/60 rounded-xl pl-3 pr-12 py-2 text-xs font-extrabold text-on-surface focus:border-primary focus:outline-none"
                                                                     />
                                                                     <span class="absolute right-3 top-2 text-xs font-bold text-primary">{{ $selUnitCode }}</span>
                                                                 </div>
                                                             </div>

                                                             <div class="flex items-center gap-2">
                                                                 @if($selUnitCode !== 'M' && $selUnitCode !== 'm')
                                                                     <span class="text-[11px] font-mono font-bold text-on-surface-variant bg-surface px-2.5 py-1.5 rounded-lg border border-outline-variant/40">
                                                                         &asymp; {{ round($rollData['cut_length'] ?? 0, 2) }}m
                                                                     </span>
                                                                 @endif

                                                                 <button
                                                                     type="button"
                                                                     wire:click="setFullRollCut({{ $fIdx }}, {{ $roll->id }})"
                                                                     class="px-4 py-2 bg-primary text-on-primary font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container transition-all flex items-center justify-center gap-1.5 shrink-0 active:scale-95 cursor-pointer"
                                                                 >
                                                                     <span class="material-symbols-outlined text-[16px]">content_cut</span>
                                                                     Cut Full Roll ({{ $maxInSelectedUnitDisplay }}{{ $selUnitCode }})
                                                                 </button>
                                                             </div>
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
                                                                        $selProduct = $rProdId ? $manufacturingProducts->firstWhere('id', $rProdId) : null;
                                                                        $selPattern = $pRow['pattern_id'] ? \App\Models\ManufacturingProductPattern::with('patternFabricWidths.fabricWidth.unitModel', 'fabricWidth')->find($pRow['pattern_id']) : null;
                                                                        $pDims = $selProduct ? \App\Services\FabricCuttingAreaService::formatProductPatternDimensions($selProduct, $selPattern, $roll) : null;
                                                                        $cLenVal = floatval($rollData['cut_length'] ?? 0);
                                                                    @endphp
                                                                    <div wire:key="roll-prod-{{ $roll->id }}-{{ $pIdx }}-{{ $cLenVal }}" class="p-3 bg-surface border border-outline-variant/60 rounded-xl space-y-2">
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
                                                                                @php
                                                                                    $calcMaxPcs = ($cLenVal > 0 && $rProdId) ? $this->computeMaxPcsForRollProduct($roll->id, $cLenVal, $rProdId, $pRow['pattern_id'] ?? null) : 0;
                                                                                @endphp
                                                                                <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-wider mb-1">
                                                                                    QUANTITY (PCS) *
                                                                                    @if($calcMaxPcs > 0)
                                                                                        <span class="text-primary font-mono text-[10px] lowercase font-semibold ml-1">(max: {{ $calcMaxPcs }})</span>
                                                                                    @endif
                                                                                </label>
                                                                                <div class="flex items-center gap-1.5">
                                                                                    <input type="number" min="1" placeholder="Qty (Pcs)..." wire:model.live.debounce.300ms="selectedFabrics.{{ $fIdx }}.selected_rolls.{{ $roll->id }}.products.{{ $pIdx }}.planned_quantity" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                                                                    @if(count($rollData['products'] ?? []) > 1)
                                                                                        <button type="button" wire:click="removeProductFromRoll({{ $fIdx }}, {{ $roll->id }}, {{ $pIdx }})" class="text-error hover:bg-error-container/20 p-1.5 rounded-lg transition-colors cursor-pointer shrink-0">
                                                                                            <span class="material-symbols-outlined text-base">delete</span>
                                                                                        </button>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        @if($pDims)
                                                                            @if(!empty($pDims['is_configured']))
                                                                                <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 px-3.5 py-2 bg-surface-container-low/80 border border-outline-variant/50 rounded-xl text-xs">
                                                                                    <div class="flex flex-wrap items-center gap-2 text-on-surface-variant font-bold">
                                                                                        <span class="material-symbols-outlined text-[16px] text-primary">square_foot</span>
                                                                                        <span class="text-primary font-black uppercase text-[10px] tracking-wider">Pattern Dimensions:</span>
                                                                                        <span class="text-on-surface font-extrabold">{{ $pDims['dimensions_display'] }}</span>
                                                                                    </div>
                                                                                    <span class="px-2.5 py-1 bg-primary/10 text-primary font-black rounded-lg text-[11px] border border-primary/20">
                                                                                        Piece Area: {{ $pDims['area_display'] }}
                                                                                    </span>
                                                                                </div>
                                                                            @else
                                                                                <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 px-3.5 py-2 bg-amber-500/10 border border-amber-500/30 rounded-xl text-xs text-amber-900 dark:text-amber-200 font-bold">
                                                                                    <div class="flex items-center gap-2">
                                                                                        <span class="material-symbols-outlined text-[18px] text-amber-600 shrink-0">warning</span>
                                                                                        <span>{{ $pDims['error_message'] ?? "No fabric length defined for selected width on pattern." }}</span>
                                                                                    </div>
                                                                                    @if($selProduct)
                                                                                        <a href="/factory/products/{{ $selProduct->id }}/edit" target="_blank" class="px-2.5 py-1 bg-amber-600 text-white font-black rounded-lg text-[10px] uppercase tracking-wider hover:bg-amber-700 transition-colors shrink-0 flex items-center gap-1 shadow-xs">
                                                                                            <span>Configure Pattern</span>
                                                                                            <span class="material-symbols-outlined text-xs">open_in_new</span>
                                                                                        </a>
                                                                                    @endif
                                                                                </div>
                                                                            @endif
                                                                        @endif
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
                                                                        <p class="text-[10px] font-extrabold text-primary uppercase tracking-wider">Roll Cut Length</p>
                                                                        <div class="flex items-center gap-2">
                                                                            <span class="material-symbols-outlined text-primary text-lg">straighten</span>
                                                                            <span class="text-sm font-black text-on-surface">{{ $rLive['cut_length'] }} m</span>
                                                                        </div>
                                                                        <p class="text-[11px] text-on-surface-variant font-bold">Cut Area: <span class="text-primary">{{ $rLive['cut_area_m2'] }} m²</span> &middot; {{ $rLive['dimensions_display'] ?? ('Width: ' . $rLive['roll_width_display']) }}</p>
                                                                    </div>

                                                                    <div class="space-y-1">
                                                                        <p class="text-[10px] font-extrabold text-emerald-700 uppercase tracking-wider">Allocated Fabric Length</p>
                                                                        <div class="flex items-center gap-2">
                                                                            <span class="material-symbols-outlined text-emerald-600 text-lg">fact_check</span>
                                                                            <span class="text-sm font-black {{ !empty($rLive['is_over_capacity']) ? 'text-error' : 'text-emerald-700' }}">{{ $rLive['total_req_length'] ?? 0 }} m</span>
                                                                        </div>
                                                                        <p class="text-[11px] text-on-surface-variant font-bold">Product Area: {{ $rLive['used_area_m2'] }} m² &middot; Utilized: {{ $rLive['usage_percentage'] }}%</p>
                                                                    </div>

                                                                    <div class="space-y-1">
                                                                        <p class="text-[10px] font-extrabold text-amber-800 uppercase tracking-wider">Fabric Wastage Length</p>
                                                                        <div class="flex items-center gap-2">
                                                                            <span class="material-symbols-outlined text-amber-600 text-lg">delete_sweep</span>
                                                                            <span class="text-sm font-black text-amber-900">{{ $rLive['wastage_length'] }} m</span>
                                                                        </div>
                                                                        <p class="text-[11px] text-amber-800 font-bold">Wastage Area: {{ $rLive['wastage_area_m2'] }} m² ({{ $rLive['wastage_percentage'] }}%) &middot; Cost: ₹{{ number_format($rLive['wastage_cost'], 2) }}</p>
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
                    Proceed to Step 2 (Review &amp; Confirm)
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </div>
        </div>
    @endif

    <!-- STEP 2: Output Items Definition -->
    @if($currentStep === 2)
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
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs text-outline font-semibold">Pattern: {{ $out['pattern_name'] }}</span>
                                        @if(!empty($out['dimensions_display']))
                                            <span class="px-2 py-0.5 bg-primary/10 text-primary text-[11px] font-bold rounded-lg border border-primary/20">
                                                {{ $out['dimensions_display'] }}
                                            </span>
                                        @endif
                                    </div>
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
                <button type="button" wire:click="goToStep(1)" class="px-6 py-3 border border-outline-variant/60 rounded-xl text-xs font-bold text-on-surface-variant hover:bg-surface-container-high transition-colors cursor-pointer">
                    Back to Step 1
                </button>
                <button type="button" wire:click="goToStep(3)" class="px-8 py-3.5 bg-primary text-on-primary font-extrabold text-xs rounded-xl shadow-md hover:bg-primary-container transition-all cursor-pointer flex items-center gap-2">
                    Proceed to Step 3 (Review &amp; Confirm)
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </div>
        </div>
    @endif

    <!-- STEP 3: Cutting Stage Review & Confirm -->
    @if($currentStep === 3)
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
                        <span class="text-on-surface-variant block text-[10px] font-bold uppercase">Total Cut Length</span>
                        <span class="text-xl font-black text-primary">{{ $cBreakdown['total_cut_length'] ?? 0 }} m</span>
                        <span class="text-xs text-on-surface-variant block font-bold mt-0.5">{{ $cBreakdown['cut_area_m2'] ?? 0 }} m² Surface Area</span>
                    </div>

                    <div class="flex-1 min-w-[150px] bg-surface-container-low p-4 rounded-xl border border-outline-variant/40">
                        <span class="text-on-surface-variant block text-[10px] font-bold uppercase">Utilized Fabric Length</span>
                        <span class="text-xl font-black text-emerald-700">{{ $cBreakdown['total_req_length'] ?? 0 }} m</span>
                        <span class="text-xs text-emerald-800 font-bold block mt-0.5">{{ $cBreakdown['used_area_m2'] ?? 0 }} m² ({{ $cBreakdown['usage_percentage'] ?? 0 }}% Utilized)</span>
                    </div>

                    <div class="flex-1 min-w-[150px] bg-surface-container-low p-4 rounded-xl border border-outline-variant/40">
                        <span class="text-on-surface-variant block text-[10px] font-bold uppercase">Fabric Wastage Length</span>
                        <span class="text-xl font-black text-amber-800">{{ $cBreakdown['total_wastage_length'] ?? 0 }} m</span>
                        <span class="text-xs text-amber-900 font-bold block mt-0.5">{{ $cBreakdown['wastage_area_m2'] ?? 0 }} m² ({{ $cBreakdown['wastage_percentage'] ?? 0 }}% Wastage)</span>
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

                <!-- Recorded Cutting Worker & Saved Fee Earnings Summary Table -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black text-primary uppercase tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">badge</span>
                            Recorded Cutting Worker &amp; Saved Fee Earnings
                        </span>
                        <span class="text-[11px] text-on-surface-variant font-semibold">Auto-calculated based on production batch cutter &amp; product pattern saved cutting fee</span>
                    </div>

                    <div class="bg-surface-container-low rounded-xl border border-outline-variant/60 overflow-hidden">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-surface-container-high border-b border-outline-variant/60 text-on-surface-variant uppercase font-bold text-[10px] tracking-wider">
                                    <th class="px-4 py-3">Product Name &amp; Pattern</th>
                                    <th class="px-4 py-3">Assigned Cutter Worker</th>
                                    <th class="px-4 py-3 text-center">Cut Qty</th>
                                    <th class="px-4 py-3 text-right">Saved Cutting Fee (₹/pc)</th>
                                    <th class="px-4 py-3 text-right">Calculated Wage (₹)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/40">
                                @foreach($this->laborAllocations as $lGroup)
                                    @foreach($lGroup['workers'] ?? [] as $wRow)
                                        @php
                                            $workerObj = !empty($wRow['labor_id']) ? \App\Models\Labor::find($wRow['labor_id']) : null;
                                            $fee = floatval($wRow['base_rate'] ?? 0);
                                            $qty = intval($wRow['quantity'] ?? 0);
                                            $wage = round($fee * $qty, 2);
                                        @endphp
                                        <tr class="hover:bg-surface-container/40 font-semibold text-on-surface">
                                            <td class="px-4 py-3">
                                                <strong class="font-extrabold text-sm text-primary block">{{ $lGroup['product_name'] }}</strong>
                                                <span class="text-[10px] text-on-surface-variant font-bold">{{ $lGroup['pattern_name'] }}</span>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($workerObj)
                                                    <span class="font-extrabold text-on-surface">{{ $workerObj->name }}</span>
                                                    <span class="text-[10px] text-on-surface-variant font-mono block">({{ $workerObj->worker_code }})</span>
                                                @else
                                                    <span class="text-outline font-italic">— Unassigned —</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center font-black text-sm">
                                                {{ number_format($qty) }} Pcs
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-on-surface">
                                                ₹{{ number_format($fee, 2) }}
                                            </td>
                                            <td class="px-4 py-3 text-right font-black text-primary text-sm">
                                                ₹{{ number_format($wage, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Fabric Wastage Allocation & Cost Breakdown Table -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black text-amber-800 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px] text-amber-600">delete_sweep</span>
                            Fabric Wastage Allocation &amp; Per-Piece Cost Breakdown
                        </span>
                        <span class="text-[11px] text-on-surface-variant font-semibold">Proportionally shared based on product surface area</span>
                    </div>

                    <div class="bg-surface-container-low rounded-xl border border-outline-variant/60 overflow-hidden">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-surface-container-high border-b border-outline-variant/60 text-on-surface-variant uppercase font-bold text-[10px] tracking-wider">
                                    <th class="px-4 py-3">Product Name &amp; Pattern</th>
                                    <th class="px-4 py-3 text-center">Output Qty</th>
                                    <th class="px-4 py-3 text-center">Area Share</th>
                                    <th class="px-4 py-3 text-center">Allocated Wastage Area</th>
                                    <th class="px-4 py-3 text-right">Total Product Wastage Cost</th>
                                    <th class="px-4 py-3 text-right">Per-Piece Wastage Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/40">
                                @foreach($uniqueProds as $item)
                                    <tr class="hover:bg-surface-container/40 font-semibold text-on-surface">
                                        <td class="px-4 py-3">
                                            <strong class="font-extrabold text-sm text-primary block">{{ $item['product_name'] }}</strong>
                                            <span class="text-[10px] text-on-surface-variant font-bold">{{ $item['pattern_name'] }} &middot; {{ $item['dimensions_display'] ?? '' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center font-black text-sm">
                                            {{ number_format($item['total_quantity']) }} Pcs
                                        </td>
                                        <td class="px-4 py-3 text-center font-bold">
                                            <span class="px-2.5 py-1 bg-surface-container-high text-on-surface font-mono rounded-md text-[11px]">
                                                {{ number_format($item['area_share_percentage'] ?? 0, 1) }}%
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center font-mono font-bold text-amber-900">
                                            {{ number_format($item['allocated_wastage_area_m2'] ?? 0, 4) }} m²
                                            <span class="text-[10px] text-on-surface-variant block font-normal">({{ number_format($item['per_piece_wastage_area_m2'] ?? 0, 4) }} m²/pc)</span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-black text-amber-900 text-sm">
                                            ₹{{ number_format($item['allocated_wastage_cost'] ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-black text-emerald-700 text-sm">
                                            <span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-800 border border-emerald-500/20 rounded-lg">
                                                ₹{{ number_format($item['per_piece_wastage_cost'] ?? 0, 2) }} / pc
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-surface-container-high/60 font-black text-xs text-on-surface border-t border-outline-variant/60">
                                    <td class="px-4 py-3">Total Conserved Summary</td>
                                    <td class="px-4 py-3 text-center">{{ number_format($totalPlannedOutputQty) }} Pcs</td>
                                    <td class="px-4 py-3 text-center">100.0%</td>
                                    <td class="px-4 py-3 text-center text-amber-900 font-mono">{{ number_format($cBreakdown['wastage_area_m2'] ?? 0, 4) }} m²</td>
                                    <td class="px-4 py-3 text-right text-amber-900">₹{{ number_format(($cBreakdown['cut_area_m2'] ?? 0) > 0 ? (($cBreakdown['wastage_area_m2'] ?? 0) / $cBreakdown['cut_area_m2']) * ($cBreakdown['total_fabric_cut_cost'] ?? 0) : 0, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-800">—</td>
                                </tr>
                            </tfoot>
                        </table>
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
                                    <th class="px-4 py-3">Pattern &amp; Dimensions</th>
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
                                            <span class="block">{{ $item['pattern_name'] }}</span>
                                            @if(!empty($item['dimensions_display']))
                                                <span class="text-[11px] text-primary font-extrabold block mt-0.5">{{ $item['dimensions_display'] }}</span>
                                            @endif
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
                <button type="button" wire:click="goToStep(1)" class="px-6 py-3 border border-outline-variant/60 rounded-xl text-xs font-bold text-on-surface-variant hover:bg-surface-container-high transition-colors cursor-pointer">
                    Back to Step 1
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
                        <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                            @foreach($baleRollLengths as $i => $len)
                                @php
                                    $selectedMatId = $baleRollMaterials[$i] ?? null;
                                    $convertedMeters = $this->getRollConvertedLengthInMeters($i);
                                    $selectedUnitId = $baleRollUnits[$i] ?? null;
                                    $selectedUnitObj = $selectedUnitId ? \App\Models\Unit::find($selectedUnitId) : null;
                                    $unitShortCode = $selectedUnitObj ? $selectedUnitObj->short_code : 'M';
                                @endphp
                                <div wire:key="bale-roll-card-{{ $i }}-{{ $selectedMatId }}" class="p-4 bg-surface-container-low/50 rounded-xl border border-outline-variant/40 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-primary font-mono text-sm">Roll #{{ $i + 1 }}</span>
                                        @if(!empty($baleRollLengths[$i]) && (float)$baleRollLengths[$i] > 0 && strtoupper($unitShortCode) !== 'M')
                                            <span class="px-2.5 py-0.5 bg-primary/10 text-primary font-mono text-[11px] font-extrabold rounded-md border border-primary/20">
                                                = {{ number_format($convertedMeters, 2) }}m Base Length
                                            </span>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @if(count($baleAllowedMaterials) > 1)
                                            <div class="sm:col-span-2">
                                                <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Fabric Material *</label>
                                                <select wire:model.live="baleRollMaterials.{{ $i }}" wire:key="roll-mat-select-{{ $i }}" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                                    @foreach($baleAllowedMaterials as $matItem)
                                                        <option value="{{ $matItem['id'] }}">{{ $matItem['name'] }} ({{ $matItem['code'] }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif

                                        <div>
                                            <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Fabric Standard Width *</label>
                                            @php
                                                $availWidths = $this->getAvailableWidthsForMaterial($selectedMatId);
                                            @endphp
                                            <select wire:model.live="baleRollWidths.{{ $i }}" wire:key="roll-width-select-{{ $i }}-{{ $selectedMatId }}-{{ $baleRollWidths[$i] ?? '' }}" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                                @if($availWidths->isEmpty())
                                                    <option value="">Default Standard Width</option>
                                                @else
                                                    <option value="">— Select Standard Width —</option>
                                                    @foreach($availWidths as $fw)
                                                        @php
                                                            $fwId = (string) ($fw->id ?? ($fw->value ?? ''));
                                                            $fwName = $fw->name ?? (($fw->value ?? $fw->width_inches ?? '') . ' ' . ($fw->unit ?? 'Inch'));
                                                        @endphp
                                                        <option value="{{ $fwId }}">{{ $fwName }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Roll Length &amp; Unit *</label>
                                            <div class="flex items-center gap-1.5">
                                                <input type="number" step="0.01" wire:model.live.debounce.300ms="baleRollLengths.{{ $i }}" placeholder="Length" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                                @php
                                                    $availUnits = $this->getAvailableUnitsForMaterial($selectedMatId);
                                                @endphp
                                                <select wire:model.live="baleRollUnits.{{ $i }}" wire:key="roll-unit-select-{{ $i }}-{{ $selectedMatId }}" class="bg-surface border border-outline-variant/60 rounded-xl px-2 py-2 text-xs font-extrabold text-primary shrink-0 focus:outline-none">
                                                    @foreach($availUnits as $u)
                                                        <option value="{{ $u->id }}">{{ $u->short_code }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error("baleRollLengths.{$i}") <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span> @enderror
                                        </div>
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
