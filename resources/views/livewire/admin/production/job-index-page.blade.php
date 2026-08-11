<div>
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-1 bg-primary/10 text-primary text-xs font-bold rounded-lg uppercase tracking-wider">Manufacturing Management</span>
                <span class="text-outline text-xs font-bold">• Active Production Queue</span>
            </div>
            <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Production Jobs Hub</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Create work orders, track overall stage completion, and manage factory labor assignments.</p>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" wire:click="openCreateModal" class="flex items-center gap-2 bg-primary text-on-primary px-6 py-3 rounded-xl font-label-md text-label-md font-bold shadow-md hover:bg-primary-container transition-all active:scale-95">
                <span class="material-symbols-outlined">add</span>
                Create New Production Job
            </button>
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
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Status</th>
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
                            <div class="w-48 mx-auto">
                                <div class="flex justify-between items-center text-xs font-extrabold mb-1">
                                    <span class="text-on-surface-variant uppercase tracking-wider text-[10px]">Overall Progress</span>
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
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.production.jobs.show', $job->id) }}" wire:navigate class="inline-flex items-center gap-1.5 bg-primary/10 text-primary hover:bg-primary hover:text-on-primary px-4 py-2 rounded-xl text-xs font-bold transition-all active:scale-95">
                                Manage Job & Stages
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
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

            <!-- Initial Status -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Initial Status *</label>
                <select wire:model="status" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-2.5 font-bold text-sm text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="in_progress">In Progress</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                </select>
                @error('status') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
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
