<div>
    <x-slot:title>Tags Management</x-slot:title>
    
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Tags Management</h1>
            <p class="font-body-md text-on-surface-variant">Create and manage product tags to improve searchability and customer filtering.</p>
        </div>
        <x-admin.button variant="primary" icon="add" wire:click="create">Add Tag</x-admin.button>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-md mb-lg">
        <div class="w-full sm:w-1/3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tags by name or slug..." class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
        </div>
    </div>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="w-full overflow-visible pb-32">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr class="whitespace-nowrap text-xs">
                        <th class="px-lg py-md">Tag Name</th>
                        <th class="px-lg py-md">Slug</th>
                        <th class="px-lg py-md">Associated Categories</th>
                        <th class="px-lg py-md text-center">Products Count</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse($tags as $tag)
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-lg font-bold text-primary">{{ $tag->name }}</td>
                        <td class="px-lg py-lg text-on-surface-variant font-mono text-sm">{{ $tag->slug }}</td>
                        <td class="px-lg py-lg text-on-surface-variant text-sm max-w-[250px] truncate" title="{{ $tag->categories->pluck('name')->implode(', ') }}">
                            {{ $tag->categories->pluck('name')->implode(', ') ?: 'None' }}
                        </td>
                        <td class="px-lg py-lg text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                                {{ $tag->products()->count() }} products
                            </span>
                        </td>
                        <td class="px-lg py-lg text-right">
                            <x-admin.action-menu>
                                <x-admin.action-menu-item wire:click="edit({{ $tag->id }})" icon="edit" label="Edit" />
                                <x-admin.action-menu-item wire:click="confirmDelete({{ $tag->id }})" icon="delete" label="Delete" danger="true" />
                            </x-admin.action-menu>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-lg py-xl text-center text-on-surface-variant font-body-md">
                            No tags found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($tags->hasPages())
        <div class="px-lg py-md border-t border-outline-variant/20">
            {{ $tags->links() }}
        </div>
        @endif
    </x-admin.card>

    <!-- Add/Edit Modal -->
    <x-admin.modal id="add-tag" title="{{ $editingId ? 'Edit Tag' : 'Add Tag' }}" maxWidth="2xl">
        <form wire:submit.prevent="save" class="space-y-md">
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant font-semibold">Tag Name *</label>
                <input type="text" wire:model.live="form.name" placeholder="e.g. Eco-Friendly, Premium Linen" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                @error('form.name') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            @if(!empty($form['name']))
            <div class="flex items-center gap-sm text-sm text-on-surface-variant bg-surface-container/50 border border-outline-variant/30 rounded-lg px-md py-sm">
                <span class="material-symbols-outlined text-[16px] text-secondary">link</span>
                <span class="font-medium">Slug:</span>
                <code class="font-mono text-secondary font-semibold">{{ \Illuminate\Support\Str::slug($form['name']) }}</code>
                <span class="text-xs text-on-surface-variant/60 ml-auto italic">Auto-generated</span>
            </div>
            @endif

            <!-- Categories Checklist -->
            <div class="space-y-xs pt-sm border-t border-outline-variant/10">
                <div class="flex justify-between items-center">
                    <label class="font-label-md text-on-surface-variant font-semibold">Associate with Categories *</label>
                    <div class="relative w-48">
                        <input type="text" wire:model.live.debounce.150ms="categorySearch" placeholder="Search categories..." class="w-full px-xs py-0.5 text-xs bg-surface-container border border-outline-variant/30 rounded focus:ring-1 focus:ring-secondary outline-none transition-all text-on-surface">
                    </div>
                </div>
                <div class="border border-outline-variant/30 rounded-lg p-md max-h-[220px] overflow-y-auto bg-surface-container-low divide-y divide-outline-variant/10 mt-1">
                    @forelse($leafCategories as $leaf)
                        <div class="flex items-center gap-md py-sm select-none">
                            <input type="checkbox" id="modal_cat_{{ $leaf->id }}" value="{{ $leaf->id }}" wire:model="selectedCategoryIds" class="w-4 h-4 rounded border-outline-variant text-[#5c44c4] focus:ring-[#5c44c4] cursor-pointer">
                            <label for="modal_cat_{{ $leaf->id }}" class="text-sm text-on-surface cursor-pointer select-none">
                                <span class="font-bold text-primary">{{ $leaf->name }}</span>
                                <span class="text-xs text-on-surface-variant block">{{ $leaf->full_path }}</span>
                            </label>
                        </div>
                    @empty
                        <p class="text-xs text-on-surface-variant text-center py-md">No leaf categories configured yet.</p>
                    @endforelse
                </div>
                @error('selectedCategoryIds') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>
            
            <div class="flex justify-end gap-md mt-xl pt-md border-t border-outline-variant/20">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="save">Save Tag</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Delete Confirmation Modal -->
    <x-admin.modal id="delete-tag" title="Delete Tag" maxWidth="sm">
        <div class="space-y-md">
            <p class="font-body-md text-on-surface">Are you sure you want to delete this tag? It will be detached from all products.</p>
            <p class="font-body-md text-on-surface-variant text-sm">This action cannot be undone.</p>
            
            <div class="flex justify-end gap-md mt-xl pt-md border-t border-outline-variant/20">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="button" variant="primary" wire:click="delete" class="!bg-error hover:!bg-error/90 !text-white" icon="delete">Confirm Delete</x-admin.button>
            </div>
        </div>
    </x-admin.modal>
</div>
