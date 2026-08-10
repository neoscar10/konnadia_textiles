<div>
    <!-- Header & Breadcrumbs -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <nav class="flex items-center gap-2 text-on-surface-variant mb-2">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="font-label-sm text-xs text-on-surface-variant hover:text-primary transition-all">Dashboard</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a href="{{ route('factory.tasks.index') }}" wire:navigate class="font-label-sm text-xs text-on-surface-variant hover:text-primary transition-all">Factory Floor</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="font-label-sm text-xs text-primary font-bold">Finished Goods Combination</span>
            </nav>
            <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Storefront Finished Goods Combination</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Combine and convert completed factory floor outputs (e.g., 1 Bed Sheet + 2 Pillow Cases) into a single storefront retail product set.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Section -->
        <div class="lg:col-span-2 space-y-6">
            <form wire:submit="createBundleAndConvert" class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs space-y-6">
                <div class="flex items-center gap-2 text-primary border-b border-outline-variant/40 pb-4">
                    <span class="material-symbols-outlined text-[22px]">extension</span>
                    <h3 class="font-headline-sm text-base font-extrabold">Assemble Storefront Product Set</h3>
                </div>

                <!-- Select Target Storefront Product / Variant -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Target Storefront Product <span class="text-error">*</span></label>
                        <select wire:model.live="target_product_id" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-body-md focus:border-primary focus:outline-none">
                            <option value="">— Select Storefront Product —</option>
                            @foreach($storefrontProducts as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (SKU: {{ $p->sku ?? 'N/A' }})</option>
                            @endforeach
                        </select>
                        @error('target_product_id')
                            <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-2">Quantity of Sets to Assemble <span class="text-error">*</span></label>
                        <input type="number" min="1" wire:model="bundle_quantity" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-extrabold text-right text-primary focus:border-primary focus:outline-none" />
                        @error('bundle_quantity')
                            <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Component Factory Batches Selection -->
                <div>
                    <div class="flex justify-between items-center mb-3">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Required Completed Factory Components</label>
                        <button type="button" wire:click="addComponentRow" class="text-xs font-bold text-primary hover:underline flex items-center gap-1 cursor-pointer">
                            <span class="material-symbols-outlined text-[16px]">add</span> Add Component Batch
                        </button>
                    </div>

                    @foreach($bundleComponents as $cIdx => $cRow)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-surface-container-low/30 rounded-xl p-4 mb-3 border border-outline-variant/30 items-center">
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Completed Factory Batch</label>
                                <select wire:model.live="bundleComponents.{{ $cIdx }}.production_batch_id" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-body-md">
                                    <option value="">— Select Completed Factory Batch —</option>
                                    @foreach($completedBatches as $cBatch)
                                        <option value="{{ $cBatch->id }}">
                                            {{ $cBatch->manufacturingProduct?->name }} ({{ $cBatch->batch_code }}) — Unconverted Bal: {{ $cBatch->remaining_unconverted_quantity }} Pcs
                                        </option>
                                    @endforeach
                                </select>
                                @error("bundleComponents.{$cIdx}.production_batch_id")
                                    <p class="text-error text-[10px] font-semibold mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Units per Set</label>
                                    <input type="number" min="1" wire:model="bundleComponents.{{ $cIdx }}.quantity_per_bundle" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-right" />
                                </div>
                                @if(count($bundleComponents) > 1)
                                    <button type="button" wire:click="removeComponentRow({{ $cIdx }})" class="text-error hover:bg-error-container/20 p-2 rounded-lg transition-colors cursor-pointer mt-4">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-2 border-t border-outline-variant/40">
                    <button type="submit" class="px-8 py-3.5 bg-primary hover:bg-primary-container text-on-primary font-extrabold text-xs rounded-xl shadow-md transition-all active:scale-95 cursor-pointer flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">auto_fix_high</span>
                        Assemble & Convert to Storefront Stock
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Bundles Log Sidebar -->
        <div class="space-y-6">
            <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs">
                <h3 class="font-headline-sm text-sm font-extrabold text-primary mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">history</span>
                    Recent Combination History
                </h3>

                @if($recentBundles->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($recentBundles as $bndl)
                            <div class="bg-surface-container-low/40 rounded-xl p-4 border border-outline-variant/30 text-xs">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-mono font-black text-primary">{{ $bndl->bundle_code }}</span>
                                    <span class="text-[10px] text-on-surface-variant font-bold">{{ $bndl->created_at->format('d M H:i') }}</span>
                                </div>
                                <p class="font-bold text-on-surface mb-2">{{ $bndl->product?->name }} ({{ $bndl->quantity_created }} Sets Assembled)</p>

                                <div class="space-y-1 pl-2 border-l-2 border-primary/30">
                                    @foreach($bndl->items as $bItem)
                                        <p class="text-[11px] text-on-surface-variant">
                                            • {{ $bItem->manufacturingProduct?->name }} ({{ $bItem->productionBatch?->batch_code }}): <strong>{{ $bItem->quantity_used }} Pcs used</strong>
                                        </p>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-on-surface-variant/50 italic text-center py-6">No storefront combination bundles recorded yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
