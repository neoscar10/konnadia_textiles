<div>
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-1 bg-primary/10 text-primary text-xs font-bold rounded-lg uppercase tracking-wider">Manufacturing Management</span>
                <span class="text-outline text-xs font-bold">• Active Production Queue</span>
            </div>
            <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Production Jobs Hub</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Create work orders, track overall stage completion, and convert completed products to storefront inventory.</p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <button type="button" wire:click="openConversionModal" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-xl font-label-md text-label-md font-bold shadow-md transition-all active:scale-95 whitespace-nowrap">
                <span class="material-symbols-outlined">shopping_cart_checkout</span>
                Convert to Storefront Product
            </button>
            <button type="button" wire:click="openCreateModal" class="flex items-center gap-2 bg-primary text-on-primary px-6 py-3 rounded-xl font-label-md text-label-md font-bold shadow-md hover:bg-primary-container transition-all active:scale-95 whitespace-nowrap">
                <span class="material-symbols-outlined">add</span>
                Create New Production Job
            </button>
        </div>
    </div>

    <!-- Conversion & Production Summary Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">task_alt</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Completed Jobs</p>
                <p class="text-2xl font-black text-on-surface">{{ number_format($totalCompletedJobsCount) }}</p>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Finished Goods Produced</p>
                <p class="text-2xl font-black text-on-surface">{{ number_format($totalFinishedUnitsProduced) }} <span class="text-xs font-bold text-outline">Pcs</span></p>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">storefront</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Converted to Storefront</p>
                <p class="text-2xl font-black text-emerald-600">{{ number_format($totalStorefrontConvertedUnits) }} <span class="text-xs font-bold text-outline">Pcs</span></p>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">pending_actions</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Available Unconverted Pool</p>
                <p class="text-2xl font-black text-amber-600">{{ number_format($availableUnconvertedPoolUnits) }} <span class="text-xs font-bold text-outline">Pcs</span></p>
            </div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/60 mb-6 flex flex-wrap items-center gap-4 shadow-xs">
        <div class="w-full max-w-md">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-on-surface-variant">search</span>
                <input wire:model.live.debounce.300ms="search" class="w-full px-4 py-2.5 bg-surface rounded-xl border border-outline-variant/60 focus:ring-2 focus:ring-primary/20 focus:border-primary font-body-sm text-body-sm" placeholder="Search Job Code, Batch ID, Product Name..." type="text"/>
            </div>
        </div>
        <div>
            <select wire:model.live="statusFilter" class="bg-surface border border-outline-variant/60 rounded-xl font-label-md text-label-md py-2.5 px-4 focus:ring-2 focus:ring-primary/20 focus:border-primary font-bold">
                <option value="">Status (All Jobs)</option>
                <option value="in_progress">In Progress</option>
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <!-- Data List: Pure Production Batches Hub -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 overflow-hidden shadow-xs">
        <table class="w-full text-left border-collapse font-body-md">
            <thead>
                <tr class="bg-surface-container-low border-b border-outline-variant/60 text-xs text-on-surface-variant uppercase tracking-wider">
                    <th class="px-6 py-4 font-bold">Production Batch ID</th>
                    <th class="px-6 py-4 font-bold">Manufacturing Product</th>
                    <th class="px-6 py-4 font-bold text-center">Total Jobs</th>
                    <th class="px-6 py-4 font-bold text-center">Batch Target Qty</th>
                    <th class="px-6 py-4 font-bold text-center">Unconverted Stock</th>
                    <th class="px-6 py-4 font-bold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/40">
                @forelse($paginatedBatches as $batchCode => $batchJobs)
                    @php
                        $firstJob = $batchJobs->first();
                        $supervisor = $firstJob?->supervisor;
                        $batchUnconvertedSum = $batchJobs->sum(fn($j) => $j->remaining_unconverted_quantity);
                        $plannedTargetQty = $batchJobs->sum(fn($j) => $j->target_quantity);
                        $batchDbId = $firstJob?->production_batch_db_id;
                        if (!$batchDbId && !empty($batchCode)) {
                            $batchObj = \App\Models\ProductionBatch::where('batch_code', $batchCode)->first();
                            if (!$batchObj) {
                                $batchObj = \App\Models\ProductionBatch::create([
                                    'batch_code' => $batchCode,
                                    'manufacturing_product_id' => $firstJob?->manufacturing_product_id,
                                    'planned_quantity' => $plannedTargetQty,
                                    'status' => 'In Progress',
                                    'supervisor_id' => $supervisor?->id ?: auth()->id(),
                                ]);
                            }
                            $batchDbId = $batchObj->id;
                        }
                        $uniqueProducts = $batchJobs->map(fn($j) => $j->manufacturingProduct)->filter()->unique('id');
                    @endphp
                    <tr class="hover:bg-surface-container/50 transition-colors">
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.production.batches.jobs', $batchCode) }}" wire:navigate class="font-bold text-primary text-base font-mono hover:underline flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">layers</span>
                                {{ $batchCode }}
                            </a>
                            <span class="text-xs text-outline block">Supervisor: {{ $supervisor?->name ?? 'Unassigned' }}</span>
                        </td>
                        <td class="px-6 py-4 space-y-1.5">
                            @forelse($uniqueProducts as $prod)
                                <div>
                                    <p class="font-bold text-on-surface text-sm">{{ $prod->name }}</p>
                                    <span class="text-xs text-outline font-mono">{{ $prod->code }}</span>
                                </div>
                            @empty
                                <p class="font-bold text-on-surface text-sm">Custom Batch</p>
                            @endforelse
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-on-surface">
                            <span class="px-3 py-1 bg-primary/10 text-primary font-black rounded-full text-xs font-mono">
                                {{ $batchJobs->count() }} Job(s)
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center font-black text-on-surface text-sm">
                            {{ number_format($plannedTargetQty) }} Pcs
                        </td>
                        <td class="px-6 py-4 text-center font-black text-sm">
                            @if($batchUnconvertedSum > 0)
                                <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-lg text-xs font-bold font-mono">
                                    {{ number_format($batchUnconvertedSum) }} Pcs
                                </span>
                            @else
                                <span class="text-xs text-outline">0 Pcs</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">

                            <a href="{{ route('admin.production.batches.jobs', $batchCode) }}" wire:navigate class="inline-flex items-center gap-1 bg-primary text-on-primary hover:bg-primary-container px-3 py-1.5 rounded-xl text-xs font-bold transition-all shadow-xs active:scale-95">
                                View Jobs
                                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                            </a>
                            @if($batchDbId)
                                <a href="{{ route('admin.production.batches.ledger', $batchDbId) }}" wire:navigate class="inline-flex items-center gap-1 bg-surface border border-outline-variant/60 text-on-surface hover:bg-surface-container px-3 py-1.5 rounded-xl text-xs font-bold transition-all">
                                    360 Ledger
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-4xl text-outline mb-2">assignment_late</span>
                            <p class="font-body-lg text-body-lg">No production batches found.</p>
                            <button type="button" wire:click="openCreateModal" class="mt-3 text-primary font-bold text-sm hover:underline">
                                + Create your first production batch
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($paginatedBatches->hasPages())
            <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/60">
                {{ $paginatedBatches->links() }}
            </div>
        @endif
    </div>

    <!-- Storefront Finished Goods Conversion Modal -->
    <x-admin.modal id="storefront-conversion-modal" title="Convert Completed Goods to Storefront Product" maxWidth="4xl">
        <form wire:submit.prevent="processConversion" class="space-y-5">
            <p class="text-on-surface-variant text-sm">Convert completed factory products into sellable Storefront Products. Select your target storefront product, define the piece ratio for 1 set (e.g. 1 Bed Sheet + 2 Pillow Cases = 1 Set), and enter the total factory pieces to process from each completed job.</p>

            @if($errors->has('conversionComponents'))
                <div class="bg-error-container/40 border border-error/30 text-error p-3.5 rounded-xl text-xs font-bold">
                    {{ $errors->first('conversionComponents') }}
                </div>
            @endif

            <!-- 1. Select Target Storefront Product SKU -->
            <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/60 space-y-4">
                <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">storefront</span>
                    1. Target Storefront Product SKU *
                </h4>

                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Target Storefront Product SKU *</label>
                        <span class="text-[10px] text-outline font-semibold">({{ count($storefrontProducts) }} available)</span>
                    </div>

                    <div x-data="{ open: false, search: '' }" class="relative">
                        <!-- Dropdown Trigger Button -->
                        <button type="button" @click="open = !open; if(open){ $nextTick(() => $refs.searchInput.focus()) }" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2.5 text-left text-sm font-bold text-on-surface flex items-center justify-between shadow-xs hover:border-primary/50 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                            @if($this->selectedTargetProduct)
                                <div class="flex items-center gap-2 truncate">
                                    <span class="material-symbols-outlined text-primary text-base">check_circle</span>
                                    <span class="truncate">{{ $this->selectedTargetProduct->title ?? $this->selectedTargetProduct->name }}</span>
                                    <span class="px-2 py-0.5 bg-primary/10 text-primary font-mono text-[11px] font-bold rounded-lg shrink-0">SKU: {{ $this->selectedTargetProduct->sku ?? 'SKU-'.$this->selectedTargetProduct->id }}</span>
                                </div>
                            @else
                                <span class="text-on-surface-variant/70 font-semibold">-- Search & Select Target Storefront Product --</span>
                            @endif
                            <span class="material-symbols-outlined text-on-surface-variant text-base transition-transform duration-200" :class="open ? 'rotate-180' : ''">unfold_more</span>
                        </button>

                        <!-- Searchable Floating Menu Panel -->
                        <div x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="absolute z-50 left-0 right-0 mt-1.5 bg-surface border border-outline-variant/60 rounded-xl shadow-xl overflow-hidden p-2 space-y-2">
                            <!-- Embedded Live Search Input -->
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-base">search</span>
                                <input x-ref="searchInput" type="text" x-model="search" placeholder="Type title or SKU to search products..." class="w-full bg-surface-container-low border border-outline-variant/60 rounded-lg pl-9 pr-8 py-2 text-xs font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <button x-show="search.length > 0" type="button" @click="search = ''" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-on-surface">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                </button>
                            </div>

                            <!-- Scrollable Product Options List -->
                            <div class="max-h-56 overflow-y-auto divide-y divide-outline-variant/30 font-body-md text-xs">
                                @forelse($storefrontProducts as $sp)
                                    <div x-show="!search || '{{ strtolower(addslashes(($sp->title ?? $sp->name) . ' ' . $sp->sku)) }}'.includes(search.toLowerCase())"
                                         @click="$wire.set('target_product_id', {{ $sp->id }}); open = false"
                                         class="p-2.5 hover:bg-primary/5 cursor-pointer rounded-lg flex items-center justify-between transition-colors {{ $target_product_id == $sp->id ? 'bg-primary/10 font-bold text-primary' : 'text-on-surface' }}">
                                        <div class="flex items-center gap-2 truncate">
                                            <span class="material-symbols-outlined text-sm {{ $target_product_id == $sp->id ? 'text-primary' : 'text-on-surface-variant' }}">inventory_2</span>
                                            <div class="truncate">
                                                <p class="font-bold text-sm leading-tight truncate">{{ $sp->title ?? $sp->name }}</p>
                                                <p class="text-[10px] text-outline font-mono mt-0.5">SKU: {{ $sp->sku ?? 'SKU-'.$sp->id }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0 ml-2">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $sp->stock_quantity > 0 ? 'bg-secondary/10 text-secondary' : 'bg-error/10 text-error' }}">
                                                {{ $sp->stock_quantity }} in stock
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-4 text-center text-outline font-semibold">No storefront products available.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    @error('target_product_id') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Dual Unit (Unit 1 vs Unit 2) Conversion Selection -->
                @if($this->selectedTargetProduct)
                    @php
                        $unit1 = $this->selectedTargetProduct->units->firstWhere('level', 1);
                        $unit2 = $this->selectedTargetProduct->units->firstWhere('level', 2);
                    @endphp
                    <div class="pt-2 border-t border-outline-variant/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-secondary text-base">straighten</span>
                            <span class="text-xs font-bold text-on-surface">Target Unit of Measure:</span>
                        </div>

                        @if($unit2)
                            <div class="flex items-center gap-2 bg-surface p-1 rounded-xl border border-outline-variant/60">
                                <button type="button" wire:click="$set('target_unit_level', 1)" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 {{ $target_unit_level === 1 ? 'bg-primary text-on-primary shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                    <span class="material-symbols-outlined text-xs">category</span>
                                    Unit 1: {{ $unit1?->name ?? 'Piece' }} ({{ $unit1?->short_code ?? 'Pc' }})
                                </button>
                                <button type="button" wire:click="$set('target_unit_level', 2)" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 {{ $target_unit_level === 2 ? 'bg-secondary text-on-secondary shadow-xs' : 'text-on-surface-variant hover:text-on-surface' }}">
                                    <span class="material-symbols-outlined text-xs">inventory_2</span>
                                    Unit 2: {{ $unit2->name }} ({{ $unit2->short_code }})
                                    <span class="text-[10px] opacity-80 font-normal">(1 {{ $unit2->short_code }} = {{ number_format($unit2->conversion_to_base) }} {{ $unit1?->short_code ?? 'Pcs' }})</span>
                                </button>
                            </div>
                        @else
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-surface-container text-on-surface font-bold text-xs rounded-lg border border-outline-variant/50 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-xs text-primary">check_circle</span>
                                    Unit 1: {{ $unit1?->name ?? 'Pieces' }} ({{ $unit1?->short_code ?? 'Pcs' }})
                                </span>
                                <span class="text-[10px] text-outline font-semibold">(Standard Single-Unit Conversion)</span>
                            </div>
                        @endif
                    </div>

                    @if($target_unit_level === 2 && $unit2)
                        <div class="bg-secondary/10 border border-secondary/30 p-2.5 rounded-xl text-xs font-bold text-secondary flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base">swap_calls</span>
                                Target Unit 2 Conversion: Each assembled Box = {{ number_format($unit2->conversion_to_base) }} Base {{ $unit1?->short_code ?? 'Pcs' }} required from Factory Jobs
                            </span>
                            <span class="text-[11px] font-mono bg-surface px-2 py-0.5 rounded-md text-on-surface border border-secondary/20">
                                Ratio: 1 {{ $unit2->short_code }} = {{ number_format($unit2->conversion_to_base) }} {{ $unit1?->short_code ?? 'Pcs' }}
                            </span>
                        </div>
                    @endif
                @endif
            </div>

            <!-- 2. Source Factory Components & Processed Pieces -->
            <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/60 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">fact_check</span>
                        2. Set Definition & Factory Pieces to Process *
                    </h4>
                    <button type="button" wire:click="addConversionComponentRow" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">add_circle</span> Add Another Component
                    </button>
                </div>

                <div class="space-y-3">
                    @foreach($conversionComponents as $index => $comp)
                        @php
                            $selectedJob = !empty($comp['production_job_id']) ? $completedJobsForPicker->firstWhere('id', intval($comp['production_job_id'])) : null;
                            $maxAvail = $selectedJob ? $selectedJob->remaining_unconverted_quantity : 0;
                            $inputPcs = intval($comp['total_pieces_input'] ?? 0);
                            $isExceed = $selectedJob && ($inputPcs > $maxAvail);
                        @endphp
                        <div wire:key="conv-comp-row-{{ $index }}" class="p-3.5 bg-surface border border-outline-variant/60 rounded-xl space-y-2">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                <div class="md:col-span-5">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1 whitespace-nowrap">Source Completed Job #{{ $index + 1 }} *</label>
                                    <select wire:model.live="conversionComponents.{{ $index }}.production_job_id" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                        <option value="">-- Choose Completed Factory Job --</option>
                                        @foreach($completedJobsForPicker as $cj)
                                            <option value="{{ $cj->id }}">
                                                Job {{ $cj->job_code }} — {{ $cj->manufacturingProduct?->name }} (Available: {{ $cj->remaining_unconverted_quantity }} Pcs)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("conversionComponents.{$index}.production_job_id") <span class="text-error text-[11px] block mt-1 font-semibold">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1 whitespace-nowrap">Ratio (Pcs per Set) *</label>
                                    <div class="flex items-center gap-1.5">
                                        <input type="number" min="1" wire:model.live="conversionComponents.{{ $index }}.quantity_per_set" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                        <span class="text-[11px] font-bold text-outline whitespace-nowrap">Pcs/Set</span>
                                    </div>
                                    @error("conversionComponents.{$index}.quantity_per_set") <span class="text-error text-[11px] block mt-1 font-semibold">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-4">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1 whitespace-nowrap">Factory Pieces to Process *</label>
                                    <div class="flex items-center gap-1.5">
                                        <input type="number" min="1" wire:model.live="conversionComponents.{{ $index }}.total_pieces_input" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                        @if(count($conversionComponents) > 1)
                                            <button type="button" wire:click="removeConversionComponentRow({{ $index }})" class="text-error hover:bg-error-container/20 p-1.5 rounded-lg transition-colors shrink-0">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </button>
                                        @endif
                                    </div>
                                    @error("conversionComponents.{$index}.total_pieces_input") <span class="text-error text-[11px] block mt-1 font-semibold">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            @if($selectedJob)
                                <div class="flex items-center justify-between text-xs pt-1 border-t border-outline-variant/30">
                                    <span class="text-on-surface-variant font-semibold">
                                        Selected: <strong>{{ $selectedJob->manufacturingProduct?->name }}</strong> (Job {{ $selectedJob->job_code }})
                                    </span>
                                    <span class="font-bold {{ $isExceed ? 'text-error' : 'text-emerald-700' }}">
                                        Available Unconverted Stock: {{ $maxAvail }} Pcs {{ $isExceed ? '(Exceeds available stock!)' : '' }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Automatic Live Conversion Calculation Summary Callout -->
                @php
                    $summary = $this->conversionSummary;
                    $maxSets = $summary['max_sets'];
                    $targetProduct = $target_product_id ? $storefrontProducts->firstWhere('id', $target_product_id) : null;
                @endphp
                @if($target_product_id && !empty($summary['rows']))
                    <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl space-y-2 mt-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-800 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-base">calculate</span>
                                Automatic Conversion Summary
                            </span>
                            <span class="text-lg font-black text-emerald-700 bg-emerald-100 px-3 py-0.5 rounded-lg">
                                +{{ number_format($maxSets) }} Sets
                            </span>
                        </div>

                        <p class="text-xs font-semibold text-emerald-900">
                            Converting the entered factory pieces will produce <strong class="text-emerald-700 font-extrabold text-sm">{{ number_format($maxSets) }} complete sets</strong> of <strong>{{ $targetProduct?->title ?? $targetProduct?->name }}</strong> (stock will increase from {{ $targetProduct?->stock_quantity }} to {{ ($targetProduct?->stock_quantity ?? 0) + $maxSets }}).
                        </p>

                        <div class="pt-2 border-t border-emerald-200/60 space-y-1">
                            @foreach($summary['rows'] as $r)
                                @if($r['job'])
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-emerald-900">
                                            • <strong>{{ $r['job']->manufacturingProduct?->name }}</strong> (Job {{ $r['job']->job_code }}): Processing {{ $r['inputPcs'] }} Pcs @ {{ $r['ratio'] }} Pcs/Set &rightarrow; Consumes <strong>{{ $r['consumedPcs'] }} Pcs</strong>
                                        </span>
                                        @if($r['leftoverPcs'] > 0)
                                            <span class="text-amber-800 font-bold bg-amber-100 px-2 py-0.5 rounded text-[11px]">
                                                {{ $r['leftoverPcs'] }} Pcs will remain unconverted
                                            </span>
                                        @else
                                            <span class="text-emerald-700 font-bold text-[11px]">
                                                0 Pcs leftover
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- 3. Record Packaging Materials Used -->
            <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/60 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">box</span>
                        3. Record Packaging Materials Used (Optional)
                    </h4>
                    <button type="button" wire:click="addConversionPackagingRow" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">add_circle</span> Add Packaging Material
                    </button>
                </div>

                <div class="space-y-3">
                    @foreach($conversionPackaging as $index => $pkg)
                        <div class="flex items-start gap-2 w-full">
                            <div class="flex-1 min-w-0">
                                <select wire:model.live="conversionPackaging.{{ $index }}.raw_material_id" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                    <option value="">-- Select Packaging Material --</option>
                                    @foreach($packagingRawMaterials as $mat)
                                        <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->code }} · {{ $mat->unit }})</option>
                                    @endforeach
                                </select>
                                @error("conversionPackaging.{$index}.raw_material_id") <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span> @enderror
                            </div>

                            <div class="w-36 shrink-0">
                                <input type="number" step="0.0001" min="0.0001" placeholder="Qty Used" wire:model.blur="conversionPackaging.{{ $index }}.quantity_used" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                @error("conversionPackaging.{$index}.quantity_used") <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span> @enderror
                            </div>

                            <div class="shrink-0 flex items-center pt-1">
                                <button type="button" wire:click="removeConversionPackagingRow({{ $index }})" class="text-error hover:bg-error-container/20 p-1.5 rounded-lg transition-colors">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Conversion Remarks</label>
                <textarea wire:model="conversion_notes" rows="2" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-2 text-xs text-on-surface" placeholder="Optional notes for storefront stock conversion audit..."></textarea>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="shopping_cart_checkout">Assemble & Convert to Storefront Stock</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Create New Job Modal -->
    <x-admin.modal id="create-job-modal" title="Create New Production Job" maxWidth="xl">
        <form wire:submit.prevent="saveJob" class="space-y-5">
            <p class="text-on-surface-variant text-sm mb-4">Initialize a new production job order. Work order codes are automatically generated.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Auto-generated Job Code Preview -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Job Code (Auto-generated)</label>
                    <div class="px-4 py-2.5 bg-surface-container-high/60 border border-outline-variant/60 rounded-xl font-bold font-mono text-primary text-sm flex items-center justify-between">
                        <span>Auto Generated</span>
                        <span class="bg-primary/10 text-primary px-2 py-0.5 rounded text-[10px]">JOB-2026-XXXX</span>
                    </div>
                </div>

                <!-- Production Batch ID -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Production Batch ID</label>
                    <input type="text" wire:model="production_batch_id" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-2.5 font-bold text-sm text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    @error('production_batch_id') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Production Notes</label>
                <textarea wire:model="notes" rows="3" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-2.5 text-sm text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="Optional notes for shop floor supervisor..."></textarea>
                @error('notes') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="add">Create Job & Manage Stages</x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>

