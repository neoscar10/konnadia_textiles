<div x-data="{
    initSortable() {
        if (typeof Sortable === 'undefined') return;
        Sortable.create(this.$refs.sortableTable, {
            handle: '.drag-handle',
            animation: 200,
            ghostClass: 'bg-primary/10',
            onEnd: () => {
                let rows = this.$refs.sortableTable.querySelectorAll('tr[data-id]');
                let orderedIds = Array.from(rows).map(row => row.getAttribute('data-id'));
                $wire.reorderTasks(orderedIds);
            }
        });
    }
}" x-init="initSortable()">
    <!-- Page Header & Actions -->
    <div class="bg-surface-container-lowest border-b border-outline-variant/60 p-6 rounded-2xl mb-6 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Task Master Configuration</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Manage reusable manufacturing step sequences, material permissions, and labor requirements.</p>
        </div>
        <button
            type="button"
            wire:click="openCreateModal"
            class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary px-5 py-3 rounded-xl font-bold text-xs shadow-md transition-all active:scale-95 cursor-pointer"
        >
            <span class="material-symbols-outlined text-[18px]">add_circle</span>
            Configure New Task
        </button>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 p-4 rounded-2xl mb-6 shadow-xs flex items-center justify-between gap-4">
        <div class="relative w-full max-w-md">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-outline material-symbols-outlined text-[20px]">search</span>
            <input
                type="text"
                wire:model.live.debounce.250ms="search"
                placeholder="Search by task name or code..."
                class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
            />
        </div>
        <p class="text-xs text-on-surface-variant/75 font-medium flex items-center gap-1.5 hidden sm:flex select-none">
            <span class="material-symbols-outlined text-[16px] text-primary">drag_indicator</span>
            Drag handles to reorder sequence
        </p>
    </div>

    <!-- Data Table Card -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left select-none">
                <thead>
                    <tr class="bg-surface-container-low/30 border-b border-outline-variant/60 text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                        <th class="w-10 px-3 py-4 text-center"></th>
                        <th class="px-6 py-4">Task Code</th>
                        <th class="px-6 py-4">Sequence</th>
                        <th class="px-6 py-4">Task Name</th>
                        <th class="px-6 py-4">Consumes Stock</th>
                        <th class="px-6 py-4">Labor Dependent</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody x-ref="sortableTable" class="divide-y divide-outline-variant/40">
                    @forelse($tasks as $task)
                        <tr data-id="{{ $task->id }}" class="hover:bg-surface-container-low/30 transition-colors group">
                            <td class="w-10 px-3 py-4 text-center whitespace-nowrap">
                                <span class="drag-handle material-symbols-outlined text-[20px] text-outline/50 hover:text-primary cursor-grab active:cursor-grabbing transition-colors inline-block" title="Drag to reorder sequence">
                                    drag_indicator
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono font-black text-primary text-xs bg-primary/10 px-2.5 py-1 rounded-lg">
                                    {{ $task->code }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($task->sequence_number)
                                    <span class="font-mono font-extrabold text-xs text-secondary bg-secondary/10 px-2.5 py-1 rounded-lg">
                                        #{{ $task->sequence_number }}
                                    </span>
                                @else
                                    <span class="text-outline font-medium text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-sm text-on-surface">{{ $task->name }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($task->consumes_raw_material)
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @forelse($task->rawMaterialCategories as $cat)
                                            <span class="inline-flex items-center text-[10px] font-extrabold bg-secondary-container text-on-secondary-container px-2 py-0.5 rounded-full border border-secondary/20 font-mono">
                                                {{ $cat->code }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-on-surface-variant italic font-medium">All categories</span>
                                        @endforelse
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs text-on-surface-variant/60 font-semibold bg-surface-container-high/40 border border-outline-variant/30 px-2 py-1 rounded-lg">
                                        <span class="material-symbols-outlined text-[14px]">block</span>
                                        No
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($task->is_labor_required)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-tertiary-container/60 text-on-tertiary-container font-extrabold text-[10px] border border-tertiary/20 uppercase">
                                        <span class="material-symbols-outlined text-[14px]">groups</span>
                                        Yes
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container-high text-on-surface-variant/60 font-extrabold text-[10px] border border-outline-variant/30 uppercase">
                                        <span class="material-symbols-outlined text-[14px]">person_off</span>
                                        No
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    type="button"
                                    wire:click="toggleStatus({{ $task->id }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold border transition-all cursor-pointer {{ $task->status ? 'bg-secondary-container/20 text-secondary border-secondary/20 hover:bg-secondary-container/30' : 'bg-outline-variant/20 text-on-surface-variant border-outline-variant/40 hover:bg-outline-variant/30' }}"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full {{ $task->status ? 'bg-secondary' : 'bg-on-surface-variant' }}"></span>
                                    {{ $task->status ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="openEditModal({{ $task->id }})"
                                        class="w-9 h-9 rounded-xl border border-outline-variant/60 text-primary hover:bg-primary-container/25 flex items-center justify-center transition-colors cursor-pointer"
                                        title="Edit Task parameters"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="confirmDelete({{ $task->id }})"
                                        class="w-9 h-9 rounded-xl border border-outline-variant/60 text-error/70 hover:bg-error-container/20 hover:text-error flex items-center justify-center transition-colors cursor-pointer"
                                        title="Delete Task master"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete_outline</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2">account_tree</span>
                                    <p class="text-sm font-semibold text-on-surface">No manufacturing tasks configured</p>
                                    <p class="text-xs text-on-surface-variant mt-1">Configure a reusable task master stage to map it to product routings.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($tasks->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/60 bg-surface-container-low/20">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>

    <!-- TASK MASTER CREATE/EDIT MODAL -->
    @if($showModal)
        <div
            x-data="{ show: @entangle('showModal') }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @keydown.escape.window="$wire.closeModal()"
            class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            style="display: none;"
        >
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                @click.away="$wire.closeModal()"
                class="bg-surface-container-lowest border border-outline-variant/60 rounded-3xl shadow-2xl overflow-hidden w-full max-w-3xl max-h-[90vh] flex flex-col my-auto"
            >
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-outline-variant/60 bg-surface-container-low/40 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shadow-xs">
                            <span class="material-symbols-outlined text-xl">account_tree</span>
                        </div>
                        <div>
                            <h3 class="font-headline-sm text-headline-sm font-extrabold text-primary flex items-center">
                                <span>{{ $taskId ? 'Edit Task Master' : 'Configure New Task Master' }}</span>
                                @if($taskId && $code)
                                    <span class="font-mono text-xs bg-primary/10 text-primary px-2.5 py-0.5 rounded-lg ml-2 font-bold">{{ $code }}</span>
                                @endif
                            </h3>
                            <p class="text-xs text-on-surface-variant font-medium">Set up reusable manufacturing step sequences, material permissions, and labor attributes.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="w-9 h-9 rounded-xl border border-outline-variant/60 text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface flex items-center justify-center transition-colors cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 space-y-6 overflow-y-auto flex-1 custom-scrollbar">
                    
                    <!-- Basic Information Card -->
                    <div class="bg-surface-container-low/30 rounded-2xl border border-outline-variant/60 p-5 space-y-4">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="material-symbols-outlined text-primary text-[18px]">info</span>
                            <h4 class="font-bold text-xs uppercase tracking-wider text-primary">Basic Task Information</h4>
                        </div>

                        <div>
                            <div>
                                <label class="block font-label-md text-xs font-bold text-on-surface-variant mb-1.5">Task Name *</label>
                                <input
                                    type="text"
                                    wire:model="name"
                                    placeholder="e.g. Cut & Align Fabric Sheets"
                                    class="w-full rounded-xl border border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary font-body-md px-3.5 py-2.5 bg-surface text-sm font-semibold"
                                />
                                @error('name')
                                    <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Material Consumption Card -->
                    <div class="bg-surface-container-low/30 rounded-2xl border border-outline-variant/60 p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-secondary text-[18px]">category</span>
                                <h4 class="font-bold text-xs uppercase tracking-wider text-secondary">Raw Material Consumption</h4>
                            </div>

                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" wire:model.live="consumes_raw_material" class="sr-only peer">
                                <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary"></div>
                                <span class="font-label-md text-xs font-bold ml-2.5 {{ $consumes_raw_material ? 'text-secondary' : 'text-on-surface-variant' }}">
                                    {{ $consumes_raw_material ? 'Consumes Stock' : 'No Consumption' }}
                                </span>
                            </label>
                        </div>

                        @if($consumes_raw_material)
                            <div class="pt-2 space-y-3">
                                <p class="text-[11px] text-on-surface-variant font-semibold uppercase tracking-wider">Select Allowed Raw Material Categories for Consumption</p>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach($categories as $cat)
                                        @php $checked = in_array((string)$cat->id, $selected_category_ids); @endphp
                                        <label
                                            class="flex items-center gap-3 p-3.5 rounded-xl border-2 cursor-pointer transition-all {{ $checked ? 'border-secondary bg-secondary-container/20' : 'border-outline-variant/40 bg-surface/60 hover:border-secondary/50' }}"
                                        >
                                            <input
                                                type="checkbox"
                                                wire:model.live="selected_category_ids"
                                                value="{{ $cat->id }}"
                                                class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary"
                                            />
                                            <div class="flex-1 min-w-0">
                                                <p class="font-bold text-xs text-on-surface truncate">{{ $cat->name }}</p>
                                                <p class="text-[10px] text-on-surface-variant font-mono font-bold">{{ $cat->code }}</p>
                                            </div>
                                            @if($checked)
                                                <span class="material-symbols-outlined text-secondary text-[18px] flex-shrink-0">check_circle</span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>

                                @error('selected_category_ids')
                                    <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span>
                                @enderror
                            </div>
                        @else
                            <p class="text-xs text-on-surface-variant italic">This task stage will execute without requiring raw material inventory deductions.</p>
                        @endif
                    </div>

                    <!-- Labor Involvement Card -->
                    <div class="bg-surface-container-low/30 rounded-2xl border border-outline-variant/60 p-5 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-tertiary text-[18px]">groups</span>
                                <h4 class="font-bold text-xs uppercase tracking-wider text-tertiary">Labor Involvement</h4>
                            </div>

                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" wire:model.live="is_labor_required" class="sr-only peer">
                                <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-tertiary"></div>
                                <span class="font-label-md text-xs font-bold ml-2.5 {{ $is_labor_required ? 'text-tertiary' : 'text-on-surface-variant' }}">
                                    {{ $is_labor_required ? 'Labor Dependent' : 'Non-Labor Stage' }}
                                </span>
                            </label>
                        </div>

                        <p class="text-xs text-on-surface-variant">
                            @if($is_labor_required)
                                Enables operator assignment, worker piece-rate tracking, and labor wage rollups during job execution.
                            @else
                                Bypasses operator assignment and labor costing during job execution.
                            @endif
                        </p>
                    </div>

                    <!-- Status Selection Card -->
                    <div class="bg-surface-container-low/30 rounded-2xl border border-outline-variant/60 p-5 flex items-center justify-between gap-4">
                        <div>
                            <h4 class="font-bold text-xs uppercase tracking-wider text-on-surface">Operational Status</h4>
                            <p class="text-xs text-on-surface-variant">Inactive tasks will be hidden from new product routings.</p>
                        </div>
                        <select
                            wire:model="status"
                            class="bg-surface border border-outline-variant/60 rounded-xl px-4 py-2 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary focus:border-primary shrink-0"
                        >
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <!-- Action Buttons (Scrolled to inside content flow) -->
                    <div class="pt-4 border-t border-outline-variant/60 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="px-5 py-2.5 rounded-xl border border-outline-variant hover:bg-surface-container-high text-on-surface-variant font-bold text-xs transition-colors cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            wire:click="saveTask"
                            wire:loading.attr="disabled"
                            class="px-6 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-bold text-xs shadow-md transition-all active:scale-95 flex items-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="saveTask" class="material-symbols-outlined text-[18px]">save</span>
                            <span wire:loading wire:target="saveTask" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            {{ $taskId ? 'Save Task Changes' : 'Create Task Master' }}
                        </button>
                    </div>

                </div>

            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <x-admin.modal id="delete-task-modal" title="Delete Task Master" maxWidth="md">
        <div class="space-y-4">
            <p class="font-body-md text-on-surface">Are you sure you want to delete task <strong class="text-primary font-bold">[{{ $deletingTaskName }}]</strong>?</p>
            <p class="font-body-md text-on-surface-variant text-xs">This action cannot be undone. Task definitions mapped to completed manufacturing jobs will be preserved.</p>
            
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-outline-variant/30">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="button" variant="primary" wire:click="delete" class="!bg-error hover:!bg-error/90 !text-white" icon="delete">Confirm Delete</x-admin.button>
            </div>
        </div>
    </x-admin.modal>
</div>
