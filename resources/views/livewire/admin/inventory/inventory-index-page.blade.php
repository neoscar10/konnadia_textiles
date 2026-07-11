<div>
    <x-slot:title>Inventory Management</x-slot:title>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Inventory Management</h1>
            <p class="font-body-md text-on-surface-variant">Monitor, audit, and adjust product stock levels and variant inventories.</p>
        </div>
    </div>

    <!-- Stats Summary Widgets -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-lg mb-xl">
        <!-- Card 1: Total Items -->
        <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl p-lg flex items-center justify-between card-shadow">
            <div>
                <p class="text-xs font-label-md text-on-surface-variant uppercase tracking-wider">Total Stock Qty</p>
                <h3 class="font-headline-lg font-bold text-primary mt-xs">{{ number_format($stats['total_items']) }}</h3>
            </div>
            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-[24px]">inventory_2</span>
            </div>
        </div>

        <!-- Card 2: Total Stock Value -->
        <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl p-lg flex items-center justify-between card-shadow">
            <div>
                <p class="text-xs font-label-md text-on-surface-variant uppercase tracking-wider">Estimated Value</p>
                <h3 class="font-headline-lg font-bold text-primary mt-xs">₹{{ number_format($stats['total_value'], 2) }}</h3>
            </div>
            <div class="w-12 h-12 bg-[#E6F4EA] rounded-full flex items-center justify-center text-[#0F8A46]">
                <span class="material-symbols-outlined text-[24px]">payments</span>
            </div>
        </div>

        <!-- Card 3: Low Stock Alerts -->
        <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl p-lg flex items-center justify-between card-shadow">
            <div>
                <p class="text-xs font-label-md text-on-surface-variant uppercase tracking-wider">Low Stock Items</p>
                <h3 class="font-headline-lg font-bold {{ $stats['low_stock'] > 0 ? 'text-secondary' : 'text-primary' }} mt-xs">{{ $stats['low_stock'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-secondary-container/30 rounded-full flex items-center justify-center text-secondary">
                <span class="material-symbols-outlined text-[24px]">warning</span>
            </div>
        </div>

        <!-- Card 4: Out of Stock -->
        <div class="bg-surface-container-low border border-outline-variant/30 rounded-xl p-lg flex items-center justify-between card-shadow">
            <div>
                <p class="text-xs font-label-md text-on-surface-variant uppercase tracking-wider">Out of Stock</p>
                <h3 class="font-headline-lg font-bold {{ $stats['out_of_stock'] > 0 ? 'text-error' : 'text-primary' }} mt-xs">{{ $stats['out_of_stock'] }}</h3>
            </div>
            <div class="w-12 h-12 bg-error-container rounded-full flex items-center justify-center text-error">
                <span class="material-symbols-outlined text-[24px]">cancel</span>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <x-admin.card class="mb-xl">
        <x-slot:bodyClass>p-md flex flex-wrap gap-md items-center justify-between</x-slot:bodyClass>
        
        <div class="flex flex-wrap gap-md items-center w-full lg:w-auto">
            <!-- Search -->
            <div class="flex items-center gap-sm w-full sm:w-72 bg-surface-container-low border border-outline-variant/50 rounded-lg px-sm focus-within:ring-2 focus-within:ring-secondary focus-within:border-secondary transition-all">
                <span class="material-symbols-outlined text-on-surface-variant/60 text-[18px] select-none pl-xs">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Title, SKU..." class="w-full bg-transparent border-none py-xs pr-xs font-body-md focus:ring-0 focus:outline-none outline-none text-on-surface">
            </div>
            
            <!-- Category Filter -->
            <select wire:model.live="filterCategory" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @if($cat->children)
                        @foreach($cat->children as $child)
                            <option value="{{ $child->id }}">&nbsp;&nbsp;&mdash; {{ $child->name }}</option>
                        @endforeach
                    @endif
                @endforeach
            </select>

            <!-- Stock Status Filter -->
            <select wire:model.live="filterStock" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">All Stock Levels</option>
                <option value="instock">Good Stock (> 10)</option>
                <option value="lowstock">Low Stock (1-10)</option>
                <option value="outofstock">Out of Stock (0)</option>
            </select>
        </div>
        
        <div class="font-label-md text-on-surface-variant">
            Showing <span class="text-primary font-bold">{{ $products->total() }}</span> product listings
        </div>
    </x-admin.card>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="overflow-x-auto pb-32">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20 select-none">
                    <tr class="whitespace-nowrap">
                        <th class="px-lg py-md">Product Details</th>
                        <th class="px-lg py-md">SKU</th>
                        {{-- TYPE COLUMN HIDDEN: remove comment to restore
                        <th class="px-lg py-md text-center">Type</th>
                        --}}
                        <th class="px-lg py-md text-right">Unit Price</th>
                        <th class="px-lg py-md text-center">Current Stock</th>
                        <th class="px-lg py-md text-center">Stock Status</th>
                        <th class="px-lg py-md text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse($products as $prod)
                        @php
                            $hasComb = $prod->combinations->count() > 0;
                        @endphp
                        <tr class="hover:bg-primary/[0.01] transition-colors group">
                            <!-- Product Info -->
                            <td class="px-lg py-md">
                                <div class="flex items-center gap-sm">
                                    <div class="w-10 h-10 rounded bg-surface-container flex-shrink-0 overflow-hidden flex items-center justify-center border border-outline-variant/30">
                                        @if($prod->primaryMedia)
                                            <img src="{{ Storage::url($prod->primaryMedia->file_path) }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="material-symbols-outlined text-outline">image</span>
                                        @endif
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-primary">{{ $prod->title }}</span>
                                        <span class="text-xs text-on-surface-variant/80">{{ $prod->categories->pluck('name')->implode(', ') ?: 'No Category' }}</span>
                                    </div>
                                </div>
                            </td>
                            <!-- Base SKU -->
                            <td class="px-lg py-md text-on-surface-variant font-mono text-sm whitespace-nowrap">{{ $prod->sku }}</td>
                            {{-- TYPE CELL HIDDEN: remove comment to restore
                            <!-- Product Type -->
                            <td class="px-lg py-md text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-sm py-xxs rounded text-[10px] font-bold uppercase tracking-wider {{ $hasComb ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container-high text-on-surface' }}">
                                    {{ $hasComb ? 'Variant (' . $prod->combinations->count() . ')' : 'Simple' }}
                                </span>
                            </td>
                            --}}
                            <!-- Price -->
                            <td class="px-lg py-md text-right font-medium whitespace-nowrap">₹{{ number_format($prod->base_price, 2) }}</td>
                            <!-- Stock -->
                            <td class="px-lg py-md text-center">
                                <span class="font-headline-sm font-bold {{ $prod->stock_quantity == 0 ? 'text-error' : ($prod->stock_quantity <= 10 ? 'text-secondary font-bold' : 'text-on-surface') }}">
                                    {{ number_format($prod->stock_quantity) }}
                                </span>
                            </td>
                            <!-- Stock Status Badge -->
                            <td class="px-lg py-md text-center">
                                @if($prod->stock_quantity == 0)
                                    <x-admin.badge type="danger">Out of stock</x-admin.badge>
                                @elseif($prod->stock_quantity <= 10)
                                    <x-admin.badge type="warning">Low Stock</x-admin.badge>
                                @else
                                    <x-admin.badge type="success">Good Stock</x-admin.badge>
                                @endif
                            </td>
                            <!-- Actions -->
                            <td class="px-lg py-md text-right whitespace-nowrap">
                                @if(!$hasComb)
                                    <x-admin.button variant="outline" size="sm" icon="edit" wire:click="openAdjustStockModal({{ $prod->id }})">Adjust Stock</x-admin.button>
                                @else
                                    <x-admin.button variant="outline" size="sm" icon="settings" wire:click="openManageVariantsModal({{ $prod->id }})">Manage Variant Stock</x-admin.button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-lg py-2xl text-center text-on-surface-variant font-medium">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="material-symbols-outlined text-4xl mb-sm text-outline">inventory</span>
                                    <p class="font-body-lg">No inventory listings found.</p>
                                    <p class="text-sm">Adjust your filters or search terms.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages() || $products->total() > 0)
        <x-slot:footer class="flex justify-between items-center">
            <span class="font-label-md text-on-surface-variant">Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} items</span>
            <div class="flex items-center gap-xs">
                {{ $products->links(data: ['scrollTo' => false]) }}
            </div>
        </x-slot:footer>
        @endif
    </x-admin.card>

    <!-- Adjustment Modal -->
    <x-admin.modal id="adjust-stock-modal" title="Adjust Stock Quantity" maxWidth="lg">
        <form wire:submit.prevent="saveAdjustment" class="space-y-lg py-md">
            
            <!-- Item Details Box -->
            <div class="p-md rounded-xl bg-surface-container-low border border-outline-variant/30 flex items-center justify-between">
                <div>
                    <h4 class="font-title-md text-primary font-bold">{{ $adjustingProductTitle }}</h4>
                    <p class="text-xs text-on-surface-variant font-mono mt-xxs">SKU: {{ $adjustingSku }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-on-surface-variant font-medium uppercase tracking-wider">Current Stock</p>
                    <span class="font-headline-sm font-black text-primary">{{ number_format($currentStock) }}</span>
                </div>
            </div>

            <!-- Quantity Input (Direct Edit) -->
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">New Stock Value *</label>
                <input type="number" min="0" step="1" wire:model="adjustmentQuantity" placeholder="e.g. 50" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                @error('adjustmentQuantity') <span class="text-error text-xs block">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end gap-md pt-md border-t border-outline-variant/20">
                <x-admin.button variant="ghost" type="button" @click="show = false">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Save Stock</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Manage Variants Modal -->
    <x-admin.modal id="manage-variants-modal" title="Manage Variant Stock" maxWidth="5xl">
        @if($selectedProductForVariants)
            <div class="space-y-lg py-md">
                <!-- Product Overview -->
                <div class="p-md rounded-xl bg-surface-container-low border border-outline-variant/30 flex items-center justify-between">
                    <div>
                        <h4 class="font-title-md text-primary font-bold">{{ $selectedProductForVariants->title }}</h4>
                        <p class="text-xs text-on-surface-variant font-mono mt-xxs">Base SKU: {{ $selectedProductForVariants->sku }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-on-surface-variant font-medium uppercase tracking-wider">Overall Stock</p>
                        <span class="font-headline-sm font-black text-primary">{{ number_format($selectedProductForVariants->stock_quantity) }}</span>
                    </div>
                </div>

                <!-- Variations List Table -->
                <form wire:submit.prevent="saveVariantStocks" class="space-y-lg">
                    <div class="overflow-x-auto border border-outline-variant/30 rounded-xl bg-surface-container-lowest">
                        <table class="w-full text-left font-body-md text-sm">
                            <thead class="bg-surface-container-low text-on-surface-variant font-bold uppercase select-none border-b border-outline-variant/20">
                                <tr>
                                    <th class="px-lg py-md">Variant Options</th>
                                    <th class="px-lg py-md">Variant SKU</th>
                                    <th class="px-lg py-md text-center w-36">Current Stock</th>
                                    <th class="px-lg py-md text-center w-40">New Stock Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/10">
                                @foreach($selectedProductForVariants->combinations as $comb)
                                    @php
                                        $options = [];
                                        if (is_array($comb->combination_values)) {
                                            foreach ($comb->combination_values as $key => $val) {
                                                $options[] = "{$key}: <strong>{$val}</strong>";
                                            }
                                        }
                                    @endphp
                                    <tr class="hover:bg-primary/[0.01]">
                                        <!-- Options detail -->
                                        <td class="px-lg py-md text-on-surface">
                                            {!! implode(', ', $options) !!}
                                        </td>
                                        <!-- SKU -->
                                        <td class="px-lg py-md font-mono text-on-surface-variant text-sm whitespace-nowrap">{{ $comb->sku ?: $selectedProductForVariants->sku }}</td>
                                        <!-- Current Stock -->
                                        <td class="px-lg py-md text-center font-bold text-on-surface-variant">
                                            {{ number_format($comb->stock_quantity) }}
                                        </td>
                                        <!-- Input Field -->
                                        <td class="px-lg py-sm text-center">
                                            <input type="number" min="0" step="1" wire:model="combinationStocks.{{ $comb->id }}" class="w-28 px-sm py-xs text-center bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface font-semibold">
                                            @error("combinationStocks.{$comb->id}")
                                                <span class="text-error text-xs block mt-xxs">{{ $message }}</span>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end gap-md pt-md border-t border-outline-variant/20">
                        <x-admin.button variant="ghost" type="button" @click="show = false">Cancel</x-admin.button>
                        <x-admin.button variant="primary" type="submit">Save Variant Stock</x-admin.button>
                    </div>
                </form>
            </div>
        @endif
    </x-admin.modal>
</div>
