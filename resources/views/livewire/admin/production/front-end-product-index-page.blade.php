<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-on-surface">Leaf Category Config</h1>
            <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                Configure constituent manufacturing products and packaging materials required per sellable shop leaf category.
            </p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/50 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="relative w-full sm:w-72">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/60 text-[18px]">search</span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Leaf Category name or path..." class="w-full pl-9 pr-4 py-2 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface placeholder:text-on-surface-variant/50" />
        </div>
        <div class="text-xs text-on-surface-variant font-medium">
            Showing <strong class="text-on-surface">{{ $categories->total() }}</strong> Leaf Shop Category(ies)
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/50 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low/70 border-b border-outline-variant/60 text-[11px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                        <th class="py-3.5 px-4">Leaf Category</th>
                        <th class="py-3.5 px-4">Configuration Status</th>
                        <th class="py-3.5 px-4">Manufacturing Products Required</th>
                        <th class="py-3.5 px-4">Packaging Materials</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30 text-xs font-medium text-on-surface">
                    @forelse($categories as $cat)
                        <tr class="hover:bg-surface-container-low/40 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="font-extrabold text-on-surface text-sm">{{ $cat->name }}</div>
                                <div class="text-[11px] text-on-surface-variant/80 font-medium mt-0.5">{{ $cat->full_path }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($cat->is_configured)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Configured
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Not Configured
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1.5 max-w-md">
                                    @forelse($cat->components as $comp)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-secondary-container/30 text-on-secondary-container font-mono text-[11px] border border-secondary/20">
                                            <strong class="text-secondary mr-1">{{ $comp->manufacturingProduct?->name ?? 'Product' }}</strong> × {{ $comp->quantity }}
                                        </span>
                                    @empty
                                        <span class="text-on-surface-variant/60 italic text-[11px]">Not configured</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1.5 max-w-sm">
                                    @forelse($cat->packaging as $pkg)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-surface-container-high text-on-surface-variant text-[11px] border border-outline-variant/40">
                                            {{ $pkg->rawMaterial?->name ?? 'Material' }} (×{{ $pkg->quantity }})
                                        </span>
                                    @empty
                                        <span class="text-on-surface-variant/60 italic text-[11px]">No packaging defined</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <button wire:click="configureCategory({{ $cat->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary/10 hover:bg-primary/20 text-primary text-xs font-bold rounded-xl transition-colors">
                                    <span class="material-symbols-outlined text-[16px]">settings</span>
                                    <span>{{ $cat->is_configured ? 'Edit Assembly' : 'Configure' }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl mb-2 text-on-surface-variant/40">category</span>
                                <p class="text-sm font-semibold">No leaf shop categories found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
            <div class="p-4 border-t border-outline-variant/50">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL: CONFIGURE LEAF CATEGORY ASSEMBLY & PACKAGING -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30 backdrop-blur-xs animate-fade-in" x-data x-trap.noscroll="true">
            <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/60 shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-scale-up">
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low/40">
                    <div>
                        <h2 class="text-lg font-black text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[22px]">widgets</span>
                            Configure Assembly: {{ $categoryName }}
                        </h2>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                            {{ $categoryFullPath }}
                        </p>
                    </div>
                    <button wire:click="$set('showModal', false)" class="p-2 text-on-surface-variant hover:text-on-surface rounded-full hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 space-y-6 overflow-y-auto custom-scrollbar flex-1">
                    <!-- Dynamic Section 1: Manufacturing Products Required -->
                    <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-extrabold uppercase tracking-wider text-on-surface">Manufacturing Products Required *</h3>
                                <p class="text-[11px] text-on-surface-variant">Specify constituent manufacturing product items and required quantity per set/SKU</p>
                            </div>
                        </div>

                        @error('mfgRows')
                            <div class="p-2 rounded bg-red-500/10 text-red-600 text-[11px] font-bold">{{ $message }}</div>
                        @enderror

                        <div class="space-y-2.5">
                            @foreach($mfgRows as $idx => $mfg)
                                <div class="flex items-center gap-3">
                                    <div class="flex-1">
                                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">MANUFACTURING PRODUCT ITEM</label>
                                        <select wire:model="mfgRows.{{ $idx }}.manufacturing_product_id" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold">
                                            <option value="">Select Manufacturing Product...</option>
                                            @foreach($mfgProducts as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->category?->name ?? 'General' }})</option>
                                            @endforeach
                                        </select>
                                        @error("mfgRows.{$idx}.manufacturing_product_id") <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="w-32">
                                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">QTY PER SET</label>
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
                            <h3 class="text-xs font-extrabold uppercase tracking-wider text-on-surface">Packaging Materials Required</h3>
                            <p class="text-[11px] text-on-surface-variant">Items and quantities deducted automatically on conversion</p>
                        </div>

                        <div class="space-y-2.5">
                            @foreach($pkgRows as $idx => $pkg)
                                <div class="flex items-center gap-3">
                                    <div class="flex-1">
                                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">PACKAGING MATERIAL</label>
                                        <select wire:model="pkgRows.{{ $idx }}.raw_material_id" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold">
                                            <option value="">Select Packaging Material...</option>
                                            @foreach($packagingMaterials as $mat)
                                                <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->code }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="w-32">
                                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">QTY PER SET</label>
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
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-outline-variant/60 flex items-center justify-end gap-3 bg-surface-container-low/40">
                    <button wire:click="$set('showModal', false)" class="px-4 py-2 bg-surface-container-high text-on-surface-variant hover:text-on-surface text-xs font-bold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button wire:click="saveCategoryConfiguration" class="px-5 py-2 bg-primary text-on-primary hover:bg-primary/90 text-xs font-bold rounded-xl shadow-sm transition-all">
                        Save Category Configuration
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
