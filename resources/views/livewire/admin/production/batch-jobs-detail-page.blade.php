<div>
    <!-- Page Header & Breadcrumbs -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.production.jobs.index') }}" wire:navigate class="text-primary font-bold text-xs flex items-center gap-1 hover:underline">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Back to Production Batches Hub
                </a>
                <span class="text-outline text-xs font-bold">• Batch Jobs Detail</span>
            </div>
            <div class="flex items-center gap-3">
                <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold font-mono tracking-tight">{{ $batchCode }}</h2>
                <span class="px-3 py-1 font-bold text-xs rounded-xl uppercase tracking-wider bg-primary/10 text-primary">
                    {{ $jobs->count() }} Production Job(s)
                </span>
            </div>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                Manufacturing Product: <strong class="text-on-surface">{{ $product?->name ?? 'Custom Batch' }}</strong>
                @if($product?->code)
                    <span class="text-outline font-mono text-xs">({{ $product->code }})</span>
                @endif
                • Supervisor: <strong class="text-on-surface">{{ $supervisor?->name ?? 'Unassigned' }}</strong>
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            @if($unconvertedSum > 0)
                <button type="button" wire:click="openBatchConversionModal" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-xl font-label-md text-label-md font-bold shadow-md transition-all active:scale-95 whitespace-nowrap">
                    <span class="material-symbols-outlined text-[20px]">shopping_cart_checkout</span>
                    Convert Batch Goods ({{ number_format($unconvertedSum) }} Pcs Available)
                </button>
            @endif

            @if($batchDbId)
                <a href="{{ route('admin.production.batches.ledger', $batchDbId) }}" wire:navigate class="flex items-center gap-2 bg-surface-container-lowest border border-outline-variant/60 text-on-surface px-4 py-3 rounded-xl font-label-md text-label-md font-bold shadow-xs hover:bg-surface-container transition-all whitespace-nowrap">
                    <span class="material-symbols-outlined text-[20px]">menu_book</span>
                    View 360 Cost Ledger
                </a>
            @endif
        </div>
    </div>

    <!-- Batch Summary Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">inventory_2</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Total Output Produced</p>
                <p class="text-2xl font-black text-on-surface">{{ number_format($totalProducedSum) }} <span class="text-xs font-bold text-outline">Pcs</span></p>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">storefront</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Converted Storefront Stock</p>
                <p class="text-2xl font-black text-emerald-600">{{ number_format($convertedSum) }} <span class="text-xs font-bold text-outline">Pcs</span></p>
            </div>
        </div>

        <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold">
                <span class="material-symbols-outlined text-2xl">pending_actions</span>
            </div>
            <div>
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">Available Unconverted Stock</p>
                <p class="text-2xl font-black text-amber-600">{{ number_format($unconvertedSum) }} <span class="text-xs font-bold text-outline">Pcs</span></p>
            </div>
        </div>
    </div>

    <!-- Production Jobs Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 overflow-hidden shadow-xs mb-8">
        <div class="p-5 bg-surface-container-low border-b border-outline-variant/60 flex justify-between items-center">
            <h3 class="font-headline-sm text-headline-sm text-primary font-bold flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">assignment</span>
                Jobs in Batch {{ $batchCode }}
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-body-md">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant/60 text-xs text-on-surface-variant uppercase tracking-wider">
                        <th class="px-6 py-4 font-bold">Job Code</th>
                        <th class="px-6 py-4 font-bold">Target Product</th>
                        <th class="px-6 py-4 font-bold text-center">Output Progress</th>
                        <th class="px-6 py-4 font-bold">Stage Status</th>
                        <th class="px-6 py-4 font-bold">Storefront Conversion</th>
                        <th class="px-6 py-4 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($jobs as $job)
                        <tr class="hover:bg-surface-container/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-bold text-primary text-base font-mono">{{ $job->job_code }}</p>
                                <span class="text-xs text-outline">{{ $job->created_at ? $job->created_at->format('d M Y') : '' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-on-surface text-sm">{{ $job->manufacturingProduct?->name ?? 'Unassigned' }}</p>
                                <span class="text-xs text-outline font-mono">{{ $job->manufacturingProduct?->code }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="w-40 mx-auto">
                                    <div class="flex justify-between items-center text-xs font-extrabold mb-1">
                                        <span class="text-on-surface-variant uppercase tracking-wider text-[10px]">Progress</span>
                                        <span class="text-secondary font-black">{{ $job->progress_percentage }}%</span>
                                    </div>
                                    <div class="w-full bg-surface-container-high h-2.5 rounded-full overflow-hidden border border-outline-variant/30">
                                        <div class="bg-primary h-full transition-all duration-500 rounded-full" style="width: {{ $job->progress_percentage }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($job->status === 'completed')
                                    <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full font-label-sm text-label-sm font-bold inline-flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-secondary"></span> COMPLETED
                                    </span>
                                @elseif($job->status === 'in_progress')
                                    <span class="bg-primary-fixed text-on-primary-fixed-variant px-3 py-1 rounded-full font-label-sm text-label-sm font-bold inline-flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span> IN PROGRESS
                                    </span>
                                @else
                                    <span class="bg-surface-container-high text-on-surface-variant px-3 py-1 rounded-full font-label-sm text-label-sm font-bold inline-flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-outline"></span> {{ strtoupper($job->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $status = $job->conversion_status;
                                    $converted = $job->converted_quantity;
                                    $total = $job->total_produced_quantity;
                                    $remaining = $job->remaining_unconverted_quantity;
                                @endphp
                                @if($status === 'fully_converted')
                                    <span class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                        Fully Converted ({{ $converted }}/{{ $total }})
                                    </span>
                                @elseif($status === 'partially_converted')
                                    <div class="space-y-1">
                                        <span class="bg-amber-100 text-amber-800 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">pie_chart</span>
                                            Partial ({{ $converted }}/{{ $total }})
                                        </span>
                                        <p class="text-[11px] font-bold text-amber-700">{{ $remaining }} Pcs remaining</p>
                                    </div>
                                @else
                                    @if($total > 0)
                                        <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">storefront</span>
                                            Unconverted ({{ $remaining }} Available)
                                        </span>
                                    @else
                                        <span class="text-xs text-outline font-semibold">Not Completed</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                @if($job->remaining_unconverted_quantity > 0)
                                    <button type="button" wire:click="openJobConversionModal({{ $job->id }})" class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded-xl text-xs font-bold transition-all active:scale-95 border border-emerald-200">
                                        <span class="material-symbols-outlined text-[14px]">shopping_cart_checkout</span>
                                        Convert
                                    </button>
                                @endif
                                @if($job->production_batch_db_id || $batchDbId)
                                    <a href="{{ route('admin.production.batches.ledger', ['id' => $job->production_batch_db_id ?? $batchDbId, 'selectedJobId' => $job->id]) }}" wire:navigate class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 hover:bg-amber-600 hover:text-white px-3 py-1.5 rounded-xl text-xs font-bold transition-all active:scale-95 border border-amber-200">
                                        <span class="material-symbols-outlined text-[14px]">receipt_long</span>
                                        Ledger
                                    </a>
                                @endif
                                <a href="{{ route('admin.production.jobs.show', $job->id) }}" wire:navigate class="inline-flex items-center gap-1 bg-primary/10 text-primary hover:bg-primary hover:text-on-primary px-3 py-1.5 rounded-xl text-xs font-bold transition-all active:scale-95">
                                    Terminal
                                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl text-outline mb-2">assignment_late</span>
                                <p class="font-body-lg text-body-lg">No jobs found in batch {{ $batchCode }}.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Storefront Finished Goods Conversion Modal -->
    <x-admin.modal id="storefront-conversion-modal" title="Convert Batch Products to Storefront Inventory" maxWidth="4xl">
        <form wire:submit.prevent="processConversion" class="space-y-5">
            <p class="text-on-surface-variant text-sm">Select the target Storefront Product SKU, enter the total number of storefront items/sets you wish to produce, define the finished job components (pieces required per set), and review remaining piece balances before converting.</p>

            @if($errors->has('conversionComponents'))
                <div class="bg-error-container/40 border border-error/30 text-error p-3.5 rounded-xl text-xs font-bold">
                    {{ $errors->first('conversionComponents') }}
                </div>
            @endif

            <!-- 1. Select Target Storefront Product SKU & Desired Quantity -->
            <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/60 space-y-4">
                <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">storefront</span>
                    1. Target Storefront Product & Desired Production Quantity *
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <div class="sm:col-span-8">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Target Storefront Product *</label>
                        <select wire:model.live="target_product_id" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2.5 text-sm font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">-- Select Storefront Product SKU --</option>
                            @foreach($storefrontProducts as $sp)
                                <option value="{{ $sp->id }}">{{ $sp->title ?? $sp->name }} (SKU: {{ $sp->sku ?? 'SKU-'.$sp->id }}) — Current Stock: {{ $sp->stock_quantity }}</option>
                            @endforeach
                        </select>
                        @error('target_product_id') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-4">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Target Quantity to Create *</label>
                        <div class="relative">
                            <input type="number" min="1" wire:model.live="target_sets_desired" class="w-full bg-surface border border-outline-variant/60 rounded-xl pl-4 pr-16 py-2.5 text-sm font-black text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="1">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-outline font-bold">Items/Sets</span>
                        </div>
                        @error('target_sets_desired') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- 2. Finished Job Product Components (Set Ratio & Inputs) -->
            <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/60 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">fact_check</span>
                        2. Define Finished Job Components (Pieces per Storefront Product) *
                    </h4>
                    <button type="button" wire:click="addConversionComponentRow" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">add_circle</span> Add Component
                    </button>
                </div>

                <div class="space-y-3">
                    @foreach($conversionComponents as $index => $comp)
                        @php
                            $selectedJob = !empty($comp['production_job_id']) ? $completedJobsForPicker->firstWhere('id', intval($comp['production_job_id'])) : null;
                            $maxAvail = $selectedJob ? $selectedJob->remaining_unconverted_quantity : 0;
                            $ratioVal = max(1, intval($comp['quantity_per_set'] ?? 1));
                            $desiredVal = max(1, intval($target_sets_desired));
                            $neededPcs = $ratioVal * $desiredVal;
                            $isExceed = $selectedJob && ($neededPcs > $maxAvail);
                        @endphp
                        <div class="p-3.5 bg-surface border border-outline-variant/60 rounded-xl space-y-2">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                <div class="md:col-span-6">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1 whitespace-nowrap">Finished Factory Job Component #{{ $index + 1 }} *</label>
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
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1 whitespace-nowrap">per store prod *</label>
                                    <div class="flex items-center gap-1.5">
                                        <input type="number" min="1" wire:model.live="conversionComponents.{{ $index }}.quantity_per_set" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                        <span class="text-[11px] font-bold text-outline whitespace-nowrap">Pcs/Item</span>
                                    </div>
                                    @error("conversionComponents.{$index}.quantity_per_set") <span class="text-error text-[11px] block mt-1 font-semibold">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1 whitespace-nowrap">Available Stock</label>
                                    <div class="flex items-center justify-between bg-surface-container-low border border-outline-variant/40 rounded-xl px-3 py-2 text-xs font-extrabold text-on-surface">
                                        <span>{{ number_format($maxAvail) }} Pcs</span>
                                        @if(count($conversionComponents) > 1)
                                            <button type="button" wire:click="removeConversionComponentRow({{ $index }})" class="text-error hover:bg-error-container/20 p-1 rounded transition-colors shrink-0">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($selectedJob)
                                <div class="flex flex-wrap items-center justify-between text-xs pt-1.5 border-t border-outline-variant/30">
                                    <span class="text-on-surface-variant font-semibold">
                                        Selected: <strong>{{ $selectedJob->manufacturingProduct?->name }}</strong> (Job {{ $selectedJob->job_code }})
                                    </span>
                                    <span class="font-bold {{ $isExceed ? 'text-error' : 'text-emerald-700' }}">
                                        Required for {{ $desiredVal }} Item(s): {{ $neededPcs }} Pcs (Available: {{ $maxAvail }} Pcs)
                                    </span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Detailed Automatic Balance & Leftover Summary Callout -->
                @php
                    $summary = $this->conversionSummary;
                    $desiredSets = $summary['desired_sets'];
                    $targetProduct = $target_product_id ? $storefrontProducts->firstWhere('id', $target_product_id) : null;
                @endphp
                @if($target_product_id && !empty($summary['rows']))
                    <div class="p-4 {{ $summary['can_fulfill'] ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-950' : 'bg-amber-500/10 border-amber-500/30 text-amber-950' }} border rounded-2xl space-y-3 mt-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 {{ $summary['can_fulfill'] ? 'text-emerald-800' : 'text-amber-800' }}">
                                <span class="material-symbols-outlined text-base">calculate</span>
                                Conversion & Remaining Stock Summary
                            </span>
                            <span class="text-base font-black px-3 py-1 rounded-xl {{ $summary['can_fulfill'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' }}">
                                Target: {{ number_format($desiredSets) }} Item(s)
                            </span>
                        </div>

                        <p class="text-xs font-semibold leading-relaxed">
                            @if($summary['can_fulfill'])
                                Converting will produce <strong class="text-emerald-700 font-extrabold text-sm">{{ number_format($desiredSets) }} units</strong> of <strong>{{ $targetProduct?->title ?? $targetProduct?->name }}</strong>. Storefront stock will increase from <strong>{{ $targetProduct?->stock_quantity }}</strong> to <strong>{{ ($targetProduct?->stock_quantity ?? 0) + $desiredSets }}</strong>.
                            @else
                                <span class="text-error font-extrabold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">warning</span> Insufficient Stock!
                                </span>
                                You requested {{ number_format($desiredSets) }} item(s), but based on available finished job pieces, you can only produce up to <strong>{{ number_format($summary['max_sets']) }} item(s)</strong>.
                            @endif
                        </p>

                        <!-- Itemized Breakdown of Consumed and Leftover Pieces -->
                        <div class="pt-2 border-t {{ $summary['can_fulfill'] ? 'border-emerald-200/60' : 'border-amber-200/60' }} space-y-2">
                            <h5 class="text-[11px] font-extrabold uppercase tracking-wider">Component Stock Deduction Breakdown:</h5>
                            @foreach($summary['rows'] as $r)
                                @if($r['job'])
                                    <div class="p-2.5 bg-surface rounded-xl border border-outline-variant/30 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-on-surface">
                                        <div>
                                            <span class="font-bold text-primary">{{ $r['job']->manufacturingProduct?->name }}</span>
                                            <span class="text-[11px] text-outline font-mono">(Job {{ $r['job']->job_code }})</span>
                                            <p class="text-[11px] text-on-surface-variant">
                                                Ratio: {{ $r['ratio'] }} Pcs/Item • Processing {{ number_format($r['consumedPcs']) }} Pcs from {{ number_format($r['inputPcs']) }} available
                                            </p>
                                        </div>

                                        <div class="text-left sm:text-right shrink-0">
                                            @if($r['leftoverPcs'] > 0)
                                                <span class="px-2 py-0.5 rounded text-[11px] font-extrabold bg-amber-100 text-amber-900 border border-amber-300">
                                                    Leftover: {{ number_format($r['leftoverPcs']) }} Pcs remaining
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                    0 Pcs leftover (100% Consumed)
                                                </span>
                                            @endif
                                        </div>
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
                        <div class="grid grid-cols-12 gap-3 items-center">
                            <div class="col-span-7">
                                <select wire:model.live="conversionPackaging.{{ $index }}.raw_material_id" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                    <option value="">-- Select Packaging Material --</option>
                                    @foreach($packagingRawMaterials as $mat)
                                        <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->code }} · {{ $mat->unit }})</option>
                                    @endforeach
                                </select>
                                @error("conversionPackaging.{$index}.raw_material_id") <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-span-4">
                                <input type="number" step="0.0001" min="0.0001" placeholder="Quantity Used" wire:model.blur="conversionPackaging.{{ $index }}.quantity_used" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                @error("conversionPackaging.{$index}.quantity_used") <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-span-1 flex items-center justify-center">
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
                <x-admin.button type="submit" variant="primary" icon="shopping_cart_checkout">
                    Convert Storefront Stock
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
