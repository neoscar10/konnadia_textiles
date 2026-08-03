<div>
    <!-- Page Header & Breadcrumbs -->
    <div class="bg-surface-container-lowest border-b border-outline-variant/60 p-6 rounded-2xl mb-6 shadow-xs">
        <nav class="flex mb-2 text-xs text-on-surface-variant font-semibold space-x-2">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-primary transition-colors">Dashboard</a>
            <span>&gt;</span>
            <a href="{{ route('admin.production.workbench') }}" wire:navigate class="hover:text-primary transition-colors">Supervisor Workbench</a>
            <span>&gt;</span>
            <span class="text-primary font-bold">Finished Goods Conversion</span>
        </nav>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Finished Goods Conversion</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">Stock in completed batch items to B2B storefront inventory.</p>
            </div>
            <a href="{{ route('admin.production.batches.ledger', $batch->id) }}" wire:navigate class="inline-flex items-center gap-2 bg-surface-container-high text-primary px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-surface-container-highest shadow-xs transition-all">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Back to Cost Ledger
            </a>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        <!-- Left Content Column (9 Cols) -->
        <div class="xl:col-span-9 space-y-6">

            <!-- Bento Summary Row -->
            <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-surface-container-lowest border border-outline-variant/60 p-5 rounded-2xl shadow-xs md:col-span-2 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider">Production Summary</h3>
                        <span class="px-3 py-1 bg-secondary-container text-on-secondary-container text-xs font-bold rounded-full">
                            {{ $batch->status }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-4 items-end justify-between">
                        <div>
                            <p class="text-xs text-outline mb-1 font-semibold">Batch Number</p>
                            <p class="font-headline-sm text-primary font-black">{{ $batch->batch_code }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-outline mb-1 font-semibold">Product Model</p>
                            <p class="font-body-lg font-bold text-on-surface">{{ $batch->manufacturingProduct->name }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-primary text-on-primary p-5 rounded-2xl shadow-xs flex flex-col justify-between relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 opacity-10">
                        <span class="material-symbols-outlined text-7xl">check_circle</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="material-symbols-outlined text-2xl opacity-80">check_circle</span>
                        <span class="text-xs font-bold uppercase tracking-wider opacity-90">Ready for Stocking</span>
                    </div>
                    <div class="mt-4">
                        <p class="text-4xl font-black leading-none">{{ number_format($goodUnits) }}</p>
                        <p class="text-xs font-semibold opacity-85 mt-1">Finished Goods Available</p>
                    </div>
                </div>
            </section>

            <!-- Inventory Transfer Preview Flow Diagram -->
            <section class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs">
                <h3 class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-6">Inventory Transfer Flow</h3>
                <div class="flex flex-col md:flex-row items-center justify-between relative px-8 gap-6 md:gap-2">
                    <div class="flex flex-col items-center gap-2 z-10">
                        <div class="w-14 h-14 rounded-full bg-surface-container flex items-center justify-center border-2 border-primary">
                            <span class="material-symbols-outlined text-primary text-2xl">conveyor_belt</span>
                        </div>
                        <div class="text-center">
                            <p class="font-bold text-xs">WIP Inventory</p>
                            <p class="text-xs text-outline font-semibold">{{ number_format($goodUnits) }} Pcs</p>
                        </div>
                    </div>
                    
                    <div class="flex-1 w-full md:w-auto px-4 md:mb-6">
                        <div class="h-[2px] bg-dashed border-b border-primary/40 relative">
                            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-surface px-2.5 py-0.5 rounded border border-primary/20 text-[9px] font-bold text-primary uppercase">Conversion</div>
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-2 z-10">
                        <div class="w-14 h-14 rounded-full bg-primary flex items-center justify-center border-2 border-primary shadow-md">
                            <span class="material-symbols-outlined text-white text-2xl">inventory</span>
                        </div>
                        <div class="text-center">
                            <p class="font-bold text-xs text-primary">Finished Goods</p>
                            <p class="text-xs text-outline font-mono font-semibold">{{ $lotNumber }}</p>
                        </div>
                    </div>

                    <div class="flex-1 w-full md:w-auto px-4 md:mb-6">
                        <div class="h-[2px] bg-dashed border-b border-secondary/40 relative">
                            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-surface px-2.5 py-0.5 rounded border border-secondary/20 text-[9px] font-bold text-secondary uppercase">Stock In</div>
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-2 z-10">
                        <div class="w-14 h-14 rounded-full bg-surface-container flex items-center justify-center border-2 border-secondary">
                            <span class="material-symbols-outlined text-secondary text-2xl">sell</span>
                        </div>
                        <div class="text-center">
                            <p class="font-bold text-xs">Sales Stock</p>
                            <p class="text-xs text-outline font-semibold">Available for Orders</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Conversion Target Mapping Form -->
            <section class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs space-y-6">
                <h3 class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/30 pb-3">Finished Goods SKU Mapping</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Storefront Product Mapping *</label>
                        <select wire:model.live="productId" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-bold text-on-surface focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">-- Map to Storefront Product --</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->title }} (SKU: {{ $prod->sku }})</option>
                            @endforeach
                        </select>
                        @error('productId') <span class="text-error text-xs font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Target Warehouse</label>
                        <input type="text" wire:model="targetWarehouse" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary focus:border-primary" />
                        @error('targetWarehouse') <span class="text-error text-xs font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Lot Number</label>
                        <input type="text" wire:model="lotNumber" class="w-full bg-surface-container-low border border-dashed border-outline-variant rounded-xl px-4 py-3 text-sm font-mono font-bold text-primary focus:ring-2 focus:ring-primary" />
                        @error('lotNumber') <span class="text-error text-xs font-semibold block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                @if($productId)
                    @php
                        $targetProd = $products->firstWhere('id', $productId);
                        $currentStock = $targetProd ? $targetProd->stock_quantity : 0;
                        $projectedStock = $currentStock + $goodUnits;
                    @endphp
                    <div class="mt-6 p-5 rounded-2xl bg-secondary-container/10 border border-secondary/30 grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                        <div class="md:col-span-2">
                            <span class="text-[10px] uppercase font-bold text-secondary tracking-widest block">Storefront Target mapping</span>
                            <span class="font-extrabold text-sm text-on-surface block mt-1">{{ $targetProd->title }}</span>
                            <span class="text-xs text-outline font-mono block mt-0.5">SKU: {{ $targetProd->sku }}</span>
                        </div>
                        <div class="text-center md:text-right">
                            <span class="text-[10px] uppercase font-bold text-outline tracking-wider block">Current Stock</span>
                            <span class="font-mono text-sm font-bold text-on-surface-variant block mt-1">{{ $currentStock }} Pcs</span>
                        </div>
                        <div class="text-center md:text-right">
                            <span class="text-[10px] uppercase font-bold text-secondary tracking-wider block">Inward Addition</span>
                            <span class="font-mono text-sm font-bold text-secondary block mt-1">+{{ $goodUnits }} Pcs</span>
                        </div>
                        <div class="text-center md:text-right bg-secondary/10 rounded-xl p-3 border border-secondary/20">
                            <span class="text-[10px] uppercase font-bold text-secondary tracking-wider block">Projected Stock</span>
                            <span class="font-mono text-base font-black text-secondary block mt-0.5">{{ $projectedStock }} Pcs</span>
                        </div>
                    </div>
                @endif
            </section>

        </div>

        <!-- Sticky Right Sidebar (3 Cols) -->
        <div class="xl:col-span-3 space-y-6">
            <section class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-6 bg-primary text-on-primary">
                    <h3 class="text-[11px] font-bold text-white/80 uppercase tracking-widest mb-4">Stock In Summary</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="opacity-80">Total Output</span>
                            <span class="font-extrabold text-base">{{ number_format($goodUnits) }} Pcs</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="opacity-80">Valuation</span>
                            <span class="font-extrabold text-base">₹{{ number_format($batch->total_production_cost, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="p-5 space-y-4">
                    <button type="button" wire:click="convert" class="w-full bg-primary hover:bg-primary-container text-on-primary font-bold py-3.5 rounded-xl transition-all shadow-md active:scale-95 flex items-center justify-center gap-2 text-xs">
                        <span class="material-symbols-outlined text-[18px]">verified</span>
                        Convert to Finished Goods
                    </button>
                    <a href="{{ route('admin.production.batches.ledger', $batch->id) }}" wire:navigate class="w-full inline-flex justify-center border border-outline-variant hover:bg-surface-container-low text-on-surface-variant font-bold py-3 rounded-xl transition-all text-xs text-center">
                        Cancel & Return
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>
