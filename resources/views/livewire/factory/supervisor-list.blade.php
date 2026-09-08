<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header / Subtitle Navigation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs text-on-surface-variant font-medium">
            <span class="font-bold text-on-surface">Factory</span>
            <span>/</span>
            <span class="font-bold text-on-surface">Supervisors</span>
        </div>
    </div>

    <!-- Page Banner / Header Card -->
    <div class="p-6 bg-surface rounded-2xl border border-outline-variant/60 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-primary text-[22px]" style="font-variation-settings: 'FILL' 1;">supervised_user_circle</span>
                <span class="text-[10px] font-black uppercase tracking-wider text-primary/80 border border-primary/20 bg-primary/5 px-2 py-0.5 rounded-full">Factory</span>
            </div>
            <h1 class="text-2xl font-black text-on-surface tracking-tight font-display">Supervisors</h1>
            <p class="text-xs text-on-surface-variant max-w-2xl mt-1 leading-relaxed">
                Manage factory floor supervisors. Supervisors are assigned to Production Batches to oversee job execution from cutting through to completion.
            </p>
        </div>

        <button type="button" wire:click="createSupervisor" class="inline-flex items-center gap-2 bg-[#001229] hover:bg-slate-800 text-white font-extrabold text-xs px-5 py-3 rounded-xl transition-all shadow-sm active:scale-95 shrink-0">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>+ Add Supervisor</span>
        </button>
    </div>

    <!-- Search & Filter Control Bar -->
    <div class="p-4 bg-surface rounded-2xl border border-outline-variant/60 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="relative w-full sm:w-48">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search supervisors..." class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl pl-10 pr-4 py-2 text-xs font-bold text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20">
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
            <label class="text-xs font-bold text-on-surface-variant shrink-0">Status:</label>
            <select wire:model.live="statusFilter" class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20">
                <option value="">All Supervisors</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    <!-- Supervisors Table Card -->
    <div class="bg-surface rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant/60 text-on-surface-variant font-bold uppercase tracking-wider text-[10px]">
                        <th class="px-6 py-3.5">Supervisor</th>
                        <th class="px-6 py-3.5">Department</th>
                        <th class="px-6 py-3.5">Phone</th>
                        <th class="px-6 py-3.5">Email</th>
                        <th class="px-6 py-3.5 text-center">Status</th>
                        <th class="px-6 py-3.5 text-center">Batches</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($supervisors as $supervisor)
                        <tr class="hover:bg-surface-container/40 transition-colors">
                            <!-- Name & Code -->
                            <td class="px-6 py-4">
                                <div class="font-extrabold text-on-surface text-sm">{{ $supervisor->name }}</div>
                                @if($supervisor->code)
                                    <div class="text-[10px] font-mono text-outline mt-0.5 uppercase">
                                        {{ $supervisor->code }}
                                    </div>
                                @endif
                            </td>

                            <!-- Department -->
                            <td class="px-6 py-4">
                                @if($supervisor->department)
                                    <span class="font-semibold text-on-surface">{{ $supervisor->department }}</span>
                                @else
                                    <span class="text-outline text-[11px] italic">— Not Set —</span>
                                @endif
                            </td>

                            <!-- Phone -->
                            <td class="px-6 py-4 font-mono font-bold text-on-surface">
                                @if($supervisor->phone)
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-primary text-[16px]">call</span>
                                        {{ $supervisor->phone }}
                                    </span>
                                @else
                                    <span class="text-outline text-[11px] font-normal italic">— Not Provided —</span>
                                @endif
                            </td>

                            <!-- Email -->
                            <td class="px-6 py-4 text-on-surface">
                                @if($supervisor->email)
                                    <span class="font-semibold text-primary underline underline-offset-2">{{ $supervisor->email }}</span>
                                @else
                                    <span class="text-outline text-[11px] italic">— Not Provided —</span>
                                @endif
                            </td>

                            <!-- Status Badge -->
                            <td class="px-6 py-4 text-center">
                                @if($supervisor->is_active)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 font-black text-[10px] uppercase tracking-wider">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-rose-500/10 text-rose-700 border border-rose-500/20 font-black text-[10px] uppercase tracking-wider">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            <!-- Batch Count -->
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-surface-container-low border border-outline-variant/40 rounded-lg font-black text-primary text-xs">
                                    {{ $supervisor->production_batches_count ?? 0 }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2 justify-end">
                                    <button type="button" wire:click="editSupervisor({{ $supervisor->id }})" class="p-2 text-primary hover:bg-primary/10 rounded-xl transition-colors" title="Edit Supervisor">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button type="button" wire:click="toggleActive({{ $supervisor->id }})" class="p-2 {{ $supervisor->is_active ? 'text-amber-600 hover:bg-amber-500/10' : 'text-emerald-600 hover:bg-emerald-500/10' }} rounded-xl transition-colors" title="{{ $supervisor->is_active ? 'Deactivate' : 'Activate' }}">
                                        <span class="material-symbols-outlined text-[18px]">{{ $supervisor->is_active ? 'toggle_on' : 'toggle_off' }}</span>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $supervisor->id }})" class="p-2 text-rose-600 hover:bg-rose-500/10 rounded-xl transition-colors" title="Delete Supervisor">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-on-surface-variant">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <span class="material-symbols-outlined text-4xl text-outline">supervised_user_circle</span>
                                    <p class="font-bold text-sm">No supervisors found.</p>
                                    <p class="text-xs text-outline">Click "+ Add Supervisor" to register your first factory supervisor.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($supervisors->hasPages())
            <div class="p-4 border-t border-outline-variant/60 bg-surface-container-lowest">
                {{ $supervisors->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Supervisor Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/20" wire:click="$set('showModal', false)"></div>

            <!-- Modal Dialog Container -->
            <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
                <div class="relative z-10 bg-surface rounded-2xl border border-outline-variant/60 shadow-2xl w-full max-w-xl overflow-hidden my-8 max-h-[calc(100vh-4rem)] flex flex-col">
                    <!-- Modal Header (Fixed at top) -->
                    <div class="px-6 py-4 bg-surface-container-low border-b border-outline-variant/60 flex items-center justify-between shrink-0">
                        <div>
                            <h3 class="font-extrabold text-on-surface text-base">
                                {{ $editingSupervisorId ? 'Edit Supervisor' : 'Add Supervisor' }}
                            </h3>
                            <p class="text-[11px] text-on-surface-variant">Enter the supervisor's details. They can be assigned to production batches.</p>
                        </div>
                        <button type="button" wire:click="$set('showModal', false)" class="text-outline hover:text-on-surface p-1 rounded-lg transition-colors">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Modal Form Body (Scrollable content) -->
                    <form wire:submit.prevent="saveSupervisor" class="p-6 space-y-4 overflow-y-auto flex-1 min-h-0">
                        <!-- Name & Code -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Full Name *</label>
                                <input type="text" wire:model="name" placeholder="e.g. Ramesh Kumar" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                                @error('name') <span class="text-rose-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Phone & Department -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Phone / Mobile</label>
                                <input type="text" wire:model="phone" placeholder="e.g. +91 98765 43210" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                                @error('phone') <span class="text-rose-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Department</label>
                                <input type="text" wire:model="department" placeholder="e.g. Cutting, Stitching, QC" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                                @error('department') <span class="text-rose-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Email & Code -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Email Address</label>
                                <input type="email" wire:model="email" placeholder="e.g. ramesh@factory.in" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                                @error('email') <span class="text-rose-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Supervisor Code <span class="text-outline font-normal normal-case">(auto if blank)</span></label>
                                <input type="text" wire:model="code" placeholder="e.g. SUP-0001" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface font-mono uppercase focus:ring-2 focus:ring-primary/20">
                                @error('code') <span class="text-rose-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Active Status -->
                        <div class="p-4 bg-surface-container-low border border-outline-variant/60 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-extrabold text-xs text-on-surface block">Active Status</span>
                                    <p class="text-[10px] text-on-surface-variant mt-0.5">Inactive supervisors will not appear in the batch creation form.</p>
                                </div>
                                <button type="button" 
                                    wire:click="$toggle('is_active')" 
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $is_active ? 'bg-emerald-600' : 'bg-slate-300' }}"
                                    role="switch"
                                    aria-checked="{{ $is_active ? 'true' : 'false' }}">
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Notes</label>
                            <textarea wire:model="notes" rows="2" placeholder="Optional notes about this supervisor's role or responsibilities..." class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20"></textarea>
                        </div>

                        <!-- Modal Actions -->
                        <div class="pt-3 border-t border-outline-variant/40 flex items-center justify-end gap-3">
                            <button type="button" wire:click="$set('showModal', false)" class="px-5 py-2 bg-surface-container-low border border-outline-variant/60 hover:bg-surface-container text-on-surface font-bold text-xs rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button type="submit" class="px-6 py-2 bg-[#001229] hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition-all shadow-sm">
                                {{ $editingSupervisorId ? 'Update Supervisor' : 'Create Supervisor' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($confirmingDeletionId)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/20" wire:click="$set('confirmingDeletionId', null)"></div>

            <!-- Modal Dialog Container -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative z-10 bg-surface rounded-2xl border border-outline-variant/60 shadow-2xl w-full max-w-sm p-6 space-y-4 text-center my-8">
                    <div class="w-12 h-12 rounded-full bg-rose-500/10 text-rose-600 flex items-center justify-center mx-auto">
                        <span class="material-symbols-outlined text-2xl">warning</span>
                    </div>
                    <div>
                        <h3 class="font-black text-on-surface text-base">Delete Supervisor?</h3>
                        <p class="text-xs text-on-surface-variant mt-1">This action cannot be undone. Supervisors linked to existing production batches cannot be deleted.</p>
                    </div>
                    <div class="flex items-center justify-center gap-3 pt-2">
                        <button type="button" wire:click="$set('confirmingDeletionId', null)" class="px-5 py-2 bg-surface-container-low border border-outline-variant/60 text-on-surface font-bold text-xs rounded-xl">
                            Cancel
                        </button>
                        <button type="button" wire:click="deleteSupervisor" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-sm">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
