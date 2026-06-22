<div>
    <x-slot:title>Products</x-slot:title>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Product Catalog</h1>
            <p class="font-body-md text-on-surface-variant">Manage your inventory, variants, base pricing, and discount overrides.</p>
        </div>
        <div class="flex gap-md w-full sm:w-auto">
            <x-admin.button variant="primary" icon="add" wire:click="create">Add Product</x-admin.button>
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
                        <th class="px-lg py-md">Categories</th>
                        <th class="px-lg py-md font-bold text-primary">Base Price</th>
                        <th class="px-lg py-md text-center">Stock</th>
                        <th class="px-lg py-md text-center">Variations</th>
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
                            <td class="px-lg py-md text-on-surface-variant max-w-[200px] truncate">
                                {{ $prod->categories->pluck('name')->implode(', ') ?: 'Uncategorized' }}
                            </td>
                            <td class="px-lg py-md font-bold text-primary">₹{{ number_format($prod->base_price, 2) }}</td>
                            <td class="px-lg py-md text-center">
                                @if($prod->stock_quantity > 0)
                                    <span class="font-semibold text-success">{{ $prod->stock_quantity }}</span>
                                @else
                                    <span class="font-semibold text-error">Out of stock</span>
                                @endif
                            </td>
                            <td class="px-lg py-md text-center whitespace-nowrap">
                                <span class="inline-flex items-center justify-center px-sm py-xxs rounded bg-secondary-container text-on-secondary-container font-bold text-xs select-none">
                                    {{ $prod->combinations->count() ?: 'None' }}
                                </span>
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
                            <td colspan="8" class="px-lg py-2xl text-center text-on-surface-variant font-medium">
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

    <!-- Wizard Modal -->
    <x-admin.modal id="add-product" title="{{ $selectedProductId ? 'Edit Product' : 'Add New Product' }}" maxWidth="5xl">
        <!-- Stepper Navigation -->
        <div class="border-b border-outline-variant/20 px-xl py-md bg-surface-container-low flex flex-nowrap items-center justify-between gap-md overflow-x-auto whitespace-nowrap select-none">
            @php
                $steps = [
                    1 => 'Basic Info',
                    2 => 'Media',
                    3 => 'Categories',
                    4 => 'Variations',
                    5 => 'Combinations',
                    6 => 'Pricing & Units',
                    7 => 'Review'
                ];
            @endphp
            @foreach($steps as $num => $title)
                <button type="button" x-on:click="$wire.call('selectStep', {{ $num }})" class="flex items-center gap-xs font-label-md transition-all focus:outline-none {{ $currentStep == $num ? 'text-primary font-bold' : ($currentStep > $num ? 'text-secondary' : 'text-on-surface-variant/40') }}">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $currentStep == $num ? 'bg-primary text-on-primary' : ($currentStep > $num ? 'bg-secondary-container text-on-secondary-container' : 'bg-outline-variant/30') }}">
                        {{ $num }}
                    </span>
                    <span class="hidden md:inline">{{ $title }}</span>
                </button>
            @endforeach
        </div>

        <!-- Wizard Steps Content -->
        <div class="p-xl overflow-y-auto max-h-[550px]" style="min-height: 400px;">
            
            <!-- STEP 1: Basic Info -->
            @if($currentStep === 1)
                <div class="space-y-lg">
                    {{-- Warning for existing products with missing GST --}}
                    @if($selectedProductId && $basicInfo['gst_percentage'] === '')
                        <div class="flex items-start gap-sm p-md rounded-lg bg-warning/10 border border-warning/30">
                            <span class="material-symbols-outlined text-warning text-[20px] shrink-0 mt-0.5">warning</span>
                            <div>
                                <p class="font-label-md text-on-surface font-bold">GST Not Configured</p>
                                <p class="font-body-sm text-on-surface-variant">This product is missing a GST percentage. Customers will not be able to purchase it until GST is set. Please enter the applicable rate below.</p>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                        <div class="space-y-xs">
                            <label class="font-label-md text-on-surface-variant">Product Title *</label>
                            <input type="text" wire:model="basicInfo.title" placeholder="e.g. Classic Premium Linen Shirt" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                            @error('basicInfo.title') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-xs">
                            <label class="font-label-md text-on-surface-variant">Base Price (MRP in INR) *</label>
                            <div class="relative">
                                <span class="absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant font-bold">₹</span>
                                <input type="number" step="0.01" wire:model="basicInfo.base_price" placeholder="0.00" class="w-full pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                            </div>
                            @error('basicInfo.base_price') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-xs">
                            <label class="font-label-md text-on-surface-variant">HSN Code</label>
                            <input type="text" wire:model="basicInfo.hsn_code" placeholder="e.g. 6205" maxlength="20" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface font-mono">
                            <p class="text-xs text-on-surface-variant/70">Enter the applicable HSN code for this product if available.</p>
                            @error('basicInfo.hsn_code') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-xs">
                            <label class="font-label-md text-on-surface-variant">GST Percentage *</label>
                            <div class="relative">
                                <input type="number" step="0.01" min="0" max="100" wire:model="basicInfo.gst_percentage" placeholder="e.g. 12" class="w-full pr-xl px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                                <span class="absolute right-md top-1/2 -translate-y-1/2 text-on-surface-variant font-bold">%</span>
                            </div>
                            <p class="text-xs text-on-surface-variant/70">Enter the GST % for this product. Used for cart, checkout, and order tax calculation. Enter <strong>0</strong> for zero-rated products.</p>
                            @error('basicInfo.gst_percentage') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-xs">
                            <label class="font-label-md text-on-surface-variant">Min Order Qty *</label>
                            <input type="number" min="1" step="1" wire:model="basicInfo.minimum_order_quantity" placeholder="e.g. 1" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                            <p class="text-xs text-on-surface-variant/70">Minimum quantity a customer must add to their cart.</p>
                            @error('basicInfo.minimum_order_quantity') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-xs">
                            <label class="font-label-md text-on-surface-variant">Product Type *</label>
                            <select wire:model.live="basicInfo.product_type" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                                <option value="retail">Retail Product (Stock defined, bought from another business)</option>
                                <option value="manufactured">Manufactured Product (Stock not defined/unlimited, made to order)</option>
                            </select>
                            @error('basicInfo.product_type') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Markdown Description Editor -->
                    <div class="space-y-xs">
                        <label class="font-label-md text-on-surface-variant select-none">Description</label>
                        
                        <div class="border border-outline-variant/60 rounded-lg overflow-hidden bg-white shadow-sm">
                            <!-- Quick Markup toolbar -->
                            <div class="px-md py-xs border-b border-outline-variant/30 bg-surface-container-low/40 flex items-center gap-md select-none flex-wrap">
                                <!-- Group 1: Text style -->
                                <div class="flex items-center gap-xs">
                                    <button type="button" onclick="const ta = document.getElementById('desc-editor'); const start = ta.selectionStart; const end = ta.selectionEnd; const text = ta.value; ta.value = text.substring(0, start) + '**' + text.substring(start, end) + '**' + text.substring(end); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container font-extrabold text-sm text-primary" title="Bold">B</button>
                                    <button type="button" onclick="const ta = document.getElementById('desc-editor'); const start = ta.selectionStart; const end = ta.selectionEnd; const text = ta.value; ta.value = text.substring(0, start) + '*' + text.substring(start, end) + '*' + text.substring(end); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container italic font-bold text-sm text-primary" title="Italic">I</button>
                                    <button type="button" onclick="const ta = document.getElementById('desc-editor'); const start = ta.selectionStart; const text = ta.value; ta.value = text.substring(0, start) + '### ' + text.substring(start); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container font-bold text-sm text-primary" title="Heading">H</button>
                                </div>

                                <div class="w-px h-5 bg-outline-variant/40"></div>

                                <!-- Group 2: Lists & Quote -->
                                <div class="flex items-center gap-xs">
                                    <button type="button" onclick="const ta = document.getElementById('desc-editor'); const start = ta.selectionStart; const text = ta.value; ta.value = text.substring(0, start) + '> ' + text.substring(start); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary text-base font-bold" title="Quote">"</button>
                                    <button type="button" onclick="const ta = document.getElementById('desc-editor'); const start = ta.selectionStart; const text = ta.value; ta.value = text.substring(0, start) + '- ' + text.substring(start); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary flex items-center justify-center" title="Bullet List">
                                        <span class="material-symbols-outlined text-[20px]">format_list_bulleted</span>
                                    </button>
                                    <button type="button" onclick="const ta = document.getElementById('desc-editor'); const start = ta.selectionStart; const text = ta.value; ta.value = text.substring(0, start) + '1. ' + text.substring(start); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary flex items-center justify-center" title="Numbered List">
                                        <span class="material-symbols-outlined text-[20px]">format_list_numbered</span>
                                    </button>
                                </div>

                                <div class="w-px h-5 bg-outline-variant/40"></div>

                                <!-- Group 3: Utility links & preview -->
                                <div class="flex items-center gap-xs">
                                    <button type="button" onclick="const ta = document.getElementById('desc-editor'); const start = ta.selectionStart; const end = ta.selectionEnd; const text = ta.value; ta.value = text.substring(0, start) + '[Link Text](url)' + text.substring(end); ta.focus(); ta.dispatchEvent(new Event('input'));" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary flex items-center justify-center" title="Add Link">
                                        <span class="material-symbols-outlined text-[20px]">link</span>
                                    </button>
                                    <button type="button" wire:click="$toggle('isPreviewMode')" class="w-8 h-8 rounded flex items-center justify-center {{ $isPreviewMode ? 'bg-secondary/15 text-secondary' : 'text-primary hover:bg-surface-container' }} flex items-center justify-center" title="Toggle Preview">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </button>
                                    <a href="https://www.markdownguide.org/basic-syntax/" target="_blank" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary flex items-center justify-center" title="Markdown Help">
                                        <span class="material-symbols-outlined text-[20px]">help</span>
                                    </a>
                                </div>
                            </div>
                            
                            @if(!$isPreviewMode)
                                <textarea id="desc-editor" rows="6" wire:model="basicInfo.description" placeholder="Enter text..." class="w-full px-md py-md bg-transparent border-0 outline-none focus:ring-0 font-body-md text-on-surface resize-none min-h-[160px]"></textarea>
                            @else
                                <div class="prose max-w-none p-md min-h-[160px] bg-surface-container-low/20 text-on-surface text-sm overflow-y-auto">
                                    {!! Illuminate\Support\Str::markdown($basicInfo['description'] ?? '*Enter text...*') !!}
                                </div>
                            @endif
                        </div>
                        @error('basicInfo.description') <span class="text-error text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
            @endif

            <!-- STEP 2: Media Upload -->
            @if($currentStep === 2)
                <style>
                    .bg-checkered {
                        background-color: #ffffff;
                        background-image: 
                            linear-gradient(45deg, #efefef 25%, transparent 25%), 
                            linear-gradient(-45deg, #efefef 25%, transparent 25%), 
                            linear-gradient(45deg, transparent 75%, #efefef 75%), 
                            linear-gradient(-45deg, transparent 75%, #efefef 75%);
                        background-size: 16px 16px;
                        background-position: 0 0, 0 8px, 8px -8px, -8px 0px;
                    }
                </style>
                <div class="space-y-xl">
                    <!-- Drag and Drop Zone -->
                    <div x-data="{ dragging: false }"
                         @dragover.prevent="dragging = true"
                         @dragleave.prevent="dragging = false"
                         @drop.prevent="dragging = false; $wire.upload('mediaUploads', $event.dataTransfer.files)"
                         class="border border-outline-variant/60 rounded-xl p-xl flex flex-col items-center justify-center text-center transition-all bg-surface-container-low/20 select-none relative"
                         :class="dragging ? 'border-primary bg-primary/5' : 'hover:bg-surface-container-low/30'">
                        <input type="file" multiple id="media-uploader" class="hidden" wire:model="mediaUploads" accept="image/png, image/jpeg, image/jpg, image/webp">
                        <label for="media-uploader" class="cursor-pointer flex flex-col items-center">
                            <span class="material-symbols-outlined text-[44px] text-on-surface-variant/70 mb-xs select-none">cloud_upload</span>
                            <span class="font-bold text-on-surface text-lg mb-xxs">Product Gallery</span>
                            <span class="text-xs text-on-surface-variant mb-md">Upload high-quality images for the product.</span>
                            <span class="px-md py-sm bg-[#5c44c4] hover:bg-[#4d37a8] text-white rounded-lg font-semibold text-sm transition-all shadow-sm select-none">Add Images</span>
                            <span class="text-xs text-[#0284c7] font-semibold flex items-center gap-xxs mt-md select-none bg-[#f0f9ff] px-md py-xs rounded border border-[#bae6fd]/30">
                                <span class="material-symbols-outlined text-[16px]">info</span>
                                Recommended: 1080 &times; 1080
                            </span>
                        </label>
                    </div>

                    <!-- Gallery Grid -->
                    @if(!empty($existingMedia) || !empty($mediaUploads))
                        <div class="space-y-md">
                            <!-- Existing Images (Sortable) -->
                            @if(!empty($existingMedia))
                                <div 
                                    wire:ignore.self
                                    x-data
                                    x-init="
                                        $nextTick(() => {
                                            const grid = $el.querySelector('.sortable-grid');
                                            if (grid && typeof Sortable !== 'undefined') {
                                                new Sortable(grid, {
                                                    animation: 250,
                                                    ghostClass: 'sortable-ghost',
                                                    chosenClass: 'sortable-chosen',
                                                    dragClass: 'sortable-drag',
                                                    filter: '.no-drag',
                                                    preventOnFilter: false,
                                                    onEnd: function(evt) {
                                                        if (evt.oldIndex !== evt.newIndex) {
                                                            $wire.call('reorderExistingMediaInArray', evt.oldIndex, evt.newIndex);
                                                        }
                                                    }
                                                });
                                            }
                                        });
                                    "
                                >
                                    <style>
                                        .sortable-ghost {
                                            opacity: 0.3;
                                            border: 2px dashed #5c44c4 !important;
                                            background: rgba(92, 68, 196, 0.05) !important;
                                        }
                                        .sortable-chosen {
                                            box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
                                            transform: scale(1.05);
                                            z-index: 50;
                                        }
                                        .sortable-drag {
                                            opacity: 0.9;
                                        }
                                    </style>
                                    <div class="sortable-grid grid grid-cols-2 sm:grid-cols-4 gap-lg select-none">
                                        @foreach($existingMedia as $index => $m)
                                            <div 
                                                class="drag-handle border rounded-lg overflow-hidden bg-checkered relative group aspect-square flex items-center justify-center border-outline-variant/30 cursor-grab active:cursor-grabbing transition-all duration-200 shadow-sm hover:shadow-md hover:scale-[1.02]"
                                                wire:key="existing-media-{{ $m['id'] }}"
                                            >
                                                <img src="{{ Storage::url($m['file_path']) }}" class="w-full h-full object-cover select-none pointer-events-none" draggable="false">
                                                
                                                <!-- Drag Handle Indicator (top-left) -->
                                                <span class="absolute top-2 left-2 text-white/90 opacity-0 group-hover:opacity-100 transition-opacity drop-shadow-lg pointer-events-none">
                                                    <span class="material-symbols-outlined text-[20px]">drag_indicator</span>
                                                </span>

                                                <!-- Order Number Badge -->
                                                <span class="absolute top-2 left-8 bg-black/50 text-white text-[10px] font-bold rounded px-1.5 py-0.5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">{{ $index + 1 }}</span>

                                                <!-- Delete Button (Top Right) -->
                                                <button type="button" x-on:click.stop.prevent="$wire.call('removeExistingMedia', {{ $m['id'] }})" class="bg-[#ef4444] hover:bg-[#dc2626] text-white rounded-md w-7 h-7 flex items-center justify-center absolute top-2 right-2 shadow z-10 transition-colors cursor-pointer" title="Delete">
                                                    <span class="material-symbols-outlined text-[16px] font-bold">close</span>
                                                </button>

                                                <!-- Cover Label -->
                                                @if($m['is_primary'])
                                                    <span class="absolute bottom-2 left-2 px-sm py-xxs bg-[#5c44c4] text-on-primary text-[10px] font-bold rounded shadow-sm select-none pointer-events-none">COVER</span>
                                                @else
                                                    <button type="button" x-on:click.stop.prevent="$wire.call('setPrimaryMedia', {{ $m['id'] }})" class="absolute bottom-2 left-2 px-sm py-xxs bg-white/95 text-[#5c44c4] text-[10px] font-bold rounded shadow-sm select-none opacity-0 group-hover:opacity-100 transition-opacity hover:bg-white cursor-pointer">SET COVER</button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- New Upload Previews (Sortable) -->
                            @if(!empty($mediaUploads))
                                <div 
                                    wire:ignore.self
                                    x-data
                                    x-init="
                                        $nextTick(() => {
                                            const grid = $el.querySelector('.sortable-uploads-grid');
                                            if (grid && typeof Sortable !== 'undefined') {
                                                new Sortable(grid, {
                                                    animation: 250,
                                                    ghostClass: 'sortable-ghost',
                                                    chosenClass: 'sortable-chosen',
                                                    dragClass: 'sortable-drag',
                                                    filter: '.no-drag',
                                                    preventOnFilter: false,
                                                    onEnd: function(evt) {
                                                        if (evt.oldIndex !== evt.newIndex) {
                                                            $wire.call('reorderUploadedMedia', evt.oldIndex, evt.newIndex);
                                                        }
                                                    }
                                                });
                                            }
                                        });
                                    "
                                >
                                    <div class="sortable-uploads-grid grid grid-cols-2 sm:grid-cols-4 gap-lg select-none">
                                        @foreach($mediaUploads as $index => $file)
                                            <div class="border rounded-lg overflow-hidden bg-checkered relative group aspect-square flex items-center justify-center border-outline-variant/30 shadow-sm cursor-grab active:cursor-grabbing transition-all duration-200 hover:shadow-md hover:scale-[1.02]" wire:key="new-upload-{{ $index }}">
                                                @php
                                                    $tempUrl = null;
                                                    try { $tempUrl = $file->temporaryUrl(); } catch (\Exception $e) {}
                                                @endphp
                                                @if($tempUrl)
                                                    <img src="{{ $tempUrl }}" class="w-full h-full object-cover select-none pointer-events-none" draggable="false" />
                                                @else
                                                    <div class="flex flex-col items-center justify-center text-on-surface-variant/50">
                                                        <span class="material-symbols-outlined text-3xl">image</span>
                                                        <span class="text-[10px] mt-1">Preview unavailable</span>
                                                    </div>
                                                @endif
                                                
                                                <!-- Drag Handle Indicator (top-left) -->
                                                <span class="absolute top-2 left-2 text-white/90 opacity-0 group-hover:opacity-100 transition-opacity drop-shadow-lg pointer-events-none">
                                                    <span class="material-symbols-outlined text-[20px]">drag_indicator</span>
                                                </span>

                                                <!-- Order Number Badge -->
                                                <span class="absolute top-2 left-8 bg-black/50 text-white text-[10px] font-bold rounded px-1.5 py-0.5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">{{ $index + 1 }}</span>

                                                <!-- Delete Button -->
                                                <button type="button" x-on:click.stop.prevent="$wire.call('deleteUploadedFile', {{ $index }})" class="bg-[#ef4444] hover:bg-[#dc2626] text-white rounded-md w-7 h-7 flex items-center justify-center absolute top-2 right-2 shadow z-10 transition-colors cursor-pointer" title="Remove">
                                                    <span class="material-symbols-outlined text-[16px] font-bold">close</span>
                                                </button>
                                                <span class="absolute bottom-2 left-2 px-sm py-xxs bg-secondary text-on-secondary text-[10px] font-bold rounded shadow-sm select-none">NEW</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            <!-- STEP 3: Categories Assignment -->
            @if($currentStep === 3)
                <div class="space-y-lg select-none">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="font-title-md text-primary">Assign Categories *</h4>
                            <p class="text-xs text-on-surface-variant">Assign this product to one or multiple categories recursively.</p>
                        </div>
                        @if(!empty($selectedCategoryIds))
                            <button type="button" wire:click="$set('selectedCategoryIds', [])" class="text-xs text-error font-bold hover:underline">Clear All</button>
                        @endif
                    </div>

                    <!-- Selected categories list -->
                    @if(!empty($selectedCategoryIds))
                        <div class="flex flex-wrap gap-xs p-sm bg-surface-container/30 border border-outline-variant/20 rounded-lg">
                            @foreach($selectedCategoryIds as $catId)
                                @php
                                    $catModel = $categories->firstWhere('id', $catId);
                                @endphp
                                @if($catModel)
                                    <span class="inline-flex items-center gap-xxs px-sm py-xxs bg-secondary-container text-on-secondary-container text-xs font-semibold rounded-full">
                                        {{ $catModel->name }}
                                        <button type="button" x-on:click="$wire.call('removeCategory', {{ $catId }})" class="hover:text-error text-sm font-bold">&times;</button>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <!-- Recursive categories checklist -->
                    <div class="border border-outline-variant/30 rounded-lg p-lg max-h-[300px] overflow-y-auto bg-surface-container-low custom-scrollbar">
                        @foreach($categories->whereNull('parent_id') as $cat)
                            @include('admin.products.category-checkbox-item', ['cat' => $cat, 'prefix' => ''])
                        @endforeach
                    </div>
                    @error('selectedCategoryIds') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>
            @endif

            <!-- STEP 4: Variations -->
            @if($currentStep === 4)
                <div class="space-y-lg">
                    <div class="flex justify-between items-center select-none">
                        <div>
                            <h4 class="font-title-md text-primary">Variant Configurations</h4>
                            <p class="text-xs text-on-surface-variant">Add properties like size, color, or fabric. Skip if non-variant.</p>
                        </div>
                        <x-admin.button variant="outline" size="sm" icon="add" type="button" wire:click="addVariationGroup">Add Group</x-admin.button>
                    </div>

                    @foreach($variationGroups as $gIndex => $group)
                        <div class="border border-outline-variant/30 rounded-xl p-lg bg-surface-container-low/40 space-y-md relative">
                            <!-- Delete group button -->
                            <button type="button" x-on:click="$wire.call('removeVariationGroup', {{ $gIndex }})" class="absolute top-4 right-4 text-outline hover:text-error transition-colors focus:outline-none" title="Remove group">
                                <span class="material-symbols-outlined text-[20px]">delete</span>
                            </button>

                            <!-- Single Row for Group Configuration -->
                            <div class="flex flex-col md:flex-row gap-lg items-end">
                                <div class="space-y-xs flex-1 w-full">
                                    <label class="font-label-md text-on-surface-variant">Group Name *</label>
                                    <input type="text" wire:model="variationGroups.{{ $gIndex }}.name" placeholder="e.g. Size, Color" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                                    @error("variationGroups.{$gIndex}.name") <span class="text-error text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-xs w-full md:w-64">
                                    <label class="font-label-md text-on-surface-variant">Type</label>
                                    <select wire:model.live="variationGroups.{{ $gIndex }}.display_type" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                                        <option value="text">Text Label</option>
                                        <option value="color">Color Swatch</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-sm h-12 w-full md:w-auto pb-sm select-none">
                                    <!-- Switch style checkbox -->
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="g_img_{{ $gIndex }}" wire:model="variationGroups.{{ $gIndex }}.has_images" class="sr-only peer">
                                        <div class="w-11 h-6 bg-outline-variant/50 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary"></div>
                                        <span class="ml-sm font-body-md text-on-surface">Has Images</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Values Table layout -->
                            <div class="space-y-sm">
                                <div class="overflow-x-auto border border-outline-variant/20 rounded-lg">
                                    <table class="w-full text-left font-body-md text-sm">
                                        <thead class="bg-surface-container text-on-surface-variant font-bold uppercase text-xs border-b border-outline-variant/20 select-none">
                                            <tr>
                                                <th class="px-lg py-sm">Value</th>
                                                @if(($group['display_type'] ?? '') === 'color')
                                                    <th class="px-lg py-sm text-center">Color</th>
                                                @endif
                                                @if(!empty($group['has_images']))
                                                    <th class="px-lg py-sm text-center">Gallery</th>
                                                @endif
                                                <th class="px-lg py-sm text-center">Default</th>
                                                <th class="px-lg py-sm text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-outline-variant/10 bg-white">
                                            @foreach($group['values'] ?? [] as $vIndex => $val)
                                                <tr wire:key="var-val-row-{{ $gIndex }}-{{ $vIndex }}">
                                                    <td class="px-lg py-md">
                                                        <input type="text" wire:model="variationGroups.{{ $gIndex }}.values.{{ $vIndex }}.value" placeholder="e.g. UK6, XXL" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                                                        @error("variationGroups.{$gIndex}.values.{$vIndex}.value") <span class="text-error text-xs block">{{ $message }}</span> @enderror
                                                    </td>
                                                    @if(($group['display_type'] ?? '') === 'color')
                                                        <td class="px-lg py-md text-center">
                                                            <div class="flex items-center justify-center gap-xs">
                                                                <input type="color" wire:model="variationGroups.{{ $gIndex }}.values.{{ $vIndex }}.color_hex" class="w-12 h-8 p-0 border border-outline-variant/50 rounded cursor-pointer overflow-hidden bg-transparent" title="Select color hex">
                                                                <span class="font-mono text-xs text-on-surface-variant">{{ $val['color_hex'] ?: '#000000' }}</span>
                                                            </div>
                                                        </td>
                                                    @endif
                                                    @if(!empty($group['has_images']))
                                                        <td class="px-lg py-md text-center">
                                                            <button type="button" x-on:click="$wire.call('manageValueMedia', {{ $gIndex }}, {{ $vIndex }})" class="inline-flex items-center gap-xs px-md py-xs bg-primary/10 hover:bg-primary/20 text-primary rounded-lg font-bold text-xs transition-colors">
                                                                <span class="material-symbols-outlined text-[16px]">image</span>
                                                                Manage ({{ count($val['media'] ?? []) }})
                                                            </button>
                                                        </td>
                                                    @endif
                                                    <td class="px-lg py-md text-center">
                                                        <input type="radio" name="default_var_{{ $gIndex }}" x-on:click="$wire.call('setVariationDefault', {{ $gIndex }}, {{ $vIndex }})" {{ $val['is_default'] ? 'checked' : '' }} class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
                                                    </td>
                                                    <td class="px-lg py-md text-right">
                                                        <button type="button" x-on:click="$wire.call('removeVariationValue', {{ $gIndex }}, {{ $vIndex }})" class="p-xs rounded bg-error/15 text-error hover:bg-error/20 text-xs font-bold transition-all focus:outline-none" title="Remove Value">
                                                            <span class="material-symbols-outlined text-[18px]">close</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="flex justify-end select-none">
                                    <button type="button" x-on:click="$wire.call('addVariationValue', {{ $gIndex }})" class="px-md py-sm bg-primary/10 text-primary hover:bg-primary/20 rounded-lg font-bold text-xs transition-colors">+ Add Value</button>
                                </div>
                                @error("variationGroups.{$gIndex}.values") <span class="text-error text-xs block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- STEP 5: Combinations Matrix / Stock -->
            @if($currentStep === 5)
                <div class="space-y-lg">
                    @if($basicInfo['product_type'] === 'manufactured')
                        @if(empty($variationGroups))
                            <div class="max-w-md p-md rounded-lg bg-secondary/5 border border-secondary/20 select-none">
                                <h4 class="font-title-md text-primary mb-xs">Manufactured Product</h4>
                                <p class="text-sm text-on-surface-variant">This product is set as a <strong>Manufactured Product</strong>. Stock tracking is not required, and it will be available for purchase on demand.</p>
                            </div>
                        @else
                            <!-- Combinations Table for Manufactured Product (No Stock columns) -->
                            <div class="overflow-x-auto border border-outline-variant/20 rounded-lg">
                                <table class="w-full text-left font-body-md">
                                    <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider text-xs border-b border-outline-variant/20 select-none">
                                        <tr>
                                            <th class="px-lg py-sm">Combination</th>
                                            <th class="px-lg py-sm text-center">Price Override</th>
                                            <th class="px-lg py-sm text-center">Active</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant/10 bg-white">
                                        @foreach($combinations as $cIndex => $comb)
                                            <tr>
                                                <td class="px-lg py-md whitespace-nowrap font-bold text-primary text-sm">
                                                    {{ collect($comb['combination_values'])->map(fn($v, $k) => "$k: $v")->implode(', ') }}
                                                </td>
                                                <td class="px-lg py-md text-center">
                                                    <div class="flex items-center justify-center">
                                                        <div class="relative w-32 flex items-center bg-surface-container-low border border-outline-variant/50 rounded px-sm focus-within:ring-1 focus-within:ring-secondary focus-within:border-secondary">
                                                            <span class="text-on-surface-variant font-bold text-xs select-none pr-xs">₹</span>
                                                            <input type="number" step="0.01" wire:model="combinations.{{ $cIndex }}.price" placeholder="{{ (float)($basicInfo['base_price'] ?: 0) }}" class="w-full bg-transparent border-none p-xs text-right focus:ring-0 outline-none text-on-surface font-semibold text-sm">
                                                        </div>
                                                    </div>
                                                    @error("combinations.{$cIndex}.price") <span class="text-error text-xs block">{{ $message }}</span> @enderror
                                                </td>
                                                <td class="px-lg py-md text-center">
                                                    <input type="checkbox" wire:model="combinations.{{ $cIndex }}.is_active" class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @else
                        @if(empty($variationGroups))
                            <div class="max-w-md space-y-md">
                                <h4 class="font-title-md text-primary">Non-Variant Inventory</h4>
                                <div class="space-y-xs">
                                    <label class="font-label-md text-on-surface-variant">Total Stock Quantity *</label>
                                    <input type="number" wire:model="nonVariantStock" placeholder="0" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                                    @error('nonVariantStock') <span class="text-error text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        @else
                            <!-- Combinations Table -->
                            <div class="overflow-x-auto border border-outline-variant/20 rounded-lg">
                                <table class="w-full text-left font-body-md">
                                    <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider text-xs border-b border-outline-variant/20 select-none">
                                        <tr>
                                            <th class="px-lg py-sm">Combination</th>
                                            <th class="px-lg py-sm text-center">Stock *</th>
                                            <th class="px-lg py-sm text-center">Price Override</th>
                                            <th class="px-lg py-sm text-center">Active</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-outline-variant/10 bg-white">
                                        @foreach($combinations as $cIndex => $comb)
                                            <tr>
                                                <td class="px-lg py-md whitespace-nowrap font-bold text-primary text-sm">
                                                    {{ collect($comb['combination_values'])->map(fn($v, $k) => "$k: $v")->implode(', ') }}
                                                </td>
                                                <td class="px-lg py-md text-center">
                                                    <input type="number" wire:model="combinations.{{ $cIndex }}.stock_quantity" placeholder="0" class="w-20 px-sm py-xs border border-outline-variant/50 rounded text-center focus:ring-1 focus:ring-secondary outline-none text-on-surface">
                                                    @error("combinations.{$cIndex}.stock_quantity") <span class="text-error text-xs block">{{ $message }}</span> @enderror
                                                </td>
                                                <td class="px-lg py-md text-center">
                                                    <div class="flex items-center justify-center">
                                                        <div class="relative w-32 flex items-center bg-surface-container-low border border-outline-variant/50 rounded px-sm focus-within:ring-1 focus-within:ring-secondary focus-within:border-secondary">
                                                            <span class="text-on-surface-variant font-bold text-xs select-none pr-xs">₹</span>
                                                            <input type="number" step="0.01" wire:model="combinations.{{ $cIndex }}.price" placeholder="{{ (float)($basicInfo['base_price'] ?: 0) }}" class="w-full bg-transparent border-none p-xs text-right focus:ring-0 outline-none text-on-surface font-semibold text-sm">
                                                        </div>
                                                    </div>
                                                    @error("combinations.{$cIndex}.price") <span class="text-error text-xs block">{{ $message }}</span> @enderror
                                                </td>
                                                <td class="px-lg py-md text-center">
                                                    <input type="checkbox" wire:model="combinations.{{ $cIndex }}.is_active" class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            <!-- STEP 6: Pricing overrides & Units -->
            @if($currentStep === 6)
                <div class="space-y-xl">
                    <!-- Pricing overrides -->
                    <div class="space-y-md">
                        <h4 class="font-title-md text-primary">Customer Level-Specific Discount Overrides</h4>
                        <p class="text-xs text-on-surface-variant">Define custom discounts overrides for specific levels instead of default level discounts.</p>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-lg bg-surface-container-low/40 p-lg border border-outline-variant/20 rounded-lg">
                            @foreach($customerLevels as $level)
                                <div class="space-y-xs">
                                    <label class="font-label-md text-on-surface-variant">{{ $level->name }}</label>
                                    <div class="flex items-center gap-xs">
                                        <div class="relative w-full">
                                            <input type="number" step="0.01" wire:model="pricingOverrides.{{ $level->id }}" placeholder="Default: {{ $level->discount_percentage }}%" class="w-full pr-lg px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                                            <span class="absolute right-md top-1/2 -translate-y-1/2 text-on-surface-variant font-bold text-sm">%</span>
                                        </div>
                                    </div>
                                    @error("pricingOverrides.{$level->id}") <span class="text-error text-xs">{{ $message }}</span> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Unit setup -->
                    <div class="space-y-md border-t border-outline-variant/20 pt-lg">
                        <h4 class="font-title-md text-primary">Product Unit Configuration</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-lg">
                            <div class="space-y-xs">
                                <label class="font-label-md text-on-surface-variant">Level 1 Unit Name *</label>
                                <input type="text" wire:model="units.level1_name" placeholder="e.g. Piece" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                                @error('units.level1_name') <span class="text-error text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-xs">
                                <label class="font-label-md text-on-surface-variant">Level 1 Code *</label>
                                <input type="text" wire:model="units.level1_code" placeholder="e.g. pcs" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface uppercase">
                                @error('units.level1_code') <span class="text-error text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-xs">
                                <label class="font-label-md text-on-surface-variant">Level 2 Unit Name (Optional)</label>
                                <input type="text" wire:model="units.level2_name" placeholder="e.g. Box" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                                @error('units.level2_name') <span class="text-error text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-xs">
                                <label class="font-label-md text-on-surface-variant">Level 2 Code (Optional)</label>
                                <input type="text" wire:model="units.level2_code" placeholder="e.g. box" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface uppercase">
                                @error('units.level2_code') <span class="text-error text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-xs sm:col-span-2">
                                <label class="font-label-md text-on-surface-variant">Level 2 Conversion Qty ({{ $units['level1_name'] ?: 'Pieces' }} inside {{ $units['level2_name'] ?: 'Box' }})</label>
                                <input type="number" wire:model="units.level2_conversion" placeholder="e.g. 12 (means 1 {{ $units['level2_name'] ?: 'Box' }} = 12 {{ $units['level1_name'] ?: 'Pieces' }})" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                                @error('units.level2_conversion') <span class="text-error text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- STEP 7: Review -->
            @if($currentStep === 7)
                <div class="space-y-xl">
                    <h3 class="font-title-lg text-primary select-none border-b pb-xs border-outline-variant/20">Summary Review</h3>

                    <!-- Basic Info Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md bg-surface-container-low/40 p-md rounded-lg border border-outline-variant/20">
                        <div>
                            <span class="text-xs text-on-surface-variant block select-none">Title</span>
                            <span class="font-bold text-primary">{{ $basicInfo['title'] ?: 'Not Specified' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-on-surface-variant block select-none">Base Price</span>
                            <span class="font-bold text-primary">₹{{ number_format((float)($basicInfo['base_price'] ?: 0), 2) }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-on-surface-variant block select-none">HSN Code</span>
                            <span class="font-mono text-sm text-on-surface font-bold">{{ $basicInfo['hsn_code'] ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-on-surface-variant block select-none">GST Percentage</span>
                            @if($basicInfo['gst_percentage'] !== '')
                                <span class="font-bold text-success">{{ $basicInfo['gst_percentage'] }}%</span>
                            @else
                                <span class="font-bold text-error flex items-center gap-xxs">
                                    <span class="material-symbols-outlined text-[14px]">warning</span>
                                    Missing — product cannot be sold until set
                                </span>
                            @endif
                        </div>
                        @if($selectedProductId)
                            @php
                                $existingProduct = \App\Models\Product::find($selectedProductId);
                            @endphp
                            @if($existingProduct)
                                <div class="md:col-span-2">
                                    <span class="text-xs text-on-surface-variant block select-none">SKU</span>
                                    <span class="font-mono text-sm text-primary font-bold">{{ $existingProduct->sku }}</span>
                                </div>
                            @endif
                        @endif
                        <div class="md:col-span-2">
                            <span class="text-xs text-on-surface-variant block select-none">Description</span>
                            <div class="prose max-w-none text-xs border p-sm rounded bg-white mt-xs">
                                {!! Illuminate\Support\Str::markdown($basicInfo['description'] ?? '') !!}
                            </div>
                        </div>
                    </div>

                    <!-- Categories Summary -->
                    <div>
                        <span class="text-xs text-on-surface-variant block select-none mb-xs">Assigned Categories</span>
                        <div class="flex flex-wrap gap-xs">
                            @forelse($selectedCategoryIds as $catId)
                                @php
                                    $catModel = $categories->firstWhere('id', $catId);
                                @endphp
                                @if($catModel)
                                    <span class="px-sm py-xxs bg-secondary-container text-on-secondary-container text-xs rounded-full font-bold">
                                        {{ $catModel->name }}
                                    </span>
                                @endif
                            @empty
                                <span class="text-error text-xs font-semibold select-none">No categories assigned.</span>
                            @endforelse
                        </div>
                    </div>

                    <!-- Pricing & Units Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-lg select-none">
                        <div>
                            <span class="text-xs text-on-surface-variant block select-none mb-xs">Customer Pricing Details</span>
                            <table class="w-full text-left font-body-md text-xs border rounded-lg overflow-hidden">
                                <thead class="bg-surface-container text-on-surface-variant font-bold">
                                    <tr>
                                        <th class="p-xs">Level</th>
                                        <th class="p-xs text-center">Discount</th>
                                        <th class="p-xs text-right">Selling Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customerLevels as $level)
                                        @php
                                            $override = $pricingOverrides[$level->id] ?? '';
                                            $disc = $override !== '' ? (float)$override : (float)$level->discount_percentage;
                                            $price = (float)($basicInfo['base_price'] ?: 0) * (1 - ($disc / 100));
                                        @endphp
                                        <tr class="border-t">
                                            <td class="p-xs">{{ $level->name }}</td>
                                            <td class="p-xs text-center font-semibold text-secondary">
                                                {{ $disc }}% {{ $override !== '' ? '(Override)' : '(Default)' }}
                                            </td>
                                            <td class="p-xs text-right font-bold text-primary">₹{{ number_format($price, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <span class="text-xs text-on-surface-variant block select-none mb-xs">Units Setup</span>
                            <div class="p-md border rounded-lg bg-white space-y-xs">
                                <div>
                                    <span class="text-[10px] text-on-surface-variant block">Primary Unit (Level 1)</span>
                                    <span class="font-bold">{{ $units['level1_name'] }} ({{ $units['level1_code'] }})</span>
                                </div>
                                @if(!empty($units['level2_name']))
                                    <div class="border-t pt-xs">
                                        <span class="text-[10px] text-on-surface-variant block">Secondary Unit (Level 2)</span>
                                        <span class="font-bold">{{ $units['level2_name'] }} ({{ $units['level2_code'] }})</span>
                                        <span class="text-xs text-on-surface-variant block">Conversion: 1 {{ $units['level2_name'] }} = {{ $units['level2_conversion'] }} {{ $units['level1_code'] }}</span>
                                        <span class="text-xs text-primary font-bold block">Level 2 Price: ₹{{ number_format((float)($basicInfo['base_price'] ?: 0) * (float)$units['level2_conversion'], 2) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- Sticky Footer Buttons -->
        <x-slot name="footer" class="flex justify-between items-center select-none">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <div class="flex gap-sm">
                @if($currentStep > 1)
                    <x-admin.button variant="outline" type="button" wire:click="prevStep">Back</x-admin.button>
                @endif
                @if($selectedProductId && $currentStep < 7)
                    <x-admin.button variant="primary" type="button" wire:click="saveCurrentStep" icon="save">Save Changes</x-admin.button>
                @endif
                @if($currentStep < 7)
                    <x-admin.button variant="primary" type="button" wire:click="nextStep">Next Step</x-admin.button>
                @endif
                @if($currentStep === 7)
                    <x-admin.button variant="primary" type="button" wire:click="save" icon="save">
                        {{ $selectedProductId ? 'Save Changes' : 'Create Product' }}
                    </x-admin.button>
                @endif
            </div>
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
</div>
