<div>
    <!-- Page Header & Breadcrumbs -->
    <div class="bg-surface-container-lowest border-b border-outline-variant/60 p-6 rounded-2xl mb-6 shadow-xs">
        <nav class="flex mb-2 text-xs text-on-surface-variant font-semibold space-x-2">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-primary transition-colors">Dashboard</a>
            <span>&gt;</span>
            <span class="text-primary font-bold">Production Queue & Workbench</span>
        </nav>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Supervisor Workbench</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">Manage and dispatch production batches across all manufacturing stage routings.</p>
            </div>
            <a href="{{ route('admin.production.batches.create') }}" wire:navigate class="inline-flex items-center gap-2 bg-primary text-on-primary px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-primary-container shadow-md transition-all active:scale-95">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Initiate New Production Batch
            </a>
        </div>
    </div>

    <div class="space-y-6">
        <!-- KPI Summary Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Batches Created</p>
                    <span class="text-3xl font-black text-on-surface">{{ $totalWaiting }}</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary font-bold flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">pending_actions</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">In Progress</p>
                    <span class="text-3xl font-black text-secondary">{{ $totalInProgress }}</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-secondary/10 text-secondary font-bold flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">play_circle</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Urgent Priority</p>
                    <span class="text-3xl font-black text-error">{{ $totalUrgent }}</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-error/10 text-error font-bold flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">priority_high</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/60 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Completed Batches</p>
                    <span class="text-3xl font-black text-primary">{{ $totalCompleted }}</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-primary-fixed/40 text-on-primary-fixed-variant font-bold flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">task_alt</span>
                </div>
            </div>
        </div>

        <!-- Main Layout 12-Col Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
            <!-- Left Data Table Container (8 Cols) -->
            <div class="xl:col-span-8 space-y-6 min-w-0">
                <!-- Search & Filter Bar -->
                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 p-4 flex flex-wrap items-center gap-4 shadow-xs">
                    <div class="flex-1 min-w-[220px]">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">search</span>
                            <input wire:model.live.debounce.300ms="search" class="w-full px-4 h-11 bg-surface-container-low rounded-xl border border-outline-variant/60 focus:ring-2 focus:ring-primary/20 focus:border-primary font-semibold text-sm" placeholder="Search Batch Code, Product Name..." type="text"/>
                        </div>
                    </div>
                    <div>
                        <select wire:model.live="statusFilter" class="bg-surface-container-low border border-outline-variant/60 rounded-xl font-bold text-xs h-11 px-4 focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Status (All Batches)</option>
                            <option value="Created">Created</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>

                <!-- Production Batches Data Table -->
                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 overflow-hidden shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse font-body-md">
                            <thead>
                                <tr class="bg-surface-container-low text-xs text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/60">
                                    <th class="px-5 py-4 font-bold">Batch Code</th>
                                    <th class="px-5 py-4 font-bold">Product & Active Stage</th>
                                    <th class="px-5 py-4 font-bold text-right">Planned Qty</th>
                                    <th class="px-5 py-4 font-bold">Status</th>
                                    <th class="px-5 py-4 font-bold">Priority</th>
                                    <th class="px-5 py-4 font-bold text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/40">
                                @forelse($batches as $batch)
                                    @php
                                        $isRowSelected = ($selectedBatchId == $batch->id);
                                        $activeJob = $batch->jobs->where('status', '!=', 'completed')->first() ?? $batch->jobs->last();
                                        $totalRoutingSteps = $batch->manufacturingProduct?->tasks->count() ?? 1;
                                        $currentStep = $activeJob ? $activeJob->sequence_number : 1;
                                    @endphp
                                    <tr wire:click="selectBatch({{ $batch->id }})" class="cursor-pointer transition-colors {{ $isRowSelected ? 'bg-primary/5 font-bold' : 'hover:bg-surface-container-low/50' }}">
                                        <td class="px-5 py-4">
                                            <p class="font-extrabold font-mono text-primary text-sm">{{ $batch->batch_code }}</p>
                                            <span class="text-xs text-outline">{{ $batch->batch_date ? $batch->batch_date->format('d M Y') : '' }}</span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <p class="font-bold text-on-surface text-sm">{{ $batch->manufacturingProduct?->name ?? 'Unassigned' }}</p>
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <span class="px-2 py-0.5 rounded bg-primary/10 text-primary font-mono text-[10px] font-bold">
                                                    Step {{ $currentStep }} of {{ $totalRoutingSteps }}
                                                </span>
                                                @if($activeJob)
                                                    <span class="text-xs font-semibold text-on-surface-variant">
                                                        • {{ $activeJob->task?->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-right font-extrabold text-on-surface">
                                            {{ number_format($batch->planned_quantity) }} <span class="text-xs font-normal text-outline">Pcs</span>
                                        </td>
                                        <td class="px-5 py-4">
                                            @if($batch->isReadyForConversion())
                                                <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                                    <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span> Ready for Conversion
                                                </span>
                                            @elseif($batch->is_converted)
                                                <span class="bg-primary-container/20 text-primary px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                                    <span class="w-2 h-2 rounded-full bg-primary"></span> Converted
                                                </span>
                                            @elseif($batch->status === 'Completed')
                                                <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                                    <span class="w-2 h-2 rounded-full bg-secondary"></span> Completed
                                                </span>
                                            @elseif($batch->status === 'In Progress')
                                                <span class="bg-primary-fixed text-on-primary-fixed-variant px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                                    <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span> In Progress
                                                </span>
                                            @else
                                                <span class="bg-surface-container-high text-on-surface-variant px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                                    <span class="w-2 h-2 rounded-full bg-outline"></span> Created
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $batch->priority === 'Urgent' ? 'bg-error-container text-on-error-container' : 'bg-primary/10 text-primary' }}">
                                                {{ $batch->priority }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            @if($batch->isReadyForConversion())
                                                <a href="{{ route('factory.batches.convert', $batch->id) }}" wire:navigate class="inline-flex items-center gap-1.5 bg-secondary text-on-secondary px-3.5 py-1.5 rounded-xl text-xs font-bold hover:bg-secondary-container transition-all active:scale-95 shadow-xs">
                                                    <span class="material-symbols-outlined text-[16px]">autofps_select</span>
                                                    <span>Convert</span>
                                                </a>
                                            @elseif($batch->is_converted)
                                                <a href="{{ route('admin.production.batches.ledger', $batch->id) }}" wire:navigate class="inline-flex items-center gap-1 bg-surface-container-high text-primary px-3.5 py-1.5 rounded-xl text-xs font-bold hover:bg-surface-container-highest transition-all active:scale-95 shadow-xs">
                                                    <span>Ledger</span>
                                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                                </a>
                                            @elseif($activeJob)
                                                <a href="{{ route('admin.production.jobs.show', $activeJob->id) }}" wire:navigate class="inline-flex items-center gap-1 bg-primary text-on-primary px-3.5 py-1.5 rounded-xl text-xs font-bold hover:bg-primary-container transition-all active:scale-95 shadow-xs">
                                                    <span>{{ $activeJob->status === 'completed' ? 'View Job' : 'Execute Job' }}</span>
                                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                                </a>
                                            @else
                                                <span class="text-xs text-outline italic">No Job Created</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                                            <span class="material-symbols-outlined text-4xl text-outline mb-2">inventory_2</span>
                                            <p class="font-body-lg text-body-lg font-bold">No production batches found.</p>
                                            <a href="{{ route('admin.production.batches.create') }}" wire:navigate class="mt-3 text-primary font-bold text-sm hover:underline inline-block">
                                                + Initiate your first production batch
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($batches->hasPages())
                        <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/60">
                            {{ $batches->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Preview Sidebar (4 Cols) -->
            <div class="xl:col-span-4 min-w-0 space-y-6">
                <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden sticky top-6">
                    <div class="p-5 border-b border-outline-variant/40 bg-surface-container-low flex justify-between items-center">
                        <div>
                            <h3 class="font-headline-sm text-headline-sm font-extrabold text-primary">{{ $selectedBatch ? $selectedBatch->batch_code : 'No Selection' }}</h3>
                            <p class="text-xs text-on-surface-variant font-medium">Batch Detail Preview</p>
                        </div>
                    </div>

                    @if($selectedBatch)
                        @php
                            $productRoutingTasks = $selectedBatch->manufacturingProduct?->tasks ?? collect();
                            $totalSteps = $productRoutingTasks->count();
                        @endphp
                        <div class="p-5 space-y-4 text-sm">
                            @if($selectedBatch->isReadyForConversion())
                                <div class="p-4 bg-secondary-container/20 border border-secondary/30 rounded-xl text-center shadow-xs">
                                    <span class="px-2 py-0.5 bg-secondary text-on-secondary text-[10px] font-black uppercase tracking-wider rounded">Batch Ready</span>
                                    <h4 class="font-bold text-xs text-secondary mt-1">Ready for Finished Goods Conversion</h4>
                                    <a href="{{ route('factory.batches.convert', $selectedBatch->id) }}" wire:navigate class="mt-3 w-full inline-flex items-center justify-center gap-1.5 bg-secondary hover:bg-secondary-container text-on-secondary font-bold py-2 rounded-lg text-xs transition-all shadow-xs">
                                        <span class="material-symbols-outlined text-sm">autofps_select</span>
                                        Convert to Finished Goods
                                    </a>
                                </div>
                            @elseif($selectedBatch->is_converted)
                                <div class="p-4 bg-primary-container/10 border border-primary/20 rounded-xl text-center shadow-xs">
                                    <span class="px-2 py-0.5 bg-primary text-on-primary text-[10px] font-black uppercase tracking-wider rounded">Stocked In</span>
                                    <h4 class="font-bold text-xs text-primary mt-1">Converted to Finished Goods</h4>
                                    <a href="{{ route('admin.production.batches.ledger', $selectedBatch->id) }}" wire:navigate class="mt-3 w-full inline-flex items-center justify-center gap-1.5 bg-primary hover:bg-primary-container text-on-primary font-bold py-2 rounded-lg text-xs transition-all shadow-xs">
                                        <span class="material-symbols-outlined text-sm">description</span>
                                        View 360 Cost Ledger
                                    </a>
                                </div>
                            @endif

                            <div class="flex justify-between items-center">
                                <span class="text-on-surface-variant font-semibold">Product</span>
                                <span class="font-bold text-on-surface text-right">{{ $selectedBatch->manufacturingProduct?->name }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-on-surface-variant font-semibold">Planned Quantity</span>
                                <span class="font-extrabold text-primary">{{ number_format($selectedBatch->planned_quantity) }} Pcs</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-on-surface-variant font-semibold">Supervisor</span>
                                <span class="font-bold text-on-surface">{{ $selectedBatch->supervisor?->name ?? 'System' }}</span>
                            </div>

                            <!-- Sequential Workflow Routing Steps -->
                            <div class="border-t border-outline-variant/40 pt-3">
                                <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider block mb-2">Sequential Routing Progress</span>
                                <div class="space-y-2">
                                    @forelse($selectedBatch->jobs as $j)
                                        @php
                                            $isDone = ($j->status === 'completed');
                                            $isCurrent = ($j->status !== 'completed');
                                            $seqNo = $j->sequence_number;
                                        @endphp
                                        <div class="p-3 rounded-xl border flex justify-between items-center {{ $isDone ? 'bg-secondary-container/20 border-secondary/30' : ($isCurrent ? 'bg-primary/5 border-primary/40' : 'bg-surface border-outline-variant/40') }}">
                                            <div class="flex items-center gap-2.5">
                                                <span class="w-6 h-6 rounded-full text-[11px] font-mono font-bold flex items-center justify-center shrink-0 {{ $isDone ? 'bg-secondary text-on-secondary' : 'bg-primary text-on-primary' }}">
                                                    {{ $seqNo }}
                                                </span>
                                                <div>
                                                    <p class="font-bold text-xs text-primary">{{ $j->job_code }}</p>
                                                    <p class="text-[11px] text-on-surface-variant font-semibold">
                                                        Stage: {{ $j->task?->name }} ({{ number_format($j->target_quantity) }} Pcs)
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @if($isDone)
                                                    <span class="px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container text-[10px] font-bold">Completed</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full bg-primary/10 text-primary text-[10px] font-bold animate-pulse">Active</span>
                                                @endif
                                                <a href="{{ route('admin.production.jobs.show', $j->id) }}" wire:navigate class="p-1.5 bg-primary/10 text-primary rounded-lg hover:bg-primary hover:text-white transition-all">
                                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                                </a>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-xs text-outline italic">No jobs linked to this batch.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="p-8 text-center text-on-surface-variant text-sm">
                            <span class="material-symbols-outlined text-4xl text-outline mb-2 block">tab_unselected</span>
                            <p class="font-semibold text-on-surface mb-1">No Batch Selected</p>
                            <p class="text-xs text-outline">Click a batch row on the table to preview details.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
