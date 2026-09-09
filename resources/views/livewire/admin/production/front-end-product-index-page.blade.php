<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-on-surface">Front-End Products</h1>
            <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                Define sellable products assembled from factory manufacturing products and packaging materials.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="openCreateModal" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-on-primary hover:bg-primary/90 text-xs font-bold rounded-xl shadow-sm transition-all duration-200 hover:shadow">
                <span class="material-symbols-outlined text-[18px]">add</span>
                <span>Add Front-End Product</span>
            </button>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/50 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="relative w-full sm:w-64">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/60 text-[18px]">search</span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Front-End products, SKU..." class="w-full pl-9 pr-4 py-2 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface placeholder:text-on-surface-variant/50" />
        </div>
        <div class="text-xs text-on-surface-variant font-medium">
            Showing <strong class="text-on-surface">{{ $frontendProducts->total() }}</strong> Front-End Product SKU(s)
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/50 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low/70 border-b border-outline-variant/60 text-[11px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                        <th class="py-3.5 px-4">Front-End Product</th>
                        <th class="py-3.5 px-4">Leaf Category</th>
                        <th class="py-3.5 px-4">Manufacturing Products</th>
                        <th class="py-3.5 px-4">Packaging</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30 text-xs font-medium text-on-surface">
                    @forelse($frontendProducts as $feProduct)
                        <tr class="hover:bg-surface-container-low/40 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-on-surface text-sm">{{ $feProduct->name }}</div>
                                <div class="font-mono text-[11px] text-on-surface-variant font-semibold mt-0.5">{{ $feProduct->sku }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-lg bg-surface-container-high text-on-surface font-semibold text-[11px]">
                                    {{ $feProduct->category_display_name }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1.5 max-w-md">
                                    @forelse($feProduct->components as $comp)
                                        <span class="inline-flex items-center px-2 py-1 rounded-md bg-secondary-container/30 text-on-secondary-container font-mono text-[11px]">
                                            <strong class="text-secondary mr-1">{{ $comp->manufacturingProduct?->name ?? 'Product' }}</strong> × {{ $comp->quantity }}
                                        </span>
                                    @empty
                                        <span class="text-on-surface-variant/60 italic text-[11px]">No constituent products</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1.5 max-w-sm">
                                    @forelse($feProduct->packagingItems as $pkg)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-surface-container-high text-on-surface-variant text-[11px]">
                                            {{ $pkg->rawMaterial?->name ?? 'Material' }} (×{{ $pkg->quantity }})
                                        </span>
                                    @empty
                                        <span class="text-on-surface-variant/60 italic text-[11px]">No packaging items</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($feProduct->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-500/10 text-slate-600 dark:text-slate-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="editProduct({{ $feProduct->id }})" class="p-1.5 text-on-surface-variant hover:text-primary hover:bg-surface-container-high rounded-lg transition-colors" title="Edit Front-End Product">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button wire:click="toggleActive({{ $feProduct->id }})" class="p-1.5 text-on-surface-variant hover:text-secondary hover:bg-surface-container-high rounded-lg transition-colors" title="Toggle Status">
                                        <span class="material-symbols-outlined text-[18px]">{{ $feProduct->is_active ? 'visibility_off' : 'visibility' }}</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl mb-2 text-on-surface-variant/40">inventory_2</span>
                                <p class="text-sm font-semibold">No Front-End Products found</p>
                                <p class="text-xs text-on-surface-variant/70 mt-1">Click "Add Front-End Product" to define your sellable SKUs.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($frontendProducts->hasPages())
            <div class="p-4 border-t border-outline-variant/50">
                {{ $frontendProducts->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL: ADD / EDIT FRONT-END PRODUCT -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/25 backdrop-blur-xs animate-fade-in" x-data x-trap.noscroll="true">
            <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/60 shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-scale-up">
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low/40">
                    <div>
                        <h2 class="text-lg font-black text-on-surface">
                            {{ $editingProductId ? 'Edit Front-End Product' : 'Add Front-End Product' }}
                        </h2>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                            Assemble one sellable SKU from one or more manufacturing products with quantities, plus packaging materials.
                        </p>
                    </div>
                    <button wire:click="$set('showModal', false)" class="p-2 text-on-surface-variant hover:text-on-surface rounded-full hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 space-y-6 overflow-y-auto custom-scrollbar flex-1">
                    <!-- Basic Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1.5">Front-End Product Name *</label>
                            <input type="text" wire:model="name" placeholder="e.g., Regal King Bedsheet Set" class="w-full px-3.5 py-2.5 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold" />
                            @error('name') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1.5">SKU / Product Code *</label>
                            <input type="text" wire:model="sku" placeholder="e.g., KT-P-0052" class="w-full px-3.5 py-2.5 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-mono font-bold" />
                            @error('sku') <span class="text-red-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="relative" x-data="{ open: false, search: '' }" @click.outside="open = false">
                            <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1.5">Leaf Category / Store Category</label>
                            @php
                                $selectedCategory = $categories->firstWhere('id', $category_id);
                            @endphp

                            <!-- Trigger Display Box -->
                            <div @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                                 class="w-full px-3.5 py-2 bg-surface-container-low rounded-xl border border-outline-variant/60 focus-within:ring-2 focus-within:ring-primary/20 cursor-pointer flex items-center justify-between min-h-[46px] hover:border-primary/50 transition-colors">
                                @if($selectedCategory)
                                    <div class="flex flex-col truncate pr-2">
                                        <span class="text-xs font-extrabold text-on-surface truncate">{{ $selectedCategory->name }}</span>
                                        <span class="text-[11px] font-medium text-on-surface-variant/80 truncate">{{ $selectedCategory->full_path }}</span>
                                    </div>
                                @else
                                    <span class="text-xs font-semibold text-on-surface-variant/60">Select Leaf Category...</span>
                                @endif

                                <div class="flex items-center gap-1">
                                    @if($selectedCategory)
                                        <button type="button" 
                                                @click.stop="$wire.set('category_id', null); search = ''" 
                                                class="p-1 text-on-surface-variant/60 hover:text-red-500 rounded-full hover:bg-surface-container-high transition-colors" 
                                                title="Clear category selection">
                                            <span class="material-symbols-outlined text-[16px]">close</span>
                                        </button>
                                    @endif
                                    <span class="material-symbols-outlined text-on-surface-variant text-[18px] transition-transform duration-200" :class="{ 'rotate-180': open }">
                                        expand_more
                                    </span>
                                </div>
                            </div>

                            <!-- Dropdown Menu -->
                            <div x-show="open" 
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute z-50 left-0 right-0 mt-1 bg-surface-container-lowest border border-outline-variant/80 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-72">
                                
                                <!-- Search Input Header -->
                                <div class="p-2 border-b border-outline-variant/40 bg-surface-container-low/50 sticky top-0 z-10">
                                    <div class="relative flex items-center">
                                        <span class="material-symbols-outlined text-on-surface-variant/60 text-[18px] absolute left-3 pointer-events-none">search</span>
                                        <input x-ref="searchInput"
                                               type="text" 
                                               x-model="search" 
                                               placeholder="Search leaf category by name or path..." 
                                               class="w-full pl-9 pr-8 py-1.5 bg-surface-container-lowest text-xs rounded-lg border border-outline-variant/50 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-medium placeholder:text-on-surface-variant/50" />
                                        <button x-show="search !== ''" 
                                                @click="search = ''" 
                                                type="button"
                                                class="absolute right-2 text-on-surface-variant/60 hover:text-on-surface text-xs p-1">
                                            <span class="material-symbols-outlined text-[14px]">close</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Category List -->
                                <div class="overflow-y-auto custom-scrollbar divide-y divide-outline-variant/20 py-1">
                                    @forelse($categories as $cat)
                                        <div x-show="search === '' || '{{ addslashes(strtolower($cat->name)) }}'.includes(search.toLowerCase()) || '{{ addslashes(strtolower($cat->full_path)) }}'.includes(search.toLowerCase())"
                                             @click="$wire.set('category_id', {{ $cat->id }}); open = false; search = ''"
                                             class="px-3.5 py-2 hover:bg-primary/5 cursor-pointer transition-colors flex items-center justify-between group {{ $category_id == $cat->id ? 'bg-primary/10' : '' }}">
                                            <div class="flex flex-col truncate pr-2">
                                                <span class="text-xs font-bold text-on-surface group-hover:text-primary transition-colors flex items-center gap-1.5">
                                                    @if($category_id == $cat->id)
                                                        <span class="w-1.5 h-1.5 rounded-full bg-primary inline-block"></span>
                                                    @endif
                                                    {{ $cat->name }}
                                                </span>
                                                <span class="text-[11px] font-medium text-on-surface-variant/70 mt-0.5">{{ $cat->full_path }}</span>
                                            </div>
                                            @if($category_id == $cat->id)
                                                <span class="material-symbols-outlined text-primary text-[18px] flex-shrink-0">check</span>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="px-4 py-3 text-xs text-on-surface-variant/60 text-center italic">
                                            No leaf categories available
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1.5">Custom Category Label (Optional)</label>
                            <input type="text" wire:model="leaf_category_name" placeholder="e.g., Bedding / Dohars" class="w-full px-3.5 py-2.5 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold" />
                        </div>
                    </div>

                    <!-- Dynamic Section 1: Manufacturing Products Required -->
                    <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-on-surface">Manufacturing products required *</h3>
                                <p class="text-[11px] text-on-surface-variant">Specify product and quantity required per 1 SKU</p>
                            </div>
                        </div>

                        @error('mfgRows')
                            <div class="p-2 rounded bg-red-500/10 text-red-600 text-[11px] font-bold">{{ $message }}</div>
                        @enderror

                        <div class="space-y-2.5">
                            @foreach($mfgRows as $idx => $mfg)
                                <div class="flex items-center gap-3">
                                    <div class="flex-1">
                                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">MANUFACTURING PRODUCT</label>
                                        <select wire:model="mfgRows.{{ $idx }}.manufacturing_product_id" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-medium">
                                            <option value="">Select Manufacturing Product...</option>
                                            @foreach($mfgProducts as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->category?->name ?? 'Gen' }})</option>
                                            @endforeach
                                        </select>
                                        @error("mfgRows.{$idx}.manufacturing_product_id") <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="w-32">
                                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">QTY PER SKU</label>
                                        <input type="number" min="1" wire:model="mfgRows.{{ $idx }}.quantity" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-mono font-bold" />
                                    </div>
                                    @if(count($mfgRows) > 1)
                                        <div class="pt-5">
                                            <button type="button" wire:click="removeMfgRow({{ $idx }})" class="p-2 text-red-500 hover:bg-red-500/10 rounded-xl transition-colors">
                                                <span class="material-symbols-outlined text-[18px]">close</span>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <button type="button" wire:click="addMfgRow" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-high hover:bg-surface-container-highest text-primary text-xs font-bold rounded-xl transition-colors">
                            <span class="material-symbols-outlined text-[16px]">add</span>
                            <span>Add manufacturing product</span>
                        </button>
                    </div>

                    <!-- Dynamic Section 2: Packaging Materials -->
                    <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-3">
                        <div>
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-on-surface">Packaging materials *</h3>
                            <p class="text-[11px] text-on-surface-variant">Items and quantities deducted automatically on conversion</p>
                        </div>

                        <div class="space-y-2.5">
                            @foreach($pkgRows as $idx => $pkg)
                                <div class="flex items-center gap-3">
                                    <div class="flex-1">
                                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">PACKAGING MATERIAL</label>
                                        <select wire:model="pkgRows.{{ $idx }}.raw_material_id" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-medium">
                                            <option value="">Select Packaging Material...</option>
                                            @foreach($packagingMaterials as $mat)
                                                <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->code }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="w-32">
                                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">QTY PER SKU</label>
                                        <input type="number" min="1" wire:model="pkgRows.{{ $idx }}.quantity" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-mono font-bold" />
                                    </div>
                                    @if(count($pkgRows) > 1)
                                        <div class="pt-5">
                                            <button type="button" wire:click="removePkgRow({{ $idx }})" class="p-2 text-red-500 hover:bg-red-500/10 rounded-xl transition-colors">
                                                <span class="material-symbols-outlined text-[18px]">close</span>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <button type="button" wire:click="addPkgRow" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-high hover:bg-surface-container-highest text-primary text-xs font-bold rounded-xl transition-colors">
                            <span class="material-symbols-outlined text-[16px]">add</span>
                            <span>Add packaging material</span>
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface uppercase tracking-wider mb-1.5">Description / Notes</label>
                        <textarea wire:model="description" rows="2" placeholder="Optional notes for this Front-End Product SKU..." class="w-full px-3.5 py-2.5 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface"></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-outline-variant/60 flex items-center justify-end gap-3 bg-surface-container-low/40">
                    <button wire:click="$set('showModal', false)" class="px-4 py-2 bg-surface-container-high text-on-surface-variant hover:text-on-surface text-xs font-bold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button wire:click="saveProduct" class="px-5 py-2 bg-primary text-on-primary hover:bg-primary/90 text-xs font-bold rounded-xl shadow-sm transition-all">
                        {{ $editingProductId ? 'Update Front-End Product' : 'Create Front-End Product' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
