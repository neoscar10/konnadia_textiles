<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/60 shadow-xs">
        <div class="space-y-1">
            <div class="flex items-center gap-2 text-xs font-semibold text-on-surface-variant">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-primary transition-colors">Dashboard</a>
                <span>/</span>
                <span class="text-on-surface">Labour Categories</span>
            </div>
            <h1 class="text-2xl font-black text-on-surface font-display flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-3xl">badge</span>
                Labour Categories
            </h1>
            <p class="text-xs text-on-surface-variant font-medium">Define work specializations (e.g., Tailor, Cutter, Laundry Person) to link tasks and filter workers across production steps.</p>
        </div>

        <div class="flex items-center gap-3">
            <button wire:click="create" type="button" class="inline-flex items-center gap-2 bg-primary text-on-primary px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-primary-container shadow-xs transition-all active:scale-95 cursor-pointer">
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                Add Labour Category
            </button>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/60 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="relative w-full sm:w-80">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search categories by name or code..." class="w-full bg-surface-container border border-outline-variant/60 rounded-xl pl-10 pr-4 py-2 text-xs font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
        </div>

        <div class="text-xs text-on-surface-variant font-medium">
            Total Categories: <span class="font-bold text-on-surface">{{ $categories->total() }}</span>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-surface-container/60 border-b border-outline-variant/60 text-[11px] text-on-surface-variant uppercase tracking-wider font-extrabold">
                        <th class="py-3.5 px-6">Category Code</th>
                        <th class="py-3.5 px-6">Category Name</th>
                        <th class="py-3.5 px-6">Description</th>
                        <th class="py-3.5 px-6 text-center">Assigned Tasks</th>
                        <th class="py-3.5 px-6 text-center">Active Workers</th>
                        <th class="py-3.5 px-6 text-center">Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60 text-on-surface font-medium">
                    @forelse($categories as $category)
                        <tr class="hover:bg-surface-container/40 transition-colors" wire:key="cat-{{ $category->id }}">
                            <td class="py-4 px-6 font-mono font-bold text-primary">
                                {{ $category->code }}
                            </td>
                            <td class="py-4 px-6 font-bold text-on-surface text-sm">
                                {{ $category->name }}
                            </td>
                            <td class="py-4 px-6 text-on-surface-variant text-xs max-w-xs truncate">
                                {{ $category->description ?: '—' }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-primary/10 text-primary">
                                    {{ $category->tasks_count }} Task(s)
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-700">
                                    {{ $category->labors_count }} Worker(s)
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <button type="button" wire:click="toggleStatus({{ $category->id }})" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold transition-all cursor-pointer {{ $category->status ? 'bg-emerald-500/15 text-emerald-700 hover:bg-emerald-500/25' : 'bg-rose-500/15 text-rose-700 hover:bg-rose-500/25' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $category->status ? 'bg-emerald-600' : 'bg-rose-600' }}"></span>
                                    {{ $category->status ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" wire:click="edit({{ $category->id }})" class="p-1.5 text-on-surface-variant hover:text-primary hover:bg-primary/10 rounded-lg transition-colors cursor-pointer" title="Edit Category">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $category->id }})" class="p-1.5 text-on-surface-variant hover:text-rose-600 hover:bg-rose-500/10 rounded-lg transition-colors cursor-pointer" title="Delete Category">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-on-surface-variant font-semibold">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <span class="material-symbols-outlined text-4xl opacity-40">badge</span>
                                    <p>No Labour Categories found.</p>
                                    @if($search)
                                        <p class="text-xs text-on-surface-variant/70">Try adjusting your search criteria.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
            <div class="p-4 border-t border-outline-variant/60 bg-surface-container/30">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Form Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-surface-container-lowest w-full max-w-lg rounded-2xl border border-outline-variant/60 shadow-xl overflow-hidden animate-in fade-in zoom-in duration-150">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container/30">
                    <h3 class="text-base font-bold text-on-surface font-display flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-xl">badge</span>
                        {{ $editingId ? 'Edit Labour Category' : 'Create Labour Category' }}
                    </h3>
                    <button type="button" wire:click="closeModal" class="text-on-surface-variant hover:text-on-surface p-1 rounded-lg transition-colors cursor-pointer">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <!-- Modal Form -->
                <form wire:submit.prevent="save" class="p-6 space-y-4">
                    <!-- Category Name -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-on-surface">Category Name <span class="text-rose-600">*</span></label>
                        <input type="text" wire:model.live="name" placeholder="e.g. Tailor, Cutter, Laundry Person, Finisher" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        @error('name') <p class="text-[11px] text-rose-600 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Category Code -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-on-surface">Category Code <span class="text-on-surface-variant font-normal">(Optional - auto-generated if left blank)</span></label>
                        <input type="text" wire:model.live="code" placeholder="e.g. LCAT-0001" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-mono font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        @error('code') <p class="text-[11px] text-rose-600 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Description -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-on-surface">Description / Responsibilities</label>
                        <textarea wire:model.live="description" rows="3" placeholder="Brief details about what workers in this category do..." class="w-full bg-surface border border-outline-variant/60 rounded-xl p-3 text-xs font-medium text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"></textarea>
                        @error('description') <p class="text-[11px] text-rose-600 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Status -->
                    <div class="pt-2 flex items-center justify-between bg-surface-container/40 p-3 rounded-xl border border-outline-variant/40">
                        <div>
                            <span class="block text-xs font-bold text-on-surface">Active Status</span>
                            <span class="text-[11px] text-on-surface-variant font-medium">Inactive categories will be hidden from task assignment.</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="status" class="sr-only peer">
                            <div class="w-9 h-5 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-outline-variant/60 flex items-center justify-end gap-3">
                        <button type="button" wire:click="closeModal" class="px-4 py-2.5 rounded-xl border border-outline-variant/60 text-xs font-bold text-on-surface hover:bg-surface-container transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary text-on-primary text-xs font-bold hover:bg-primary-container shadow-xs transition-all active:scale-95 cursor-pointer flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                            {{ $editingId ? 'Save Changes' : 'Create Category' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    <x-admin.modal id="delete-category-modal" title="Delete Labour Category">
        <div class="space-y-4">
            <div class="flex items-center gap-3 text-rose-600">
                <span class="material-symbols-outlined text-3xl">warning</span>
                <h2 class="text-lg font-bold font-display">Confirm Category Removal</h2>
            </div>

            <p class="text-xs text-on-surface-variant font-medium">
                Are you sure you want to delete the category <span class="font-bold text-on-surface font-mono">'{{ $deletingCategoryName }}'</span>? This action cannot be undone.
            </p>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/60">
                <button type="button" x-on:click="$dispatch('close-modal', 'delete-category-modal')" class="px-4 py-2.5 rounded-xl border border-outline-variant/60 text-xs font-bold text-on-surface hover:bg-surface-container transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="button" wire:click="delete" class="px-5 py-2.5 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 shadow-xs transition-all active:scale-95 cursor-pointer">
                    Confirm Delete
                </button>
            </div>
        </div>
    </x-admin.modal>
</div>
