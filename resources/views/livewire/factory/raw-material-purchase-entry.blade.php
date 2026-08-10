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
        <div class="flex flex-col lg:flex-row gap-gutter items-start">
            <!-- Left Column (Main Form) -->
            <div class="flex-1 space-y-6 w-full">
                <!-- Card 1: Purchase Information -->
                <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/60 p-6 shadow-xs">
                    <div class="flex items-center gap-2 mb-6 text-primary">
                        <span class="material-symbols-outlined text-[20px] font-bold">receipt_long</span>
                        <h3 class="font-headline-sm text-headline-sm font-extrabold">Purchase Information</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="supplier-name" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Supplier <span class="text-error">*</span></label>
                            <input
                                id="supplier-name"
                                type="text"
                                wire:model="supplier_name"
                                placeholder="e.g., TexVenture Fabrics Co."
                                class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                            />
                            @error('supplier_name') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="purchase-date" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Purchase Date <span class="text-error">*</span></label>
                            <input
                                id="purchase-date"
                                type="date"
                                wire:model="purchase_date"
                                class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
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
                                class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                            />
                            @error('invoice_number') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <!-- Card 2: Raw Material Selection -->
                <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/60 p-6 shadow-xs">
                    <div class="flex items-center gap-2 mb-6 text-primary">
                        <span class="material-symbols-outlined text-[20px] font-bold">inventory</span>
                        <h3 class="font-headline-sm text-headline-sm font-extrabold">Raw Material Selection</h3>
                    </div>
                    
                    <div class="mb-6">
                        <label for="material-picker" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Select Material <span class="text-error">*</span></label>
                        <select
                            id="material-picker"
                            wire:model.live="raw_material_id"
                            class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                        >
                            <option value="">— Search by Name or Code —</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->name }} ({{ $material->code }})</option>
                            @endforeach
                        </select>
                        @error('raw_material_id') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Selected Item Info -->
                    @if($raw_material_id)
                        @php
                            $selectedMat = $materials->firstWhere('id', $raw_material_id);
                        @endphp
                        @if($selectedMat)
                            <div class="bg-surface-container-low rounded-xl p-5 mb-8 border border-outline-variant/30">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h4 class="font-headline-sm text-sm font-black text-primary">{{ $selectedMat->name }}</h4>
                                        <p class="font-mono font-semibold text-xs text-on-surface-variant/75 mt-0.5">{{ $selectedMat->code }}</p>
                                    </div>
                                    @if($selectedMat->category)
                                        <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full font-label-md text-[10px] font-extrabold uppercase font-mono border border-secondary/20">
                                            {{ $selectedMat->category->code }}
                                        </span>
                                    @endif
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    <div>
                                        <p class="font-label-sm text-[10px] font-bold text-on-surface-variant/60 uppercase">Category Type</p>
                                        <p class="font-label-md text-xs font-extrabold text-on-surface mt-1">{{ $selectedMat->category?->unit_type?->label() }}</p>
                                    </div>
                                    @if($selectedMat->category?->unit_type?->value === 'length_based')
                                        <div>
                                            <p class="font-label-sm text-[10px] font-bold text-on-surface-variant/60 uppercase">Std Width</p>
                                            <p class="font-label-md text-xs font-extrabold text-on-surface mt-1">{{ $selectedMat->standard_width }} {{ $selectedMat->width_unit }}</p>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-label-sm text-[10px] font-bold text-on-surface-variant/60 uppercase">Default Unit</p>
                                        <p class="font-label-md text-xs font-extrabold text-on-surface mt-1">{{ $selectedMat->unit }}</p>
                                    </div>
                                    <div class="sm:text-right">
                                        <p class="font-label-sm text-[10px] font-bold text-on-surface-variant/60 uppercase">Status</p>
                                        <span class="inline-flex items-center gap-1 mt-1 text-xs font-bold {{ $selectedMat->is_active ? 'text-secondary' : 'text-on-surface-variant/50' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $selectedMat->is_active ? 'bg-secondary' : 'bg-outline' }}"></span>
                                            {{ $selectedMat->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- Dynamic Purchase Fields -->
                    @if($raw_material_id)
                        @if($unitType === 'length_based')
                            <!-- Fabric Bale Configuration -->
                            <div class="bg-primary/5 border border-primary/20 rounded-xl p-5 mb-6 space-y-4">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-primary/15 pb-3">
                                    <div class="flex items-center gap-2 text-primary font-bold text-xs uppercase tracking-wider">
                                        <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                                        <span>Fabric Bale Purchase Details</span>
                                    </div>
                                    <!-- Toggle Switch: All bales equal length -->
                                    <label class="flex items-center gap-2.5 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            wire:model.live="all_bales_equal_length"
                                            class="sr-only peer"
                                        />
                                        <div class="relative w-11 h-6 bg-outline-variant/60 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                        <span class="font-label-md text-xs font-extrabold text-on-surface">All bale lengths are equal</span>
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="num-bales" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                            Number of Bales Purchased <span class="text-error">*</span>
                                        </label>
                                        <input
                                            id="num-bales"
                                            type="number"
                                            min="1"
                                            max="100"
                                            wire:model.live="num_bales"
                                            placeholder="e.g., 3"
                                            class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none font-bold"
                                        />
                                        @error('num_bales') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    @if($all_bales_equal_length)
                                        <div>
                                            <label for="declared-bale-length" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                                Declared Length Written on Bale <span class="text-error">*</span>
                                            </label>
                                            <div class="relative">
                                                <input
                                                    id="declared-bale-length"
                                                    type="number"
                                                    step="0.01"
                                                    wire:model.live="declared_bale_length"
                                                    placeholder="e.g., 300"
                                                    class="w-full bg-surface border border-outline-variant/60 rounded-xl pl-4 pr-16 py-3 font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none font-bold text-right"
                                                />
                                                <span class="absolute right-4 top-3 text-xs font-bold text-on-surface-variant/60 pointer-events-none">{{ $unitName }} / Bale</span>
                                            </div>
                                            @error('declared_bale_length') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    @endif
                                </div>

                                <!-- Individual Bale Lengths Grid (When toggle is OFF) -->
                                @if(!$all_bales_equal_length)
                                    <div class="pt-3 border-t border-primary/15 space-y-3">
                                        <div class="flex justify-between items-center">
                                            <label class="block font-label-md text-xs font-bold text-primary uppercase tracking-wider">
                                                Individual Declared Length Per Bale
                                            </label>
                                            <span class="text-[11px] font-bold text-on-surface-variant/80">Enter specific length for each bale</span>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                            @for($i = 0; $i < intval($num_bales ?: 1); $i++)
                                                <div class="bg-surface rounded-xl p-3 border border-outline-variant/60">
                                                    <label for="bale-len-{{ $i }}" class="block font-mono text-[11px] font-extrabold text-primary mb-1">
                                                        Bale #{{ $i + 1 }} Length
                                                    </label>
                                                    <div class="relative">
                                                        <input
                                                            id="bale-len-{{ $i }}"
                                                            type="number"
                                                            step="0.01"
                                                            wire:model.live="individual_bale_lengths.{{ $i }}"
                                                            placeholder="0.00"
                                                            class="w-full bg-surface-container-low border border-outline-variant/40 rounded-lg pl-3 pr-10 py-2 font-body-md text-xs font-bold text-right focus:border-primary focus:outline-none"
                                                        />
                                                        <span class="absolute right-2 top-2 text-[10px] font-bold text-on-surface-variant/60 pointer-events-none">{{ $unitName }}</span>
                                                    </div>
                                                    @error("individual_bale_lengths.{$i}")
                                                        <p class="text-error text-[10px] font-semibold mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            @endfor
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="md:col-span-2">
                                <label for="qty-received" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">
                                    {{ $unitType === 'length_based' ? 'Total Length Calculated' : 'Quantity Received' }} <span class="text-error">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        id="qty-received"
                                        type="number"
                                        step="0.0001"
                                        wire:model.live="quantity_received"
                                        {{ $unitType === 'length_based' ? 'readonly' : '' }}
                                        placeholder="0.0000"
                                        class="w-full border rounded-xl pl-4 pr-16 py-3 font-body-md text-sm text-right focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none
                                            {{ $unitType === 'length_based' ? 'bg-surface-container border-outline-variant/40 font-bold text-primary cursor-not-allowed' : 'bg-surface border-outline-variant/60' }}"
                                    />
                                    <span class="absolute right-4 top-3 text-xs font-bold text-on-surface-variant/60 pointer-events-none">{{ $unitName }}</span>
                                </div>
                                @error('quantity_received') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="purchase-rate" class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">
                                    {{ $unitType === 'length_based' ? 'Rate per ' . rtrim($unitName, 's') : 'Rate per Unit' }} <span class="text-error">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-3.5 text-xs font-bold text-on-surface-variant/60">₹</span>
                                    <input
                                        id="purchase-rate"
                                        type="number"
                                        step="0.01"
                                        wire:model.live="purchase_rate"
                                        placeholder="0.00"
                                        class="w-full bg-surface border border-outline-variant/60 rounded-xl pl-8 pr-4 py-3 font-body-md text-sm text-right focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                    />
                                </div>
                                @error('purchase_rate') <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Total Value</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-3.5 text-xs font-bold text-on-surface-variant/60">₹</span>
                                    <input
                                        type="text"
                                        readonly
                                        value="{{ number_format($total_amount, 2) }}"
                                        class="w-full bg-surface-container border border-outline-variant/30 rounded-xl pl-8 pr-4 py-3 font-body-md text-sm font-bold text-primary text-right outline-none cursor-not-allowed"
                                    />
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8 text-on-surface-variant/50 italic bg-surface-container-low/20 rounded-xl border border-dashed border-outline-variant/40">
                            <span class="material-symbols-outlined text-4xl mb-2">inventory_2</span>
                            <p class="text-sm font-semibold">Select a raw material to configure quantities & pricing</p>
                        </div>
                    @endif
                </section>

                <!-- Card 3: Inventory Batch Preview -->
                @if($raw_material_id && $quantity_received && $purchase_rate)
                    @php
                        $selectedMat = $materials->firstWhere('id', $raw_material_id);
                    @endphp
                    <section class="bg-surface-container-lowest rounded-xl border border-outline-variant/60 p-6 shadow-xs">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-2 text-primary">
                                <span class="material-symbols-outlined text-[20px] font-bold">reorder</span>
                                <h3 class="font-headline-sm text-headline-sm font-extrabold">Inventory Batch Preview</h3>
                            </div>
                            <p class="font-label-sm text-xs text-on-surface-variant/60 flex items-center gap-1 italic">
                                <span class="material-symbols-outlined text-[16px] text-secondary">info</span>
                                Auto-generated upon saving.
                            </p>
                        </div>
                        <div class="overflow-x-auto border border-outline-variant/40 rounded-xl">
                            <table class="w-full border-collapse text-left text-sm">
                                <thead class="bg-surface-container-low border-b border-outline-variant/40">
                                    <tr class="font-bold text-xs text-on-surface-variant uppercase">
                                        <th class="px-6 py-3">Batch Number</th>
                                        <th class="px-6 py-3">Material</th>
                                        <th class="px-6 py-3">Date</th>
                                        <th class="px-6 py-3 text-right">Qty Received</th>
                                        <th class="px-6 py-3 text-right">Unit Rate</th>
                                        <th class="px-6 py-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="hover:bg-surface-container-low/10 transition-colors">
                                        <td class="px-6 py-4 font-mono font-bold text-xs text-primary">
                                            BAT-{{ \Illuminate\Support\Carbon::parse($purchase_date)->year }}-XXXX
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-on-surface">
                                            {{ $selectedMat?->name }}
                                        </td>
                                        <td class="px-6 py-4 text-on-surface-variant">
                                            {{ \Illuminate\Support\Carbon::parse($purchase_date)->format('d M Y') }}
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold text-on-surface">
                                            {{ number_format(floatval($quantity_received), 2) }} {{ $unitName }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-on-surface-variant">
                                            ₹{{ number_format(floatval($purchase_rate), 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="bg-secondary-container text-on-secondary-container px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase border border-secondary/20 font-mono">
                                                Available
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif
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

            <!-- Right Sidebar (Sticky/Cost Summary) -->
            <div class="w-full lg:w-80 space-y-6 lg:sticky lg:top-[80px]">
                <!-- Card 4: Purchase Cost Summary -->
                <section class="bg-primary text-on-primary rounded-xl p-6 shadow-md relative overflow-hidden">
                    <div class="relative z-10">
                        <h3 class="font-label-md text-xs font-bold text-on-primary-fixed-variant opacity-80 uppercase tracking-widest mb-6">Cost Summary</h3>
                        <div class="space-y-4 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="opacity-80">Purchase Quantity</span>
                                <span class="font-bold">
                                    @if($quantity_received)
                                        {{ number_format(floatval($quantity_received), 4) }} {{ $unitName }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="opacity-80">Unit Rate</span>
                                <span class="font-bold">
                                    @if($purchase_rate)
                                        ₹{{ number_format(floatval($purchase_rate), 2) }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between items-center border-t border-on-primary/10 pt-4">
                                <span class="opacity-80">Subtotal Value</span>
                                <span class="font-bold">₹{{ number_format($total_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-on-primary/10 pb-4">
                                <span class="opacity-80">GST (18% Estimated)</span>
                                <span class="font-bold">₹{{ number_format($total_amount * 0.18, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-end pt-2">
                                <span class="text-base font-extrabold">Grand Total</span>
                                <span class="text-xl font-black text-secondary-fixed">₹{{ number_format($total_amount * 1.18, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <!-- Background Pattern -->
                    <div class="absolute -bottom-6 -right-6 opacity-10 rotate-12">
                        <span class="material-symbols-outlined text-[120px] font-bold">payments</span>
                    </div>
                </section>

                <!-- Stats Panel -->
                @php
                    $todayEntriesCount = \App\Models\InventoryBatch::whereDate('created_at', \Illuminate\Support\Carbon::today())->count();
                    $todayTotalValue = \App\Models\InventoryBatch::whereDate('created_at', \Illuminate\Support\Carbon::today())->sum('total_amount');
                    $latestBatch = \App\Models\InventoryBatch::latest()->first();
                @endphp
                <section class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-5 shadow-xs">
                    <h4 class="font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-4">Today's Activity</h4>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-primary">
                                <span class="material-symbols-outlined text-[20px] font-bold">shopping_cart</span>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant font-bold uppercase tracking-wider">Purchases Today</p>
                                <p class="text-sm font-extrabold text-on-surface mt-0.5">{{ $todayEntriesCount }} Entries</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-primary">
                                <span class="material-symbols-outlined text-[20px] font-bold">account_balance_wallet</span>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant font-bold uppercase tracking-wider">Daily Value</p>
                                <p class="text-sm font-extrabold text-on-surface mt-0.5">₹{{ number_format($todayTotalValue, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    @if($latestBatch)
                        <div class="mt-6 pt-6 border-t border-outline-variant/40">
                            <p class="text-[10px] text-on-surface-variant font-bold uppercase tracking-wider mb-2">Latest Batch Generated</p>
                            <div class="flex justify-between items-center">
                                <span class="font-mono font-bold text-xs text-primary">{{ $latestBatch->batch_number }}</span>
                                <span class="text-xs text-on-surface-variant font-medium">{{ $latestBatch->created_at->format('h:i A') }}</span>
                            </div>
                        </div>
                    @endif
                </section>

                <!-- Quick Tips -->
                <section class="bg-tertiary-fixed text-on-tertiary-fixed rounded-xl p-5 relative overflow-hidden border border-outline-variant/20 shadow-xs">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="material-symbols-outlined text-[20px] font-bold">lightbulb</span>
                        <h4 class="font-label-md text-xs font-bold uppercase tracking-wider">Quick Tips</h4>
                    </div>
                    <ul class="space-y-3 relative z-10 text-xs font-medium opacity-90">
                        <li class="flex gap-2">
                            <span class="material-symbols-outlined text-[16px] mt-0.5 text-primary">check_circle</span>
                            <span>Verify that quantities received align with supplier packing slips.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="material-symbols-outlined text-[16px] mt-0.5 text-primary">check_circle</span>
                            <span>Inventory batches maintain strict costing and traceability per lot.</span>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </form>
</div>
