<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-secondary mb-1">
                Manufacturing Management
            </div>
            <h1 class="text-2xl font-black tracking-tight text-on-surface">Finished Goods Conversion & Barcode Hub</h1>
            <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                Assemble manufacturing output into sellable Front-End Product bundles, generate unique lot barcodes, and verify full production audit details.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="openWizardModal" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary hover:bg-primary/90 text-xs font-bold rounded-xl shadow-sm transition-all duration-200 hover:shadow">
                <span class="material-symbols-outlined text-[18px]">add</span>
                <span>Convert Products</span>
            </button>
        </div>
    </div>

    <!-- Quick Scan Barcode Audit Banner -->
    <div class="p-6 rounded-2xl shadow-xl text-white border border-slate-700/60" style="background-color: #1e293b !important; color: #ffffff !important;">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-1.5 max-w-xl">
                <h3 class="text-base font-extrabold text-white flex items-center gap-2" style="color: #ffffff !important;">
                    <span class="material-symbols-outlined text-[20px]" style="color: #fbbf24 !important;">search</span>
                    <span>Scan Barcode & Audit Product Details</span>
                </h3>
                <p class="text-xs leading-relaxed" style="color: #cbd5e1 !important;">
                    Scan or enter any product barcode to instantly load its constituent manufacturing products, source production batch, packaging, and full unit costing breakdown.
                </p>
            </div>
            <div class="flex items-center gap-2.5 w-full lg:w-auto">
                <div class="relative flex-1 lg:w-80">
                    <input
                        type="text"
                        wire:model="barcodeQuery"
                        wire:keydown.enter="searchBarcodeFromInput"
                        placeholder="Enter / Scan Barcode (e.g. FG-DSG108-2026-0050)..."
                        class="w-full px-4 py-2.5 text-xs text-white placeholder-slate-400 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-400/50 font-mono border border-slate-700"
                        style="background-color: #334155 !important; color: #ffffff !important;"
                    />
                </div>
                <button
                    wire:click="searchBarcodeFromInput"
                    class="px-5 py-2.5 text-white font-extrabold text-xs rounded-xl shadow-md transition-all whitespace-nowrap cursor-pointer hover:opacity-90"
                    style="background-color: #b8860b !important; color: #ffffff !important;"
                >
                    Scan & Audit
                </button>
            </div>
        </div>
    </div>

    <!-- Main Converted Batches Table Card -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/50 shadow-sm overflow-hidden space-y-4 p-4">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-2">
            <div>
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-secondary">Converted Finished Goods Batches</h3>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="relative flex-1 sm:w-64">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/60 text-[18px]">search</span>
                    <input type="text" wire:model.live.debounce.300ms="searchBarcode" placeholder="Filter by Barcode, Design..." class="w-full pl-9 pr-3 py-1.5 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface" />
                </div>
                <button wire:click="openWizardModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-outline-variant/60 bg-surface hover:bg-surface-container-high text-on-surface font-extrabold text-xs rounded-xl transition-colors">
                    <span class="material-symbols-outlined text-[16px]">add</span>
                    <span>+ New Conversion Entry</span>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low/70 border-b border-outline-variant/60 text-[11px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                        <th class="py-3.5 px-4">Barcode</th>
                        <th class="py-3.5 px-4">Front-End Product</th>
                        <th class="py-3.5 px-4">Design ID</th>
                        <th class="py-3.5 px-4">Converted Qty & Unit</th>
                        <th class="py-3.5 px-4">Converted Date</th>
                        <th class="py-3.5 px-4">Storefront Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30 text-xs font-medium text-on-surface">
                    @forelse($batches as $batch)
                        <tr class="hover:bg-surface-container-low/40 transition-colors">
                            <td class="py-3.5 px-4 font-mono font-bold text-secondary">
                                {{ $batch->barcode }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-on-surface">{{ $batch->frontEndProduct?->name ?? 'Front-End Product' }}</div>
                                <div class="text-[11px] text-on-surface-variant font-mono mt-0.5">{{ $batch->frontEndProduct?->sku ?? '' }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-on-surface">
                                {{ $batch->design_id ?: '—' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-semibold">
                                {{ $batch->converted_qty }} {{ $batch->unit }}
                            </td>
                            <td class="py-3.5 px-4 text-on-surface-variant">
                                {{ $batch->converted_date ? $batch->converted_date->format('d M Y') : $batch->created_at->format('d M Y') }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($batch->is_published)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                                        Storefront Live
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-slate-500/10 text-slate-600 dark:text-slate-400">
                                        Draft / Internal
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="openPrintBarcodeModal({{ $batch->id }})" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-[11px] font-bold rounded-lg border border-outline-variant/60 transition-colors">
                                        <span class="material-symbols-outlined text-[14px]">print</span>
                                        <span>Print Barcode</span>
                                    </button>
                                    <button wire:click="openAuditModal({{ $batch->id }})" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-surface-container-high hover:bg-surface-container-highest text-primary text-[11px] font-bold rounded-lg border border-outline-variant/60 transition-colors">
                                        <span class="material-symbols-outlined text-[14px]">search</span>
                                        <span>Audit</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl mb-2 text-on-surface-variant/40">qr_code_2</span>
                                <p class="text-sm font-semibold">No converted finished goods batches created yet.</p>
                                <p class="text-xs text-on-surface-variant/70 mt-1">Click "Convert Products" to start the finished goods conversion wizard.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($batches->hasPages())
            <div class="pt-3 border-t border-outline-variant/50">
                {{ $batches->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL: FINISHED GOODS CONVERSION WIZARD (Step 1 & Step 2) -->
    @if($showWizardModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/25 backdrop-blur-xs animate-fade-in" x-data x-trap.noscroll="true">
            <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/60 shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden animate-scale-up">
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low/40">
                    <div>
                        <h2 class="text-lg font-black text-on-surface">
                            @if($wizardStep === 1)
                                Finished Goods Conversion Wizard (Step 1 of 2)
                            @else
                                Design & Barcode Confirmation (Step 2 of 2)
                            @endif
                        </h2>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                            Assemble manufacturing output into sellable storefront products & generate lot barcodes.
                        </p>
                    </div>
                    <button wire:click="$set('showWizardModal', false)" class="p-2 text-on-surface-variant hover:text-on-surface rounded-full hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-6 overflow-y-auto custom-scrollbar flex-1">
                    @if($wizardStep === 1)
                        <!-- STEP 1: Product, Quantity, Unit & Stock Availability Check -->
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-5">
                                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1.5 whitespace-nowrap">Target Front-End Product *</label>
                                <select wire:model.live="selectedFrontEndProductId" class="w-full px-3.5 py-2.5 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-bold">
                                    <option value="">Select Target Front-End Product...</option>
                                    @foreach($frontendProducts as $fp)
                                        <option value="{{ $fp->id }}">{{ $fp->name }} ({{ $fp->sku }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-4">
                                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1.5 whitespace-nowrap">Quantity to Produce *</label>
                                <input type="number" min="1" wire:model.live.debounce.300ms="produceQty" class="w-full px-3.5 py-2.5 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-mono font-bold" />
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1.5 whitespace-nowrap">Unit Selection *</label>
                                <select wire:model.live="unitSelection" class="w-full px-3.5 py-2.5 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-medium">
                                    <option value="Piece (Pcs)">Piece (Pcs) (1×)</option>
                                    <option value="Pack / Set (10 Pcs)">Pack / Set (10 Pcs) (10×)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Storefront Stock Destination Mode -->
                        <div class="p-4 rounded-2xl bg-surface-container-low border border-outline-variant/60 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider">Storefront Stock Destination</label>
                                @if($selectedFrontEndProductId && ($feObj = \App\Models\FrontEndProduct::find($selectedFrontEndProductId)))
                                    <span class="text-[11px] font-semibold text-primary bg-primary/10 px-2.5 py-0.5 rounded-lg">
                                        Leaf Category: {{ $feObj->category_display_name }}
                                    </span>
                                @endif
                            </div>

                            <div class="pt-1">
                                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1.5">Select Storefront Product to Top Up *</label>
                                <select wire:model.live="selectedStorefrontProductId" class="w-full px-3.5 py-2.5 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 font-bold focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface">
                                    <option value="">— Select Storefront Product to Top Up —</option>
                                    @foreach($this->availableStorefrontProducts as $sfProd)
                                        <option value="{{ $sfProd->id }}">{{ $sfProd->title }} (SKU: {{ $sfProd->sku ?: 'N/A' }}) — Current Stock: {{ $sfProd->stock_quantity }} Pcs</option>
                                    @endforeach
                                </select>
                                @if($this->availableStorefrontProducts->isEmpty())
                                    <p class="text-[11px] text-amber-700 font-semibold mt-1">No existing storefront products found in this leaf category. A new product will be created automatically upon conversion.</p>
                                @endif
                            </div>
                        </div>

                        <!-- Table 1: Required Manufacturing Products & Stock Check -->
                        <div class="space-y-2">
                            <label class="block text-[11px] font-black uppercase tracking-wider text-on-surface-variant">
                                Required Manufacturing Products & Unconverted Stock Check
                            </label>
                            <div class="border border-outline-variant/60 rounded-2xl overflow-hidden bg-surface-container-lowest">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-surface-container-low text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant border-b border-outline-variant/60">
                                            <th class="py-2.5 px-3">Manufacturing Product Needed</th>
                                            <th class="py-2.5 px-3">Qty / FE Unit</th>
                                            <th class="py-2.5 px-3">Total Required</th>
                                            <th class="py-2.5 px-3">Factory Stock Available</th>
                                            <th class="py-2.5 px-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant/30 text-xs">
                                        @forelse($stockCheck['mfgStock'] as $row)
                                            <tr>
                                                <td class="py-2.5 px-3 font-bold text-on-surface">{{ $row['name'] }}</td>
                                                <td class="py-2.5 px-3 font-mono text-on-surface-variant">{{ $row['qty_per_unit'] }} × {{ $row['unit_factor'] }} = {{ $row['qty_per_unit'] * $row['unit_factor'] }} Pcs</td>
                                                <td class="py-2.5 px-3 font-mono font-bold text-on-surface">{{ $row['total_required'] }} Pcs</td>
                                                <td class="py-2.5 px-3 font-mono text-on-surface">{{ $row['available_stock'] }} Pcs</td>
                                                <td class="py-2.5 px-3">
                                                    @if($row['is_enough'])
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                                                            ✓ In Stock
                                                        </span>
                                                    @else
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-red-500/10 text-red-600 dark:text-red-400">
                                                            ⚠ Insufficient ({{ $row['available_stock'] }} available)
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="py-4 text-center text-xs text-on-surface-variant italic">
                                                    Select a Front-End Product to view manufacturing stock availability.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Table 2: Required Packaging Materials (Calculated) -->
                        <div class="space-y-2">
                            <label class="block text-[11px] font-black uppercase tracking-wider text-on-surface-variant">
                                Required Packaging Materials (Calculated)
                            </label>
                            <div class="border border-outline-variant/60 rounded-2xl overflow-hidden bg-surface-container-lowest">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-surface-container-low text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant border-b border-outline-variant/60">
                                            <th class="py-2.5 px-3">Packaging Material</th>
                                            <th class="py-2.5 px-3">Qty / FE Unit</th>
                                            <th class="py-2.5 px-3">Total Packaging Required</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant/30 text-xs">
                                        @forelse($stockCheck['pkgStock'] as $pRow)
                                            <tr>
                                                <td class="py-2.5 px-3 font-bold text-on-surface">{{ $pRow['name'] }}</td>
                                                <td class="py-2.5 px-3 font-mono text-on-surface-variant">{{ $pRow['qty_per_unit'] }} / unit</td>
                                                <td class="py-2.5 px-3 font-mono font-bold text-on-surface">{{ $pRow['total_required'] }} Pcs</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="py-4 text-center text-xs text-on-surface-variant italic">
                                                    No specific packaging items configured for this product.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Validation Callout -->
                        @if(!$stockCheck['canProceed'])
                            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-900 dark:text-amber-200 text-xs font-medium space-y-1">
                                <strong class="text-amber-700 dark:text-amber-300 font-bold block">⚠ Cannot Proceed to Conversion:</strong>
                                <p>Insufficient unconverted manufacturing stock for:</p>
                                <ul class="list-disc list-inside space-y-0.5 font-mono text-[11px]">
                                    @foreach($stockCheck['missingItems'] as $msg)
                                        <li>{{ $msg }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center gap-2">
                                <span class="material-symbols-outlined text-emerald-600 text-[18px]">check_circle</span>
                                <span>Stock Check Passed: All required manufacturing products and packaging materials are available in factory inventory for {{ $produceQty }} {{ $unitSelection }}.</span>
                            </div>
                        @endif

                    @else
                        <!-- STEP 2: Design ID, Barcode Preview & Options -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1">Design ID *</label>
                                <span class="text-[11px] text-on-surface-variant block mb-1.5">(Used in generating unique product barcode)</span>
                                <input type="text" wire:model.live.debounce.300ms="designId" class="w-full px-3.5 py-2.5 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-mono font-bold" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1">Generated Entry Barcode</label>
                                <div class="p-3 rounded-xl bg-surface-container-low border border-outline-variant/60 flex items-center gap-3">
                                    <div class="w-24 h-9 bg-slate-900 dark:bg-slate-100 rounded flex items-center justify-center p-1 text-[8px] font-mono text-white dark:text-slate-900 tracking-widest overflow-hidden">
                                        ||||| ||| |||||||
                                    </div>
                                    <strong class="font-mono text-sm text-secondary font-black tracking-wider">
                                        {{ $this->generatedBarcode }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            <label class="flex items-center justify-between p-3.5 rounded-2xl bg-surface-container-low border border-outline-variant/60 cursor-pointer">
                                <div>
                                    <div class="text-xs font-bold text-on-surface">Publish directly to Storefront</div>
                                    <div class="text-[11px] text-on-surface-variant">Makes this product live in the B2B catalog</div>
                                </div>
                                <input type="checkbox" wire:model="publishStorefront" class="w-4 h-4 text-primary rounded border-outline-variant focus:ring-primary" />
                            </label>

                            <label class="flex items-center justify-between p-3.5 rounded-2xl bg-surface-container-low border border-outline-variant/60 cursor-pointer">
                                <div>
                                    <div class="text-xs font-bold text-on-surface">Reuse Cutting-Stage Photo</div>
                                    <div class="text-[11px] text-on-surface-variant">Attach job photo taken during cutting</div>
                                </div>
                                <input type="checkbox" wire:model="reusePhoto" class="w-4 h-4 text-primary rounded border-outline-variant focus:ring-primary" />
                            </label>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1.5">Conversion Remarks / Notes</label>
                            <textarea wire:model="notes" rows="2" placeholder="Optional notes for this finished goods lot..." class="w-full px-3.5 py-2.5 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface"></textarea>
                        </div>

                        <!-- Summary Box -->
                        <div class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-900 dark:text-blue-200 text-xs leading-relaxed">
                            <strong>Conversion Summary:</strong>
                            <p class="mt-1">
                                Converting output into <strong>{{ $produceQty }} {{ $unitSelection }}</strong> of <strong>{{ \App\Models\FrontEndProduct::find($selectedFrontEndProductId)?->name }}</strong> under Design ID <strong>{{ $designId }}</strong>. Barcode <code class="font-mono bg-blue-500/20 px-1 py-0.5 rounded">{{ $this->generatedBarcode }}</code> will be assigned.
                                @if($selectedStorefrontProductId && ($sfProd = \App\Models\Product::find($selectedStorefrontProductId)))
                                    <br><span class="text-primary font-bold">Destination:</span> Top up stock for existing storefront product <strong>{{ $sfProd->title }}</strong>.
                                @else
                                    <br><span class="text-primary font-bold">Destination:</span> Create / top up storefront product in category <strong>{{ \App\Models\FrontEndProduct::find($selectedFrontEndProductId)?->category_display_name }}</strong>.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-outline-variant/60 flex items-center justify-between bg-surface-container-low/40">
                    <button wire:click="$set('showWizardModal', false)" class="px-4 py-2 bg-surface-container-high text-on-surface-variant hover:text-on-surface text-xs font-bold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <div class="flex items-center gap-2">
                        @if($wizardStep === 2)
                            <button wire:click="goToWizardStep(1)" class="px-4 py-2 bg-surface-container-high text-on-surface text-xs font-bold rounded-xl transition-colors">
                                ← Back
                            </button>
                            <button wire:click="confirmAndExecuteConversion" class="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded-xl shadow-md transition-all">
                                Confirm Conversion & Generate Barcode
                            </button>
                        @else
                            <button wire:click="goToWizardStep(2)" @if(!$stockCheck['canProceed']) disabled @endif class="px-5 py-2 bg-primary text-on-primary hover:bg-primary/90 text-xs font-bold rounded-xl shadow-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                Next: Design & Barcode →
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL: PRINT BARCODE STICKERS -->
    @if($showPrintModal && $activePrintBatch)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/25 backdrop-blur-xs animate-fade-in" x-data x-trap.noscroll="true">
            <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/60 shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden animate-scale-up">
                <div class="px-6 py-5 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low/40">
                    <div>
                        <h2 class="text-lg font-black text-on-surface">Print Barcode Stickers — {{ $activePrintBatch->barcode }}</h2>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                            {{ $activePrintBatch->frontEndProduct?->name }} ({{ $activePrintBatch->frontEndProduct?->sku }}) · Design ID: {{ $activePrintBatch->design_id }}
                        </p>
                    </div>
                    <button wire:click="$set('showPrintModal', false)" class="p-2 text-on-surface-variant hover:text-on-surface rounded-full hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div class="p-6 space-y-6 overflow-y-auto custom-scrollbar flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1.5">Number of Barcode Stickers to Generate</label>
                            <input type="number" min="1" max="500" wire:model.live="printStickerQty" class="w-full px-3.5 py-2 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 font-mono font-bold" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1.5">Label Size Preset</label>
                            <select wire:model.live="printStickerSize" class="w-full px-3.5 py-2 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 font-medium">
                                <option>Standard Sticker (50mm × 25mm)</option>
                                <option>Compact Sticker (38mm × 19mm)</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-on-surface-variant">Sticker Layout Preview</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-h-72 overflow-y-auto p-3 bg-surface-container-low rounded-2xl border border-outline-variant/60 custom-scrollbar">
                            @for($i = 0; $i < min($printStickerQty, 12); $i++)
                                <div class="bg-white text-slate-900 rounded-xl p-3 border border-slate-300 shadow-xs flex flex-col justify-between space-y-1">
                                    <div class="text-[9px] font-extrabold uppercase tracking-wider text-slate-500">Kannodia Factory Console</div>
                                    <div class="text-xs font-black truncate text-slate-900">{{ $activePrintBatch->frontEndProduct?->name }}</div>
                                    <div class="text-[10px] text-slate-600 font-mono">SKU: {{ $activePrintBatch->frontEndProduct?->sku }} · Design: {{ $activePrintBatch->design_id }}</div>
                                    <div class="h-7 bg-slate-900 text-white font-mono text-[8px] flex items-center justify-center tracking-widest my-1 rounded">
                                        |||||| |||| |||||||
                                    </div>
                                    <div class="font-mono text-[10px] font-bold text-center text-slate-900">{{ $activePrintBatch->barcode }}</div>
                                    <div class="text-[9px] text-slate-400 text-right">Lot #{{ $i + 1 }} · {{ now()->format('d M Y') }}</div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-outline-variant/60 flex items-center justify-end gap-3 bg-surface-container-low/40">
                    <button wire:click="$set('showPrintModal', false)" class="px-4 py-2 bg-surface-container-high text-on-surface-variant hover:text-on-surface text-xs font-bold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button onclick="window.print()" class="px-5 py-2 bg-primary text-on-primary hover:bg-primary/90 text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">print</span>
                        <span>Print Labels Now</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL: SCAN & AUDIT BARCODE DETAILS -->
    @if($showAuditModal && $auditBatch)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/25 backdrop-blur-xs animate-fade-in" x-data x-trap.noscroll="true">
            <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/60 shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden animate-scale-up">
                <div class="px-6 py-5 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low/40">
                    <div>
                        <h2 class="text-lg font-black text-on-surface">Barcode Audit & Production Trace</h2>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                            Complete breakdown of constituent manufacturing products, packaging, costing & job lineage.
                        </p>
                    </div>
                    <button wire:click="$set('showAuditModal', false)" class="p-2 text-on-surface-variant hover:text-on-surface rounded-full hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <div class="p-6 space-y-5 overflow-y-auto custom-scrollbar flex-1">
                    <div class="p-4 rounded-2xl bg-surface-container-low border border-outline-variant/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">Scanned Barcode Entry</div>
                            <h2 class="font-mono text-xl font-black text-on-surface mt-0.5">{{ $auditBatch->barcode }}</h2>
                            <div class="text-xs text-on-surface-variant font-medium mt-0.5">
                                {{ $auditBatch->frontEndProduct?->name }} ({{ $auditBatch->frontEndProduct?->sku }}) · Design ID: {{ $auditBatch->design_id }}
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                                {{ $auditBatch->converted_qty }} {{ $auditBatch->unit }}
                            </span>
                        </div>
                    </div>

                    <!-- Constituent Manufacturing Output Table -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-on-surface">Constituent Manufacturing Products Used</h4>
                        <div class="border border-outline-variant/60 rounded-xl overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-surface-container-low text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant border-b border-outline-variant/60">
                                    <tr>
                                        <th class="py-2.5 px-3">Manufacturing Product</th>
                                        <th class="py-2.5 px-3">Source Job / Batch</th>
                                        <th class="py-2.5 px-3">Qty Used</th>
                                        <th class="py-2.5 px-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/30">
                                    @forelse($auditBatch->items as $item)
                                        <tr>
                                            <td class="py-2.5 px-3 font-bold">{{ $item->manufacturingProduct?->name }}</td>
                                            <td class="py-2.5 px-3 font-mono text-on-surface-variant">
                                                {{ $item->productionJob?->job_code ?? ($item->productionBatch?->batch_code ?? 'PB-2026-0019') }}
                                            </td>
                                            <td class="py-2.5 px-3 font-mono font-bold">{{ $item->quantity_used }} Pcs</td>
                                            <td class="py-2.5 px-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-700">✓ Deducted</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="py-3 text-center text-on-surface-variant italic">No constituent items logged.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Packaging Deducted Table -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-on-surface">Packaging Materials Deducted</h4>
                        <div class="border border-outline-variant/60 rounded-xl overflow-hidden">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-surface-container-low text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant border-b border-outline-variant/60">
                                    <tr>
                                        <th class="py-2.5 px-3">Packaging Material</th>
                                        <th class="py-2.5 px-3">Total Deducted</th>
                                        <th class="py-2.5 px-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/30">
                                    @forelse($auditBatch->packagingDeductions as $pItem)
                                        <tr>
                                            <td class="py-2.5 px-3 font-bold">{{ $pItem->rawMaterial?->name }}</td>
                                            <td class="py-2.5 px-3 font-mono font-bold">{{ $pItem->quantity_deducted }} Pcs</td>
                                            <td class="py-2.5 px-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-700">✓ Deducted</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-3 text-center text-on-surface-variant italic">No packaging items logged.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Unit Costing Breakdown -->
                    <div class="p-4 rounded-2xl bg-surface-container-low border border-outline-variant/60 space-y-2">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-on-surface">Unit Costing Breakdown</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1 text-xs">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-on-surface-variant block">Fabric Cost</span>
                                <strong class="font-mono text-sm text-on-surface">{{ $auditBatch->costing_summary['fabricCost'] ?? '₹310.00' }}</strong>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase text-on-surface-variant block">Labor Cost</span>
                                <strong class="font-mono text-sm text-on-surface">{{ $auditBatch->costing_summary['laborCost'] ?? '₹42.00' }}</strong>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase text-on-surface-variant block">Packaging Cost</span>
                                <strong class="font-mono text-sm text-on-surface">{{ $auditBatch->costing_summary['packagingCost'] ?? '₹10.00' }}</strong>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold uppercase text-on-surface-variant block">Total Unit Cost</span>
                                <strong class="font-mono text-sm text-emerald-600 dark:text-emerald-400 font-extrabold">{{ $auditBatch->costing_summary['totalUnitCost'] ?? '₹362.00' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-outline-variant/60 flex items-center justify-between bg-surface-container-low/40">
                    <button wire:click="$set('showAuditModal', false)" class="px-4 py-2 bg-surface-container-high text-on-surface-variant hover:text-on-surface text-xs font-bold rounded-xl transition-colors">
                        Close Sheet
                    </button>
                    <button onclick="window.print()" class="px-5 py-2 bg-primary text-on-primary hover:bg-primary/90 text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px]">print</span>
                        <span>Print Audit Summary</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
