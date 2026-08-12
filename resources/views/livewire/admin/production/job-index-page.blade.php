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

    <!-- Data Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 overflow-hidden shadow-xs">
        <table class="w-full text-left border-collapse font-body-md">
            <thead>
                <tr class="bg-surface-container-low border-b border-outline-variant/60">
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Job Code</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Batch ID</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Product</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-center">Output Progress</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Stage Status</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Storefront Conversion</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/40">
                @forelse($jobs as $job)
                    <tr class="hover:bg-surface-container/50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-primary text-base">{{ $job->job_code }}</p>
                            <span class="text-xs text-outline">{{ $job->created_at ? $job->created_at->format('d M Y') : '' }}</span>
                        </td>
                        <td class="px-6 py-4 font-mono font-bold text-sm text-on-surface">
                            {{ $job->production_batch_id ?? '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-on-surface text-sm">{{ $job->manufacturingProduct?->name ?? 'Unassigned' }}</p>
                            <span class="text-xs text-outline">{{ $job->manufacturingProduct?->code }}</span>
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
                                <button type="button" wire:click="openConversionModal({{ $job->id }})" class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded-xl text-xs font-bold transition-all active:scale-95 border border-emerald-200">
                                    <span class="material-symbols-outlined text-[14px]">shopping_cart_checkout</span>
                                    Convert
                                </button>
                            @endif
                            <a href="{{ route('admin.production.jobs.show', $job->id) }}" wire:navigate class="inline-flex items-center gap-1 bg-primary/10 text-primary hover:bg-primary hover:text-on-primary px-3 py-1.5 rounded-xl text-xs font-bold transition-all active:scale-95">
                                Manage
                                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-4xl text-outline mb-2">assignment_late</span>
                            <p class="font-body-lg text-body-lg">No production jobs found.</p>
                            <button type="button" wire:click="openCreateModal" class="mt-3 text-primary font-bold text-sm hover:underline">
                                + Create your first production job
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($jobs->hasPages())
            <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/60">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>

    <!-- Storefront Finished Goods Conversion Modal -->
    <x-admin.modal id="storefront-conversion-modal" title="Convert Completed Goods to Storefront Product" maxWidth="2xl">
        <form wire:submit.prevent="processConversion" class="space-y-5">
            <p class="text-on-surface-variant text-sm">Convert completed factory products into sellable Storefront Products. You can combine multiple completed jobs into a single storefront bundle set (e.g. 1 Bed Sheet + 2 Pillow Cases = 1 Storefront Set).</p>

            @if($errors->has('conversionComponents'))
                <div class="bg-error-container/40 border border-error/30 text-error p-3.5 rounded-xl text-xs font-bold">
                    {{ $errors->first('conversionComponents') }}
                </div>
            @endif

            <!-- 1. Select Target Storefront Product SKU -->
            <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/60 space-y-4">
                <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">storefront</span>
                    1. Target Storefront Product / Variant SKU *
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Select Storefront Product *</label>
                        <select wire:model.live="target_product_id" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2.5 text-sm font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">-- Select Storefront Product SKU --</option>
                            @foreach($storefrontProducts as $sp)
                                <option value="{{ $sp->id }}">{{ $sp->title ?? $sp->name }} (SKU: {{ $sp->sku ?? 'SKU-'.$sp->id }}) — Stock: {{ $sp->stock_quantity }}</option>
                            @endforeach
                        </select>
                        @error('target_product_id') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Qnty of Sets to Convert *</label>
                        <input type="number" min="1" wire:model.live="assembled_sets_quantity" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2.5 font-bold text-sm text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('assembled_sets_quantity') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>
                </div>

                @if($target_product_id)
                    @php
                        $selectedProd = $storefrontProducts->firstWhere('id', $target_product_id);
                    @endphp
                    @if($selectedProd && $selectedProd->combinations->count() > 0)
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Select Product Variant (Optional)</label>
                            <select wire:model="target_combination_id" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2.5 text-sm font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">-- Apply directly to Base Product ({{ $selectedProd->title ?? $selectedProd->name }}) --</option>
                                @foreach($selectedProd->combinations as $comb)
                                    <option value="{{ $comb->id }}">{{ $comb->combination_string ?? 'Variant #'.$comb->id }} — Stock: {{ $comb->stock_quantity }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                @endif
            </div>

            <!-- 2. Source Factory Components -->
            <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/60 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">fact_check</span>
                        2. Factory Product Components per Set *
                    </h4>
                    <button type="button" wire:click="addConversionComponentRow" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">add_circle</span> Add Another Item
                    </button>
                </div>

                <div class="space-y-3">
                    @foreach($conversionComponents as $index => $comp)
                        @php
                            $selectedJob = !empty($comp['production_job_id']) ? $completedJobsForPicker->firstWhere('id', intval($comp['production_job_id'])) : null;
                            $qtyPerSet = intval($comp['quantity_per_set'] ?? 1);
                            $totalNeeded = max(1, intval($this->assembled_sets_quantity)) * $qtyPerSet;
                            $avail = $selectedJob ? $selectedJob->remaining_unconverted_quantity : 0;
                            $isShortage = $selectedJob && ($totalNeeded > $avail);
                        @endphp
                        <div class="p-3.5 bg-surface border border-outline-variant/60 rounded-xl space-y-2">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                                <div class="md:col-span-7">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Source Completed Job #{{ $index + 1 }} *</label>
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

                                <div class="md:col-span-4">
                                    <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Pieces per Set *</label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" min="1" wire:model.live="conversionComponents.{{ $index }}.quantity_per_set" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface">
                                        <span class="text-xs font-bold text-outline">Pcs</span>
                                    </div>
                                    @error("conversionComponents.{$index}.quantity_per_set") <span class="text-error text-[11px] block mt-1 font-semibold">{{ $message }}</span> @enderror
                                </div>

                                <div class="md:col-span-1 text-right">
                                    @if(count($conversionComponents) > 1)
                                        <button type="button" wire:click="removeConversionComponentRow({{ $index }})" class="text-error hover:bg-error-container/20 p-1.5 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if($selectedJob)
                                <div class="flex items-center justify-between text-xs pt-1 border-t border-outline-variant/30">
                                    <span class="text-on-surface-variant font-semibold">
                                        Total Required: <strong class="text-primary">{{ $totalNeeded }} Pcs</strong> ({{ $qtyPerSet }} Pcs/set &times; {{ $assembled_sets_quantity }} sets)
                                    </span>
                                    <span class="font-bold {{ $isShortage ? 'text-error' : 'text-emerald-700' }}">
                                        Available Unconverted: {{ $avail }} Pcs {{ $isShortage ? '(Shortage: ' . ($totalNeeded - $avail) . ' Pcs)' : '' }}
                                    </span>
                                </div>
                            @endif
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

