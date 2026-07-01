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
                            Leaf Category
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-sm">
                    @if($isLeafMode)
                        {{-- Leaf mode: Add Product button --}}
                        <x-admin.button variant="primary" icon="add" wire:click="create">
                            Add Product
                        </x-admin.button>
                    @else
                        {{-- Folder mode: New Sub-Category button --}}
                        <x-admin.button variant="primary" icon="create_new_folder" wire:click="createCategory">
                            {{ $currentCategoryId ? 'New Sub-Category' : 'New Category' }}
                        </x-admin.button>
                    @endif
                </div>
            </div>

            @if($isLeafMode)

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
                This category will be permanently deleted. Products assigned to it will not be deleted but may become uncategorized.
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

    <!-- ── Product Wizard Modal ── -->
    @include('admin.products.product-wizard-modal', [
        'modalId'         => 'cat-add-product',
        'deleteModalId'   => 'cat-delete-product',
        'valueMediaModalId' => 'cat-manage-value-media',
        'lockedCategory'  => $currentCategory,
    ])

</div>
