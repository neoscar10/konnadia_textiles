<div>
    <x-slot:title>Product Transfers</x-slot:title>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Internal Product Transfers</h1>
            <p class="font-body-md text-on-surface-variant">Transfer manufactured products to company-owned retail shops.</p>
        </div>
        <div class="flex gap-md w-full sm:w-auto">
            <x-admin.button wire:click="create" variant="primary" icon="sync_alt">Create Transfer</x-admin.button>
        </div>
    </div>

    <!-- Filters & Search -->
    <x-admin.card class="mb-xl">
        <x-slot:bodyClass>p-md</x-slot:bodyClass>
        
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-md w-full">
            <div class="flex items-center gap-sm px-md py-xs bg-surface-container-low border border-outline-variant/50 rounded-lg focus-within:ring-2 focus-within:ring-secondary w-full sm:col-span-6 transition-all">
                <span class="material-symbols-outlined text-on-surface-variant/70 text-[20px] select-none">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Transfer No..." class="w-full bg-transparent border-none p-0 font-body-md text-on-surface placeholder:text-on-surface-variant/50 focus:ring-0 outline-none h-8">
            </div>

            <select wire:model.live="filterShop" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none w-full sm:col-span-3">
                <option value="">All Shops</option>
                @foreach($activeShops as $shop)
                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="status" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none w-full sm:col-span-3">
                <option value="">All Statuses</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </x-admin.card>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="w-full overflow-x-auto pb-32">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr class="whitespace-nowrap text-xs">
                        <th class="px-lg py-md whitespace-nowrap">Transfer No</th>
                        <th class="px-lg py-md whitespace-nowrap">Retail Shop</th>
                        <th class="px-lg py-md whitespace-nowrap">Transfer Date</th>
                        <th class="px-lg py-md text-center whitespace-nowrap">Total Items</th>
                        <th class="px-lg py-md text-center whitespace-nowrap">Qty (Base Units)</th>
                        <th class="px-lg py-md text-center whitespace-nowrap">Stock Deducted</th>
                        <th class="px-lg py-md text-center whitespace-nowrap">Status</th>
                        <th class="px-lg py-md whitespace-nowrap">Created By</th>
                        <th class="px-lg py-md text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse($transfers as $trf)
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-lg font-bold text-primary whitespace-nowrap">{{ $trf->transfer_number }}</td>
                        <td class="px-lg py-lg text-on-surface whitespace-nowrap">{{ $trf->shop->name }}</td>
                        <td class="px-lg py-lg text-on-surface-variant whitespace-nowrap font-mono text-xs">
                            {{ $trf->transfer_date ? $trf->transfer_date->format('Y-m-d') : 'N/A' }}
                        </td>
                        <td class="px-lg py-lg text-center whitespace-nowrap">{{ $trf->total_items }}</td>
                        <td class="px-lg py-lg text-center whitespace-nowrap font-bold text-primary">{{ number_format($trf->total_quantity_base_units, 2) }}</td>
                        <td class="px-lg py-lg text-center whitespace-nowrap">
                            <span class="material-symbols-outlined text-[18px] {{ $trf->stock_deducted ? 'text-success' : 'text-on-surface-variant/40' }}">
                                {{ $trf->stock_deducted ? 'check_circle' : 'cancel' }}
                            </span>
                        </td>
                        <td class="px-lg py-lg text-center whitespace-nowrap">
                            <x-admin.badge type="{{ $trf->status === 'completed' ? 'success' : 'default' }}">
                                {{ ucfirst($trf->status) }}
                            </x-admin.badge>
                        </td>
                        <td class="px-lg py-lg text-on-surface-variant whitespace-nowrap text-xs">{{ $trf->createdBy->name ?? 'N/A' }}</td>
                        <td class="px-lg py-lg text-right whitespace-nowrap">
                            <x-admin.action-menu>
                                <x-admin.action-menu-item wire:click="showDetails({{ $trf->id }})" icon="visibility" label="View Details" />
                                <x-admin.action-menu-item icon="print" label="Print / Save PDF" href="{{ route('admin.product-transfers.pdf', ['id' => $trf->id]) }}" target="_blank" />
                            </x-admin.action-menu>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-lg py-2xl text-center text-on-surface-variant">
                            <div class="flex flex-col items-center justify-center">
                                <span class="material-symbols-outlined text-4xl mb-sm text-outline">sync_alt</span>
                                <p class="font-body-lg">No product transfers found.</p>
                                <p class="text-sm">Click "Create Transfer" to initiate a new internal transfer.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transfers->hasPages() || $transfers->total() > 0)
        <x-slot:footer class="flex justify-between items-center">
            <span class="font-label-md text-on-surface-variant">Showing {{ $transfers->firstItem() ?? 0 }} to {{ $transfers->lastItem() ?? 0 }} of {{ $transfers->total() }} transfers</span>
            <div class="flex items-center gap-xs">
                {{ $transfers->links(data: ['scrollTo' => false]) }}
            </div>
        </x-slot:footer>
        @endif
    </x-admin.card>

    <!-- 3-Step Create Transfer Modal -->
    <x-admin.modal id="create-transfer-modal" title="Internal Product Transfer Wizard" maxWidth="5xl">
        <!-- Stepper Indicator -->
        <div class="border-b border-outline-variant/20 px-xl py-md bg-surface-container-low flex flex-nowrap items-center justify-between gap-md overflow-x-auto select-none mb-lg rounded-t-lg">
            <div class="flex items-center gap-xs font-label-md transition-all {{ $currentStep == 1 ? 'text-primary font-bold' : 'text-secondary' }}">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $currentStep == 1 ? 'bg-primary text-on-primary' : 'bg-secondary-container text-on-secondary-container' }}">1</span>
                <span>Shop & Date</span>
            </div>
            <div class="h-px bg-outline-variant/30 flex-1 mx-sm"></div>
            <div class="flex items-center gap-xs font-label-md transition-all {{ $currentStep == 2 ? 'text-primary font-bold' : ($currentStep > 2 ? 'text-secondary' : 'text-on-surface-variant/40') }}">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $currentStep == 2 ? 'bg-primary text-on-primary' : ($currentStep > 2 ? 'bg-secondary-container text-on-secondary-container' : 'bg-outline-variant/30') }}">2</span>
                <span>Products Selection</span>
            </div>
            <div class="h-px bg-outline-variant/30 flex-1 mx-sm"></div>
            <div class="flex items-center gap-xs font-label-md transition-all {{ $currentStep == 3 ? 'text-primary font-bold' : 'text-on-surface-variant/40' }}">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $currentStep == 3 ? 'bg-primary text-on-primary' : 'bg-outline-variant/30' }}">3</span>
                <span>Review & Confirm</span>
            </div>
        </div>

        <!-- Step 1 Content -->
        @if($currentStep === 1)
        <div class="space-y-xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant font-bold">Destination Retail Shop *</label>
                    <select wire:model="retailShopId" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all text-on-surface">
                        <option value="">Select Retail Shop</option>
                        @foreach($activeShops as $shop)
                            <option value="{{ $shop->id }}">{{ $shop->name }} ({{ $shop->shop_code }})</option>
                        @endforeach
                    </select>
                    @error('retailShopId') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant font-bold">Transfer Date *</label>
                    <input type="date" wire:model="transferDate" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all text-on-surface font-mono">
                    @error('transferDate') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs md:col-span-2">
                    <label class="font-label-md text-on-surface-variant">Transfer Notes</label>
                    <textarea wire:model="notes" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all text-on-surface" rows="3" placeholder="Enter notes or shipping references..."></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-md pt-md border-t border-outline-variant/20">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="button" wire:click="nextStep" variant="primary" icon="arrow_forward">Next: Products</x-admin.button>
            </div>
        </div>
        @endif

        <!-- Step 2 Content -->
        @if($currentStep === 2)
        <div class="space-y-xl">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
                <!-- Left Pane: Paginated Product Catalog & Filters -->
                <div class="lg:col-span-2 space-y-md border-r border-outline-variant/20 pr-lg">
                    <h4 class="font-title-md text-primary font-bold">Select Manufactured Products</h4>
                    
                    <!-- Filters Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-sm">
                        <!-- Search -->
                        <div class="flex items-center gap-sm px-sm py-xxs bg-surface-container-low border border-outline-variant/50 rounded-lg focus-within:ring-2 focus-within:ring-secondary w-full transition-all">
                            <span class="material-symbols-outlined text-on-surface-variant/70 text-[18px] select-none pl-xs">search</span>
                            <input type="text" wire:model.live.debounce.300ms="wizardProductSearch" placeholder="Search Title, SKU..." class="w-full bg-transparent border-none p-0 font-body-sm text-on-surface placeholder:text-on-surface-variant/50 focus:ring-0 outline-none h-8">
                        </div>

                        <!-- Category Filter -->
                        <select wire:model.live="wizardCategoryFilter" class="px-sm py-xxs bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-sm text-on-surface focus:ring-2 focus:ring-secondary outline-none h-8">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Products List Table -->
                    <div class="border border-outline-variant/30 rounded-lg overflow-hidden bg-white max-h-[280px] overflow-y-auto">
                        <table class="w-full text-left font-body-sm text-xs">
                            <thead class="bg-surface-container text-on-surface-variant font-bold uppercase text-[10px] border-b border-outline-variant/20 select-none">
                                <tr>
                                    <th class="px-sm py-xs">Product</th>
                                    <th class="px-sm py-xs">SKU</th>
                                    <th class="px-sm py-xs text-center">Available Stock</th>
                                    <th class="px-sm py-xs text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/10">
                                @forelse($wizardProducts as $p)
                                <tr class="hover:bg-primary/[0.02] transition-colors {{ $selectedProductId == $p->id ? 'bg-secondary/10' : '' }}">
                                    <td class="px-sm py-xs">
                                        <div class="flex items-center gap-xs">
                                            <div class="w-8 h-8 rounded bg-surface-container-low flex-shrink-0 overflow-hidden flex items-center justify-center border border-outline-variant/30">
                                                @if($p->primaryMedia)
                                                    <img src="{{ Storage::url($p->primaryMedia->file_path) }}" class="w-full h-full object-cover">
                                                @else
                                                    <span class="material-symbols-outlined text-outline text-[16px]">image</span>
                                                @endif
                                            </div>
                                            <span class="font-bold text-slate-800">{{ $p->title }}</span>
                                        </div>
                                    </td>
                                    <td class="px-sm py-xs font-mono text-[10px] text-on-surface-variant/80">{{ $p->sku }}</td>
                                    <td class="px-sm py-xs text-center font-bold font-mono">
                                        @if($p->stock_quantity === null)
                                            <span class="text-on-surface-variant/50">Untracked</span>
                                        @elseif($p->stock_quantity > 0)
                                            <span class="text-success">{{ $p->stock_quantity }} pcs</span>
                                        @else
                                            <span class="text-error">0 pcs</span>
                                        @endif
                                    </td>
                                    <td class="px-sm py-xs text-right">
                                        <x-admin.button type="button" wire:click="selectProduct({{ $p->id }})" variant="{{ $selectedProductId == $p->id ? 'primary' : 'outline' }}" size="sm">
                                            {{ $selectedProductId == $p->id ? 'Selected' : 'Select' }}
                                        </x-admin.button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-sm py-xl text-center text-on-surface-variant">No manufactured products found matching criteria.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Wizard Products Pagination -->
                    @if($wizardProducts->hasPages() || $wizardProducts->total() > 0)
                    <div class="pt-xs flex justify-between items-center text-[10px] text-on-surface-variant select-none">
                        <span>Showing {{ $wizardProducts->firstItem() ?? 0 }} to {{ $wizardProducts->lastItem() ?? 0 }} of {{ $wizardProducts->total() }}</span>
                        <div>
                            {{ $wizardProducts->links(data: ['scrollTo' => false]) }}
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right Pane: Selection & Quantity Configuration -->
                <div class="lg:col-span-1 space-y-md">
                    @if(!empty($selectedProductId))
                        @php
                            $selectedProductObj = \App\Models\Product::find($selectedProductId);
                        @endphp
                        @if($selectedProductObj)
                        <div class="border border-outline-variant/30 rounded-xl p-md bg-surface-container-low/30 space-y-md">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Configure Selection</span>
                                <h4 class="font-bold text-primary text-sm">{{ $selectedProductObj->title }}</h4>
                                <span class="text-[10px] font-mono text-on-surface-variant">SKU: {{ $selectedProductObj->sku }}</span>
                            </div>

                            <!-- Variations Select (if variations exist) -->
                            @if($availableCombinations->isNotEmpty())
                            <div class="space-y-xs">
                                <label class="font-label-md text-on-surface-variant font-bold block">Select Variation *</label>
                                <select wire:model.live="selectedCombinationId" class="w-full px-md py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none text-xs text-on-surface h-9">
                                    <option value="">Choose Option</option>
                                    @foreach($availableCombinations as $comb)
                                        @php
                                            $opts = [];
                                            foreach ($comb->combination_values as $k => $v) { $opts[] = "{$k}: {$v}"; }
                                            $lbl = implode(', ', $opts);
                                        @endphp
                                        <option value="{{ $comb->id }}">{{ $lbl }} (Stock: {{ $comb->stock_quantity ?? 'NA' }})</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <!-- Quantity Selection for each Unit Level -->
                            <div class="space-y-sm">
                                <label class="font-label-md text-on-surface-variant font-bold block">Enter Quantity</label>
                                
                                @foreach($availableUnits as $unit)
                                <div class="bg-white p-sm rounded-lg border border-outline-variant/30 space-y-xs">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-bold text-slate-700">{{ $unit->name }} ({{ $unit->short_code }})</span>
                                        @if($unit->level === 2)
                                            <span class="text-[10px] text-slate-400">1 {{ $unit->short_code }} = {{ (int)$unit->conversion_to_base }} base units</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-xs">
                                        <button type="button" wire:click="decrementUnitQty({{ $unit->id }})" class="w-8 h-8 border border-outline-variant/40 rounded flex items-center justify-center hover:bg-slate-100 font-bold focus:outline-none select-none text-slate-700">-</button>
                                        <input type="number" wire:model.live="unitQuantities.{{ $unit->id }}" min="0" class="w-12 text-center bg-transparent border border-outline-variant/30 rounded focus:ring-0 focus:outline-none text-sm font-bold text-[#001229] py-1 h-8">
                                        <button type="button" wire:click="incrementUnitQty({{ $unit->id }})" class="w-8 h-8 border border-outline-variant/40 rounded flex items-center justify-center hover:bg-slate-100 font-bold focus:outline-none select-none text-slate-700">+</button>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <!-- Remarks -->
                            <div class="space-y-xs">
                                <label class="font-label-md text-on-surface-variant">Item Notes / Remarks</label>
                                <input type="text" wire:model="selectedNote" placeholder="e.g. Batch references..." class="w-full px-md py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none text-xs text-on-surface h-9">
                            </div>

                            <div class="flex gap-xs pt-xs border-t border-outline-variant/20">
                                <x-admin.button type="button" wire:click="$set('selectedProductId', '')" variant="ghost" class="w-full text-xs">Cancel</x-admin.button>
                                <x-admin.button type="button" wire:click="addSelectedProductToTransfer" variant="primary" icon="add" class="w-full text-xs">Add to List</x-admin.button>
                            </div>
                        </div>
                        @endif
                    @else
                        <div class="border border-dashed border-outline-variant/60 rounded-xl p-xl flex flex-col items-center justify-center text-center text-on-surface-variant/40 select-none min-h-[220px]">
                            <span class="material-symbols-outlined text-[36px] mb-xs">touch_app</span>
                            <p class="font-bold text-sm">No Product Selected</p>
                            <p class="text-xs">Click "Select" on a product in the left list to configure quantities and variations.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Transfer Items List (The Queue) -->
            <div>
                <h4 class="font-title-md text-on-surface mb-xs font-bold">Transfer Staging Queue ({{ count($items) }})</h4>
                <div class="border border-outline-variant/30 rounded-lg overflow-hidden bg-white max-h-[300px] overflow-y-auto shadow-sm">
                    <table class="w-full text-left font-body-md text-sm">
                        <thead class="bg-surface-container-low text-on-surface-variant font-bold uppercase text-xs border-b border-outline-variant/20 select-none">
                            <tr>
                                <th class="px-md py-sm">Product</th>
                                <th class="px-md py-sm">Variation</th>
                                <th class="px-md py-sm">Unit</th>
                                <th class="px-md py-sm text-center">Qty</th>
                                <th class="px-md py-sm text-center">Base Qty</th>
                                <th class="px-md py-sm text-center">Stock Status</th>
                                <th class="px-md py-sm text-right">Remove</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            @forelse($items as $idx => $item)
                            <tr>
                                <td class="px-md py-sm">
                                    <span class="font-bold text-primary">{{ $item['product_title'] }}</span>
                                    <span class="block font-mono text-[10px] text-on-surface-variant/80">{{ $item['product_sku'] }}</span>
                                    @if($item['note'])
                                        <span class="block text-[11px] text-[#0284c7] font-semibold italic">Note: {{ $item['note'] }}</span>
                                    @endif
                                </td>
                                <td class="px-md py-sm text-xs text-on-surface-variant">{{ $item['combination_name'] }}</td>
                                <td class="px-md py-sm text-xs text-on-surface-variant">{{ $item['unit_name'] }} ({{ $item['unit_short_code'] }})</td>
                                <td class="px-md py-sm text-center font-bold font-mono">{{ $item['quantity'] }}</td>
                                <td class="px-md py-sm text-center font-bold font-mono text-primary">{{ number_format($item['base_quantity'], 2) }}</td>
                                <td class="px-md py-sm text-center">
                                    @if($item['stock_status'] === 'tracked')
                                        <span class="inline-flex px-xs py-xxs text-[10px] font-bold bg-success/15 text-success border border-success/30 rounded uppercase tracking-wide">Will deduct stock</span>
                                    @elseif($item['stock_status'] === 'not_tracked')
                                        <span class="inline-flex px-xs py-xxs text-[10px] font-bold bg-surface-container-high text-on-surface border border-outline-variant/30 rounded uppercase tracking-wide">Stock not tracked</span>
                                    @elseif($item['stock_status'] === 'insufficient')
                                        <span class="inline-flex px-xs py-xxs text-[10px] font-bold bg-error/15 text-error border border-error/30 rounded uppercase tracking-wide">Insufficient stock</span>
                                    @endif
                                </td>
                                <td class="px-md py-sm text-right">
                                    <button type="button" wire:click="removeItem('{{ $item['temp_id'] }}')" class="text-outline hover:text-error transition-colors focus:outline-none">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-md py-xl text-center text-on-surface-variant">No items added to the transfer list yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="flex justify-between pt-md border-t border-outline-variant/20 font-body-md">
                <x-admin.button type="button" wire:click="prevStep" variant="ghost" icon="arrow_back">Step 1</x-admin.button>
                <x-admin.button type="button" wire:click="nextStep" variant="primary" icon="arrow_forward" :disabled="empty($items) || $this->hasStockErrors()">Next: Review</x-admin.button>
            </div>
        </div>
        @endif

        <!-- Step 3 Content -->
        @if($currentStep === 3)
        <div class="space-y-xl">
            <!-- Review Summary Header -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-lg bg-surface-container-low/40 border border-outline-variant/30 rounded-xl p-lg">
                <div class="grid grid-cols-3 gap-sm text-sm">
                    <div class="font-bold text-on-surface-variant">Retail Shop:</div>
                    <div class="col-span-2 text-primary font-bold">
                        @php
                            $selectedShopObj = $activeShops->firstWhere('id', $retailShopId);
                        @endphp
                        {{ $selectedShopObj ? $selectedShopObj->name : 'N/A' }}
                    </div>

                    <div class="font-bold text-on-surface-variant">Address:</div>
                    <div class="col-span-2 text-on-surface-variant">
                        {{ $selectedShopObj ? $selectedShopObj->address : 'N/A' }}
                    </div>

                    <div class="font-bold text-on-surface-variant">Transfer Date:</div>
                    <div class="col-span-2 font-mono text-primary font-bold">{{ $transferDate }}</div>
                </div>

                <div class="grid grid-cols-3 gap-sm text-sm">
                    <div class="font-bold text-on-surface-variant">Total Items:</div>
                    <div class="col-span-2 font-bold">{{ count($items) }}</div>

                    <div class="font-bold text-on-surface-variant">Base Quantity:</div>
                    <div class="col-span-2 font-bold text-primary">
                        {{ number_format(collect($items)->sum('base_quantity'), 2) }}
                    </div>

                    <div class="font-bold text-on-surface-variant">Notes:</div>
                    <div class="col-span-2 italic text-on-surface-variant">{{ $notes ?: 'No notes entered.' }}</div>
                </div>
            </div>

            <!-- Review Items Table -->
            <div class="space-y-xs">
                <h4 class="font-title-md text-on-surface font-bold">Confirm Items Table</h4>
                <div class="border border-outline-variant/30 rounded-lg overflow-hidden bg-white max-h-[220px] overflow-y-auto">
                    <table class="w-full text-left font-body-md text-xs">
                        <thead class="bg-surface-container-low text-on-surface-variant font-bold uppercase text-[10px] border-b border-outline-variant/20">
                            <tr>
                                <th class="px-md py-sm">Product</th>
                                <th class="px-md py-sm">Variation</th>
                                <th class="px-md py-sm">Unit</th>
                                <th class="px-md py-sm text-center">Qty</th>
                                <th class="px-md py-sm text-center">Base Qty</th>
                                <th class="px-md py-sm text-center">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            @foreach($items as $item)
                            <tr>
                                <td class="px-md py-xs">
                                    <span class="font-bold text-primary">{{ $item['product_title'] }}</span>
                                    <span class="block font-mono text-[9px] text-on-surface-variant/80">{{ $item['product_sku'] }}</span>
                                </td>
                                <td class="px-md py-xs text-on-surface-variant">{{ $item['combination_name'] }}</td>
                                <td class="px-md py-xs text-on-surface-variant">{{ $item['unit_name'] }}</td>
                                <td class="px-md py-xs text-center font-bold font-mono">{{ $item['quantity'] }}</td>
                                <td class="px-md py-xs text-center font-bold font-mono text-primary">{{ number_format($item['base_quantity'], 2) }}</td>
                                <td class="px-md py-xs text-on-surface-variant italic">{{ $item['note'] ?: '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Confirmation disclaimer statement -->
            <div class="bg-primary/5 border border-primary/20 p-md rounded-xl space-y-xs">
                <h5 class="font-bold text-primary text-sm flex items-center gap-xs select-none">
                    <span class="material-symbols-outlined text-[18px]">info</span>
                    Confirmation Statement
                </h5>
                <p class="text-xs text-on-surface-variant">
                    This will record an internal transfer and deduct stock only where stock is tracked. It will not create a sale or order.
                </p>
            </div>

            <!-- Footer Buttons -->
            <div class="flex justify-between pt-md border-t border-outline-variant/20">
                <x-admin.button type="button" wire:click="prevStep" variant="ghost" icon="arrow_back">Step 2</x-admin.button>
                <x-admin.button type="button" wire:click="completeTransfer" variant="primary" icon="check_circle" class="bg-success hover:bg-success/90 text-white border-success">Complete Transfer</x-admin.button>
            </div>
        </div>
        @endif
    </x-admin.modal>

    <!-- Success Modal -->
    <x-admin.modal id="success-modal" title="Transfer Completed" maxWidth="lg">
        <div class="space-y-lg text-center py-md">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-success/15 mx-auto mb-lg">
                <span class="material-symbols-outlined text-[36px] text-success">check_circle</span>
            </div>
            
            <h3 class="font-headline-md text-primary">Product Transfer Completed Successfully</h3>
            <p class="font-body-md text-on-surface-variant">
                The transfer sequence has been finalized and recorded in history. Tracked stock levels have been updated.
            </p>

            @if($newlyCreatedTransfer)
                <div class="bg-surface-container-low border border-outline-variant/30 rounded-lg p-md font-mono text-sm max-w-[280px] mx-auto space-y-xxs">
                    <div class="flex justify-between"><span class="text-on-surface-variant font-bold">Transfer Number:</span><span class="text-primary font-bold">{{ $newlyCreatedTransfer->transfer_number }}</span></div>
                    <div class="flex justify-between"><span class="text-on-surface-variant font-bold">Total Items:</span><span class="font-bold">{{ $newlyCreatedTransfer->total_items }}</span></div>
                </div>
            @endif
        </div>
        <x-slot name="footer">
            <div class="flex w-full justify-between items-center gap-md">
                <x-admin.button variant="ghost" @click="show = false" class="text-on-surface-variant">Close</x-admin.button>
                <div class="flex gap-md">
                    <x-admin.button variant="outline" icon="sync_alt" wire:click="create" @click="show = false">Create Another</x-admin.button>
                    @if($newlyCreatedTransfer)
                        <x-admin.button variant="primary" icon="print" href="{{ route('admin.product-transfers.pdf', ['id' => $newlyCreatedTransfer->id]) }}" target="_blank" @click="show = false">Print Transfer</x-admin.button>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-admin.modal>

    <!-- Details View Modal -->
    <x-admin.modal id="transfer-details" title="Transfer Details" maxWidth="5xl">
        @if($selectedTransfer)
        <div class="space-y-lg text-on-surface text-sm">
            <div class="flex justify-between items-start border-b border-outline-variant/20 pb-sm">
                <div>
                    <h4 class="font-headline-sm text-primary font-bold">Transfer #{{ $selectedTransfer->transfer_number }}</h4>
                    <p class="font-mono text-xs text-on-surface-variant">Date: {{ $selectedTransfer->transfer_date ? $selectedTransfer->transfer_date->format('Y-m-d') : 'N/A' }}</p>
                </div>
                <div class="flex items-center gap-sm">
                    <x-admin.badge type="{{ $selectedTransfer->status === 'completed' ? 'success' : 'default' }}">
                        {{ ucfirst($selectedTransfer->status) }}
                    </x-admin.badge>
                    <x-admin.button variant="outline" size="sm" icon="print" href="{{ route('admin.product-transfers.pdf', ['id' => $selectedTransfer->id]) }}" target="_blank">Print View</x-admin.button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-lg bg-surface-container-low/40 p-md rounded-lg">
                <div class="space-y-xxs">
                    <p><span class="font-bold text-on-surface-variant">Destination Shop:</span> {{ $selectedTransfer->shop->name }}</p>
                    <p><span class="font-bold text-on-surface-variant">Address:</span> {{ $selectedTransfer->shop->address }}, {{ $selectedTransfer->shop->city }}, {{ $selectedTransfer->shop->state }} - {{ $selectedTransfer->shop->pincode }}</p>
                    <p><span class="font-bold text-on-surface-variant">Contact Person:</span> {{ $selectedTransfer->shop->contact_person ?: 'N/A' }} ({{ $selectedShopObj = $selectedTransfer->shop->contact_phone ?: 'N/A' }})</p>
                </div>
                <div class="space-y-xxs">
                    <p><span class="font-bold text-on-surface-variant">Created By:</span> {{ $selectedTransfer->createdBy->name ?? 'N/A' }}</p>
                    <p><span class="font-bold text-on-surface-variant">Notes:</span> <span class="italic">{{ $selectedTransfer->notes ?: 'None' }}</span></p>
                    <p><span class="font-bold text-on-surface-variant">Stock Deducted:</span> {{ $selectedTransfer->stock_deducted ? 'Yes' : 'No' }} ({{ $selectedTransfer->stock_deducted_at ? $selectedTransfer->stock_deducted_at->format('Y-m-d H:i') : '-' }})</p>
                </div>
            </div>

            <!-- Items table -->
            <div class="space-y-xs">
                <h5 class="font-title-sm text-primary font-bold">Transfer Items</h5>
                <div class="border border-outline-variant/30 rounded-lg overflow-hidden bg-white">
                    <table class="w-full text-left font-body-md text-xs">
                        <thead class="bg-surface-container text-on-surface-variant font-bold uppercase text-[10px] border-b border-outline-variant/20 select-none">
                            <tr>
                                <th class="px-md py-sm">Product</th>
                                <th class="px-md py-sm">Variation</th>
                                <th class="px-md py-sm">Unit</th>
                                <th class="px-md py-sm text-center">Qty</th>
                                <th class="px-md py-sm text-center">Base Qty</th>
                                <th class="px-md py-sm text-center">Stock Tracked</th>
                                <th class="px-md py-sm text-center">Deducted?</th>
                                <th class="px-md py-sm">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            @foreach($selectedTransfer->items as $item)
                            <tr>
                                <td class="px-md py-sm">
                                    <span class="font-bold text-primary">{{ $item->product_title }}</span>
                                    <span class="block font-mono text-[9px] text-on-surface-variant/80">{{ $item->product_sku }}</span>
                                </td>
                                <td class="px-md py-sm text-on-surface-variant">
                                    @if($item->selected_options)
                                        @php
                                            $opts = [];
                                            foreach ($item->selected_options as $k => $v) { $opts[] = "{$k}: {$v}"; }
                                            echo implode(', ', $opts);
                                        @endphp
                                    @else
                                        None
                                    @endif
                                </td>
                                <td class="px-md py-sm text-on-surface-variant">{{ $item->unit_name ?: 'Piece' }} ({{ $item->unit_short_code ?: 'pcs' }})</td>
                                <td class="px-md py-sm text-center font-bold font-mono">{{ $item->quantity }}</td>
                                <td class="px-md py-sm text-center font-bold font-mono text-primary">{{ number_format($item->base_quantity, 2) }}</td>
                                <td class="px-md py-sm text-center">
                                    <span class="material-symbols-outlined text-[16px] {{ $item->stock_tracked ? 'text-success' : 'text-on-surface-variant/40' }}">
                                        {{ $item->stock_tracked ? 'check_circle' : 'cancel' }}
                                    </span>
                                </td>
                                <td class="px-md py-sm text-center">
                                    <span class="material-symbols-outlined text-[16px] {{ $item->stock_deducted ? 'text-success' : 'text-on-surface-variant/40' }}">
                                        {{ $item->stock_deducted ? 'check_circle' : 'cancel' }}
                                    </span>
                                </td>
                                <td class="px-md py-sm text-on-surface-variant italic">{{ $item->note ?: '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
        <x-slot name="footer">
            <x-admin.button variant="primary" @click="show = false">Close</x-admin.button>
        </x-slot>
    </x-admin.modal>
</div>
