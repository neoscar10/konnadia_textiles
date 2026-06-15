<div>
    <x-slot:title>Categories</x-slot:title>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Product Categories</h1>
            <p class="font-body-md text-on-surface-variant">Manage product categories and nested sub-categories like folders.</p>
        </div>
    </div>

    <!-- Double Panel Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-xl items-start">
        
        <!-- Left Panel: Category Tree -->
        <div class="lg:col-span-4 bg-white rounded-xl card-shadow border border-outline-variant/30 overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant/20 bg-surface-container-low flex justify-between items-center">
                <h3 class="font-title-md text-primary flex items-center gap-xs">
                    <span class="material-symbols-outlined text-[20px] text-primary select-none">lan</span>
                    All Categories
                </h3>
            </div>
            <div class="p-lg space-y-sm max-h-[600px] overflow-y-auto custom-scrollbar select-none">
                <div class="flex items-center gap-xs py-xs px-sm rounded-lg cursor-pointer hover:bg-surface-container-high transition-all {{ is_null($currentCategoryId) ? 'bg-primary text-on-primary font-semibold' : 'text-on-surface-variant' }}"
                     wire:click="selectCategory(null)">
                    <span class="material-symbols-outlined text-[20px] {{ is_null($currentCategoryId) ? 'text-on-primary' : 'text-primary' }} select-none">grid_view</span>
                    <span class="text-sm">Root Directory</span>
                </div>

                <div class="w-full h-px bg-outline-variant/20 my-sm"></div>

                <div class="space-y-sm">
                    @forelse($tree as $rootItem)
                        @include('admin.categories.tree-item', ['item' => $rootItem, 'openFolderIds' => $openFolderIds])
                    @empty
                        <p class="text-xs text-on-surface-variant text-center py-md">No categories configured.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Panel: Folder Contents -->
        <div class="lg:col-span-8 bg-white rounded-xl card-shadow border border-outline-variant/30 overflow-hidden flex flex-col min-h-[500px]">
            
            <!-- Folder Header Bar -->
            <div class="p-lg border-b border-outline-variant/20 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md bg-surface-container-low/30">
                <div class="flex items-center gap-sm flex-wrap">
                    @if($currentCategoryId)
                        <button wire:click="navigateUp" class="w-8 h-8 rounded-full bg-white border border-outline-variant/50 flex items-center justify-center text-primary hover:bg-surface-container transition-colors shadow-sm focus:outline-none" title="Go Up One Folder">
                            <span class="material-symbols-outlined text-[18px] select-none">arrow_upward</span>
                        </button>
                    @endif
                    
                    <div class="flex items-center gap-xs font-title-sm text-on-surface flex-wrap select-none">
                        <span class="cursor-pointer text-secondary font-semibold hover:underline" wire:click="selectCategory(null)">Root</span>
                        @foreach($breadcrumbs as $bc)
                            <span class="text-outline">/</span>
                            <span class="cursor-pointer text-secondary font-semibold hover:underline" wire:click="selectCategory({{ $bc['id'] }})">{{ $bc['name'] }}</span>
                        @endforeach
                    </div>
                </div>

                <x-admin.button variant="primary" icon="add" wire:click="create">
                    {{ $currentCategoryId ? 'New Sub-Category' : 'New Category' }}
                </x-admin.button>
            </div>

            <!-- Folder Filter Sub-Bar -->
            <div class="px-lg py-sm bg-surface-container-low/10 border-b border-outline-variant/10 flex items-center justify-between gap-md">
                <div class="flex items-center gap-sm w-full sm:w-72 bg-surface-container-low border border-outline-variant/50 rounded-lg px-sm focus-within:ring-2 focus-within:ring-secondary focus-within:border-secondary transition-all">
                    <span class="material-symbols-outlined text-on-surface-variant/60 text-[18px] select-none pl-xs">search</span>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search in this folder..." class="w-full bg-transparent border-none py-xs pr-xs font-body-md focus:ring-0 focus:outline-none outline-none text-on-surface">
                </div>
                <div class="text-xs text-on-surface-variant select-none">
                    {{ $children->count() }} items inside
                </div>
            </div>

            <!-- Folder Contents Table -->
            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-left font-body-md">
                    <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider text-xs border-b border-outline-variant/20 select-none">
                        <tr>
                            <th class="px-lg py-sm">Name</th>
                            <th class="px-lg py-sm">Slug</th>
                            <th class="px-lg py-sm text-center">Subcategories</th>
                            <th class="px-lg py-sm text-center">Status</th>
                            <th class="px-lg py-sm text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10">
                        @forelse($children as $child)
                            <tr class="hover:bg-primary/[0.01] transition-colors group">
                                <td class="px-lg py-md whitespace-nowrap">
                                    <div class="flex items-center gap-sm">
                                        <span class="material-symbols-outlined text-secondary text-[24px] select-none group-hover:scale-110 transition-transform">folder</span>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-primary cursor-pointer hover:underline" wire:click="selectCategory({{ $child->id }})">
                                                {{ $child->name }}
                                            </span>
                                            @if($child->description)
                                                <span class="text-xs text-on-surface-variant truncate max-w-[200px]">{{ $child->description }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-lg py-md text-on-surface-variant font-mono text-xs">{{ $child->slug }}</td>
                                <td class="px-lg py-md text-center whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center px-sm py-xxs rounded bg-secondary-container text-on-secondary-container font-bold text-xs select-none">
                                        {{ $child->children()->count() }}
                                    </span>
                                </td>
                                <td class="px-lg py-md text-center whitespace-nowrap">
                                    <button wire:click="toggleStatus({{ $child->id }})" class="focus:outline-none">
                                        <x-admin.badge type="{{ $child->is_active ? 'success' : 'default' }}">
                                            {{ $child->is_active ? 'Active' : 'Inactive' }}
                                        </x-admin.badge>
                                    </button>
                                </td>
                                <td class="px-lg py-md text-right whitespace-nowrap">
                                    <x-admin.action-menu>
                                        <x-admin.action-menu-item wire:click="selectCategory({{ $child->id }})" icon="folder_open" label="Open Folder" />
                                        <x-admin.action-menu-item wire:click="edit({{ $child->id }})" icon="edit" label="Edit" />
                                        <x-admin.action-menu-item wire:click="toggleStatus({{ $child->id }})" icon="{{ $child->is_active ? 'block' : 'check_circle' }}" label="{{ $child->is_active ? 'Deactivate' : 'Activate' }}" />
                                        <x-admin.action-menu-item wire:click="confirmDelete({{ $child->id }})" icon="delete" label="Delete" class="text-error hover:text-error hover:bg-error/10" />
                                    </x-admin.action-menu>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-lg py-2xl text-center text-on-surface-variant">
                                    <div class="flex flex-col items-center justify-center">
                                        <span class="material-symbols-outlined text-4xl mb-sm text-outline select-none">folder_off</span>
                                        @if($search)
                                            <p class="font-body-lg">No matching sub-categories found.</p>
                                            <p class="text-sm">Try checking your spelling or search folder criteria.</p>
                                        @else
                                            <p class="font-body-lg">This category is empty</p>
                                            <p class="text-sm">Create your first sub-category inside this folder to organize products.</p>
                                            <button wire:click="create" class="mt-md px-md py-sm bg-primary text-white rounded-lg font-button flex items-center gap-xs hover:bg-primary/95 transition-all text-xs">
                                                <span class="material-symbols-outlined text-sm select-none">add</span>
                                                Create Folder
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <x-admin.modal id="add-category" title="{{ $editingCategoryId ? 'Edit Category' : 'New Category' }}" maxWidth="md">
        <form class="space-y-md" wire:submit.prevent="save">
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Category Name *</label>
                <input type="text" wire:model="form.name" placeholder="e.g. Linen Collections" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md">
                @error('form.name') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Description</label>
                <textarea rows="3" wire:model="form.description" placeholder="Short description of folder contents" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md"></textarea>
                @error('form.description') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center gap-sm mt-md">
                <input type="checkbox" wire:model="form.is_active" id="form_is_active" class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
                <label for="form_is_active" class="font-body-md text-on-surface cursor-pointer select-none">Active Status</label>
            </div>
        </form>

        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button variant="primary" icon="save" wire:click="save">Save Folder</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <x-admin.modal id="delete-category" title="Delete Category" maxWidth="md">
        <div class="space-y-md">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-error/10 mx-auto mb-lg text-error select-none">
                <span class="material-symbols-outlined text-[32px]">warning</span>
            </div>
            <h3 class="text-center font-title-lg text-on-surface">Are you sure?</h3>
            <p class="text-center font-body-md text-on-surface-variant">
                This category and its nested sub-categories may no longer be available for product assignment.
            </p>
        </div>
        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button wire:click="delete" variant="primary" class="bg-error hover:bg-error/90 text-white border-error">Delete Category</x-admin.button>
        </x-slot>
    </x-admin.modal>
</div>
