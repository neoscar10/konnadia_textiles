<div>
    <!-- Page Header & Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.production.workbench') }}" wire:navigate class="text-primary font-bold text-xs flex items-center gap-1 hover:underline">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    Back to Production Queue
                </a>
                <span class="text-outline text-xs font-bold">• Production Management</span>
            </div>
            <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Initiate Production Batch</h2>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.production.products.index') }}" wire:navigate class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs bg-surface-container-lowest text-primary border border-outline-variant/60 shadow-xs hover:bg-surface-container-high transition-all">
                <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                Manufacturing Products Master
            </a>
            <a href="{{ route('admin.production.workbench') }}" wire:navigate class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs bg-primary text-on-primary shadow-xs hover:bg-primary-container transition-all">
                <span class="material-symbols-outlined text-[18px]">view_kanban</span>
                Supervisor Workbench
            </a>
        </div>
    </div>

    <!-- Batch Form Section -->
    <form wire:submit.prevent="saveBatch" class="space-y-6 mb-8">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <div class="xl:col-span-8 space-y-6">
                <!-- Batch Information Card -->
                <section class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 p-6 shadow-xs">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-outline-variant/40">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0">
                            <span class="material-symbols-outlined text-[24px]">precision_manufacturing</span>
                        </div>
                        <div>
                            <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Batch Information</h3>
                            <p class="text-xs text-on-surface-variant font-medium mt-0.5">Specify product, planned batch quantity, priority, and date</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold text-on-surface-variant uppercase tracking-wider mb-2">Manufacturing Product *</label>
                                <select wire:model.live="manufacturing_product_id" class="w-full h-12 bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                    @foreach($allProducts as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                                    @endforeach
                                </select>
                                @error('manufacturing_product_id') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-extrabold text-on-surface-variant uppercase tracking-wider mb-2">Planned Qty (Units/Pcs) *</label>
                                <div class="relative">
                                    <input type="number" min="1" wire:model.live="planned_quantity" class="w-full h-12 bg-surface-container-low border border-outline-variant/60 rounded-xl pl-4 pr-12 text-sm font-bold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="500">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-outline font-bold">Pcs</span>
                                </div>
                                @error('planned_quantity') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-extrabold text-on-surface-variant uppercase tracking-wider mb-2">Batch Priority *</label>
                                <select wire:model.live="priority" class="w-full h-12 bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                    <option value="Normal">Normal Priority</option>
                                    <option value="Urgent">Urgent Priority</option>
                                    <option value="Low">Low Priority</option>
                                </select>
                                @error('priority') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                            <div>
                                <label class="block text-xs font-extrabold text-on-surface-variant uppercase tracking-wider mb-2">Auto-Generated Batch Code</label>
                                <input type="text" value="{{ $batch_code_preview }}" disabled class="w-full h-12 bg-surface-container-high/60 border border-outline-variant/40 rounded-xl px-4 text-sm font-mono font-bold text-primary cursor-not-allowed">
                                <span class="text-[10px] text-outline mt-1 block font-medium">Auto-generated parent batch identifier</span>
                            </div>

                            <div>
                                <label class="block text-xs font-extrabold text-on-surface-variant uppercase tracking-wider mb-2">Batch Date *</label>
                                <input type="date" wire:model="batch_date" class="w-full h-12 bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                @error('batch_date') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-extrabold text-on-surface-variant uppercase tracking-wider mb-2">Assigned Supervisor *</label>
                                <select wire:model="supervisor_id" class="w-full h-12 bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                                    @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}">{{ $supervisor->name }} ({{ $supervisor->email }})</option>
                                    @endforeach
                                </select>
                                @error('supervisor_id') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Product Routing Preview Card -->
                <section class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 p-6 shadow-xs">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-outline-variant/40">
                        <div class="w-10 h-10 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center font-bold shrink-0">
                            <span class="material-symbols-outlined text-[24px]">route</span>
                        </div>
                        <div>
                            <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Product Routing & Standard Rates Preview</h3>
                            <p class="text-xs text-on-surface-variant font-medium mt-0.5">Automated stage sequence configured for <span class="font-bold text-primary">{{ $selectedProduct?->name }}</span></p>
                        </div>
                    </div>

                    @if($selectedProduct && $selectedProduct->tasks->count() > 0)
                        <div class="space-y-3">
                            @foreach($selectedProduct->tasks as $idx => $task)
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border {{ $idx === 0 ? 'bg-primary/5 border-primary/40 shadow-xs' : 'bg-surface border-outline-variant/40' }}">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-9 h-9 rounded-full {{ $idx === 0 ? 'bg-primary text-white' : 'bg-surface-container-high text-on-surface' }} font-extrabold text-xs flex items-center justify-center shrink-0">
                                            P0{{ $idx + 1 }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h4 class="font-bold text-on-surface text-base">{{ $task->name }}</h4>
                                                @if($idx === 0)
                                                    <span class="px-2.5 py-0.5 bg-secondary-container/50 text-on-secondary-container border border-secondary/30 text-[10px] font-extrabold uppercase rounded-full whitespace-nowrap">
                                                        Auto-Initiated First Job
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-outline font-mono">Task Code: {{ $task->code }}</p>
                                        </div>
                                    </div>

                                    <div class="text-left sm:text-right shrink-0">
                                        <span class="text-xs font-bold text-primary block">Standard Labor Rate</span>
                                        <span class="text-sm font-black text-secondary">₹{{ number_format((float)($task->pivot->standard_labor_rate ?? $selectedProduct->standard_labor_rate ?? 0), 2) }} / Pcs</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 bg-surface-container-low rounded-xl text-center text-on-surface-variant border border-dashed border-outline-variant/60">
                            <span class="material-symbols-outlined text-3xl text-outline mb-1">info</span>
                            <p class="text-sm font-semibold">Select a product above to preview its production routing workflow.</p>
                        </div>
                    @endif
                </section>

                <!-- Remarks Card -->
                <section class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 p-6 shadow-xs">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0">
                            <span class="material-symbols-outlined text-[22px]">sticky_note_2</span>
                        </div>
                        <h3 class="font-headline-sm text-headline-sm text-primary font-bold">Production Remarks & Special Instructions</h3>
                    </div>
                    <textarea wire:model="remarks" rows="3" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl p-4 text-sm text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all" placeholder="Enter optional production remarks or special shop-floor instructions..."></textarea>
                </section>
            </div>

            <!-- Right Column: Summary Panel -->
            <div class="xl:col-span-4 space-y-6">
                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden sticky top-6">
                    <div class="p-5 border-b border-outline-variant/40 bg-surface-container-low">
                        <h3 class="font-headline-sm text-headline-sm text-primary font-extrabold">Initiation Summary</h3>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">Summary of batch and initial job order</p>
                    </div>

                    <div class="p-5 space-y-4 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant font-semibold">Batch Code</span>
                            <span class="font-extrabold font-mono text-primary">{{ $batch_code_preview }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant font-semibold">Planned Quantity</span>
                            <span class="font-extrabold text-on-surface">{{ number_format($planned_quantity) }} Pcs</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-on-surface-variant font-semibold">Priority</span>
                            <span class="font-extrabold text-xs px-3 py-1 rounded-full {{ $priority === 'Urgent' ? 'bg-error-container text-on-error-container' : 'bg-primary/10 text-primary' }}">
                                {{ strtoupper($priority) }}
                            </span>
                        </div>
                        <div class="border-t border-outline-variant/40 pt-3 flex justify-between items-center">
                            <span class="text-on-surface-variant font-semibold">Initial Stage Job</span>
                            <span class="font-extrabold text-secondary">Phase 01 (Cutting)</span>
                        </div>
                    </div>

                    <div class="p-5 bg-surface-container-low border-t border-outline-variant/40">
                        <button type="submit" class="w-full py-3.5 bg-primary text-on-primary rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow-md hover:bg-primary-container transition-all active:scale-95">
                            <span>Initiate Batch & Auto-Create Job</span>
                            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- RECENT PRODUCTION BATCHES & 360 LEDGERS TABLE -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-5 sm:p-6 shadow-xs">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h3 class="font-headline-sm text-headline-sm text-primary font-bold">
                    Active Production Batches & 360 Ledgers
                </h3>
                <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                    Click <span class="font-bold text-primary">360 Ledger</span> to inspect consolidated costs, wastage, labor wages, and child alteration batches.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-body-md">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant/60 text-xs text-on-surface-variant uppercase tracking-wider">
                        <th class="px-4 py-3 font-bold">Batch Code</th>
                        <th class="px-4 py-3 font-bold">Manufacturing Product SKU</th>
                        <th class="px-4 py-3 font-bold text-center">Status</th>
                        <th class="px-4 py-3 font-bold text-center">Planned Qty</th>
                        <th class="px-4 py-3 font-bold text-right">Consolidated Cost</th>
                        <th class="px-4 py-3 font-bold text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($recentBatches as $batchItem)
                        <tr class="hover:bg-surface-container/50 transition-colors">
                            <td class="px-4 py-3.5">
                                <span class="font-mono font-bold text-primary text-sm">{{ $batchItem->batch_code }}</span>
                                @if($batchItem->parentBatch)
                                    <span class="block text-[10px] text-amber-700 font-bold">Child of {{ $batchItem->parentBatch->batch_code }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <p class="font-bold text-on-surface text-sm">{{ $batchItem->manufacturingProduct?->name }}</p>
                                <span class="text-xs text-outline font-mono">{{ $batchItem->manufacturingProduct?->code }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $batchItem->status === 'Completed' ? 'bg-secondary/10 text-secondary' : 'bg-primary/10 text-primary' }}">
                                    {{ $batchItem->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center font-bold text-on-surface text-sm">
                                {{ number_format($batchItem->planned_quantity) }} Pcs
                            </td>
                            <td class="px-4 py-3.5 text-right font-black text-secondary text-sm">
                                ₹{{ number_format($batchItem->total_production_cost, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <a href="{{ route('admin.production.batches.ledger', $batchItem->id) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-primary/10 text-primary hover:bg-primary hover:text-white rounded-xl text-xs font-bold transition-all">
                                    <span class="material-symbols-outlined text-[16px]">menu_book</span>
                                    <span>360 Ledger</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-on-surface-variant text-sm">
                                No production batches initiated yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
