<div>
    <!-- Breadcrumb & Title -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <nav class="flex items-center gap-2 text-on-surface-variant mb-2">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="font-label-sm text-xs text-on-surface-variant hover:text-primary transition-all">Dashboard</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a href="{{ route('factory.raw-materials.index') }}" wire:navigate class="font-label-sm text-xs text-on-surface-variant hover:text-primary transition-all">Raw Materials</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="font-label-sm text-xs text-primary font-bold">Purchase Entry</span>
            </nav>
            <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Raw Material Purchase Entry</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Record a new raw material procurement invoice and generate its inventory batch.</p>
        </div>
        <a href="{{ route('factory.raw-materials.index') }}" wire:navigate class="inline-flex items-center gap-2 border border-outline-variant/60 hover:bg-surface-container-high/30 text-on-surface px-5 py-2.5 rounded-xl font-bold text-xs shadow-sm transition-all active:scale-95">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to Master
        </a>
    </div>

    <form wire:submit="savePurchaseEntry">
        <div class="w-full space-y-6">
            @if ($errors->any())
                <div class="p-4 rounded-xl bg-error/10 border border-error/30 text-error flex items-start gap-3 shadow-xs">
                    <span class="material-symbols-outlined text-[22px] shrink-0 mt-0.5">error</span>
                    <div>
                        <h4 class="font-bold text-sm">Please correct the missing or invalid field(s) before saving:</h4>
                        <ul class="list-disc list-inside text-xs mt-1.5 space-y-1 font-medium">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Card 1: Purchase Information -->
            <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/60 p-6 shadow-xs">
                <div class="flex items-center gap-2 mb-6 text-primary">
                    <span class="material-symbols-outlined text-[20px] font-bold">receipt_long</span>
                    <h3 class="font-headline-sm text-headline-sm font-extrabold">Purchase Information</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="supplier-id" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Supplier <span class="text-error">*</span></label>
                        <select
                            id="supplier-id"
                            wire:model.live="supplier_id"
                            class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none font-semibold"
                        >
                            <option value="">— Select Supplier —</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">
                                    {{ $sup->name }} @if($sup->whatsapp_number || $sup->phone) — ({{ $sup->whatsapp_number ?? $sup->phone }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_name') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                        @error('supplier_id') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="purchase-date" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Purchase Date <span class="text-error">*</span></label>
                        <input
                            id="purchase-date"
                            type="date"
                            wire:model="purchase_date"
                            class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none font-semibold"
                        />
                        @error('purchase_date') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="invoice-number" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Invoice Number <span class="text-error">*</span></label>
                        <input
                            id="invoice-number"
                            type="text"
                            wire:model="invoice_number"
                            placeholder="e.g., INV-2026-991"
                            class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none font-mono font-bold"
                        />
                        @error('invoice_number') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="lot-number" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Lot Number <span class="text-error">*</span></label>
                        <input
                            id="lot-number"
                            type="text"
                            wire:model="lot_number"
                            placeholder="e.g., LOT-2026-014"
                            class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none font-mono font-bold text-primary"
                        />
                        @error('lot_number') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

                <!-- Card 2: Raw Material Selection -->
                <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/60 p-6 shadow-xs">
                    <div class="flex items-center gap-2 mb-6 text-primary">
                        <span class="material-symbols-outlined text-[20px] font-bold">inventory</span>
                        <h3 class="font-headline-sm text-headline-sm font-extrabold">Raw Material Category Selection</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="category-picker" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Select Raw Material Category <span class="text-error">*</span></label>
                            <select
                                id="category-picker"
                                wire:model.live="raw_material_category_id"
                                class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none font-bold"
                            >
                                <option value="">— Select Category —</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }} ({{ $cat->code }})</option>
                                @endforeach
                            </select>
                            @error('raw_material_category_id') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Non-Fabric Single Material Picker -->
                        @if($raw_material_category_id && $unitType !== 'length_based')
                            <div>
                                <label for="material-picker" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Select Raw Material Item <span class="text-error">*</span></label>
                                <select
                                    id="material-picker"
                                    wire:model.live="raw_material_id"
                                    class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none font-semibold"
                                >
                                    <option value="">— Select Material Item —</option>
                                    @foreach($availableMaterials as $material)
                                        <option value="{{ $material->id }}">{{ $material->name }} ({{ $material->code }})</option>
                                    @endforeach
                                </select>
                                @error('raw_material_id') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>

                    <!-- Dynamic Purchase Fields -->
                    @if($raw_material_category_id)
                        @if($unitType === 'length_based')
                            <!-- Fabric Bale Configuration -->
                            <div class="bg-primary/5 border border-primary/20 rounded-2xl p-5 mb-6 space-y-5">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-primary/15 pb-3">
                                    <div>
                                        <div class="flex items-center gap-2 text-primary font-extrabold text-xs uppercase tracking-wider">
                                            <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                                            <span>Fabric Bale Purchase Details</span>
                                        </div>
                                        <p class="text-[11px] text-on-surface-variant/80 font-medium mt-0.5">
                                            Each bale receives its own fabric raw material item, design number, stock ID, and quantity breakdown.
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="num-bales" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5 whitespace-nowrap">
                                            Number of Bales *
                                        </label>
                                        <input
                                            id="num-bales"
                                            type="number"
                                            min="1"
                                            max="100"
                                            wire:model.live="num_bales"
                                            placeholder="e.g., 2"
                                            class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2.5 font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none font-bold"
                                        />
                                        @error('num_bales') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <!-- Dynamic Bale Items Cards Grid -->
                                <div class="pt-3 border-t border-primary/15 space-y-5">
                                    <div class="flex justify-between items-center">
                                        <label class="block font-label-md text-xs font-bold text-primary uppercase tracking-wider">
                                            Bale Item Breakdown &amp; Barcode Identification
                                        </label>
                                        <span class="text-[11px] font-bold text-on-surface-variant/80">
                                            Select fabric item, enter design number, stock ID, length &amp; photo per item line
                                        </span>
                                    </div>

                                    <div class="space-y-6">
                                        @foreach($bale_items as $baleIndex => $bale)
                                            @php
                                                $baleSubtotal = 0.0;
                                                $baleTotalQty = 0.0;
                                                foreach ($bale['items'] ?? [] as $it) {
                                                    $l = floatval($it['declared_length'] ?? 0);
                                                    $r = floatval(($it['cost_per_unit'] !== '' && $it['cost_per_unit'] !== null) ? $it['cost_per_unit'] : ($purchase_rate ?: 0));
                                                    $baleSubtotal += ($l * $r);
                                                    $baleTotalQty += $l;
                                                }
                                            @endphp
                                            <div class="bg-surface rounded-2xl p-5 border border-outline-variant/60 shadow-2xs space-y-4">
                                                <!-- Bale Header & Number -->
                                                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-outline-variant/30 pb-3 gap-3">
                                                    <div class="flex items-center gap-3 flex-1">
                                                        <span class="w-7 h-7 rounded-full bg-primary text-on-primary font-mono text-xs font-bold flex items-center justify-center shrink-0">
                                                            {{ $baleIndex + 1 }}
                                                        </span>
                                                        <div class="flex items-center gap-2 flex-1 max-w-xs">
                                                            <label class="text-xs font-extrabold text-primary shrink-0 uppercase tracking-wider">Bale Number *</label>
                                                            <input 
                                                                type="text" 
                                                                wire:model.live="bale_items.{{ $baleIndex }}.bale_number" 
                                                                placeholder="BALE-{{ date('Y') }}-{{ str_pad($baleIndex + 1, 4, '0', STR_PAD_LEFT) }}" 
                                                                class="w-full bg-surface-container-low border border-outline-variant/40 rounded-xl px-3 py-1.5 text-xs font-mono font-bold text-primary focus:border-primary focus:outline-none"
                                                            />
                                                        </div>
                                                        @error("bale_items.{$baleIndex}.bale_number")
                                                            <p class="text-error text-[10px] font-semibold mt-1">{{ $message }}</p>
                                                        @enderror
                                                    </div>

                                                    <div class="flex items-center gap-4 justify-between sm:justify-end">
                                                        <div class="text-xs font-bold text-on-surface-variant">
                                                            <span class="text-on-surface-variant/70">Bale Total:</span> 
                                                            <span class="font-mono font-bold text-primary">{{ number_format($baleTotalQty, 2) }} {{ $unitName }}</span>
                                                            <span class="mx-1 text-outline-variant">•</span>
                                                            <span class="font-mono font-bold text-primary">₹{{ number_format($baleSubtotal, 2) }}</span>
                                                        </div>
                                                        @if(count($bale_items) > 1)
                                                            <button 
                                                                type="button" 
                                                                wire:click="removeBale({{ $baleIndex }})" 
                                                                title="Remove entire bale"
                                                                class="w-7 h-7 rounded-lg text-on-surface-variant/60 hover:text-error hover:bg-error/10 flex items-center justify-center transition-colors cursor-pointer"
                                                            >
                                                                <span class="material-symbols-outlined text-[18px]">close</span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Item Lines inside Bale -->
                                                <div class="space-y-3">
                                                    @foreach($bale['items'] ?? [] as $itemIndex => $item)
                                                        <div class="p-3.5 rounded-xl bg-surface-container-low/60 border border-outline-variant/30 relative space-y-3">
                                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                                                                <!-- Item Select -->
                                                                <div class="lg:col-span-3">
                                                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Item *</label>
                                                                    <select wire:model.live="bale_items.{{ $baleIndex }}.items.{{ $itemIndex }}.raw_material_id" 
                                                                            class="w-full bg-surface border border-outline-variant/40 rounded-xl px-3 py-2 text-xs font-bold text-primary focus:border-primary focus:outline-none">
                                                                        <option value="">— Select Item —</option>
                                                                        @foreach($availableMaterials as $mat)
                                                                            <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->code }})</option>
                                                                        @endforeach
                                                                    </select>
                                                                    @error("bale_items.{$baleIndex}.items.{$itemIndex}.raw_material_id")
                                                                        <p class="text-error text-[10px] font-semibold mt-1">{{ $message }}</p>
                                                                    @enderror
                                                                </div>

                                                                <!-- Design Number -->
                                                                <div class="lg:col-span-2">
                                                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Design No</label>
                                                                    <input type="text" 
                                                                           wire:model.live="bale_items.{{ $baleIndex }}.items.{{ $itemIndex }}.design_number" 
                                                                           placeholder="e.g. D-114" 
                                                                           class="w-full bg-surface border border-outline-variant/40 rounded-xl px-3 py-2 text-xs font-mono font-bold text-on-surface focus:border-primary focus:outline-none" />
                                                                </div>

                                                                <!-- Quantity / Length -->
                                                                <div class="lg:col-span-2">
                                                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Qty ({{ $unitName }}) *</label>
                                                                    <input type="number" 
                                                                           step="0.01" 
                                                                           wire:model.live="bale_items.{{ $baleIndex }}.items.{{ $itemIndex }}.declared_length" 
                                                                           placeholder="0.00" 
                                                                           class="w-full bg-surface border border-outline-variant/40 rounded-xl px-3 py-2 text-xs font-mono font-extrabold text-on-surface focus:border-primary focus:outline-none" />
                                                                    @error("bale_items.{$baleIndex}.items.{$itemIndex}.declared_length")
                                                                        <p class="text-error text-[10px] font-semibold mt-1">{{ $message }}</p>
                                                                    @enderror
                                                                </div>

                                                                <!-- Cost Per Unit -->
                                                                <div class="lg:col-span-2">
                                                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Cost Per Unit (₹) <span class="text-error">*</span></label>
                                                                    <div class="relative">
                                                                        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-bold text-on-surface-variant/60">₹</span>
                                                                        <input type="number" 
                                                                               step="0.01" 
                                                                               wire:model.live="bale_items.{{ $baleIndex }}.items.{{ $itemIndex }}.cost_per_unit" 
                                                                               placeholder="{{ number_format(floatval($purchase_rate ?: 0), 2) }}" 
                                                                               class="w-full bg-surface border border-outline-variant/40 rounded-xl pl-6 pr-3 py-2 text-xs font-mono font-bold text-on-surface focus:border-primary focus:outline-none" />
                                                                    </div>
                                                                    @error("bale_items.{$baleIndex}.items.{$itemIndex}.cost_per_unit")
                                                                        <p class="text-error text-[10px] font-semibold mt-1">{{ $message }}</p>
                                                                    @enderror
                                                                </div>

                                                                <!-- Stock ID -->
                                                                <div class="lg:col-span-2">
                                                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Stock ID</label>
                                                                    <input type="text" 
                                                                           wire:model.live="bale_items.{{ $baleIndex }}.items.{{ $itemIndex }}.stock_id" 
                                                                           placeholder="e.g. STK-201-001" 
                                                                           class="w-full bg-surface border border-outline-variant/40 rounded-xl px-3 py-2 text-xs font-mono font-bold text-primary focus:border-primary focus:outline-none" />
                                                                </div>

                                                                <!-- Photo & Delete Action -->
                                                                <div class="lg:col-span-1 flex items-center justify-between gap-1.5">
                                                                    <div class="flex-1 flex items-center gap-1.5 min-w-0">
                                                                        <input type="file" 
                                                                               id="photo-bale-{{ $baleIndex }}-{{ $itemIndex }}" 
                                                                               wire:model="bale_items.{{ $baleIndex }}.items.{{ $itemIndex }}.photo" 
                                                                               accept="image/*" 
                                                                               class="hidden" />
                                                                        <label for="photo-bale-{{ $baleIndex }}-{{ $itemIndex }}" 
                                                                               title="Upload Photo"
                                                                               class="w-full px-2 py-2 bg-surface text-on-surface hover:bg-primary/10 hover:text-primary rounded-xl text-[11px] font-bold cursor-pointer transition-colors flex items-center justify-center gap-1 border border-outline-variant/40">
                                                                            <span class="material-symbols-outlined text-[16px]">add_a_photo</span>
                                                                        </label>

                                                                        @if(isset($item['photo']) && is_object($item['photo']))
                                                                            <div class="relative w-7 h-7 rounded-lg overflow-hidden border border-primary shrink-0">
                                                                                <img src="{{ $item['photo']->temporaryUrl() }}" class="w-full h-full object-cover">
                                                                            </div>
                                                                        @endif
                                                                    </div>

                                                                    @if(count($bale['items']) > 1)
                                                                        <button 
                                                                            type="button" 
                                                                            wire:click="removeItemFromBale({{ $baleIndex }}, {{ $itemIndex }})" 
                                                                            title="Remove item from bale"
                                                                            class="p-2 text-on-surface-variant/60 hover:text-error hover:bg-error/10 rounded-xl transition-colors cursor-pointer shrink-0"
                                                                        >
                                                                            <span class="material-symbols-outlined text-[18px]">close</span>
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <!-- Add item to this bale button -->
                                                <div class="pt-1">
                                                    <button 
                                                        type="button" 
                                                        wire:click="addItemToBale({{ $baleIndex }})" 
                                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:text-primary-container transition-colors py-1 px-2 rounded-lg hover:bg-primary/5 cursor-pointer"
                                                    >
                                                        <span class="material-symbols-outlined text-[16px]">add</span>
                                                        <span>+ Add item to this bale</span>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <!-- Add Bale button -->
                                    <div class="pt-2">
                                        <button 
                                            type="button" 
                                            wire:click="addBale" 
                                            class="w-full border-2 border-dashed border-primary/30 hover:border-primary hover:bg-primary/5 rounded-2xl py-3 text-xs font-extrabold text-primary transition-all flex items-center justify-center gap-2 cursor-pointer"
                                        >
                                            <span class="material-symbols-outlined text-[18px]">add_circle</span>
                                            <span>+ Add Bale</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- GST Pricing Mode & Config -->
                        <div class="bg-surface-container-low rounded-xl p-4 border border-outline-variant/40 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <span class="text-xs font-bold text-on-surface uppercase tracking-wider block">GST Tax Mode</span>
                                <p class="text-[11px] text-on-surface-variant font-medium mt-0.5">Toggle whether the entered purchase rate includes GST taxes by default.</p>
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-4">
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model.live="gst_included"
                                        class="sr-only peer"
                                    />
                                    <div class="relative w-11 h-6 bg-outline-variant/60 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    <span class="font-label-md text-xs font-extrabold text-on-surface">
                                        {{ $gst_included ? 'GST Included in Price' : 'GST Excluded (Add GST %)' }}
                                    </span>
                                </label>

                                @if(!$gst_included)
                                    <div class="flex flex-col items-end">
                                        <div class="flex items-center gap-1.5 bg-surface border border-outline-variant/60 rounded-xl px-3 py-1.5 shadow-2xs">
                                            <label for="gst-percent" class="text-xs font-bold text-on-surface-variant shrink-0">GST %:</label>
                                            <input
                                                id="gst-percent"
                                                type="number"
                                                step="0.5"
                                                min="0"
                                                max="100"
                                                wire:model.live="gst_percent"
                                                placeholder="18"
                                                class="w-16 bg-transparent border-none text-xs font-bold text-primary focus:ring-0 p-0 text-right outline-none"
                                            />
                                            <span class="text-xs font-bold text-on-surface-variant">%</span>
                                        </div>
                                        @error('gst_percent') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($unitType !== 'length_based')
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                                <div class="md:col-span-5">
                                    <label for="qty-received" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">
                                        Quantity Received ({{ strtoupper($unitName) }}) <span class="text-error">*</span>
                                    </label>
                                    <input
                                        id="qty-received"
                                        type="number"
                                        step="0.0001"
                                        wire:model.live="quantity_received"
                                        placeholder="0.0000"
                                        class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 font-body-md text-sm font-bold text-left focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                    />
                                    @error('quantity_received') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-4">
                                    <label for="purchase-rate" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">
                                        Rate per Unit (₹) <span class="text-error">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-on-surface-variant/70">₹</span>
                                        <input
                                            id="purchase-rate"
                                            type="number"
                                            step="0.01"
                                            wire:model.live="purchase_rate"
                                            placeholder="0.00"
                                            class="w-full bg-surface border border-outline-variant/60 rounded-xl pl-8 pr-4 py-3 font-body-md text-sm font-bold text-left focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                        />
                                    </div>
                                    @error('purchase_rate') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Total Value (₹)</label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-primary">₹</span>
                                        <input
                                            type="text"
                                            readonly
                                            value="{{ number_format($total_amount, 2) }}"
                                            class="w-full bg-surface-container border border-outline-variant/30 rounded-xl pl-8 pr-4 py-3 font-body-md text-sm font-black text-primary text-left outline-none cursor-not-allowed"
                                        />
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-8 text-on-surface-variant/50 italic bg-surface-container-low/20 rounded-xl border border-dashed border-outline-variant/40">
                            <span class="material-symbols-outlined text-4xl mb-2">inventory_2</span>
                            <p class="text-sm font-semibold">Select a raw material category to configure quantities & pricing</p>
                        </div>
                    @endif
                </section>

                <!-- Compact Cost Summary Bar -->
                <section class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-5 shadow-xs">
                    <div class="flex items-center gap-2 mb-3 text-primary">
                        <span class="material-symbols-outlined text-[20px] font-bold">payments</span>
                        <h3 class="font-headline-sm text-xs font-extrabold uppercase tracking-wider">Purchase Cost Summary</h3>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-6 items-center border-t border-outline-variant/30 pt-4">
                        <div>
                            <span class="text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider block">Total Quantity</span>
                            <span class="text-sm font-extrabold text-on-surface mt-0.5 block">
                                @if($quantity_received)
                                    {{ number_format(floatval($quantity_received), 2) }} {{ $unitName }}
                                @else
                                    —
                                @endif
                            </span>
                        </div>

                        <div>
                            <span class="text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider block">Avg Unit Rate</span>
                            <span class="text-sm font-extrabold text-on-surface mt-0.5 block">
                                @if($purchase_rate)
                                    ₹{{ number_format(floatval($purchase_rate), 2) }}
                                @else
                                    —
                                @endif
                            </span>
                        </div>

                        <div>
                            <span class="text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider block">Subtotal Value</span>
                            <span class="text-sm font-extrabold text-on-surface mt-0.5 block">₹{{ number_format($total_amount, 2) }}</span>
                        </div>

                        <div>
                            <span class="text-[11px] font-bold text-on-surface-variant/70 uppercase tracking-wider block">
                                @if($gst_included)
                                    GST (Included)
                                @else
                                    GST ({{ floatval($gst_percent) }}%)
                                @endif
                            </span>
                            <span class="text-sm font-extrabold text-on-surface mt-0.5 block">
                                @if($gst_included)
                                    ₹0.00 <span class="text-[10px] text-on-surface-variant font-normal">(Included)</span>
                                @else
                                    ₹{{ number_format($this->gstAmount, 2) }}
                                @endif
                            </span>
                        </div>

                        <div>
                            <span class="text-[11px] font-bold text-primary uppercase tracking-wider block">Grand Total</span>
                            <span class="text-base font-black text-primary font-mono mt-0.5 block">₹{{ number_format($this->grandTotal, 2) }}</span>
                        </div>
                    </div>
                </section>

                <!-- Action Buttons (In-flow & Left-aligned) -->
                <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-6 shadow-xs flex flex-col sm:flex-row items-center justify-start gap-4">
                    <button type="submit" class="w-full sm:w-auto px-8 py-3.5 text-sm font-extrabold text-on-primary bg-primary hover:bg-primary-container rounded-xl flex items-center justify-center gap-2.5 shadow-md transition-all active:scale-95 cursor-pointer">
                        <span class="material-symbols-outlined text-[20px]">save</span>
                        Save Purchase Entry
                    </button>
                    <a href="{{ route('factory.raw-materials.index') }}" wire:navigate class="w-full sm:w-auto px-7 py-3.5 text-sm font-bold text-on-surface-variant bg-surface-container-high hover:bg-surface-container-highest border border-outline-variant/60 rounded-xl text-center transition-all">
                        Cancel
                    </a>
                    <div class="flex items-center gap-2 text-on-surface-variant/70 sm:ml-auto">
                        <span class="material-symbols-outlined text-[18px]">verified_user</span>
                        <p class="font-label-sm text-xs font-semibold">Secure Entry Session</p>
                    </div>
                </div>
        </div>
    </form>
</div>
