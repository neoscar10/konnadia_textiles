<div>
    <x-slot:title>Products</x-slot:title>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Product Catalog</h1>
            <p class="font-body-md text-on-surface-variant">Manage your inventory, variants, base pricing, and discount overrides.</p>
        </div>
        <div class="flex gap-md w-full sm:w-auto">
            <x-admin.button variant="primary" icon="settings" wire:click="openSelectLeafForDefaults" class="bg-secondary text-on-secondary hover:bg-secondary/90 whitespace-nowrap">Configure Defaults</x-admin.button>
            <x-admin.button variant="primary" icon="add" wire:click="openSelectLeafForAddProduct" class="whitespace-nowrap">Add Product</x-admin.button>
        </div>
    </div>

    <!-- Filters & Search -->
    <x-admin.card class="mb-xl">
        <x-slot:bodyClass>p-md flex flex-wrap gap-md items-center justify-between</x-slot:bodyClass>
        
        <div class="flex flex-wrap gap-md items-center w-full lg:w-auto">
            <!-- Search -->
            <div class="flex items-center gap-sm w-full sm:w-72 bg-surface-container-low border border-outline-variant/50 rounded-lg px-sm focus-within:ring-2 focus-within:ring-secondary focus-within:border-secondary transition-all">
                <span class="material-symbols-outlined text-on-surface-variant/60 text-[18px] select-none pl-xs">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Title, SKU, Code..." class="w-full bg-transparent border-none py-xs pr-xs font-body-md focus:ring-0 focus:outline-none outline-none text-on-surface">
            </div>
            
            <!-- Category Filter -->
            <select wire:model.live="filterCategory" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">All Categories</option>
                @foreach($categories->whereNull('parent_id') as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>

            <!-- Status Filter -->
            <select wire:model.live="filterStatus" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <!-- Stock Filter -->
            <select wire:model.live="filterStock" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">Stock Status</option>
                <option value="instock">In Stock</option>
                <option value="outofstock">Out of Stock</option>
            </select>
        </div>
        
        <div class="font-label-md text-on-surface-variant">
            Total products: <span class="text-primary font-bold">{{ $products->total() }}</span>
        </div>
    </x-admin.card>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="overflow-x-auto pb-32">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20 select-none">
                    <tr class="whitespace-nowrap">
                        <th class="px-lg py-md">Product</th>
                        <th class="px-lg py-md">SKU</th>
                        <th class="px-lg py-md text-center">Type</th>
                        <th class="px-lg py-md">Categories</th>
                        <th class="px-lg py-md font-bold text-primary">Base Price</th>
                        <th class="px-lg py-md text-center">Stock</th>
                        <th class="px-lg py-md text-center">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse($products as $prod)
                        <tr class="hover:bg-primary/[0.02] transition-colors group">
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
                                        <a href="{{ route('admin.products.show', ['id' => $prod->id]) }}" class="font-bold text-primary hover:underline">
                                            {{ $prod->title }}
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td class="px-lg py-md text-on-surface-variant font-mono text-sm whitespace-nowrap">{{ $prod->sku }}</td>
                            <td class="px-lg py-md text-center">
                                @if(($prod->product_type ?? '') === 'retail')
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-purple-50 text-purple-700 border border-purple-200 text-xs font-bold font-mono shadow-sm cursor-help" title="Manufactured">M</span>
                                @else
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-teal-50 text-teal-700 border border-teal-200 text-xs font-bold font-mono shadow-sm cursor-help" title="Retail / Bought">R</span>
                                @endif
                            </td>
                            <td class="px-lg py-md text-on-surface-variant max-w-[200px] truncate">
                                {{ $prod->categories->pluck('name')->implode(', ') ?: 'Uncategorized' }}
                            </td>
                            <td class="px-lg py-md font-bold text-primary">₹{{ number_format($prod->base_price, 2) }}</td>
                            <td class="px-lg py-md text-center">
                                @if($prod->product_type === 'manufactured' || $prod->stock_quantity === null)
                                    <span class="font-semibold text-on-surface-variant">NA</span>
                                @elseif($prod->stock_quantity > 0)
                                    <span class="font-semibold text-success">{{ $prod->stock_quantity }}</span>
                                @else
                                    <span class="font-semibold text-error">Out of stock</span>
                                @endif
                            </td>
                            <td class="px-lg py-md text-center">
                                <button x-on:click="$wire.call('toggleStatus', {{ $prod->id }})" class="focus:outline-none">
                                    <x-admin.badge type="{{ $prod->is_active ? 'success' : 'default' }}">
                                        {{ $prod->is_active ? 'Active' : 'Inactive' }}
                                    </x-admin.badge>
                                </button>
                            </td>
                            <td class="px-lg py-md text-right">
                                <x-admin.action-menu>
                                    <x-admin.action-menu-item icon="visibility" label="View Details" href="{{ route('admin.products.show', ['id' => $prod->id]) }}" />
                                    <x-admin.action-menu-item icon="edit" label="Edit" x-on:click="$wire.call('edit', {{ $prod->id }})" />
                                    <x-admin.action-menu-item icon="{{ $prod->is_active ? 'block' : 'check_circle' }}" label="{{ $prod->is_active ? 'Deactivate' : 'Activate' }}" x-on:click="$wire.call('toggleStatus', {{ $prod->id }})" />
                                    <x-admin.action-menu-item icon="delete" label="Delete" x-on:click="$wire.call('confirmDelete', {{ $prod->id }})" class="text-error hover:text-error hover:bg-error/10" />
                                </x-admin.action-menu>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-lg py-2xl text-center text-on-surface-variant font-medium">
                                No products found matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer class="flex justify-between items-center">
            <span class="font-label-md text-on-surface-variant">
                Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products
            </span>
            <div>
                {{ $products->links('pagination::tailwind') }}
            </div>
        </x-slot:footer>
    </x-admin.card>

    <!-- Wizard Modal (shared partial) -->
    @include('admin.products.category-product-wizard-modal', [
        'modalId'           => 'add-product',
        'deleteModalId'     => 'delete-product',
        'valueMediaModalId' => 'manage-value-media',
        'lockedCategory'    => null,
    ])

    <!-- ── Select Leaf Category for Defaults Modal ── -->
    <x-admin.modal id="select-leaf-for-defaults" title="Configure Category Defaults" maxWidth="5xl">
        <div x-data="{ search: '' }" class="space-y-md p-md max-h-[600px] flex flex-col">
            <p class="text-sm text-on-surface-variant">Select a leaf category to configure or edit its default HSN, GST, MOQ, units, and pricing matrix overrides.</p>
            
            <!-- Search input -->
            <div class="relative mb-2">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl pointer-events-none">search</span>
                <input type="text" x-model="search" placeholder="Search leaf categories by name or path..." class="w-full bg-slate-100 text-[#001229] placeholder-slate-400 pl-10 pr-4 py-2 rounded-lg text-sm border border-outline-variant/20 focus:outline-none focus:ring-2 focus:ring-[#001229]">
            </div>

            <div class="divide-y divide-outline-variant/10 border border-outline-variant/20 rounded-lg overflow-y-auto bg-surface-container-low/30 flex-1">
                @forelse($leafCategories as $leaf)
                    <div x-show="search === '' || '{{ addslashes(strtolower($leaf->name)) }}'.includes(search.toLowerCase()) || '{{ addslashes(strtolower($leaf->full_path)) }}'.includes(search.toLowerCase())"
                         class="flex justify-between items-center px-lg py-md hover:bg-surface-container-high/50 transition-colors">
                        <div class="flex items-center gap-sm">
                            <span class="material-symbols-outlined text-secondary text-[22px] select-none">bookmark</span>
                            <div class="flex flex-col">
                                <span class="font-bold text-primary text-sm">{{ $leaf->name }}</span>
                                <span class="text-xs text-on-surface-variant">{{ $leaf->full_path }}</span>
                            </div>
                        </div>
                        <x-admin.button variant="primary" icon="settings" wire:click="selectLeafForDefaults({{ $leaf->id }})" class="bg-secondary text-on-secondary hover:bg-secondary/90 text-xs whitespace-nowrap !shrink-0">
                            Configure Defaults
                        </x-admin.button>
                    </div>
                @empty
                    <p class="text-xs text-on-surface-variant text-center py-md">No leaf categories configured yet.</p>
                @endforelse
            </div>
        </div>
        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <!-- ── Select Leaf Category for Add Product Modal ── -->
    <x-admin.modal id="select-leaf-for-add-product" title="Select Category for New Product" maxWidth="5xl">
        <div x-data="{ search: '' }" class="space-y-md p-md max-h-[600px] flex flex-col">
            <p class="text-sm text-on-surface-variant">Choose the leaf category you want to add the product to. Only categories with configured defaults can accept new products.</p>
            
            <!-- Search input -->
            <div class="relative mb-2">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl pointer-events-none">search</span>
                <input type="text" x-model="search" placeholder="Search leaf categories by name or path..." class="w-full bg-slate-100 text-[#001229] placeholder-slate-400 pl-10 pr-4 py-2 rounded-lg text-sm border border-outline-variant/20 focus:outline-none focus:ring-2 focus:ring-[#001229]">
            </div>

            <div class="divide-y divide-outline-variant/10 border border-outline-variant/20 rounded-lg overflow-y-auto bg-surface-container-low/30 flex-1">
                @forelse($leafCategories as $leaf)
                    @php
                        $defaultsConfigured = !empty($leaf->default_product_config) && !empty($leaf->default_product_config['units']['level1_name']);
                    @endphp
                    <div x-show="search === '' || '{{ addslashes(strtolower($leaf->name)) }}'.includes(search.toLowerCase()) || '{{ addslashes(strtolower($leaf->full_path)) }}'.includes(search.toLowerCase())"
                         class="flex justify-between items-center px-lg py-md hover:bg-surface-container-high/50 transition-colors {{ !$defaultsConfigured ? 'opacity-60 bg-surface-container-low' : '' }}">
                        <div class="flex items-center gap-sm">
                            <span class="material-symbols-outlined text-[22px] select-none {{ $defaultsConfigured ? 'text-secondary' : 'text-outline-variant' }}">bookmark</span>
                            <div class="flex flex-col">
                                <span class="font-bold text-sm {{ $defaultsConfigured ? 'text-primary' : 'text-on-surface-variant' }}">{{ $leaf->name }}</span>
                                <span class="text-xs text-on-surface-variant">{{ $leaf->full_path }}</span>
                            </div>
                        </div>

                        @if($defaultsConfigured)
                            <x-admin.button variant="primary" icon="add" wire:click="selectLeafForAddProduct({{ $leaf->id }})" class="text-xs whitespace-nowrap !shrink-0">
                                Select Category
                            </x-admin.button>
                        @else
                            <div x-data="{ tooltip: false }" @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="relative cursor-not-allowed flex-shrink-0">
                                <x-admin.button variant="primary" icon="add" class="text-xs !bg-[#001229] !text-white opacity-50 cursor-not-allowed whitespace-nowrap">
                                    Select Category
                                </x-admin.button>
                                <!-- High-fidelity custom dark tooltip using inline styles -->
                                <div x-show="tooltip" x-cloak 
                                     style="position: absolute; right: 0; top: 100%; margin-top: 8px; display: flex; flex-direction: column; background-color: #2e3135; color: #ffffff; border-radius: 8px; padding: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3), 0 4px 6px -2px rgba(0,0,0,0.05); z-index: 9999; min-width: 240px; text-align: left; border: 1px solid rgba(255,255,255,0.15);"
                                     class="select-none">
                                    <span style="font-weight: 700; font-size: 13.5px; color: #ffffff; display: block; line-height: 1.2;">Select Category</span>
                                    <span style="font-weight: 400; font-size: 11.5px; color: rgba(255,255,255,0.85); margin-top: 6px; line-height: 1.5; display: block; white-space: normal;">Configure category defaults first to enable product uploads in this leaf folder.</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-on-surface-variant text-center py-md">No leaf categories configured yet.</p>
                @endforelse
            </div>
        </div>
        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <!-- ── Category Defaults Modal ── -->
    <x-admin.modal id="category-defaults" title="Configure Category Defaults" maxWidth="3xl">
        <div class="space-y-xl overflow-y-auto max-h-[550px] p-md">
            <p class="text-sm text-on-surface-variant">Configure shared properties inherited by all products created inside this leaf category.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-lg border-b border-outline-variant/20 pb-lg">
                <!-- HSN Code -->
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">Default HSN Code</label>
                    <input type="text" wire:model="categoryDefaults.hsn_code" placeholder="e.g. 6205" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    @error('categoryDefaults.hsn_code') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- GST Percentage -->
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">Default GST Percentage *</label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" max="100" wire:model="categoryDefaults.gst_percentage" placeholder="e.g. 12" class="w-full pr-xl px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                        <span class="absolute right-md top-1/2 -translate-y-1/2 text-on-surface-variant font-bold">%</span>
                    </div>
                    @error('categoryDefaults.gst_percentage') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Min Order Qty -->
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">Default Min Order Qty *</label>
                    <input type="number" min="1" step="1" wire:model="categoryDefaults.minimum_order_quantity" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    @error('categoryDefaults.minimum_order_quantity') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Product Type -->
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">Default Product Type *</label>
                    <select wire:model="categoryDefaults.product_type" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                        <option value="retail">Manufactured </option>
                        <option value="manufactured">Retail / Bought</option>
                    </select>
                    @error('categoryDefaults.product_type') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Default Base Price -->
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">Default Base Price (MRP in INR) *</label>
                    <div class="relative">
                        <span class="absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant font-bold">₹</span>
                        <input type="number" step="0.01" min="0" wire:model="categoryDefaults.base_price" placeholder="0.00" class="w-full pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    </div>
                    @error('categoryDefaults.base_price') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Default Description -->
                <div class="space-y-xs md:col-span-2">
                    <label class="font-label-md text-on-surface-variant select-none">Default Product Description</label>
                    <div class="border border-outline-variant/60 rounded-lg overflow-hidden bg-white shadow-sm">
                        <!-- Quick Markup toolbar -->
                        <div class="px-md py-xs border-b border-outline-variant/30 bg-surface-container-low/40 flex items-center gap-md select-none flex-wrap">
                            <div class="flex items-center gap-xs">
                                <button type="button" onmousedown="event.preventDefault();" onclick="insertMarkdown('desc-editor-defaults-prod', '**', '**')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container font-extrabold text-sm text-primary" title="Bold">B</button>
                                <button type="button" onmousedown="event.preventDefault();" onclick="insertMarkdown('desc-editor-defaults-prod', '*', '*')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container italic font-bold text-sm text-primary" title="Italic">I</button>
                                <button type="button" onmousedown="event.preventDefault();" onclick="insertMarkdown('desc-editor-defaults-prod', '# ', '')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container font-bold text-sm text-primary" title="Heading">H</button>
                            </div>
                            <div class="w-px h-5 bg-outline-variant/40"></div>
                            <div class="flex items-center gap-xs">
                                <button type="button" onmousedown="event.preventDefault();" onclick="insertMarkdown('desc-editor-defaults-prod', '> ', '')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary text-base font-bold" title="Quote">"</button>
                                <button type="button" onmousedown="event.preventDefault();" onclick="insertMarkdown('desc-editor-defaults-prod', '- ', '')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary flex items-center justify-center" title="Bullet List">
                                    <span class="material-symbols-outlined text-[20px]">format_list_bulleted</span>
                                </button>
                                <button type="button" onmousedown="event.preventDefault();" onclick="insertMarkdown('desc-editor-defaults-prod', '1. ', '')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary flex items-center justify-center" title="Numbered List">
                                    <span class="material-symbols-outlined text-[20px]">format_list_numbered</span>
                                </button>
                            </div>
                            <div class="w-px h-5 bg-outline-variant/40"></div>
                            <div class="flex items-center gap-xs">
                                <button type="button" wire:click="$toggle('isPreviewModeDefaults')" class="w-8 h-8 rounded flex items-center justify-center {{ $isPreviewModeDefaults ? 'bg-secondary/15 text-secondary' : 'text-primary hover:bg-surface-container' }} flex items-center justify-center" title="Toggle Preview">
                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                </button>
                            </div>
                        </div>

                        @if(!$isPreviewModeDefaults)
                            <textarea id="desc-editor-defaults-prod" rows="6" wire:model="categoryDefaults.description" placeholder="Enter default product description..." class="w-full px-md py-md bg-transparent border-0 outline-none focus:ring-0 font-body-md text-on-surface resize-none min-h-[160px]"></textarea>
                        @else
                            <div class="prose max-w-none p-md min-h-[160px] bg-surface-container-low/20 text-on-surface text-sm overflow-y-auto">
                                {!! Illuminate\Support\Str::markdown($categoryDefaults['description'] ?? '*Enter default product description...*') !!}
                            </div>
                        @endif
                    </div>
                    @error('categoryDefaults.description') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Customer Level Discounts -->
            <div class="space-y-md border-b border-outline-variant/20 pb-lg">
                <h4 class="font-title-md text-primary">Default Level-Specific Discount Overrides</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-lg bg-surface-container-low/40 p-lg border border-outline-variant/20 rounded-lg">
                    @foreach($customerLevels as $level)
                        <div class="space-y-xs">
                            <label class="font-label-md text-on-surface-variant">{{ $level->name }}</label>
                            <div class="relative w-full">
                                <input type="number" step="0.01" min="-100" max="100" wire:model="categoryDefaults.pricingOverrides.{{ $level->id }}" placeholder="Default: {{ $level->discount_percentage }}%" class="w-full pr-lg px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                                <span class="absolute right-md top-1/2 -translate-y-1/2 text-on-surface-variant font-bold text-sm">%</span>
                            </div>
                            @error("categoryDefaults.pricingOverrides.{$level->id}") <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Product Unit Configuration -->
            <div class="space-y-lg">
                <div>
                    <h4 class="font-title-md text-primary">Default Unit Configuration</h4>
                </div>
                <div class="flex flex-col sm:flex-row gap-sm items-stretch">
                    <!-- Level 1 -->
                    <div class="flex-1 bg-surface-container-low/60 border-2 {{ !empty($categoryDefaults['units']['level1_name']) ? 'border-primary/20' : 'border-dashed border-outline-variant/40' }} rounded-xl p-md space-y-md relative">
                        <div class="flex items-center gap-xs mb-sm select-none">
                            <span class="w-5 h-5 rounded-full bg-primary text-on-primary text-[11px] font-bold flex items-center justify-center">1</span>
                            <span class="font-label-md text-primary">Level 1 — Base Unit <span class="text-error">*</span></span>
                        </div>
                        <div class="grid grid-cols-2 gap-sm">
                            <div class="space-y-xs">
                                <label class="text-xs text-on-surface-variant font-medium">Unit Name</label>
                                <input type="text" wire:model.live="categoryDefaults.units.level1_name" placeholder="Piece" class="w-full px-sm py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all font-body-md text-on-surface text-sm">
                                @error('categoryDefaults.units.level1_name') <span class="text-error text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-xs">
                                <label class="text-xs text-on-surface-variant font-medium">Short Code</label>
                                <input type="text" wire:model.live="categoryDefaults.units.level1_code" placeholder="pcs" class="w-full px-sm py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-primary outline-none transition-all font-body-md text-on-surface text-sm uppercase">
                                @error('categoryDefaults.units.level1_code') <span class="text-error text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Arrow connector -->
                    <div class="flex sm:flex-col items-center justify-center gap-xs px-sm text-on-surface-variant/50 select-none">
                        @if(!empty($categoryDefaults['units']['level2_name']) && !empty($categoryDefaults['units']['level2_conversion']))
                            <div class="hidden sm:flex flex-col items-center gap-xs">
                                <span class="material-symbols-outlined text-[28px] text-secondary">swap_vert</span>
                                <div class="text-center">
                                    <div class="text-[10px] text-on-surface-variant leading-tight">1 {{ $categoryDefaults['units']['level2_name'] ?: '...' }}</div>
                                    <div class="text-[10px] font-bold text-secondary leading-tight">= {{ $categoryDefaults['units']['level2_conversion'] }}×</div>
                                </div>
                            </div>
                            <div class="sm:hidden flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[24px] text-secondary">swap_horiz</span>
                                <span class="text-xs font-bold text-secondary">1 {{ $categoryDefaults['units']['level2_name'] }} = {{ $categoryDefaults['units']['level2_conversion'] }} {{ $categoryDefaults['units']['level1_name'] }}</span>
                            </div>
                        @else
                            <span class="material-symbols-outlined text-[24px] opacity-30">add_circle</span>
                        @endif
                    </div>

                    <!-- Level 2 -->
                    <div class="flex-1 bg-surface-container-low/40 border-2 {{ !empty($categoryDefaults['units']['level2_name']) ? 'border-secondary/25' : 'border-dashed border-outline-variant/40' }} rounded-xl p-md space-y-md">
                        <div class="flex items-center gap-xs mb-sm select-none">
                            <span class="w-5 h-5 rounded-full {{ !empty($categoryDefaults['units']['level2_name']) ? 'bg-secondary text-on-secondary' : 'bg-outline-variant/40 text-on-surface-variant' }} text-[11px] font-bold flex items-center justify-center">2</span>
                            <span class="font-label-md {{ !empty($categoryDefaults['units']['level2_name']) ? 'text-secondary' : 'text-on-surface-variant/60' }}">Level 2 — Group Unit <span class="text-on-surface-variant/50 font-normal text-xs">(optional)</span></span>
                        </div>
                        <div class="grid grid-cols-2 gap-sm">
                            <div class="space-y-xs">
                                <label class="text-xs text-on-surface-variant font-medium">Unit Name</label>
                                <input type="text" wire:model.live="categoryDefaults.units.level2_name" placeholder="Box" class="w-full px-sm py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface text-sm">
                                @error('categoryDefaults.units.level2_name') <span class="text-error text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-xs">
                                <label class="text-xs text-on-surface-variant font-medium">Short Code</label>
                                <input type="text" wire:model.live="categoryDefaults.units.level2_code" placeholder="box" class="w-full px-sm py-sm bg-white border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface text-sm uppercase">
                                @error('categoryDefaults.units.level2_code') <span class="text-error text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="space-y-xs">
                            <label class="text-xs text-on-surface-variant font-medium">How many <strong>{{ $categoryDefaults['units']['level1_name'] ?: 'base units' }}</strong> in 1 <strong>{{ $categoryDefaults['units']['level2_name'] ?: 'group unit' }}</strong>?</label>
                            <div class="flex items-center gap-sm">
                                <div class="flex items-center gap-xs bg-white border border-outline-variant/50 rounded-lg px-sm py-xs focus-within:ring-2 focus-within:ring-secondary w-36">
                                    <span class="text-xs text-on-surface-variant select-none font-medium whitespace-nowrap">1 {{ $categoryDefaults['units']['level2_name'] ?: '...' }} =</span>
                                    <input type="number" wire:model.live="categoryDefaults.units.level2_conversion" placeholder="qty" min="0.0001" step="any" class="w-16 bg-transparent border-none focus:ring-0 outline-none text-on-surface font-bold text-sm text-right">
                                </div>
                                <span class="text-sm font-bold text-on-surface-variant">{{ $categoryDefaults['units']['level1_name'] ?: '...' }}</span>
                            </div>
                            @error('categoryDefaults.units.level2_conversion') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>

                        @if(!empty($categoryDefaults['units']['level2_name']) && !empty($categoryDefaults['units']['level2_conversion']))
                            <div class="bg-secondary/8 border border-secondary/15 rounded-lg px-sm py-xs flex items-center gap-xs select-none">
                                <span class="material-symbols-outlined text-secondary text-[16px]">package_2</span>
                                <span class="text-xs text-secondary font-medium">
                                    <strong>1 {{ $categoryDefaults['units']['level2_name'] }} ({{ $categoryDefaults['units']['level2_code'] ?: '...' }})</strong> = <strong>{{ $categoryDefaults['units']['level2_conversion'] }} {{ $categoryDefaults['units']['level1_name'] }}</strong>
                                </span>
                            </div>
                        @else
                            <div class="bg-surface-container-low border border-dashed border-outline-variant/30 rounded-lg px-sm py-xs flex items-center gap-xs select-none opacity-60">
                                <span class="material-symbols-outlined text-[16px]">info</span>
                                <span class="text-xs text-on-surface-variant">Fill in Level 2 fields to enable group unit ordering</span>
                            </div>
                        @endif
                    </div>
                </div>

                @if(!empty($categoryDefaults['units']['level2_name']) && !empty($categoryDefaults['units']['level2_conversion']))
                    <div class="bg-gradient-to-r from-primary/5 to-secondary/5 border border-outline-variant/20 rounded-xl px-lg py-md flex items-center gap-md select-none mt-md">
                        <span class="material-symbols-outlined text-primary text-[28px]">swap_horiz</span>
                        <div class="flex-1">
                            <p class="font-label-md text-on-surface">Unit Relationship</p>
                            <p class="text-sm font-bold text-primary">
                                1 <span class="text-secondary">{{ $categoryDefaults['units']['level2_name'] }}</span> ({{ $categoryDefaults['units']['level2_code'] ?: '...' }})
                                = {{ $categoryDefaults['units']['level2_conversion'] }} <span class="text-primary">{{ $categoryDefaults['units']['level1_name'] }}</span> ({{ $categoryDefaults['units']['level1_code'] ?: '...' }})
                            </p>
                            <p class="text-xs text-on-surface-variant">Customers can order in individual <strong>{{ $categoryDefaults['units']['level1_name'] }}</strong> or by the <strong>{{ $categoryDefaults['units']['level2_name'] }}</strong>.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button variant="primary" icon="save" wire:click="saveCategoryDefaults">Save Defaults</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <!-- Delete Modal -->
    <x-admin.modal id="delete-product" title="Delete Product" maxWidth="md">
        <div class="space-y-md select-none">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-error/10 mx-auto mb-lg text-error">
                <span class="material-symbols-outlined text-[32px]">warning</span>
            </div>
            <h3 class="text-center font-title-lg text-on-surface">Delete Product?</h3>
            <p class="text-center font-body-md text-on-surface-variant">
                This product will no longer be available in the catalog. Existing order history may still reference it later.
            </p>
        </div>
        <x-slot name="footer" class="select-none">
            <x-admin.button variant="ghost" wire:click="closeDeleteModal">Cancel</x-admin.button>
            <x-admin.button wire:click="delete" variant="primary" class="bg-error hover:bg-error/90 text-white border-error">Delete Product</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <!-- Manage Variation Value Media Modal -->
    <x-admin.modal id="manage-value-media" title="Manage Variation Value Images" maxWidth="2xl">
        @if($managingGroupIndex !== null && $managingValueIndex !== null)
            @php
                $activeGroup = $variationGroups[$managingGroupIndex];
                $activeVal = $activeGroup['values'][$managingValueIndex];
            @endphp
            <div class="space-y-lg select-none">
                <div>
                    <h4 class="font-title-md text-primary">Images for {{ $activeGroup['name'] }}: <span class="font-bold text-secondary">{{ $activeVal['value'] ?: 'Untitled Value' }}</span></h4>
                    <p class="text-xs text-on-surface-variant">Select existing product images or upload new ones specifically for this variation value.</p>
                </div>

                <!-- Existing product images selector -->
                <div class="space-y-xs">
                    <span class="font-label-md text-on-surface-variant block">Select from Product Media</span>
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-sm">
                        @forelse($existingMedia as $m)
                            @php
                                $isSelected = in_array($m['file_path'], $activeVal['media'] ?? []);
                            @endphp
                            <div wire:click="toggleValueProductMedia('{{ $m['file_path'] }}')" class="border rounded-lg overflow-hidden bg-surface-container-low relative aspect-square flex items-center justify-center cursor-pointer transition-all hover:scale-95 {{ $isSelected ? 'ring-4 ring-secondary border-secondary' : 'border-outline-variant/30' }}">
                                <img src="{{ Storage::url($m['file_path']) }}" class="w-full h-full object-cover">
                                @if($isSelected)
                                    <span class="absolute top-1 right-1 bg-secondary text-on-secondary rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold shadow">&check;</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-on-surface-variant col-span-6">No product media uploaded in Step 2 yet.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Value specific upload zone -->
                <div class="space-y-xs border-t border-outline-variant/20 pt-md">
                    <span class="font-label-md text-on-surface-variant block">Upload Specific Images</span>
                    <div class="flex items-center gap-md">
                        <input type="file" multiple id="val-media-uploader" class="hidden" wire:model="valueMediaUploads" accept="image/png, image/jpeg, image/jpg, image/webp">
                        <label for="val-media-uploader" class="px-md py-sm bg-primary/10 text-primary hover:bg-primary/20 rounded-lg font-bold text-xs cursor-pointer select-none transition-all flex items-center gap-xs">
                            <span class="material-symbols-outlined text-[16px]">cloud_upload</span>
                            Choose Images
                        </label>
                        @if(!empty($valueMediaUploads))
                            <button type="button" wire:click="uploadValueMedia" class="px-md py-sm bg-secondary text-on-secondary hover:bg-secondary/95 rounded-lg font-bold text-xs transition-all">
                                Upload & Add ({{ count($valueMediaUploads) }})
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Active media list for this value -->
                @if(!empty($activeVal['media']))
                    <div class="space-y-xs border-t border-outline-variant/20 pt-md">
                        <span class="font-label-md text-on-surface-variant block">Assigned Value Images ({{ count($activeVal['media']) }})</span>
                        <div class="grid grid-cols-4 sm:grid-cols-8 gap-sm">
                            @foreach($activeVal['media'] as $path)
                                <div class="border rounded-lg overflow-hidden bg-surface-container-low relative aspect-square flex items-center justify-center border-outline-variant/30 group">
                                    <img src="{{ Storage::url($path) }}" class="w-full h-full object-cover">
                                    <button type="button" wire:click="toggleValueProductMedia('{{ $path }}')" class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-error font-bold text-xs" title="Remove image">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
        <x-slot name="footer" class="select-none">
            <x-admin.button variant="primary" @click="show = false">Done</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <script>
        if (typeof insertMarkdown !== 'function') {
            window.insertMarkdown = function(textareaId, tagOpen, tagClose = '') {
                const ta = document.getElementById(textareaId);
                if (!ta) return;
                const start = ta.selectionStart;
                const end = ta.selectionEnd;
                const text = ta.value;
                const selected = text.substring(start, end);
                const replacement = tagOpen + selected + tagClose;
                ta.value = text.substring(0, start) + replacement + text.substring(end);
                ta.focus();
                if (start === end) {
                    const newCursorPos = start + tagOpen.length;
                    ta.setSelectionRange(newCursorPos, newCursorPos);
                } else {
                    ta.setSelectionRange(start + tagOpen.length, start + tagOpen.length + selected.length);
                }
                ta.dispatchEvent(new Event('input'));
            }
        }
    </script>
</div>
