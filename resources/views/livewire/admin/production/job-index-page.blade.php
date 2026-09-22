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
            {{-- Hidden for now since conversion is handled on finished goods page --}}
            {{-- <button type="button" wire:click="openConversionModal" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-xl font-label-md text-label-md font-bold shadow-md transition-all active:scale-95 whitespace-nowrap">
                <span class="material-symbols-outlined">shopping_cart_checkout</span>
                Convert to Storefront Product
            </button> --}}
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

    <!-- Main Hub Tab Switcher -->
    <div class="flex items-center gap-2 border-b border-outline-variant/60 mb-6">
        <button type="button" wire:click="$set('activeTab', 'all')" class="pb-3 px-4 text-sm font-black border-b-2 transition-all flex items-center gap-2 {{ $activeTab === 'all' ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
            <span class="material-symbols-outlined text-base">layers</span>
            <span>All Production Batches &amp; Jobs</span>
        </button>
        <button type="button" wire:click="$set('activeTab', 'discrepancies')" class="pb-3 px-4 text-sm font-black border-b-2 transition-all flex items-center gap-2 {{ $activeTab === 'discrepancies' ? 'border-amber-600 text-amber-800' : 'border-transparent text-on-surface-variant hover:text-on-surface' }}">
            <span class="material-symbols-outlined text-base">warning</span>
            <span>Completed Jobs with Discrepancies</span>
            @if(count($discrepancyJobs) > 0)
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-500 text-white">
                    {{ count($discrepancyJobs) }}
                </span>
            @else
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                    0
                </span>
            @endif
        </button>
    </div>

    @if($activeTab === 'discrepancies')
        <!-- Completed Jobs Discrepancies Table -->
        <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 overflow-hidden shadow-xs mb-6">
            <div class="p-4 bg-amber-50/80 border-b border-amber-200">
                <h3 class="font-extrabold text-sm text-amber-950 uppercase tracking-wider flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-700">warning</span>
                    <span>Completed Jobs Pending Discrepancy Recording</span>
                </h3>
                <p class="text-xs text-amber-800 font-medium mt-0.5">
                    These finished production jobs have a discrepancy between the initial cut quantity and final completed labor output. Click "Record Discrepancy" to categorize scrap, damage, and alterations.
                </p>
            </div>

            <table class="w-full text-left border-collapse font-body-md">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant/60 text-xs text-on-surface-variant uppercase tracking-wider">
                        <th class="px-6 py-4 font-bold">Job Code &amp; Batch</th>
                        <th class="px-6 py-4 font-bold">Manufacturing Product</th>
                        <th class="px-6 py-4 font-bold text-center">Initial Cut Qty</th>
                        <th class="px-6 py-4 font-bold text-center">Final Output Qty</th>
                        <th class="px-6 py-4 font-bold text-center">Discrepancy Qty</th>
                        <th class="px-6 py-4 font-bold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($discrepancyJobs as $dJob)
                        <tr class="hover:bg-surface-container/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-bold text-primary text-base font-mono block">{{ $dJob->job_code }}</span>
                                <span class="text-xs text-outline font-mono block">Batch: {{ $dJob->production_batch_id }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-on-surface text-sm">{{ $dJob->manufacturingProduct?->name }}</p>
                                @if($dJob->pattern)
                                    <span class="text-xs text-amber-700 font-semibold block">Pattern: {{ $dJob->pattern->name }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-slate-800">
                                {{ $dJob->initial_cut_quantity }} Pcs
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-emerald-800">
                                {{ $dJob->final_produced_yield }} Pcs
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 bg-rose-100 text-rose-800 font-black rounded-full text-xs font-mono border border-rose-200">
                                    {{ $dJob->discrepancy_quantity }} Pcs
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button" wire:click="openDiscrepancyModal({{ $dJob->id }})" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-black text-xs rounded-xl transition-all shadow-xs active:scale-95 inline-flex items-center gap-1 cursor-pointer">
                                    <span class="material-symbols-outlined text-[16px]">edit_note</span>
                                    <span>Record Discrepancy</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl text-emerald-600 mb-2">check_circle</span>
                                <p class="font-body-lg text-body-lg font-bold">No completed jobs with unresolved discrepancies.</p>
                                <p class="text-xs text-outline mt-1">All finished jobs match their initial cut quantities!</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
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
            <div>
                <select wire:model.live="supervisorFilter" class="bg-surface border border-outline-variant/60 rounded-xl font-label-md text-label-md py-2.5 px-4 focus:ring-2 focus:ring-primary/20 focus:border-primary font-bold">
                    <option value="">All Supervisors</option>
                    @foreach($supervisors as $sup)
                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                    @endforeach
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
                        <th class="px-6 py-4 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($paginatedBatches as $batchCode => $batchJobs)
                        @php
                            $firstJob = $batchJobs->first();
                            $supervisorObj = $firstJob?->factorySupervisor 
                                ?? $firstJob?->batch?->factorySupervisor 
                                ?? $firstJob?->supervisor;
                            $supervisorName = $supervisorObj?->name ?? 'Unassigned';
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
                                        'supervisor_id' => auth()->id(),
                                        'factory_supervisor_id' => $firstJob?->factory_supervisor_id,
                                    ]);
                                }
                                $batchDbId = $batchObj->id;
                            }
                            $uniqueProducts = $batchJobs->map(fn($j) => $j->manufacturingProduct)->filter()->unique('id');
                        @endphp
                        @php
                            $batchRecord = $batchDbId ? \App\Models\ProductionBatch::find($batchDbId) : null;
                            $isBatchComplete = $batchRecord ? $batchRecord->isFullyCompleted() : false;
                            $isBatchConverted = $batchRecord ? $batchRecord->is_converted : false;
                        @endphp
                        <tr class="hover:bg-surface-container/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.production.batches.jobs', $batchCode) }}" wire:navigate class="font-bold text-primary text-base font-mono hover:underline flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[18px]">layers</span>
                                        {{ $batchCode }}
                                    </a>
                                    @if($isBatchComplete)
                                        <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-black rounded-full uppercase tracking-wider border border-emerald-300">
                                            Complete
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold rounded-full uppercase tracking-wider">
                                            In Progress
                                        </span>
                                    @endif
                                </div>
                                <span class="text-xs text-outline block mt-0.5">Supervisor: {{ $supervisorName }}</span>
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
                            <td class="px-6 py-4 text-right space-x-2">
                                @if($isBatchComplete && $batchDbId && !$isBatchConverted)
                                    <button type="button" wire:click="openBatchDesignModal({{ $batchDbId }})" class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-xl text-xs font-bold transition-all shadow-xs active:scale-95 cursor-pointer">
                                        <span class="material-symbols-outlined text-[14px]">swap_horiz</span>
                                        Convert
                                    </button>
                                @elseif($isBatchConverted)
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-xs font-bold rounded-xl border border-slate-200">
                                        Converted
                                    </span>
                                @endif

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
                            <td colspan="5" class="px-6 py-12 text-center text-on-surface-variant">
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
    @endif

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
                            <div @wheel.stop @touchmove.stop class="max-h-60 overflow-y-auto overscroll-contain divide-y divide-outline-variant/30 font-body-md text-xs">
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

    <!-- Create New Production Batch Modal -->
    <x-admin.modal id="create-job-modal" title="Create New Production Batch" maxWidth="2xl">
        <form wire:submit.prevent="saveJob" class="space-y-5">
            <p class="text-on-surface-variant text-xs">
                Select factory supervisor, select designated cutter, set batch priority, and add notes. Products to produce will be defined during the fabric cutting stage.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Supervisor -->
                <div>
                    <label class="block text-[11px] font-black text-on-surface-variant uppercase tracking-wider mb-1">FACTORY SUPERVISOR *</label>
                    @if($supervisors->isEmpty())
                        <div class="w-full bg-amber-500/10 border border-amber-500/30 rounded-xl px-3 py-2 text-xs text-amber-800 font-semibold flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[14px]">warning</span>
                            <span>No active supervisors. <a href="{{ route('factory.supervisors.index') }}" wire:navigate class="underline">Add Supervisor →</a></span>
                        </div>
                    @else
                        <select wire:model="factory_supervisor_id" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                            <option value="">— Select a Supervisor —</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }} ({{ $supervisor->code }}){{ $supervisor->department ? ' · ' . $supervisor->department : '' }}</option>
                            @endforeach
                        </select>
                    @endif
                    @error('factory_supervisor_id') <span class="text-error text-[11px] block mt-1 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Cutter Selection -->
                <div>
                    <label class="block text-[11px] font-black text-on-surface-variant uppercase tracking-wider mb-1">DESIGNATED CUTTER *</label>
                    @if($cutters->isEmpty())
                        <div class="w-full bg-amber-500/10 border border-amber-500/30 rounded-xl px-3 py-2 text-xs text-amber-800 font-semibold flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[14px]">warning</span>
                            <span>No active cutters.</span>
                        </div>
                    @else
                        <select wire:model="cutter_id" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                            <option value="">— Select Batch Cutter —</option>
                            @foreach($cutters as $cutter)
                                <option value="{{ $cutter->id }}">{{ $cutter->name }} ({{ $cutter->worker_code }})</option>
                            @endforeach
                        </select>
                    @endif
                    @error('cutter_id') <span class="text-error text-[11px] block mt-1 font-semibold">{{ $message }}</span> @enderror
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-[11px] font-black text-on-surface-variant uppercase tracking-wider mb-1">PRIORITY *</label>
                    <select wire:model="priority" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface">
                        <option value="Normal">Normal</option>
                        <option value="Urgent">Urgent</option>
                        <option value="Low">Low</option>
                    </select>
                </div>

                <!-- Remarks / Notes -->
                <div>
                    <label class="block text-[11px] font-black text-on-surface-variant uppercase tracking-wider mb-1">REMARKS / NOTES</label>
                    <input type="text" wire:model="notes" placeholder="Optional notes for shop floor supervisor..." class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface">
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <button type="submit" class="px-6 py-2.5 bg-[#001229] hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-sm transition-all active:scale-95 flex items-center gap-2">
                    Proceed to Cutting Stage
                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Discrepancy Resolution Modal -->
    <x-admin.modal id="discrepancy-resolution-modal" title="Record Job Discrepancy &amp; Reconciliation" maxWidth="3xl">
        @if($activeDiscrepancyJob)
            <form wire:submit.prevent="saveDiscrepancyResolution" class="space-y-6">
                <!-- Summary Header -->
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl space-y-2 text-amber-950">
                    <div class="flex items-center justify-between">
                        <h4 class="font-black text-sm uppercase tracking-wider text-amber-900 font-display">
                            Job Discrepancy Summary — {{ $activeDiscrepancyJob->job_code }}
                        </h4>
                        <span class="px-3 py-1 bg-amber-200 text-amber-900 rounded-full font-black text-xs font-mono">
                            Discrepancy: {{ $activeDiscrepancyJob->discrepancy_quantity }} Pcs
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-xs font-bold">
                        <span>Product: <strong class="text-slate-900">{{ $activeDiscrepancyJob->manufacturingProduct?->name }}</strong></span>
                        <span>·</span>
                        <span>Initial Cut Qty: <strong>{{ $activeDiscrepancyJob->initial_cut_quantity }} Pcs</strong></span>
                        <span>·</span>
                        <span>Final Recorded Output: <strong class="text-emerald-800">{{ $activeDiscrepancyJob->final_produced_yield }} Pcs</strong></span>
                    </div>
                </div>

                @error('discrepancyTotal')
                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs font-bold text-rose-700">
                        {{ $message }}
                    </div>
                @enderror

                <!-- 1. Alteration Units (Spawns New Alteration Production Job) -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <div>
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">
                                1. Alteration Units (Spawns New Alteration Production Job)
                            </h4>
                            <p class="text-[11px] text-slate-500">Products sent for alteration will instantiate a new Production Job inside the batch for the chosen target product and pattern.</p>
                        </div>
                        <button type="button" wire:click="addDiscrepancyAlterationRow" class="px-3 py-1 bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-900 font-bold text-xs rounded-lg transition-all cursor-pointer">
                            ＋ Add Alteration Item
                        </button>
                    </div>

                    <div class="space-y-3">
                        @foreach($alterationRows as $aIdx => $aRow)
                            @php
                                $selectedTargetProdId = $aRow['target_product_id'] ?? null;
                                $targetProdObj = $selectedTargetProdId ? $allProducts->firstWhere('id', $selectedTargetProdId) : null;
                                $targetPatterns = $targetProdObj ? $targetProdObj->patterns : collect();
                            @endphp
                            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                                <div class="sm:col-span-3">
                                    <label class="block text-[10px] font-black text-amber-900 uppercase tracking-wider mb-1">ALTERED QTY (PCS) *</label>
                                    <input type="number" min="0" wire:model.live.number="alterationRows.{{ $aIdx }}.altered_qty" placeholder="0" class="w-full bg-white border-2 border-amber-300 focus:border-amber-500 rounded-xl px-3 py-2 text-xs font-extrabold text-slate-900 shadow-xs">
                                </div>
                                <div class="sm:col-span-4">
                                    <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider mb-1">TARGET PRODUCT *</label>
                                    <select wire:model.live="alterationRows.{{ $aIdx }}.target_product_id" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                        <option value="">-- Select Target Product --</option>
                                        @foreach($allProducts as $ap)
                                            <option value="{{ $ap->id }}">{{ $ap->name }} ({{ $ap->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-4">
                                    <label class="block text-[10px] font-extrabold text-slate-600 uppercase tracking-wider mb-1">TARGET PATTERN *</label>
                                    <select wire:model="alterationRows.{{ $aIdx }}.target_pattern_id" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                        <option value="">-- Select Target Pattern --</option>
                                        @foreach($targetPatterns as $pat)
                                            <option value="{{ $pat->id }}">{{ $pat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-1 flex justify-end">
                                    @if(count($alterationRows) > 1)
                                        <button type="button" wire:click="removeDiscrepancyAlterationRow({{ $aIdx }})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer">
                                            ✕
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- 2. Non-Good Output Categorization: Scrap vs. Damage -->
                <div class="space-y-4 pt-2 border-t border-slate-200">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">
                                2. Non-Good Output Categorization: Scrap vs. Damage
                            </h4>
                            <p class="text-[11px] text-slate-500 mt-0.5">Distinguish between completely unsalvageable scrap loss versus partially damaged items that can still be sold or reused.</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" wire:click="fillAllScrap" class="px-2.5 py-1 bg-rose-100 hover:bg-rose-200 border border-rose-300 text-rose-900 text-[11px] font-bold rounded-lg transition-colors cursor-pointer flex items-center gap-1">
                                <span>♻️</span> Fill All Scrap ({{ $activeDiscrepancyJob->discrepancy_quantity }} Pcs)
                            </button>
                            <button type="button" wire:click="fillAllDamage" class="px-2.5 py-1 bg-amber-100 hover:bg-amber-200 border border-amber-300 text-amber-900 text-[11px] font-bold rounded-lg transition-colors cursor-pointer flex items-center gap-1">
                                <span>⚠️</span> Fill All Damage ({{ $activeDiscrepancyJob->discrepancy_quantity }} Pcs)
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Scrap Section Card -->
                        <div class="p-4 bg-rose-50/60 border border-rose-200 rounded-xl space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold uppercase text-rose-900 flex items-center gap-1">
                                    <span>♻️</span> Scrap Output (Completely Unusable Loss)
                                </span>
                            </div>
                            <div class="grid grid-cols-12 gap-3 items-center">
                                <div class="col-span-5">
                                    <label class="block text-[10px] font-black text-rose-900 uppercase tracking-wider mb-1">SCRAP QTY (PCS) *</label>
                                    <input type="number" min="0" wire:model.live.number="scrapQty" placeholder="0" class="w-full bg-white border-2 border-rose-300 focus:border-rose-500 rounded-xl px-3 py-2 text-xs font-extrabold text-slate-900 shadow-xs">
                                </div>
                                <div class="col-span-7">
                                    <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">REASON NOTE</label>
                                    <input type="text" wire:model="scrapNotes" placeholder="e.g. Fabric cut loss" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                </div>
                            </div>
                        </div>

                        <!-- Damage Section Card -->
                        <div class="p-4 bg-amber-50/60 border border-amber-200 rounded-xl space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold uppercase text-amber-900 flex items-center gap-1">
                                    <span>⚠️</span> Damaged Output (Partially Damaged / Resold)
                                </span>
                            </div>
                            <div class="grid grid-cols-12 gap-3 items-center">
                                <div class="col-span-5">
                                    <label class="block text-[10px] font-black text-amber-900 uppercase tracking-wider mb-1">DAMAGE QTY (PCS) *</label>
                                    <input type="number" min="0" wire:model.live.number="damageQty" placeholder="0" class="w-full bg-white border-2 border-amber-300 focus:border-amber-500 rounded-xl px-3 py-2 text-xs font-extrabold text-slate-900 shadow-xs">
                                </div>
                                <div class="col-span-7">
                                    <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">REASON NOTE</label>
                                    <input type="text" wire:model="damageNotes" placeholder="e.g. Minor defect" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Total Reconciliation Match Indicator -->
                @php
                    $reqDiscrepancy = $activeDiscrepancyJob->discrepancy_quantity;
                    $calcTotal = intval($scrapQty ?? 0) + intval($damageQty ?? 0);
                    foreach($alterationRows as $ar) {
                        $calcTotal += intval($ar['altered_qty'] ?? 0);
                    }
                    $isMatch = $calcTotal === $reqDiscrepancy;
                @endphp
                <div class="p-4 rounded-xl border flex items-center justify-between text-xs font-bold {{ $isMatch ? 'bg-emerald-50 border-emerald-300 text-emerald-950' : 'bg-rose-50 border-rose-300 text-rose-950' }}">
                    <div>
                        <span class="uppercase tracking-wider font-extrabold">Recorded Total: {{ $calcTotal }} / {{ $reqDiscrepancy }} Pcs</span>
                        <span class="block text-[11px] font-medium text-slate-600">
                            {{ $isMatch ? '✓ Perfect match! Discrepancy total is fully reconciled.' : '⚠️ Total recorded (Scrap + Damage + Alterations) must equal exactly ' . $reqDiscrepancy . ' Pcs.' }}
                        </span>
                    </div>
                    <span class="px-3 py-1 rounded-full font-black text-xs uppercase {{ $isMatch ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white' }}">
                        {{ $isMatch ? 'Ready to Save' : 'Mismatch' }}
                    </span>
                </div>

                <!-- Remarks -->
                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Reconciliation Remarks</label>
                    <input type="text" wire:model="discrepancyRemarks" placeholder="Optional audit notes" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900">
                </div>

                <!-- Modal Actions -->
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                    <button type="button" @click="show = false" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-800 font-bold text-xs rounded-xl hover:bg-slate-50 cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl shadow-md transition-all active:scale-95 flex items-center gap-2 cursor-pointer" {{ !$isMatch ? 'disabled' : '' }}>
                        <span class="material-symbols-outlined text-[16px]">check_circle</span>
                        <span>Save Discrepancy Resolution</span>
                    </button>
                </div>
            </form>
        @endif
    </x-admin.modal>

    <!-- Select Design ID Modal -->
    <x-admin.modal id="select-batch-design-modal" title="Select Design ID for Storefront Conversion" maxWidth="lg">
        <div class="space-y-4">
            <div class="p-3.5 bg-primary/10 border border-primary/20 rounded-xl">
                <p class="text-xs font-extrabold text-primary uppercase tracking-wider">Production Batch: {{ $selectedBatchCode }}</p>
                <p class="text-xs text-on-surface-variant mt-0.5">Select the fabric Design ID you wish to convert to a Storefront Product.</p>
            </div>

            <div class="space-y-2 max-h-72 overflow-y-auto">
                @foreach($batchDesignOptions as $opt)
                    <div wire:click="selectDesignForConversion('{{ $opt['design_id'] }}')"
                         class="p-4 bg-surface border border-outline-variant/60 hover:border-primary hover:bg-primary/5 rounded-xl cursor-pointer transition-all flex items-center justify-between group shadow-xs">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-lg">style</span>
                                <h4 class="font-black text-on-surface text-sm font-mono group-hover:text-primary transition-colors">
                                    {{ $opt['design_id'] }}
                                </h4>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-outline">
                                @foreach($opt['products'] as $p)
                                    <span class="px-2 py-0.5 bg-surface-container rounded-md font-semibold text-on-surface text-[11px]">
                                        {{ $p['name'] }}: <strong>{{ number_format($p['qty']) }} Pcs</strong>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-black rounded-full font-mono">
                                {{ number_format($opt['total_produced_qty']) }} Pcs Total
                            </span>
                            <span class="material-symbols-outlined text-outline group-hover:text-primary text-base block mt-1 transition-transform group-hover:translate-x-1">arrow_forward</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end pt-3 border-t border-outline-variant/40">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
            </div>
        </div>
    </x-admin.modal>

    <!-- Batch Conversion Wizard Modal -->
    <x-admin.modal id="batch-conversion-wizard-modal" title="Convert Batch {{ $selectedBatchCode }} to Storefront Product" maxWidth="3xl">
        <form wire:submit.prevent="processBatchConversionSubmit" class="space-y-5">
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl flex items-center justify-between">
                <div>
                    <span class="px-2.5 py-0.5 bg-emerald-600 text-white text-[10px] font-black rounded-md uppercase tracking-wider">Design Selected</span>
                    <h4 class="text-lg font-black text-emerald-950 font-mono mt-1">{{ $selectedDesignId }}</h4>
                    <p class="text-xs text-emerald-800 font-semibold">Batch Code: {{ $selectedBatchCode }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs font-bold text-emerald-900 uppercase block">Prefilled Max Target Sets</span>
                    <span class="text-2xl font-black text-emerald-700 font-mono">{{ number_format($prefilledTargetSets) }} Sets</span>
                </div>
            </div>

            @if($errors->has('selectedCategoryIdForBatchConv') || $errors->has('prefilledTargetSets'))
                <div class="bg-error-container/40 border border-error/30 text-error p-3.5 rounded-xl text-xs font-bold">
                    {{ $errors->first('selectedCategoryIdForBatchConv') ?: $errors->first('prefilledTargetSets') }}
                </div>
            @endif

            <!-- 1. Select Storefront Category -->
            <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/60 space-y-3">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">1. Select Target Leaf Category *</label>
                @php
                    $leafCatService = app(\App\Services\Catalog\CategoryService::class);
                    $leafCats = $leafCatService->getLeafCategories(manufacturedOnly: true);
                @endphp
                <select wire:model.live="selectedCategoryIdForBatchConv" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                    <option value="">-- Select Storefront Leaf Category --</option>
                    @foreach($leafCats as $lc)
                        @php $isCfg = in_array($lc->id, $configuredCategoryIds ?? []); @endphp
                        <option value="{{ $lc->id }}">{{ $lc->name }} {{ $isCfg ? '✓ (Configured)' : '(Not Configured)' }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Target Assembled Quantity (Sets) -->
            <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/60 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">2. Target Assembled Quantity (Sets) *</label>
                    <span class="text-[11px] text-emerald-700 font-bold bg-emerald-100 px-2 py-0.5 rounded">Auto-Calculated from Batch Output</span>
                </div>
                <input type="number" min="1" wire:model.live="prefilledTargetSets" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2.5 text-sm font-black text-on-surface focus:ring-2 focus:ring-primary/20">
                <p class="text-[11px] text-outline">Target quantity is automatically set to the maximum complete sets formed from batch outputs. Any leftover items are automatically saved as Spare Products.</p>
            </div>

            <!-- 3. Spare Stock Options (If matching spare products exist for this Design ID) -->
            @if(!empty($availableSpareProducts))
                <div class="bg-amber-50 border border-amber-200 p-4 rounded-xl space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black text-amber-950 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-amber-700 text-base">inventory</span>
                            Available Spare Stock for Design {{ $selectedDesignId }}
                        </span>
                        <span class="text-[11px] font-bold text-amber-800">Add spare stock to increase target sets!</span>
                    </div>

                    <div class="space-y-2">
                        @foreach($availableSpareProducts as $idx => $sp)
                            <div class="p-3 bg-white border border-amber-200 rounded-xl flex items-center justify-between gap-3 text-xs">
                                <div>
                                    <p class="font-bold text-slate-900">{{ $sp['product_name'] }}</p>
                                    <span class="text-[10px] text-slate-500 font-mono">From {{ $sp['source_batch'] }} — {{ $sp['available_qty'] }} Pcs Available</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <input type="number" min="0" max="{{ $sp['available_qty'] }}" wire:model.live.number="availableSpareProducts.{{ $idx }}.qty_to_use" class="w-24 bg-amber-50 border border-amber-300 rounded-lg px-2 py-1 text-xs font-bold text-slate-900 text-center">
                                    <button type="button" wire:click="toggleAddAllSpareStock({{ $idx }})" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white font-bold text-[11px] rounded-lg transition-all">
                                        {{ ($sp['qty_to_use'] ?? 0) > 0 ? 'Clear' : 'Add All (' . $sp['available_qty'] . ')' }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 4. Conversion Consumption & Spare Products Breakdown -->
            @php
                $bSummary = $this->batchConversionSummary;
            @endphp
            @if($selectedCategoryIdForBatchConv && !empty($bSummary['rows']))
                <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black text-emerald-950 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-emerald-700 text-base">calculate</span>
                            Conversion &amp; Spare Products Breakdown
                        </span>
                        <span class="text-xs font-black text-emerald-800 bg-emerald-100 px-3 py-1 rounded-lg font-mono">
                            {{ number_format($bSummary['targetSets']) }} Sets Target
                        </span>
                    </div>

                    <div class="space-y-2">
                        @foreach($bSummary['rows'] as $r)
                            <div class="p-3 bg-surface rounded-xl border border-emerald-200/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                <div>
                                    <span class="font-bold text-on-surface text-sm block">{{ $r['manufacturing_product'] }}</span>
                                    <span class="text-outline text-[11px]">
                                        Req: {{ $r['req_per_set'] }} Pcs/Set × {{ number_format($bSummary['targetSets']) }} = <strong>{{ number_format($r['total_required']) }} Pcs</strong>
                                        (Available: {{ number_format($r['total_available']) }} Pcs)
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-900 font-bold rounded-lg text-[11px]">
                                        Consumes {{ number_format($r['consumed']) }} Pcs
                                    </span>
                                    @if($r['leftover'] > 0)
                                        <span class="px-2.5 py-1 bg-amber-100 text-amber-900 font-extrabold rounded-lg text-[11px] border border-amber-300/60 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs text-amber-700">inventory_2</span>
                                            +{{ number_format($r['leftover']) }} Pcs Spare Product
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 font-semibold rounded-lg text-[11px]">
                                            0 Leftover
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($bSummary['hasLeftovers'])
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-2.5 text-xs text-amber-900 font-semibold">
                            <span class="material-symbols-outlined text-amber-700 text-lg shrink-0">info</span>
                            <div>
                                <strong>Spare Products Notice:</strong> Upon completing conversion,
                                @foreach($bSummary['leftoverItems'] as $lIdx => $lItem)
                                    <strong>{{ number_format($lItem['leftover_qty']) }} Pcs of {{ $lItem['name'] }}</strong>{{ $lIdx < count($bSummary['leftoverItems']) - 1 ? ',' : '' }}
                                @endforeach
                                will be automatically saved as <strong>Spare Products</strong> for design <strong>{{ $selectedDesignId }}</strong>.
                            </div>
                        </div>
                    @else
                        <div class="p-2.5 bg-emerald-100/60 rounded-xl text-xs text-emerald-900 font-semibold flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-700 text-base">check_circle</span>
                            <span>All manufactured pieces will be 100% converted into storefront sets with 0 leftover spare items.</span>
                        </div>
                    @endif
                </div>
            @endif

            <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="shopping_cart_checkout">Complete Storefront Conversion</x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>


