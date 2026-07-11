<div>
    <x-slot:title>Categories</x-slot:title>

    <!-- ── Header ── -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Product Categories</h1>
            <p class="font-body-md text-on-surface-variant">Manage your product taxonomy. Leaf categories hold products; folder categories hold sub-categories.</p>
        </div>
    </div>

    <!-- ── Double Panel ── -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-xl items-start">

        <!-- ── Left Panel: Category Tree ── -->
        <div class="lg:col-span-4 bg-white rounded-xl card-shadow border border-outline-variant/30 overflow-hidden sticky top-6">
            <div class="px-lg py-md border-b border-outline-variant/20 bg-surface-container-low flex justify-between items-center">
                <h3 class="font-title-md text-primary flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[20px] text-primary select-none">lan</span>
                    All Categories
                </h3>
            </div>
            <div class="p-lg space-y-sm max-h-[600px] overflow-y-auto custom-scrollbar select-none">
                <div class="flex items-center gap-xs py-xs px-sm rounded-lg cursor-pointer hover:bg-surface-container-high transition-all {{ is_null($currentCategoryId) ? 'bg-primary text-on-primary font-semibold' : 'text-on-surface-variant' }}"
                     wire:click="selectCategory(null)">
                    <span class="material-symbols-outlined text-[20px] {{ is_null($currentCategoryId) ? 'text-on-primary' : 'text-primary' }} select-none">grid_view</span>
                    <span class="text-sm">Root Directory</span>
                </div>

                <div class="w-full h-px bg-outline-variant/20 my-sm"></div>

                <div class="space-y-sm">
                    @forelse($tree as $rootItem)
                        @include('admin.categories.tree-item', ['item' => $rootItem, 'openFolderIds' => $openFolderIds])
                    @empty
                        <p class="text-xs text-on-surface-variant text-center py-md">No categories configured.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- ── Right Panel: Folder Contents ── -->
        <div class="lg:col-span-8 bg-white rounded-xl card-shadow border border-outline-variant/30 overflow-hidden flex flex-col min-h-[500px]">

            <!-- Folder Header Bar -->
            <div class="p-lg border-b border-outline-variant/20 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md bg-surface-container-low/30">
                <div class="flex items-center gap-sm flex-wrap">
                    @if($currentCategoryId)
                        <button wire:click="navigateUp" class="w-8 h-8 rounded-full bg-white border border-outline-variant/50 flex items-center justify-center text-primary hover:bg-surface-container transition-colors shadow-sm focus:outline-none" title="Go Up One Folder">
                            <span class="material-symbols-outlined text-[18px] select-none">arrow_upward</span>
                        </button>
                    @endif

                    <div class="flex items-center gap-xs font-title-sm text-on-surface flex-wrap select-none">
                        <span class="cursor-pointer text-secondary font-semibold hover:underline" wire:click="selectCategory(null)">Root</span>
                        @foreach($breadcrumbs as $bc)
                            <span class="text-outline">/</span>
                            <span class="cursor-pointer text-secondary font-semibold hover:underline" wire:click="selectCategory({{ $bc['id'] }})">{{ $bc['name'] }}</span>
                        @endforeach
                    </div>

                    @if($currentCategory && $currentCategory->is_leaf)
                        <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded-full bg-secondary-container text-on-secondary-container text-[10px] font-bold uppercase tracking-wider select-none">
                            <span class="material-symbols-outlined text-[12px]">bookmark</span>
                            Catalog
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-sm">
                    @if($currentCategoryId)
                        <x-admin.button variant="outline" icon="edit" wire:click="editCategory({{ $currentCategoryId }})" class="whitespace-nowrap">
                            Edit
                        </x-admin.button>
                        <x-admin.button variant="outline" icon="delete" wire:click="confirmDeleteCategory({{ $currentCategoryId }})" class="!border-error !text-error hover:!bg-error/10 whitespace-nowrap">
                            Delete
                        </x-admin.button>
                    @endif

                    @if($isLeafMode)
                        @php
                            $defaultsConfigured = $currentCategory && !empty($currentCategory->default_product_config) && !empty($currentCategory->default_product_config['units']['level1_name']);
                        @endphp
                        
                        @if(auth()->user()->hasRole('super_admin') || auth()->user()->can('access categories'))
                            <x-admin.button variant="primary" icon="settings" wire:click="openCategoryDefaults" class="bg-secondary text-on-secondary hover:bg-secondary/90 whitespace-nowrap">
                                Configure Defaults
                            </x-admin.button>
                        @endif

                        @if($defaultsConfigured)
                            <x-admin.button variant="primary" icon="add" wire:click="create" class="whitespace-nowrap">
                                Add Product
                            </x-admin.button>
                        @else
                            <div x-data="{ tooltip: false }" @mouseenter="tooltip = true" @mouseleave="tooltip = false" class="relative cursor-not-allowed">
                                <x-admin.button variant="primary" icon="add" class="whitespace-nowrap !bg-[#001229] !text-white opacity-50 cursor-not-allowed">
                                    Add Product
                                </x-admin.button>
                                <!-- High-fidelity custom dark tooltip using inline styles -->
                                <div x-show="tooltip" x-cloak 
                                     style="position: absolute; right: 0; top: 100%; margin-top: 8px; display: flex; flex-direction: column; background-color: #2e3135; color: #ffffff; border-radius: 8px; padding: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3), 0 4px 6px -2px rgba(0,0,0,0.05); z-index: 9999; min-width: 240px; text-align: left; border: 1px solid rgba(255,255,255,0.15);"
                                     class="select-none">
                                    <span style="font-weight: 700; font-size: 13.5px; color: #ffffff; display: block; line-height: 1.2;">Add Product</span>
                                    <span style="font-weight: 400; font-size: 11.5px; color: rgba(255,255,255,0.85); margin-top: 6px; line-height: 1.5; display: block; white-space: normal;">Configure category defaults first to enable product uploads in this leaf folder.</span>
                                </div>
                            </div>
                        @endif
                    @else
                        {{-- Folder mode: New Sub-Category button --}}
                        <x-admin.button variant="primary" icon="create_new_folder" wire:click="createCategory" class="whitespace-nowrap">
                            {{ $currentCategoryId ? 'New Sub-Category' : 'New Category' }}
                        </x-admin.button>
                    @endif
                </div>
            </div>

            @if($isLeafMode)
                @if(!$defaultsConfigured)
                    <div class="mx-lg mt-lg p-md bg-warning/10 border border-warning/30 rounded-xl flex items-start gap-sm select-none">
                        <span class="material-symbols-outlined text-warning shrink-0">info</span>
                        <div>
                            <p class="font-label-md text-on-surface font-bold text-sm">Category Defaults Not Configured</p>
                            <p class="text-xs text-on-surface-variant mt-xxs">
                                @if(auth()->user()->hasRole('super_admin') || auth()->user()->can('access categories'))
                                    Please click the <strong class="text-secondary">Configure Defaults</strong> button to set HSN, GST, MOQ, and unit configuration. Product creation will be unlocked once defaults are configured.
                                @else
                                    Please ask a Super Admin or Category Admin to configure category defaults (HSN, GST, MOQ, and units) to unlock product creation.
                                @endif
                            </p>
                        </div>
                    </div>
                @endif

                {{-- ═══════════════════════════════════════════════════════════
                     LEAF MODE: Product Table
                ════════════════════════════════════════════════════════════ --}}

                <!-- Product Filter Sub-Bar -->
                <div class="px-lg py-sm bg-surface-container-low/10 border-b border-outline-variant/10 flex flex-col sm:flex-row items-start sm:items-center gap-sm">
                    <!-- Search -->
                    <div class="flex items-center gap-sm w-full sm:w-72 bg-surface-container-low border border-outline-variant/50 rounded-lg px-sm focus-within:ring-2 focus-within:ring-secondary focus-within:border-secondary transition-all">
                        <span class="material-symbols-outlined text-on-surface-variant/60 text-[18px] select-none pl-xs">search</span>
                        <input type="text" wire:model.live.debounce.300ms="productSearch" placeholder="Search products..." class="w-full bg-transparent border-none py-xs pr-xs font-body-md focus:ring-0 focus:outline-none outline-none text-on-surface">
                    </div>
                    <!-- Status filter -->
                    <select wire:model.live="productFilterStatus" class="bg-surface-container-low border border-outline-variant/50 rounded-lg px-sm py-xs text-sm focus:ring-2 focus:ring-secondary outline-none text-on-surface">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <!-- Stock filter -->
                    <select wire:model.live="productFilterStock" class="bg-surface-container-low border border-outline-variant/50 rounded-lg px-sm py-xs text-sm focus:ring-2 focus:ring-secondary outline-none text-on-surface">
                        <option value="">All Stock</option>
                        <option value="instock">In Stock</option>
                        <option value="outofstock">Out of Stock</option>
                    </select>
                    <div class="text-xs text-on-surface-variant select-none ml-auto hidden sm:block">
                        {{ $products ? $products->total() : 0 }} products
                    </div>
                </div>

                <!-- Product Table -->
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left font-body-md">
                        <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider text-xs border-b border-outline-variant/20 select-none">
                            <tr class="whitespace-nowrap">
                                <th class="px-lg py-sm">Product</th>
                                <th class="px-lg py-sm">SKU</th>
                                <th class="px-lg py-sm text-right">Price</th>
                                <th class="px-lg py-sm text-center">Stock</th>
                                <th class="px-lg py-sm text-center">Status</th>
                                <th class="px-lg py-sm text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            @forelse($products as $prod)
                                <tr class="hover:bg-primary/[0.01] transition-colors group">
                                    <td class="px-lg py-md whitespace-nowrap">
                                        <div class="flex items-center gap-sm">
                                            @if($prod->primaryMedia)
                                                <img src="{{ Storage::url($prod->primaryMedia->file_path) }}" alt="{{ $prod->title }}" class="w-10 h-10 rounded-lg object-cover border border-outline-variant/30 shadow-sm group-hover:scale-105 transition-transform">
                                            @else
                                                <div class="w-10 h-10 rounded-lg border border-outline-variant/30 bg-surface-container flex items-center justify-center text-on-surface-variant/40 select-none">
                                                    <span class="material-symbols-outlined text-[20px]">image</span>
                                                </div>
                                            @endif
                                            <div class="flex flex-col">
                                                <span class="font-bold text-primary text-sm">{{ $prod->title }}</span>
                                                <span class="text-[10px] text-on-surface-variant uppercase tracking-wide">{{ $prod->product_type === 'manufactured' ? 'Retail' : 'Manufactured' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-lg py-md text-on-surface-variant font-mono text-xs whitespace-nowrap">{{ $prod->sku }}</td>
                                    <td class="px-lg py-md text-right font-medium whitespace-nowrap">₹{{ number_format($prod->base_price, 2) }}</td>
                                    <td class="px-lg py-md text-center whitespace-nowrap">
                                        @if($prod->stock_quantity === null)
                                            <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded bg-surface-container text-on-surface-variant text-xs font-medium select-none">
                                                <span class="material-symbols-outlined text-[13px]">all_inclusive</span>
                                                N/A
                                            </span>
                                        @elseif($prod->stock_quantity > 10)
                                            <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded bg-success-container text-on-success-container text-xs font-bold select-none">
                                                {{ number_format($prod->stock_quantity) }}
                                            </span>
                                        @elseif($prod->stock_quantity > 0)
                                            <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded bg-warning/15 text-warning text-xs font-bold select-none">
                                                {{ $prod->stock_quantity }} low
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded bg-error/10 text-error text-xs font-bold select-none">
                                                Out
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-lg py-md text-center whitespace-nowrap">
                                        <button wire:click="toggleStatus({{ $prod->id }})" class="focus:outline-none">
                                            <x-admin.badge type="{{ $prod->is_active ? 'success' : 'default' }}">
                                                {{ $prod->is_active ? 'Active' : 'Inactive' }}
                                            </x-admin.badge>
                                        </button>
                                    </td>
                                    <td class="px-lg py-md text-right whitespace-nowrap">
                                        <x-admin.action-menu>
                                            <x-admin.action-menu-item wire:click="edit({{ $prod->id }})" icon="edit" label="Edit Product" />
                                            <x-admin.action-menu-item wire:click="toggleStatus({{ $prod->id }})" icon="{{ $prod->is_active ? 'block' : 'check_circle' }}" label="{{ $prod->is_active ? 'Deactivate' : 'Activate' }}" />
                                            <x-admin.action-menu-item wire:click="confirmDelete({{ $prod->id }})" icon="remove_shopping_cart" label="Remove from Category" class="text-error hover:text-error hover:bg-error/10" />
                                        </x-admin.action-menu>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-lg py-2xl text-center text-on-surface-variant">
                                        <div class="flex flex-col items-center justify-center">
                                            <span class="material-symbols-outlined text-4xl mb-sm text-outline select-none">inventory_2</span>
                                            @if($productSearch || $productFilterStatus || $productFilterStock)
                                                <p class="font-body-lg">No matching products found.</p>
                                                <p class="text-sm">Try adjusting your filters.</p>
                                            @else
                                                <p class="font-body-lg">No products here yet</p>
                                                <p class="text-sm">Add products to this leaf category.</p>
                                                <button wire:click="create" class="mt-md px-md py-sm bg-primary text-white rounded-lg font-button flex items-center gap-xs hover:bg-primary/95 transition-all text-xs">
                                                    <span class="material-symbols-outlined text-sm select-none">add</span>
                                                    Add First Product
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($products && $products->hasPages())
                    <div class="px-lg py-sm border-t border-outline-variant/10">
                        {{ $products->links() }}
                    </div>
                @endif

            @else

                {{-- ═══════════════════════════════════════════════════════════
                     FOLDER MODE: Sub-Category Table
                ════════════════════════════════════════════════════════════ --}}

                <!-- Folder Filter Sub-Bar -->
                <div class="px-lg py-sm bg-surface-container-low/10 border-b border-outline-variant/10 flex items-center justify-between gap-md">
                    <div class="flex items-center gap-sm w-full sm:w-72 bg-surface-container-low border border-outline-variant/50 rounded-lg px-sm focus-within:ring-2 focus-within:ring-secondary focus-within:border-secondary transition-all">
                        <span class="material-symbols-outlined text-on-surface-variant/60 text-[18px] select-none pl-xs">search</span>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search in this folder..." class="w-full bg-transparent border-none py-xs pr-xs font-body-md focus:ring-0 focus:outline-none outline-none text-on-surface">
                    </div>
                    <div class="text-xs text-on-surface-variant select-none">
                        {{ $children->count() }} items inside
                    </div>
                </div>

                <!-- Sub-Category Table -->
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left font-body-md">
                        <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider text-xs border-b border-outline-variant/20 select-none">
                            <tr>
                                <th class="px-lg py-sm">Name</th>
                                <th class="px-lg py-sm">Slug</th>
                                <th class="px-lg py-sm text-center">Type</th>
                                <th class="px-lg py-sm text-center">Contents</th>
                                <th class="px-lg py-sm text-center">Status</th>
                                <th class="px-lg py-sm text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            @forelse($children as $child)
                                <tr class="hover:bg-primary/[0.01] transition-colors group">
                                    <td class="px-lg py-md whitespace-nowrap">
                                        <div class="flex items-center gap-sm">
                                            @if($child->is_leaf)
                                                <span class="material-symbols-outlined text-secondary text-[22px] select-none group-hover:scale-110 transition-transform">bookmark</span>
                                            @else
                                                <span class="material-symbols-outlined text-primary text-[22px] select-none group-hover:scale-110 transition-transform">folder</span>
                                            @endif
                                            <div class="flex flex-col">
                                                <span class="font-bold {{ $child->is_leaf ? 'text-secondary' : 'text-primary' }} cursor-pointer hover:underline" wire:click="selectCategory({{ $child->id }})">
                                                    {{ $child->name }}
                                                </span>
                                                @if($child->description)
                                                    <span class="text-xs text-on-surface-variant truncate max-w-[200px]">{{ $child->description }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-lg py-md text-on-surface-variant font-mono text-xs">{{ $child->slug }}</td>
                                    <td class="px-lg py-md text-center whitespace-nowrap">
                                        @if($child->is_leaf)
                                            <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded-full bg-secondary-container text-on-secondary-container text-[10px] font-bold uppercase tracking-wider select-none">
                                                <span class="material-symbols-outlined text-[12px]">bookmark</span>
                                                Leaf
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-xxs px-sm py-xxs rounded-full bg-surface-container-high text-on-surface-variant text-[10px] font-bold uppercase tracking-wider select-none">
                                                <span class="material-symbols-outlined text-[12px]">folder</span>
                                                Folder
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-lg py-md text-center whitespace-nowrap">
                                        @if($child->is_leaf)
                                            <span class="inline-flex items-center justify-center px-sm py-xxs rounded bg-secondary-container text-on-secondary-container font-bold text-xs select-none">
                                                {{ $child->products()->count() }} products
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center px-sm py-xxs rounded bg-surface-container text-on-surface-variant font-bold text-xs select-none">
                                                {{ $child->children()->count() }} sub-folders
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-lg py-md text-center whitespace-nowrap">
                                        <button wire:click="toggleCategoryStatus({{ $child->id }})" class="focus:outline-none">
                                            <x-admin.badge type="{{ $child->is_active ? 'success' : 'default' }}">
                                                {{ $child->is_active ? 'Active' : 'Inactive' }}
                                            </x-admin.badge>
                                        </button>
                                    </td>
                                    <td class="px-lg py-md text-right whitespace-nowrap">
                                        <x-admin.action-menu>
                                            <x-admin.action-menu-item wire:click="selectCategory({{ $child->id }})" icon="{{ $child->is_leaf ? 'bookmark' : 'folder_open' }}" label="{{ $child->is_leaf ? 'View Products' : 'Open Folder' }}" />
                                            <x-admin.action-menu-item wire:click="editCategory({{ $child->id }})" icon="edit" label="Edit" />
                                            <x-admin.action-menu-item wire:click="toggleCategoryStatus({{ $child->id }})" icon="{{ $child->is_active ? 'block' : 'check_circle' }}" label="{{ $child->is_active ? 'Deactivate' : 'Activate' }}" />
                                            <x-admin.action-menu-item wire:click="confirmDeleteCategory({{ $child->id }})" icon="delete" label="Delete" class="text-error hover:text-error hover:bg-error/10" />
                                        </x-admin.action-menu>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-lg py-2xl text-center text-on-surface-variant">
                                        <div class="flex flex-col items-center justify-center">
                                            <span class="material-symbols-outlined text-4xl mb-sm text-outline select-none">folder_off</span>
                                            @if($search)
                                                <p class="font-body-lg">No matching sub-categories found.</p>
                                                <p class="text-sm">Try checking your spelling or search criteria.</p>
                                            @else
                                                <p class="font-body-lg">This folder is empty</p>
                                                <p class="text-sm">Create a sub-category or mark this as a leaf to add products.</p>
                                                <button wire:click="createCategory" class="mt-md px-md py-sm bg-primary text-white rounded-lg font-button flex items-center gap-xs hover:bg-primary/95 transition-all text-xs">
                                                    <span class="material-symbols-outlined text-sm select-none">add</span>
                                                    Create Folder
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         MODALS
    ══════════════════════════════════════════════════════════════════════ --}}

    <!-- ── Create / Edit Category Modal ── -->
    <x-admin.modal id="add-category" title="{{ $editingCategoryId ? 'Edit Category' : 'New Category' }}" maxWidth="md">
        <form class="space-y-md" wire:submit.prevent="saveCategory">
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Category Name *</label>
                <input type="text" wire:model="form.name" placeholder="e.g. Premium Linen" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md">
                @error('form.name') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Description</label>
                <textarea rows="3" wire:model="form.description" placeholder="Short description of folder contents" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md"></textarea>
                @error('form.description') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- Leaf Toggle -->
            <div x-data="{ isLeaf: @entangle('form.is_leaf').live }" class="rounded-xl border-2 transition-all p-md space-y-sm"
                 :class="isLeaf ? 'border-secondary/40 bg-secondary/5' : 'border-outline-variant/30 bg-surface-container-low/30'">
                <div class="flex items-start justify-between gap-md">
                    <div class="flex-1">
                        <p class="font-label-md text-on-surface" :class="isLeaf ? 'text-secondary' : ''">
                            <span class="material-symbols-outlined text-[16px] align-middle mr-xxs" :class="isLeaf ? 'text-secondary' : 'text-on-surface-variant'">bookmark</span>
                            Leaf Category — Products Live Here
                        </p>
                        <p class="text-xs text-on-surface-variant mt-xxs leading-relaxed">
                            Enable this if this is the <strong>final level</strong> of the hierarchy and will directly contain products (not sub-categories).
                            Once products exist under a leaf, you must move or delete them before converting back to a folder.
                        </p>
                    </div>
                    <!-- Toggle Switch -->
                    <button type="button" x-on:click="isLeaf = !isLeaf; $wire.set('form.is_leaf', isLeaf)"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none cursor-pointer"
                            :class="isLeaf ? 'bg-secondary' : 'bg-outline-variant/50'">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                              :class="isLeaf ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>
                @error('form.is_leaf') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center gap-sm">
                <input type="checkbox" wire:model="form.is_active" id="form_is_active" class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
                <label for="form_is_active" class="font-body-md text-on-surface cursor-pointer select-none">Active Status</label>
            </div>
        </form>

        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button variant="primary" icon="save" wire:click="saveCategory">Save Category</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <!-- ── Delete Category Modal ── -->
    <x-admin.modal id="delete-category" title="Delete Category" maxWidth="md">
        <div class="space-y-md">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-error/10 mx-auto mb-lg text-error select-none">
                <span class="material-symbols-outlined text-[32px]">warning</span>
            </div>
            <h3 class="text-center font-title-lg text-on-surface">Are you sure?</h3>
            <p class="text-center font-body-md text-on-surface-variant">
                This category (and all its sub-categories) will be permanently deleted. Any products exclusively belonging to these categories will also be deleted to prevent orphaned products. This action cannot be undone.
            </p>
        </div>
        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button wire:click="deleteCategory" variant="primary" class="bg-error hover:bg-error/90 text-white border-error">Delete Category</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <!-- ── Leaf Safety Modal (products exist) ── -->
    <x-admin.modal id="leaf-safety" title="Products Must Be Moved First" maxWidth="lg">
        <div x-data="{ tab: 'move' }">
            <!-- Header Info -->
            <div class="flex items-start gap-md p-md rounded-xl bg-warning/10 border border-warning/30 mb-lg">
                <span class="material-symbols-outlined text-warning text-[28px] shrink-0">inventory_2</span>
                <div>
                    <p class="font-label-md text-on-surface font-bold">
                        This leaf category has <span class="text-warning">{{ $leafSafetyProductCount }}</span> product(s)
                    </p>
                    <p class="text-sm text-on-surface-variant mt-xxs">
                        Before you can
                        @if($leafSafetyAction === 'delete') delete this category @else convert it to a folder @endif,
                        all products must be either moved to another leaf category or permanently deleted.
                    </p>
                </div>
            </div>

            <!-- Tab Bar -->
            <div class="flex border-b border-outline-variant/20 mb-lg select-none">
                <button type="button" x-on:click="tab = 'move'"
                        class="px-lg py-sm text-sm font-semibold transition-colors border-b-2 -mb-px"
                        :class="tab === 'move' ? 'border-secondary text-secondary' : 'border-transparent text-on-surface-variant hover:text-on-surface'">
                    <span class="material-symbols-outlined text-[16px] align-middle mr-xxs">drive_file_move</span>
                    Move Products
                </button>
                <button type="button" x-on:click="tab = 'delete'; $wire.set('showDeleteAllConfirm', false)"
                        class="px-lg py-sm text-sm font-semibold transition-colors border-b-2 -mb-px"
                        :class="tab === 'delete' ? 'border-error text-error' : 'border-transparent text-on-surface-variant hover:text-on-surface'">
                    <span class="material-symbols-outlined text-[16px] align-middle mr-xxs">delete_forever</span>
                    Delete All Products
                </button>
            </div>

            <!-- Move Tab -->
            <div x-show="tab === 'move'" class="space-y-md">
                <p class="text-sm text-on-surface-variant">Select a destination leaf category to move all products into:</p>
                <select wire:model="moveToTargetCategoryId" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none font-body-md text-on-surface">
                    <option value="">— Choose target leaf category —</option>
                    @foreach($leafCategories as $leaf)
                        @if($leaf->id !== $leafSafetyCategoryId)
                            <option value="{{ $leaf->id }}">{{ $leaf->full_path }}</option>
                        @endif
                    @endforeach
                </select>
                @error('moveToTargetCategoryId') <span class="text-error text-xs">{{ $message }}</span> @enderror
                <x-admin.button variant="primary" icon="drive_file_move" wire:click="moveProductsToCategory">
                    Move {{ $leafSafetyProductCount }} Product(s) & Continue
                </x-admin.button>
            </div>

            <!-- Delete Tab -->
            <div x-show="tab === 'delete'" class="space-y-md">
                @if(!$showDeleteAllConfirm)
                    <div class="rounded-xl bg-error/5 border border-error/20 p-md space-y-sm">
                        <p class="text-sm text-on-surface font-semibold">This will permanently delete all <strong class="text-error">{{ $leafSafetyProductCount }} product(s)</strong> from this category.</p>
                        <p class="text-xs text-on-surface-variant">Products that are exclusively in this category will be fully deleted. Products also in other categories will only be detached from this one.</p>
                    </div>
                    <x-admin.button variant="primary" class="bg-error hover:bg-error/90 text-white border-error" icon="delete_forever" wire:click="$set('showDeleteAllConfirm', true)">
                        I understand — Show Confirmation
                    </x-admin.button>
                @else
                    <div class="rounded-xl bg-error/10 border-2 border-error/30 p-lg text-center space-y-md">
                        <span class="material-symbols-outlined text-[40px] text-error block">warning</span>
                        <p class="font-title-sm text-on-surface">Final Confirmation</p>
                        <p class="text-sm text-on-surface-variant">Permanently delete all <strong class="text-error">{{ $leafSafetyProductCount }} product(s)</strong>? This cannot be undone.</p>
                        <div class="flex justify-center gap-sm">
                            <x-admin.button variant="ghost" wire:click="$set('showDeleteAllConfirm', false)">Cancel</x-admin.button>
                            <x-admin.button variant="primary" class="bg-error hover:bg-error/90 text-white border-error" icon="delete_forever" wire:click="deleteAllCategoryProducts">
                                Yes, Delete All Products
                            </x-admin.button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Close</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <!-- ── Remove Product from Category Confirm Modal ── -->
    <x-admin.modal id="cat-delete-product" title="Remove Product" maxWidth="md">
        <div class="space-y-md">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-error/10 mx-auto mb-lg text-error select-none">
                <span class="material-symbols-outlined text-[32px]">remove_shopping_cart</span>
            </div>
            <h3 class="text-center font-title-lg text-on-surface">Remove from category?</h3>
            <p class="text-center font-body-md text-on-surface-variant">
                This product will be removed from this category. If it is not assigned to any other category, it will be permanently deleted.
            </p>
        </div>
        <x-slot name="footer">
            <x-admin.button variant="ghost" wire:click="closeDeleteModal">Cancel</x-admin.button>
            <x-admin.button wire:click="delete" variant="primary" class="bg-error hover:bg-error/90 text-white border-error">Remove Product</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <!-- ── Simplified Product Wizard Modal ── -->
    @include('admin.products.category-product-wizard-modal', [
        'modalId'         => 'cat-add-product',
        'deleteModalId'   => 'cat-delete-product',
        'valueMediaModalId' => 'cat-manage-value-media',
        'lockedCategory'  => $currentCategory,
    ])

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
                                <button type="button" onmousedown="event.preventDefault();" onclick="insertMarkdown('desc-editor-defaults-cat', '**', '**')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container font-extrabold text-sm text-primary" title="Bold">B</button>
                                <button type="button" onmousedown="event.preventDefault();" onclick="insertMarkdown('desc-editor-defaults-cat', '*', '*')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container italic font-bold text-sm text-primary" title="Italic">I</button>
                                <button type="button" onmousedown="event.preventDefault();" onclick="insertMarkdown('desc-editor-defaults-cat', '# ', '')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container font-bold text-sm text-primary" title="Heading">H</button>
                            </div>
                            <div class="w-px h-5 bg-outline-variant/40"></div>
                            <div class="flex items-center gap-xs">
                                <button type="button" onmousedown="event.preventDefault();" onclick="insertMarkdown('desc-editor-defaults-cat', '> ', '')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary text-base font-bold" title="Quote">"</button>
                                <button type="button" onmousedown="event.preventDefault();" onclick="insertMarkdown('desc-editor-defaults-cat', '- ', '')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary flex items-center justify-center" title="Bullet List">
                                    <span class="material-symbols-outlined text-[20px]">format_list_bulleted</span>
                                </button>
                                <button type="button" onmousedown="event.preventDefault();" onclick="insertMarkdown('desc-editor-defaults-cat', '1. ', '')" class="w-8 h-8 rounded flex items-center justify-center hover:bg-surface-container text-primary flex items-center justify-center" title="Numbered List">
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
                            <textarea id="desc-editor-defaults-cat" rows="6" wire:model="categoryDefaults.description" placeholder="Enter default product description..." class="w-full px-md py-md bg-transparent border-0 outline-none focus:ring-0 font-body-md text-on-surface resize-none min-h-[160px]"></textarea>
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
