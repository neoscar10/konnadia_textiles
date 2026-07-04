<div>
    <x-slot:title>Products</x-slot:title>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Product Catalog</h1>
            <p class="font-body-md text-on-surface-variant">Manage your inventory, variants, base pricing, and discount overrides.</p>
        </div>
        <div class="flex gap-md w-full sm:w-auto">
            <x-admin.button variant="primary" icon="add" wire:click="create">Add Product</x-admin.button>
        </div>
    </div>

    <!-- Filters & Search -->
    <x-admin.card class="mb-xl">
        <x-slot:bodyClass>p-md flex flex-wrap gap-md items-center justify-between</x-slot:bodyClass>
        
        <div class="flex flex-wrap gap-md items-center w-full lg:w-auto">
            <!-- Search -->
            <div class="flex items-center gap-sm w-full sm:w-72 bg-surface-container-low border border-outline-variant/50 rounded-lg px-sm focus-within:ring-2 focus-within:ring-secondary focus-within:border-secondary transition-all">
                <span class="material-symbols-outlined text-on-surface-variant/60 text-[18px] select-none pl-xs">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Title, SKU, Code..." class="w-full bg-transparent border-none py-xs pr-xs font-body-md focus:ring-0 focus:outline-none outline-none text-on-surface">
            </div>
            
            <!-- Category Filter -->
            <select wire:model.live="filterCategory" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">All Categories</option>
                @foreach($categories->whereNull('parent_id') as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>

            <!-- Status Filter -->
            <select wire:model.live="filterStatus" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <!-- Stock Filter -->
            <select wire:model.live="filterStock" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">Stock Status</option>
                <option value="instock">In Stock</option>
                <option value="outofstock">Out of Stock</option>
            </select>
        </div>
        
        <div class="font-label-md text-on-surface-variant">
            Total products: <span class="text-primary font-bold">{{ $products->total() }}</span>
        </div>
    </x-admin.card>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="overflow-x-auto pb-32">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20 select-none">
                    <tr class="whitespace-nowrap">
                        <th class="px-lg py-md">Product</th>
                        <th class="px-lg py-md">SKU</th>
                        <th class="px-lg py-md">Categories</th>
                        <th class="px-lg py-md font-bold text-primary">Base Price</th>
                        <th class="px-lg py-md text-center">Stock</th>
                        <th class="px-lg py-md text-center">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse($products as $prod)
                        <tr class="hover:bg-primary/[0.02] transition-colors group">
                            <td class="px-lg py-md">
                                <div class="flex items-center gap-sm">
                                    <div class="w-10 h-10 rounded bg-surface-container flex-shrink-0 overflow-hidden flex items-center justify-center border border-outline-variant/30">
                                        @if($prod->primaryMedia)
                                            <img src="{{ Storage::url($prod->primaryMedia->file_path) }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="material-symbols-outlined text-outline">image</span>
                                        @endif
                                    </div>
                                    <div class="flex flex-col">
                                        <a href="{{ route('admin.products.show', ['id' => $prod->id]) }}" class="font-bold text-primary hover:underline">
                                            {{ $prod->title }}
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td class="px-lg py-md text-on-surface-variant font-mono text-sm whitespace-nowrap">{{ $prod->sku }}</td>
                            <td class="px-lg py-md text-on-surface-variant max-w-[200px] truncate">
                                {{ $prod->categories->pluck('name')->implode(', ') ?: 'Uncategorized' }}
                            </td>
                            <td class="px-lg py-md font-bold text-primary">₹{{ number_format($prod->base_price, 2) }}</td>
                            <td class="px-lg py-md text-center">
                                @if($prod->product_type === 'manufactured' || $prod->stock_quantity === null)
                                    <span class="font-semibold text-on-surface-variant">NA</span>
                                @elseif($prod->stock_quantity > 0)
                                    <span class="font-semibold text-success">{{ $prod->stock_quantity }}</span>
                                @else
                                    <span class="font-semibold text-error">Out of stock</span>
                                @endif
                            </td>
                            <td class="px-lg py-md text-center">
                                <button x-on:click="$wire.call('toggleStatus', {{ $prod->id }})" class="focus:outline-none">
                                    <x-admin.badge type="{{ $prod->is_active ? 'success' : 'default' }}">
                                        {{ $prod->is_active ? 'Active' : 'Inactive' }}
                                    </x-admin.badge>
                                </button>
                            </td>
                            <td class="px-lg py-md text-right">
                                <x-admin.action-menu>
                                    <x-admin.action-menu-item icon="visibility" label="View Details" href="{{ route('admin.products.show', ['id' => $prod->id]) }}" />
                                    <x-admin.action-menu-item icon="edit" label="Edit" x-on:click="$wire.call('edit', {{ $prod->id }})" />
                                    <x-admin.action-menu-item icon="{{ $prod->is_active ? 'block' : 'check_circle' }}" label="{{ $prod->is_active ? 'Deactivate' : 'Activate' }}" x-on:click="$wire.call('toggleStatus', {{ $prod->id }})" />
                                    <x-admin.action-menu-item icon="delete" label="Delete" x-on:click="$wire.call('confirmDelete', {{ $prod->id }})" class="text-error hover:text-error hover:bg-error/10" />
                                </x-admin.action-menu>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-lg py-2xl text-center text-on-surface-variant font-medium">
                                No products found matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer class="flex justify-between items-center">
            <span class="font-label-md text-on-surface-variant">
                Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products
            </span>
            <div>
                {{ $products->links('pagination::tailwind') }}
            </div>
        </x-slot:footer>
    </x-admin.card>

    <!-- Wizard Modal (shared partial) -->
    @include('admin.products.product-wizard-modal', [
        'modalId'           => 'add-product',
        'deleteModalId'     => 'delete-product',
        'valueMediaModalId' => 'manage-value-media',
        'lockedCategory'    => null,
    ])

    <!-- Delete Modal -->
    <x-admin.modal id="delete-product" title="Delete Product" maxWidth="md">
        <div class="space-y-md select-none">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-error/10 mx-auto mb-lg text-error">
                <span class="material-symbols-outlined text-[32px]">warning</span>
            </div>
            <h3 class="text-center font-title-lg text-on-surface">Delete Product?</h3>
            <p class="text-center font-body-md text-on-surface-variant">
                This product will no longer be available in the catalog. Existing order history may still reference it later.
            </p>
        </div>
        <x-slot name="footer" class="select-none">
            <x-admin.button variant="ghost" wire:click="closeDeleteModal">Cancel</x-admin.button>
            <x-admin.button wire:click="delete" variant="primary" class="bg-error hover:bg-error/90 text-white border-error">Delete Product</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <!-- Manage Variation Value Media Modal -->
    <x-admin.modal id="manage-value-media" title="Manage Variation Value Images" maxWidth="2xl">
        @if($managingGroupIndex !== null && $managingValueIndex !== null)
            @php
                $activeGroup = $variationGroups[$managingGroupIndex];
                $activeVal = $activeGroup['values'][$managingValueIndex];
            @endphp
            <div class="space-y-lg select-none">
                <div>
                    <h4 class="font-title-md text-primary">Images for {{ $activeGroup['name'] }}: <span class="font-bold text-secondary">{{ $activeVal['value'] ?: 'Untitled Value' }}</span></h4>
                    <p class="text-xs text-on-surface-variant">Select existing product images or upload new ones specifically for this variation value.</p>
                </div>

                <!-- Existing product images selector -->
                <div class="space-y-xs">
                    <span class="font-label-md text-on-surface-variant block">Select from Product Media</span>
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-sm">
                        @forelse($existingMedia as $m)
                            @php
                                $isSelected = in_array($m['file_path'], $activeVal['media'] ?? []);
                            @endphp
                            <div wire:click="toggleValueProductMedia('{{ $m['file_path'] }}')" class="border rounded-lg overflow-hidden bg-surface-container-low relative aspect-square flex items-center justify-center cursor-pointer transition-all hover:scale-95 {{ $isSelected ? 'ring-4 ring-secondary border-secondary' : 'border-outline-variant/30' }}">
                                <img src="{{ Storage::url($m['file_path']) }}" class="w-full h-full object-cover">
                                @if($isSelected)
                                    <span class="absolute top-1 right-1 bg-secondary text-on-secondary rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold shadow">&check;</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-xs text-on-surface-variant col-span-6">No product media uploaded in Step 2 yet.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Value specific upload zone -->
                <div class="space-y-xs border-t border-outline-variant/20 pt-md">
                    <span class="font-label-md text-on-surface-variant block">Upload Specific Images</span>
                    <div class="flex items-center gap-md">
                        <input type="file" multiple id="val-media-uploader" class="hidden" wire:model="valueMediaUploads" accept="image/png, image/jpeg, image/jpg, image/webp">
                        <label for="val-media-uploader" class="px-md py-sm bg-primary/10 text-primary hover:bg-primary/20 rounded-lg font-bold text-xs cursor-pointer select-none transition-all flex items-center gap-xs">
                            <span class="material-symbols-outlined text-[16px]">cloud_upload</span>
                            Choose Images
                        </label>
                        @if(!empty($valueMediaUploads))
                            <button type="button" wire:click="uploadValueMedia" class="px-md py-sm bg-secondary text-on-secondary hover:bg-secondary/95 rounded-lg font-bold text-xs transition-all">
                                Upload & Add ({{ count($valueMediaUploads) }})
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Active media list for this value -->
                @if(!empty($activeVal['media']))
                    <div class="space-y-xs border-t border-outline-variant/20 pt-md">
                        <span class="font-label-md text-on-surface-variant block">Assigned Value Images ({{ count($activeVal['media']) }})</span>
                        <div class="grid grid-cols-4 sm:grid-cols-8 gap-sm">
                            @foreach($activeVal['media'] as $path)
                                <div class="border rounded-lg overflow-hidden bg-surface-container-low relative aspect-square flex items-center justify-center border-outline-variant/30 group">
                                    <img src="{{ Storage::url($path) }}" class="w-full h-full object-cover">
                                    <button type="button" wire:click="toggleValueProductMedia('{{ $path }}')" class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-error font-bold text-xs" title="Remove image">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
        <x-slot name="footer" class="select-none">
            <x-admin.button variant="primary" @click="show = false">Done</x-admin.button>
        </x-slot>
    </x-admin.modal>
</div>
