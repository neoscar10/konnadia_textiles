<div>
    <!-- Header & Breadcrumbs -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <nav class="flex items-center gap-2 text-on-surface-variant mb-2">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="font-label-sm text-xs text-on-surface-variant hover:text-primary transition-all">Dashboard</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <a href="{{ route('factory.tasks.index') }}" wire:navigate class="font-label-sm text-xs text-on-surface-variant hover:text-primary transition-all">Factory Floor</a>
                <span class="material-symbols-outlined text-[14px]">chevron_right</span>
                <span class="font-label-sm text-xs text-primary font-bold">Job {{ $job->job_code }}</span>
            </nav>
            <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Production Job Stage Wizard</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Executing sequential stage progression for Job <strong class="font-mono text-primary">{{ $job->job_code }}</strong> ({{ $job->manufacturingProduct?->name }}).</p>
        </div>
        <a href="{{ route('factory.tasks.index') }}" wire:navigate class="inline-flex items-center gap-2 border border-outline-variant/60 hover:bg-surface-container-high/30 text-on-surface px-5 py-2.5 rounded-xl font-bold text-xs shadow-sm transition-all">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back to Task Master
        </a>
    </div>

    <!-- Sequential Stage Stepper Bar -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 mb-8 shadow-xs">
        <h3 class="font-label-md text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-4">Stage Progress Sequential Tracker</h3>
        <div class="flex flex-wrap items-center gap-4">
            @foreach($stageExecutions as $stg)
                @php
                    $isCompleted = $stg->status === 'completed';
                    $isInProgress = $stg->status === 'in_progress';
                    $isPending = $stg->status === 'pending';
                @endphp
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-xs font-extrabold transition-all
                        {{ $isCompleted ? 'bg-secondary/10 border-secondary/40 text-secondary' : ($isInProgress ? 'bg-primary text-on-primary border-primary shadow-md ring-2 ring-primary/20' : 'bg-surface-container-low/40 border-outline-variant/40 text-on-surface-variant/50 opacity-60') }}"
                    >
                        <span class="w-6 h-6 rounded-lg flex items-center justify-center font-mono text-[11px] font-black {{ $isCompleted ? 'bg-secondary text-on-secondary' : ($isInProgress ? 'bg-on-primary text-primary' : 'bg-outline-variant/40 text-on-surface-variant') }}">
                            {{ $stg->sequence_number }}
                        </span>
                        <span>{{ $stg->task?->name }}</span>
                        @if($isCompleted)
                            <span class="material-symbols-outlined text-[16px]">check_circle</span>
                        @elseif($isPending)
                            <span class="material-symbols-outlined text-[16px]">lock</span>
                        @endif
                    </div>

                    @if(!$loop->last)
                        <span class="material-symbols-outlined text-outline-variant/60 text-[18px]">arrow_forward</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Active Stage Execution Box -->
    @if($activeStage)
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-6 shadow-xs space-y-6">
            <div class="flex justify-between items-center border-b border-outline-variant/40 pb-4">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-extrabold text-base">
                        {{ $activeStage->sequence_number }}
                    </span>
                    <div>
                        <h3 class="font-headline-sm text-lg font-extrabold text-primary">Stage {{ $activeStage->sequence_number }}: {{ $activeStage->task?->name }}</h3>
                        <p class="text-xs text-on-surface-variant mt-0.5">Target Process Quantity: <strong>{{ $activeStage->target_quantity }} Pcs</strong></p>
                    </div>
                </div>
                <span class="bg-primary/10 text-primary border border-primary/20 px-3.5 py-1 rounded-full font-mono text-xs font-black uppercase">
                    IN PROGRESS
                </span>
            </div>

            <!-- Labor Workers Section -->
            <div>
                <div class="flex justify-between items-center mb-3">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">Labor Worker Allocations</label>
                    <button type="button" wire:click="addLaborRow" class="text-xs font-bold text-primary hover:underline flex items-center gap-1 cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">add</span> Add Labor Worker
                    </button>
                </div>

                @foreach($laborRows as $lIdx => $lRow)
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-surface-container-low/30 rounded-xl p-4 mb-3 border border-outline-variant/30 items-center">
                        <div>
                            <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Worker</label>
                            <select wire:model="laborRows.{{ $lIdx }}.labor_id" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-body-md">
                                <option value="">— Select Worker —</option>
                                @foreach($labors as $l)
                                    <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->worker_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Piece Rate (₹)</label>
                            <input type="number" step="0.01" wire:model.live="laborRows.{{ $lIdx }}.rate" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-right" />
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Processed Pcs</label>
                            <input type="number" min="1" wire:model.live="laborRows.{{ $lIdx }}.processed_qty" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-right" />
                        </div>

                        <div class="flex items-center justify-between pt-4 md:pt-0">
                            <div>
                                <p class="text-[10px] text-on-surface-variant font-bold uppercase">Wage Total</p>
                                <p class="text-sm font-extrabold text-primary">₹{{ number_format(floatval($lRow['rate'] ?? 0) * floatval($lRow['processed_qty'] ?? 0), 2) }}</p>
                            </div>
                            <button type="button" wire:click="removeLaborRow({{ $lIdx }})" class="text-error hover:bg-error-container/20 p-2 rounded-lg transition-colors cursor-pointer">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Wastage & Alteration Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-surface-container-low/20 rounded-xl p-4 border border-outline-variant/30">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Quantity Produced / Passed</label>
                    <input type="number" min="0" wire:model="producedQty" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2.5 text-sm font-extrabold text-right text-primary" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Wastage Quantity</label>
                    <input type="number" step="0.01" wire:model="wastageQty" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2.5 text-sm font-bold text-right text-error" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Alteration Quantity</label>
                    <input type="number" min="0" wire:model="alterationQty" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-2.5 text-sm font-bold text-right text-amber-700" />
                </div>
            </div>

            <!-- Action Button -->
            <div class="flex justify-end pt-4">
                <button
                    type="button"
                    wire:click="completeActiveStage"
                    class="px-8 py-4 bg-primary hover:bg-primary-container text-on-primary font-black text-sm rounded-xl shadow-lg transition-all active:scale-95 cursor-pointer flex items-center gap-2"
                >
                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                    Complete Stage {{ $activeStage->sequence_number }} ({{ $activeStage->task?->name }}) & Unlock Next Stage
                </button>
            </div>
        </div>
    @else
        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-8 text-center shadow-xs">
            <span class="material-symbols-outlined text-secondary text-5xl mb-3">task_alt</span>
            <h3 class="font-headline-sm text-lg font-black text-primary">All Stages Completed!</h3>
            <p class="text-xs text-on-surface-variant mt-1">This job has completed all routing tasks and its batch is ready for Storefront Finished Goods Combination.</p>
        </div>
    @endif
</div>
