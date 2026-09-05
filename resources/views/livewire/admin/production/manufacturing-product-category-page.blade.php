<div>
    <!-- Page Header -->
    <div class="bg-surface-container-lowest border-b border-outline-variant/60 p-6 rounded-2xl mb-6 shadow-xs">
        <nav class="flex mb-2 text-xs text-on-surface-variant font-semibold space-x-2">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-primary transition-colors">Dashboard</a>
            <span>&gt;</span>
            <a href="{{ route('admin.production.products.index') }}" wire:navigate class="hover:text-primary transition-colors">Manufacturing Products</a>
            <span>&gt;</span>
            <span class="text-primary font-bold">Product Category Master</span>
        </nav>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Manufacturing Product Categories</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                    Classify manufacturing SKUs and define each category's default task sequence routing.
                </p>
            </div>
            <button type="button" wire:click="openCreateModal"
                class="inline-flex items-center gap-2 bg-primary text-on-primary px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-primary-container shadow-md transition-all active:scale-95">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Add New Category
            </button>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/60 mb-6 shadow-xs">
        <div class="flex items-center gap-3 max-w-sm">
            <span class="material-symbols-outlined text-on-surface-variant shrink-0">search</span>
            <input wire:model.live.debounce.300ms="search"
                class="w-full px-4 py-3 bg-surface-container-low rounded-xl border border-outline-variant/60 focus:ring-2 focus:ring-primary/20 focus:border-primary font-semibold text-sm"
                placeholder="Search category name..."
                type="text"/>
        </div>
    </div>

    <!-- Categories Data Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 overflow-hidden shadow-xs mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-body-md">
                <thead>
                    <tr class="bg-surface-container-low text-xs text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/60">
                        <th class="px-6 py-4 font-bold">Category</th>
                        <th class="px-6 py-4 font-bold">Default Task Sequence <span class="ml-1 text-[10px] font-extrabold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300 uppercase">NEW</span></th>
                        <th class="px-6 py-4 font-bold text-center">Linked Products</th>
                        <th class="px-6 py-4 font-bold text-center">Status</th>
                        <th class="px-6 py-4 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($categories as $category)
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-extrabold text-on-surface text-sm">{{ $category->name }}</p>
                                <p class="text-xs text-outline mt-0.5">Created {{ $category->created_at->diffForHumans() }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if($category->defaultTasks->isNotEmpty())
                                    <div class="flex items-center flex-wrap gap-1.5">
                                        @foreach($category->defaultTasks as $idx => $t)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                                <span>{{ $t->name }}</span>
                                                @if($t->pivot->is_final_step)
                                                    <span class="text-[9px] font-extrabold px-1 rounded bg-amber-200 text-amber-900 uppercase">Final</span>
                                                @endif
                                            </span>
                                            @if(!$loop->last)
                                                <span class="text-slate-400 font-bold text-xs">→</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">No default sequence defined</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 bg-surface-container-high text-on-surface font-bold text-xs rounded-full">
                                    {{ $category->manufacturing_products_count }} SKU{{ $category->manufacturing_products_count !== 1 ? 's' : '' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button type="button" wire:click="toggleStatus({{ $category->id }})"
                                    wire:loading.attr="disabled"
                                    title="{{ $category->status ? 'Click to Deactivate' : 'Click to Activate' }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold text-[11px] uppercase tracking-wider transition-all active:scale-95
                                        {{ $category->status
                                            ? 'bg-secondary/10 text-secondary border border-secondary/30 hover:bg-secondary/20'
                                            : 'bg-error/10 text-error border border-error/30 hover:bg-error/20' }}">
                                    <span class="w-2 h-2 rounded-full {{ $category->status ? 'bg-secondary' : 'bg-error' }}"></span>
                                    {{ $category->status ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" wire:click="editCategory({{ $category->id }})"
                                        class="p-2 text-primary hover:bg-primary/10 rounded-xl transition-all" title="Edit Category">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button type="button"
                                        wire:click="deleteCategory({{ $category->id }})"
                                        wire:confirm="Are you sure you want to delete '{{ $category->name }}'? This action cannot be undone."
                                        class="p-2 text-error hover:bg-error-container/30 rounded-xl transition-all" title="Delete Category">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-5xl text-outline mb-3 block">category</span>
                                <p class="font-body-lg text-body-lg font-bold text-on-surface">No manufacturing product categories found.</p>
                                <p class="text-sm text-outline mt-1">
                                    {{ $search ? 'Try a different search term.' : 'Create your first category to get started.' }}
                                </p>
                                @if(!$search)
                                    <button type="button" wire:click="openCreateModal"
                                        class="mt-4 inline-flex items-center gap-2 bg-primary text-on-primary px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-primary-container shadow-md transition-all">
                                        <span class="material-symbols-outlined text-[18px]">add</span>
                                        Add First Category
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
            <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/60">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    <x-admin.modal id="category-modal" title="{{ $categoryId ? 'Edit Category' : 'Add Manufacturing Product Category' }}" maxWidth="lg">
        <form wire:submit.prevent="saveCategory" class="space-y-5">
            <!-- Category Name -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                    Category Name *
                </label>
                <input type="text" wire:model="name"
                    class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                    placeholder="e.g. Bedsheet, Pillow Cover, Dohar">
                @error('name')
                    <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span>
                @enderror
            </div>

            <!-- Default Task Sequence Repeater -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-extrabold uppercase text-slate-800 tracking-wider">Default Task Sequence</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Pre-fills the routing sequence for new products added under this category.</p>
                    </div>
                    <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200">DEFAULT ROUTING</span>
                </div>

                <div class="space-y-2">
                    @foreach($defaultTasksList as $index => $row)
                        <div class="flex items-center gap-2 p-2.5 bg-white border border-slate-200 rounded-xl shadow-2xs">
                            <span class="w-6 h-6 rounded-full bg-slate-900 text-white font-extrabold text-xs flex items-center justify-center shrink-0">
                                {{ $index + 1 }}
                            </span>
                            
                            <div class="flex-1 min-w-[140px]">
                                <select wire:model="defaultTasksList.{{ $index }}.task_id" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-800">
                                    <option value="">-- Select Task --</option>
                                    @foreach($availableTasks as $t)
                                        <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="w-28">
                                <input type="number" step="0.50" wire:model="defaultTasksList.{{ $index }}.standard_labor_rate" placeholder="Rate (₹)" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800">
                            </div>

                            <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-slate-700 shrink-0">
                                <input type="radio" name="cat_final_step" wire:click="setFinalStep({{ $index }})" @checked(!empty($row['is_final_step'])) class="text-amber-600 focus:ring-amber-500">
                                <span>Final</span>
                            </label>

                            <div class="flex items-center gap-1 shrink-0">
                                @if($index > 0)
                                    <button type="button" wire:click="moveDefaultTaskRow({{ $index }}, 'up')" class="w-6 h-6 rounded border border-slate-200 flex items-center justify-center hover:bg-slate-100 text-slate-600 text-xs">↑</button>
                                @endif
                                @if($index < count($defaultTasksList) - 1)
                                    <button type="button" wire:click="moveDefaultTaskRow({{ $index }}, 'down')" class="w-6 h-6 rounded border border-slate-200 flex items-center justify-center hover:bg-slate-100 text-slate-600 text-xs">↓</button>
                                @endif
                                <button type="button" wire:click="removeDefaultTaskRow({{ $index }})" class="w-6 h-6 rounded border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center text-xs">✕</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" wire:click="addDefaultTaskRow" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-slate-800 bg-white border border-slate-300 rounded-xl hover:bg-slate-100 transition-all shadow-2xs">
                    <span>＋</span> Add task to sequence
                </button>
            </div>

            <!-- Status Checkbox -->
            <div>
                <label class="flex items-center gap-3 cursor-pointer select-none group">
                    <input type="checkbox" wire:model="status"
                        class="w-4 h-4 rounded border-outline-variant/60 text-primary focus:ring-primary/30 cursor-pointer">
                    <span class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors">
                        Active Category
                    </span>
                </label>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="save">
                    {{ $categoryId ? 'Update Category' : 'Create Category' }}
                </x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
