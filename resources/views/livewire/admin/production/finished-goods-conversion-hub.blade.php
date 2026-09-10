<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-800">MANUFACTURING MANAGEMENT</span>
            <h1 class="text-2xl font-black tracking-tight text-on-surface">Finished Goods Conversion &amp; Barcode Hub</h1>
            <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                Assemble manufacturing output into sellable Front-End Product bundles, generate unique lot barcodes, and verify full production audit details.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="openWizardModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-extrabold rounded-xl shadow-sm transition-all duration-200">
                <span class="material-symbols-outlined text-[18px]">add</span>
                <span>+ Convert Products</span>
            </button>
        </div>
    </div>

    <!-- Full-Width Scan & Audit Dark Card -->
    <div class="p-6 rounded-2xl text-white shadow-md space-y-3 relative z-10" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
        <label for="barcodeQueryInput" class="flex items-center gap-2 text-amber-400 font-bold text-sm cursor-pointer">
            <span class="material-symbols-outlined text-[20px]">search</span>
            <span>Scan Barcode &amp; Audit Product Details</span>
        </label>
        <p class="text-xs text-slate-300 max-w-3xl">
            Scan or enter any product barcode to instantly load its constituent manufacturing products, source production batch, packaging, and full unit costing breakdown.
        </p>

        <form wire:submit.prevent="searchBarcodeFromInput" class="flex flex-col sm:flex-row gap-3 max-w-xl pt-1">
            <div class="relative flex-1">
                <input type="text" id="barcodeQueryInput" wire:key="barcode-query-input" wire:model.live="barcodeQuery" placeholder="Enter / Scan Barcode (e.g. FG-DSG108-2026-0050)..." class="w-full px-4 py-2.5 bg-slate-800/90 border border-slate-700 text-xs font-mono font-bold rounded-xl text-white focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30 placeholder-slate-400 cursor-text select-text relative z-20" autofocus />
            </div>
            <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs rounded-xl transition-all shadow-sm shrink-0 cursor-pointer">
                Scan &amp; Audit
            </button>
        </form>
    </div>

    <!-- Main Converted Lots Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/50 shadow-sm overflow-hidden space-y-4 p-4">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <h2 class="text-xs font-extrabold uppercase tracking-wider text-amber-900">CONVERTED FINISHED GOODS BATCHES</h2>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/60 text-[18px]">search</span>
                    <input type="text" wire:model.live.debounce.300ms="searchBarcode" placeholder="Search Barcode, Design ID..." class="w-full pl-9 pr-4 py-1.5 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface" />
                </div>
                <button wire:click="openWizardModal" class="px-3.5 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-900 font-extrabold text-xs rounded-xl shadow-2xs shrink-0">
                    + New Conversion Entry
                </button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-outline-variant/40">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low/70 border-b border-outline-variant/60 text-[11px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                        <th class="py-3 px-4">Barcode / Lot #</th>
                        <th class="py-3 px-4">Storefront Category</th>
                        <th class="py-3 px-4">Design ID</th>
                        <th class="py-3 px-4 text-center">Converted Qty</th>
                        <th class="py-3 px-4">Date Converted</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30 text-xs font-medium text-on-surface">
                    @forelse($batches as $b)
                        <tr class="hover:bg-surface-container-low/40 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="font-mono font-bold text-primary text-xs">{{ $b->barcode }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-on-surface">
                                {{ $b->frontEndProduct?->category_display_name ?? 'Leaf Category' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded-full font-mono font-bold text-[11px] bg-amber-500/10 text-amber-800 border border-amber-500/20">
                                    {{ $b->design_id }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-bold">
                                {{ $b->converted_qty }} Pcs
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-on-surface-variant">
                                {{ $b->converted_date ? $b->converted_date->format('Y-m-d H:i') : $b->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2">
                                <button wire:click="openAuditModal({{ $b->id }})" class="p-1.5 text-on-surface-variant hover:text-primary hover:bg-surface-container-high rounded-lg transition-colors" title="Audit Lot Breakdown">
                                    <span class="material-symbols-outlined text-[18px]">info</span>
                                </button>
                                <button wire:click="openPrintBarcodeModal({{ $b->id }})" class="p-1.5 text-on-surface-variant hover:text-secondary hover:bg-surface-container-high rounded-lg transition-colors" title="Print Barcode Stickers">
                                    <span class="material-symbols-outlined text-[18px]">print</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-on-surface-variant italic text-xs">
                                No finished goods conversion batches recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($batches->hasPages())
            <div class="pt-2">
                {{ $batches->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL: FINISHED GOODS CONVERSION (2-STEP WIZARD) -->
    @if($showWizardModal)
        <div class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 flex items-center justify-center bg-slate-900/20 backdrop-blur-xs animate-fade-in" x-data x-trap.noscroll="true">
            <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/60 shadow-2xl w-full max-w-3xl max-h-[88vh] flex flex-col overflow-hidden my-auto animate-scale-up">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low/40">
                    <div>
                        <h2 class="text-base font-black text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">inventory_2</span>
                            @if($wizardStep === 1)
                                Finished Goods Conversion Wizard (Step 1 of 2)
                            @else
                                Design &amp; Barcode Confirmation (Step 2 of 2)
                            @endif
                        </h2>
                        <p class="text-xs text-on-surface-variant font-medium">
                            Assemble manufacturing output into sellable storefront products &amp; generate lot barcodes.
                        </p>
                    </div>
                    <button wire:click="$set('showWizardModal', false)" class="p-2 text-on-surface-variant hover:text-on-surface rounded-full hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 overflow-y-auto custom-scrollbar flex-1 max-h-[calc(88vh-130px)]">
                    @if($wizardStep === 1)
                        <!-- STEP 1: CONVERSION CONFIGURATION & STOCK BREAKDOWN -->
                        <!-- SECTION 1: TOP LEVEL SELECTORS -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 rounded-2xl bg-surface-container-low/50 border border-outline-variant/60">
                            <!-- Leaf Category Select -->
                            <div>
                                <label class="block text-[11px] font-extrabold uppercase tracking-wider text-on-surface mb-1.5">Leaf Category *</label>
                                <select wire:model.live="selectedCategoryId" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-extrabold">
                                    <option value="">Select Leaf Category...</option>
                                    @foreach($leafCategories as $cat)
                                        @php $isCfg = in_array($cat->id, $configuredCategoryIds); @endphp
                                        <option value="{{ $cat->id }}">{{ $cat->name }} {{ $isCfg ? '✓' : '(Not Configured)' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Target Quantity -->
                            <div>
                                <label class="block text-[11px] font-extrabold uppercase tracking-wider text-on-surface mb-1.5">Target Quantity (Sets) *</label>
                                <input type="number" min="1" wire:model.live="produceQty" class="w-full px-3 py-2 bg-surface-container-lowest text-xs font-mono font-bold rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface" />
                            </div>

                            <!-- Select Design Option (New vs Existing Design) -->
                            <div>
                                <label class="block text-[11px] font-extrabold uppercase tracking-wider text-on-surface mb-1.5">Select Design Option *</label>
                                <div class="flex rounded-xl bg-surface-container-lowest border border-outline-variant/60 p-1">
                                    <button type="button" wire:click="$set('designType', 'new')" class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-colors {{ $designType === 'new' ? 'bg-primary text-on-primary shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                        New Design
                                    </button>
                                    <button type="button" wire:click="$set('designType', 'existing')" class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-colors {{ $designType === 'existing' ? 'bg-primary text-on-primary shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                        Existing Design
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: DESIGN DETAILS (NEW vs EXISTING DESIGN) -->
                        <div class="p-5 rounded-2xl border-2 border-primary/20 bg-primary/5 space-y-4">
                            @if($designType === 'new')
                                <!-- IF NEW DESIGN -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1">Design ID / Number *</label>
                                        <input type="text" wire:model.live="designId" placeholder="e.g. DSG-108-GOLD" class="w-full px-3.5 py-2.5 bg-surface-container-lowest text-xs font-mono font-extrabold rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface" />
                                        <span class="text-[11px] text-on-surface-variant mt-1 block">e.g. DSG-108-GOLD, 5934</span>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1">Product Name (Auto-Prefilled)</label>
                                        <div class="px-3.5 py-2.5 bg-surface-container-lowest rounded-xl border border-outline-variant/60 font-extrabold text-xs text-primary flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px]">sell</span>
                                            <span>{{ $this->productTitlePrefill }}</span>
                                        </div>
                                        <span class="text-[11px] text-on-surface-variant mt-1 block">Prefilled with Design ID + Leaf Category</span>
                                    </div>
                                </div>
                            @else
                                <!-- IF EXISTING DESIGN -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1">Select Existing Product *</label>
                                        <select wire:model.live="selectedStorefrontProductId" class="w-full px-3.5 py-2.5 bg-surface-container-lowest text-xs font-bold rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface">
                                            <option value="">Select Product on this Leaf Category...</option>
                                            @foreach($this->availableStorefrontProducts as $p)
                                                <option value="{{ $p->id }}">{{ $p->title }} (Stock: {{ $p->stock_quantity }} Pcs)</option>
                                            @endforeach
                                        </select>
                                        <span class="text-[11px] text-on-surface-variant mt-1 block">Strictly lists products on '{{ $this->selectedCategoryName }}'</span>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1">Current Finished Goods Stock</label>
                                        <div class="px-4 py-2 bg-surface-container-lowest rounded-xl border border-outline-variant/60 flex items-center justify-between">
                                            <span class="text-xs font-medium text-on-surface-variant">Available Finished Goods:</span>
                                            <span class="font-mono font-black text-sm text-emerald-800 dark:text-emerald-300">
                                                {{ $this->selectedStorefrontProductStock }} Pcs
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>                        <!-- SECTION 2: MANUFACTURING PRODUCTS ITEM SELECTION -->
                        <div class="p-5 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-on-surface flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-primary text-[18px]">precision_manufacturing</span>
                                        Manufacturing Product Items Breakdown
                                    </h3>
                                    <p class="text-[11px] text-on-surface-variant">Select pattern / fabric design IDs &amp; specify quantities taken for each constituent item</p>
                                </div>
                            </div>

                            @if(!$this->categoryConfiguration || $this->categoryConfiguration->components->isEmpty())
                                <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs font-medium">
                                    Warning: Assembly rules for category '{{ $this->selectedCategoryName }}' have not been configured yet on Front-End Products page.
                                </div>
                            @else
                                <div class="space-y-4">
                                    @foreach($this->categoryConfiguration->components as $idx => $comp)
                                        @php
                                            $mfg = $comp->manufacturingProduct;
                                            $reqQty = $comp->quantity * $produceQty;
                                            $stockInfo = collect($stockCheck['mfgStock'])->firstWhere('component_index', $idx);
                                            $availStock = $stockInfo['available_stock'] ?? 0;
                                            $isEnough = $stockInfo['is_enough'] ?? true;
                                            $allocatedSum = $this->getComponentAllocatedQty($idx);
                                            $isSumValid = ($allocatedSum === $reqQty);
                                            $patRows = $componentSelections[$idx] ?? [['pattern_id' => '', 'quantity' => $reqQty]];
                                        @endphp
                                        <div class="p-4 rounded-xl bg-surface-container-lowest border {{ $isSumValid ? 'border-outline-variant/60' : 'border-rose-300 dark:border-rose-800' }} space-y-3">
                                            <!-- Component Header -->
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-outline-variant/40 pb-2">
                                                <div class="font-bold text-xs text-on-surface">
                                                    {{ $mfg?->name ?? 'Manufacturing Product Item' }}
                                                    <span class="text-on-surface-variant font-normal text-[11px]">(Required Qty: {{ $comp->quantity }} × {{ $produceQty }} = <strong class="text-on-surface font-mono">{{ $reqQty }} Pcs</strong>)</span>
                                                </div>
                                                <div>
                                                    @if($isEnough)
                                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-800 border border-emerald-500/30">
                                                            Stock Available: {{ $availStock }} Pcs
                                                        </span>
                                                    @else
                                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-700 border border-rose-500/30">
                                                            Shortage: {{ $availStock }} / {{ $reqQty }} Pcs
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Pattern Allocation Rows -->
                                            <div class="space-y-2.5">
                                                @foreach($patRows as $pIdx => $pRow)
                                                    <div class="grid grid-cols-12 gap-2 items-center">
                                                        <!-- Pattern Dropdown -->
                                                        <div class="col-span-7 sm:col-span-8">
                                                            <select wire:model.live="componentSelections.{{ $idx }}.{{ $pIdx }}.pattern_id" class="w-full px-3 py-2 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold">
                                                                <option value="">Default Pattern / Auto-allocate {{ $designId ? '(Design #' . $designId . ')' : '' }}</option>
                                                                @if($mfg && $mfg->patterns->isNotEmpty())
                                                                    @foreach($mfg->patterns as $pat)
                                                                        <option value="{{ $pat->id }}">
                                                                            {{ $pat->name }} (Width: {{ $pat->fabricWidth?->name ?? 'Std' }})
                                                                        </option>
                                                                    @endforeach
                                                                @endif
                                                            </select>
                                                        </div>

                                                        <!-- Pattern Quantity Input -->
                                                        <div class="col-span-4 sm:col-span-3">
                                                            <div class="relative">
                                                                <input type="number" min="0" wire:model.live="componentSelections.{{ $idx }}.{{ $pIdx }}.quantity" placeholder="Qty Pcs" class="w-full px-3 py-2 bg-surface-container-low text-xs font-mono font-bold rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface" />
                                                                <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] text-on-surface-variant font-bold">Pcs</span>
                                                            </div>
                                                        </div>

                                                        <!-- Remove Row Button -->
                                                        <div class="col-span-1 text-right">
                                                            @if(count($patRows) > 1)
                                                                <button type="button" wire:click="removePatternRow({{ $idx }}, {{ $pIdx }})" class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition-colors" title="Remove pattern option">
                                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <!-- Component Footer: Add Pattern Button & Validation Total -->
                                            <div class="flex items-center justify-between pt-2 border-t border-outline-variant/40">
                                                <button type="button" wire:click="addPatternRow({{ $idx }})" class="text-xs font-extrabold text-primary hover:text-primary/80 flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[16px]">add_circle</span>
                                                    <span>+ Add Pattern Allocation</span>
                                                </button>

                                                <div>
                                                    @if($isSumValid)
                                                        <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-500/10 text-emerald-800 border border-emerald-500/30 flex items-center gap-1">
                                                            <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                                            Allocated: {{ $allocatedSum }} / {{ $reqQty }} Pcs
                                                        </span>
                                                    @else
                                                        <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-rose-500/10 text-rose-700 border border-rose-500/30 flex items-center gap-1">
                                                            <span class="material-symbols-outlined text-[14px]">warning</span>
                                                            Allocated: {{ $allocatedSum }} / {{ $reqQty }} Pcs (Must match required total!)
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- SECTION 3: REQUIRED PACKAGING MATERIALS (CALCULATED) -->
                        @if($this->categoryConfiguration && $this->categoryConfiguration->packagingItems->isNotEmpty())
                            <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-3">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-on-surface flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-primary text-[18px]">package_2</span>
                                    Required Packaging Materials (Calculated)
                                </h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach($this->categoryConfiguration->packagingItems as $pkg)
                                        @php
                                            $pkgReq = $pkg->quantity * $produceQty;
                                            $pkgMat = $pkg->rawMaterial;
                                            $pkgStockInfo = collect($stockCheck['pkgStock'])->firstWhere('raw_material_id', $pkgMat?->id);
                                            $pkgAvail = $pkgStockInfo['available_stock'] ?? 0;
                                        @endphp
                                        <div class="p-3 rounded-xl bg-surface-container-lowest border border-outline-variant/60 flex items-center justify-between text-xs">
                                            <div>
                                                <div class="font-bold text-on-surface">{{ $pkgMat?->name ?? 'Packaging Material' }}</div>
                                                <div class="text-[11px] text-on-surface-variant">Qty per set: {{ $pkg->quantity }} × {{ $produceQty }} = <strong class="font-mono text-on-surface">{{ $pkgReq }}</strong></div>
                                            </div>
                                            <div class="text-right font-mono font-bold text-[11px] text-on-surface-variant">
                                                Stock: {{ $pkgAvail }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- SECTION 4: STOCK CHECK STATUS BAR -->
                        <div class="p-4 rounded-2xl border text-xs font-semibold flex items-center gap-3 {{ ($stockCheck['canProceed'] ?? true) ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-900 dark:text-emerald-200' : 'bg-amber-500/10 border-amber-500/30 text-amber-900 dark:text-amber-200' }}">
                            <span class="material-symbols-outlined text-[22px] {{ ($stockCheck['canProceed'] ?? true) ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ ($stockCheck['canProceed'] ?? true) ? 'check_circle' : 'warning' }}
                            </span>
                            <div>
                                <div class="font-bold text-xs">
                                    {{ ($stockCheck['canProceed'] ?? true) ? '✓ Stock Check Passed:' : 'Stock Warning / Shortage:' }}
                                </div>
                                <div class="text-[11px] opacity-90 mt-0.5">
                                    @if($stockCheck['canProceed'] ?? true)
                                        All required manufacturing products and packaging materials are available in factory inventory for {{ $produceQty }} Piece (Pcs).
                                    @else
                                        {{ implode(', ', $stockCheck['missingItems'] ?? []) }}
                                    @endif
                                </div>
                            </div>
                        </div>

                    @else
                        <!-- STEP 2: DESIGN & BARCODE CONFIRMATION -->
                        <div class="space-y-6">
                            <!-- SECTION 1: DESIGN ID & BARCODE PREVIEW -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @if($designType === 'new')
                                    <!-- DESIGN ID INPUT (NEW DESIGN ONLY) -->
                                    <div>
                                        <label class="block text-xs font-extrabold uppercase tracking-wider text-on-surface mb-1">Design ID * <span class="normal-case text-[11px] font-normal text-on-surface-variant">(Used in generating unique product barcode)</span></label>
                                        <input type="text" wire:model.live="designId" placeholder="e.g. DSG-108-GOLD" class="w-full px-3.5 py-2.5 bg-surface-container-lowest text-xs font-mono font-extrabold rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface placeholder:font-normal" />
                                        <span class="text-[11px] text-on-surface-variant mt-1 block">e.g. DSG-108-GOLD, 5934</span>
                                    </div>
                                @else
                                    <!-- EXISTING PRODUCT PREVIEW (NO DESIGN ID INPUT REQUIRED) -->
                                    <div>
                                        <label class="block text-xs font-extrabold uppercase tracking-wider text-on-surface mb-1">Selected Existing Product</label>
                                        <div class="px-4 py-2.5 bg-surface-container-lowest rounded-xl border border-outline-variant/60 font-extrabold text-xs text-primary flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px]">shopping_bag</span>
                                            <span>{{ $this->availableStorefrontProducts->firstWhere('id', $selectedStorefrontProductId)?->title ?? 'Storefront Product' }}</span>
                                        </div>
                                        <span class="text-[11px] text-on-surface-variant mt-1 block">Existing product already includes design specification.</span>
                                    </div>
                                @endif

                                <!-- GENERATED ENTRY BARCODE PREVIEW -->
                                <div>
                                    <label class="block text-xs font-extrabold uppercase tracking-wider text-on-surface mb-1">Generated Entry Barcode</label>
                                    <div class="px-4 py-2 bg-surface-container-lowest rounded-xl border border-outline-variant/60 flex items-center gap-3">
                                        <div class="flex items-center gap-0.5 opacity-80 shrink-0">
                                            <svg class="h-8 w-24 text-slate-800 dark:text-slate-200" viewBox="0 0 100 30" fill="currentColor">
                                                <rect x="2" y="0" width="3" height="30"/>
                                                <rect x="7" y="0" width="1" height="30"/>
                                                <rect x="10" y="0" width="4" height="30"/>
                                                <rect x="16" y="0" width="2" height="30"/>
                                                <rect x="20" y="0" width="5" height="30"/>
                                                <rect x="27" y="0" width="1" height="30"/>
                                                <rect x="30" y="0" width="3" height="30"/>
                                                <rect x="35" y="0" width="2" height="30"/>
                                                <rect x="39" y="0" width="4" height="30"/>
                                                <rect x="45" y="0" width="1" height="30"/>
                                                <rect x="48" y="0" width="3" height="30"/>
                                                <rect x="53" y="0" width="2" height="30"/>
                                                <rect x="57" y="0" width="5" height="30"/>
                                                <rect x="64" y="0" width="2" height="30"/>
                                                <rect x="68" y="0" width="4" height="30"/>
                                                <rect x="74" y="0" width="1" height="30"/>
                                                <rect x="77" y="0" width="3" height="30"/>
                                                <rect x="82" y="0" width="2" height="30"/>
                                                <rect x="86" y="0" width="4" height="30"/>
                                                <rect x="92" y="0" width="2" height="30"/>
                                                <rect x="96" y="0" width="2" height="30"/>
                                            </svg>
                                        </div>
                                        <div class="font-mono font-black text-xs text-primary tracking-wider truncate">
                                            {{ $this->generatedBarcode }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION 2: PHOTO OPTIONS (ONLY SHOWN IF CREATING NEW ITEM) -->
                            @if($designType === 'new')
                                <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <label class="text-xs font-bold text-on-surface block">Reuse Cutting-Stage Photo</label>
                                            <p class="text-[11px] text-on-surface-variant">Attach job photo taken during cutting/bale receiving</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" wire:model.live="reuseCuttingPhoto" class="sr-only peer">
                                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                                        </label>
                                    </div>

                                    @if(!$reuseCuttingPhoto)
                                        <div class="pt-2 border-t border-outline-variant/40">
                                            <label class="block text-xs font-extrabold uppercase tracking-wider text-on-surface mb-1.5">Upload Product Image (Optional)</label>
                                            <input type="file" wire:model="productImage" accept="image/*" class="w-full text-xs text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary file:text-on-primary hover:file:bg-primary/90 cursor-pointer" />
                                            @if($productImage)
                                                <div class="mt-2 text-[11px] text-emerald-600 font-bold flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                                    Image selected for upload
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-800 text-xs font-medium flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px]">photo_camera</span>
                                            <span>Will automatically attach cutting-stage/receiving photo recorded for constituent materials.</span>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- SECTION 3: CONVERSION SUMMARY CARD -->
                            <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-2">
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-on-surface">Conversion Summary:</h3>
                                <p class="text-xs text-on-surface-variant leading-relaxed">
                                    Converting output into <strong class="text-on-surface">{{ $produceQty }} Piece (Pcs)</strong> of <strong class="text-primary">{{ $this->productTitlePrefill ?: $this->selectedCategoryName }}</strong> under Design ID <strong class="font-mono text-on-surface">{{ $designId ?: '[Not Set]' }}</strong>. Barcode <strong class="font-mono text-amber-800 dark:text-amber-300 font-bold">{{ $this->generatedBarcode }}</strong> will be assigned.
                                </p>
                            </div>

                            <!-- REMARKS / NOTES -->
                            <div>
                                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1.5">Conversion Remarks / Notes (Optional)</label>
                                <textarea wire:model="notes" rows="2" placeholder="Optional notes for this finished goods batch..." class="w-full px-3.5 py-2.5 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface"></textarea>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-outline-variant/60 flex items-center justify-between bg-surface-container-low/40">
                    @if($wizardStep === 1)
                        <button wire:click="$set('showWizardModal', false)" class="px-4 py-2 bg-surface-container-high text-on-surface-variant hover:text-on-surface text-xs font-bold rounded-xl transition-colors">
                            Cancel
                        </button>
                        <button wire:click="goToStep2" class="px-5 py-2.5 bg-primary text-on-primary hover:bg-primary/90 text-xs font-extrabold rounded-xl shadow-sm transition-all flex items-center gap-2">
                            <span>Next: Design &amp; Barcode</span>
                            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                        </button>
                    @else
                        <div class="flex items-center gap-2">
                            <button wire:click="$set('showWizardModal', false)" class="px-4 py-2 bg-surface-container-high text-on-surface-variant hover:text-on-surface text-xs font-bold rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button wire:click="goToStep1" class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-800 text-xs font-bold rounded-xl transition-colors">
                                ← Back
                            </button>
                        </div>
                        <button wire:click="confirmAndExecuteConversion" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-xs rounded-xl shadow-sm transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                            <span>Confirm Conversion &amp; Generate Barcode</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL: PRINT BARCODE STICKERS -->
    @if($showPrintModal && $activePrintBatch)
        <div class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 flex items-center justify-center bg-slate-900/20 backdrop-blur-xs animate-fade-in" x-data x-trap.noscroll="true">
            <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/60 shadow-2xl w-full max-w-3xl max-h-[88vh] flex flex-col overflow-hidden my-auto animate-scale-up">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low/40">
                    <div>
                        <h2 class="text-base font-black text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-600 text-[20px]">print</span>
                            Print Barcode Stickers — <span class="font-mono text-primary">{{ $activePrintBatch->barcode }}</span>
                        </h2>
                        <p class="text-xs text-on-surface-variant font-medium">
                            {{ $activePrintBatch->frontEndProduct?->name ?? 'Finished Storefront Product' }} &bull; Design ID: {{ $activePrintBatch->design_id }}
                        </p>
                    </div>
                    <button wire:click="$set('showPrintModal', false)" class="p-2 text-on-surface-variant hover:text-on-surface rounded-full hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-5 overflow-y-auto custom-scrollbar flex-1 max-h-[calc(88vh-130px)]">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-surface-container-low/50 border border-outline-variant/60">
                        <div>
                            <label class="block text-[11px] font-extrabold uppercase tracking-wider text-on-surface mb-1.5">Number of Barcode Stickers to Generate</label>
                            <input type="number" min="1" max="500" wire:model.live="printStickerQty" class="w-full px-3.5 py-2 bg-surface-container-lowest text-xs font-mono font-bold rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface" />
                        </div>

                        <div>
                            <label class="block text-[11px] font-extrabold uppercase tracking-wider text-on-surface mb-1.5">Label Size Preset</label>
                            <select wire:model="printStickerSize" class="w-full px-3 py-2 bg-surface-container-lowest text-xs font-bold rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface">
                                <option value="Standard Sticker (50mm × 25mm)">Standard Sticker (50mm × 25mm)</option>
                                <option value="Compact Sticker (38mm × 19mm)">Compact Sticker (38mm × 19mm)</option>
                                <option value="Large Shipping Label (100mm × 50mm)">Large Shipping Label (100mm × 50mm)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Sticker Layout Preview -->
                    <div class="space-y-2">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-on-surface">Sticker Layout Preview</h3>
                        <div class="p-4 rounded-2xl bg-surface-container-low/70 border border-outline-variant/60 max-h-72 overflow-y-auto custom-scrollbar">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 print-area">
                                @for($i = 1; $i <= min(max(1, $printStickerQty), 50); $i++)
                                    <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-2xs space-y-1.5 text-slate-900">
                                        <div class="text-[9px] font-black uppercase tracking-wider text-slate-500">KANNODIA FACTORY CONSOLE</div>
                                        <div class="font-extrabold text-xs text-slate-900 leading-tight">
                                            {{ $activePrintBatch->frontEndProduct?->name ?? 'Finished Storefront Product' }}
                                        </div>
                                        <div class="text-[10px] text-slate-600 font-medium">
                                            SKU: {{ $activePrintBatch->frontEndProduct?->sku ?? 'KT-P-0052' }} &bull; Design: {{ $activePrintBatch->design_id }}
                                        </div>

                                        <!-- SVG Barcode lines -->
                                        <div class="py-1 flex justify-center">
                                            <svg class="h-9 w-full max-w-[200px] text-slate-950" viewBox="0 0 100 30" fill="currentColor">
                                                <rect x="2" y="0" width="3" height="30"/>
                                                <rect x="7" y="0" width="1" height="30"/>
                                                <rect x="10" y="0" width="4" height="30"/>
                                                <rect x="16" y="0" width="2" height="30"/>
                                                <rect x="20" y="0" width="5" height="30"/>
                                                <rect x="27" y="0" width="1" height="30"/>
                                                <rect x="30" y="0" width="3" height="30"/>
                                                <rect x="35" y="0" width="2" height="30"/>
                                                <rect x="39" y="0" width="4" height="30"/>
                                                <rect x="45" y="0" width="1" height="30"/>
                                                <rect x="48" y="0" width="3" height="30"/>
                                                <rect x="53" y="0" width="2" height="30"/>
                                                <rect x="57" y="0" width="5" height="30"/>
                                                <rect x="64" y="0" width="2" height="30"/>
                                                <rect x="68" y="0" width="4" height="30"/>
                                                <rect x="74" y="0" width="1" height="30"/>
                                                <rect x="77" y="0" width="3" height="30"/>
                                                <rect x="82" y="0" width="2" height="30"/>
                                                <rect x="86" y="0" width="4" height="30"/>
                                                <rect x="92" y="0" width="2" height="30"/>
                                                <rect x="96" y="0" width="2" height="30"/>
                                            </svg>
                                        </div>
                                        <div class="font-mono font-black text-xs text-center text-slate-950 tracking-wider">
                                            {{ $activePrintBatch->barcode }}
                                        </div>
                                        <div class="flex items-center justify-between text-[9px] text-slate-400 font-mono pt-1">
                                            <span>Lot #{{ $i }}</span>
                                            <span>{{ $activePrintBatch->created_at ? $activePrintBatch->created_at->format('d M Y') : date('d M Y') }}</span>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-outline-variant/60 flex items-center justify-between bg-surface-container-low/40">
                    <button wire:click="$set('showPrintModal', false)" class="px-4 py-2 bg-surface-container-high text-on-surface-variant hover:text-on-surface text-xs font-bold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button onclick="window.print()" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-sm transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">print</span>
                        <span>Print Labels Now</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL: AUDIT LOT BREAKDOWN -->
    @if($showAuditModal && $auditBatch)
        <div class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 flex items-center justify-center bg-slate-900/20 backdrop-blur-xs animate-fade-in" x-data x-trap.noscroll="true">
            <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/60 shadow-2xl w-full max-w-3xl max-h-[88vh] flex flex-col overflow-hidden my-auto animate-scale-up">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low/40">
                    <div>
                        <h2 class="text-base font-black text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[20px]">analytics</span>
                            Product Audit Breakdown — <span class="font-mono text-primary">{{ $auditBatch->barcode }}</span>
                        </h2>
                        <p class="text-xs text-on-surface-variant font-medium">
                            Category: {{ $auditBatch->frontEndProduct?->name ?? 'Finished Category' }} &bull; Design ID: {{ $auditBatch->design_id }} &bull; Converted Qty: {{ $auditBatch->converted_qty }} Pcs
                        </p>
                    </div>
                    <button wire:click="$set('showAuditModal', false)" class="p-2 text-on-surface-variant hover:text-on-surface rounded-full hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-6 overflow-y-auto custom-scrollbar flex-1 max-h-[calc(88vh-130px)]">
                    <!-- Barcode Info Header Card -->
                    <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="text-[10px] font-extrabold uppercase tracking-wider text-amber-800">STOREFRONT BARCODE ENTITY</div>
                            <div class="font-mono font-black text-lg text-primary">{{ $auditBatch->barcode }}</div>
                            <div class="text-xs text-on-surface-variant mt-0.5">
                                Converted Date: {{ $auditBatch->converted_date ? $auditBatch->converted_date->format('d M Y, H:i') : $auditBatch->created_at->format('d M Y, H:i') }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-500/10 text-emerald-800 border border-emerald-500/30">
                                Storefront Published
                            </span>
                            <button wire:click="openPrintBarcodeModal({{ $auditBatch->id }})" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-2xs flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px]">print</span>
                                <span>Print Stickers</span>
                            </button>
                        </div>
                    </div>

                    <!-- Constituent Manufacturing Product Items -->
                    <div class="space-y-3">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-on-surface flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-primary text-[18px]">precision_manufacturing</span>
                            Constituent Manufacturing Product Items Used
                        </h3>
                        <div class="overflow-x-auto rounded-xl border border-outline-variant/40">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-surface-container-low/70 border-b border-outline-variant/60 text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                                        <th class="py-2.5 px-3">Manufacturing Item</th>
                                        <th class="py-2.5 px-3">Source Job / Batch</th>
                                        <th class="py-2.5 px-3 text-right">Quantity Consumed</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/30 font-medium text-on-surface">
                                    @forelse($auditBatch->items as $item)
                                        <tr class="hover:bg-surface-container-low/30">
                                            <td class="py-2.5 px-3 font-bold text-on-surface">
                                                {{ $item->manufacturingProduct?->name ?? 'Manufacturing Product Item' }}
                                            </td>
                                            <td class="py-2.5 px-3 font-mono text-[11px] text-on-surface-variant">
                                                {{ $item->productionJob?->job_code ?? ($item->productionBatch?->batch_code ?? 'FIFO Stock Allocation') }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-bold text-primary">
                                                {{ $item->quantity_used }} Pcs
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-4 text-center text-on-surface-variant italic text-xs">No constituent items recorded.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Packaging Material Deductions -->
                    @if($auditBatch->packagingDeductions && $auditBatch->packagingDeductions->isNotEmpty())
                        <div class="space-y-3">
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-on-surface flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-primary text-[18px]">package_2</span>
                                Deducted Packaging Materials
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach($auditBatch->packagingDeductions as $pkg)
                                    <div class="p-3 rounded-xl bg-surface-container-lowest border border-outline-variant/60 flex items-center justify-between text-xs">
                                        <div class="font-bold text-on-surface">{{ $pkg->rawMaterial?->name ?? 'Packaging Material' }}</div>
                                        <div class="font-mono font-bold text-primary text-xs">{{ $pkg->quantity_deducted }} Pcs</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Costing Summary Grid -->
                    <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-2">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-on-surface">Unit Costing Summary</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div class="p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/40">
                                <div class="text-[10px] text-on-surface-variant font-bold">Fabric Cost</div>
                                <div class="font-mono font-bold text-on-surface mt-0.5">{{ $auditBatch->costing_summary['fabricCost'] ?? '₹310.00' }}</div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/40">
                                <div class="text-[10px] text-on-surface-variant font-bold">Labor Cost</div>
                                <div class="font-mono font-bold text-on-surface mt-0.5">{{ $auditBatch->costing_summary['laborCost'] ?? '₹42.00' }}</div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/40">
                                <div class="text-[10px] text-on-surface-variant font-bold">Packaging Cost</div>
                                <div class="font-mono font-bold text-on-surface mt-0.5">{{ $auditBatch->costing_summary['packagingCost'] ?? '₹10.00' }}</div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-primary/10 border border-primary/20">
                                <div class="text-[10px] text-primary font-bold">Total Unit Cost</div>
                                <div class="font-mono font-black text-primary mt-0.5">{{ $auditBatch->costing_summary['totalUnitCost'] ?? '₹362.00' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-outline-variant/60 flex items-center justify-end bg-surface-container-low/40">
                    <button wire:click="$set('showAuditModal', false)" class="px-5 py-2 bg-surface-container-high text-on-surface-variant hover:text-on-surface text-xs font-bold rounded-xl transition-colors">
                        Close Audit
                    </button>
                </div>
            </div>
        </div>
    @endif

    <style>
    @media print {
        body * {
            visibility: hidden;
        }
        .print-area, .print-area * {
            visibility: visible;
        }
        .print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 12px !important;
        }
    }
    </style>
</div>
