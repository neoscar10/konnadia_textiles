<div>
    <x-slot:title>Retail Shops</x-slot:title>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Retail Shops</h1>
            <p class="font-body-md text-on-surface-variant">Manage company-owned retail outlets and contacts.</p>
        </div>
        <div class="flex gap-md w-full sm:w-auto">
            <x-admin.button wire:click="create" variant="primary" icon="add">Add Shop</x-admin.button>
        </div>
    </div>

    <!-- Filters & Search -->
    <x-admin.card class="mb-xl">
        <x-slot:bodyClass>p-md</x-slot:bodyClass>
        
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-md w-full">
            <div class="flex items-center gap-sm px-md py-xs bg-surface-container-low border border-outline-variant/50 rounded-lg focus-within:ring-2 focus-within:ring-secondary w-full sm:col-span-8 transition-all">
                <span class="material-symbols-outlined text-on-surface-variant/70 text-[20px] select-none">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Shop Code, Name, Person..." class="w-full bg-transparent border-none p-0 font-body-md text-on-surface placeholder:text-on-surface-variant/50 focus:ring-0 outline-none h-8">
            </div>

            <select wire:model.live="status" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none w-full sm:col-span-4">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </x-admin.card>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="w-full overflow-x-auto pb-32">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr class="whitespace-nowrap text-xs">
                        <th class="px-lg py-md whitespace-nowrap">Shop Code</th>
                        <th class="px-lg py-md whitespace-nowrap">Shop Name</th>
                        <th class="px-lg py-md whitespace-nowrap">Address</th>
                        <th class="px-lg py-md whitespace-nowrap">Contact Person</th>
                        <th class="px-lg py-md whitespace-nowrap">Contact Phone</th>
                        <th class="px-lg py-md text-center whitespace-nowrap">Status</th>
                        <th class="px-lg py-md text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse($shops as $shop)
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-lg font-bold text-primary whitespace-nowrap">{{ $shop->shop_code }}</td>
                        <td class="px-lg py-lg text-on-surface whitespace-nowrap">{{ $shop->name }}</td>
                        <td class="px-lg py-lg text-on-surface-variant whitespace-nowrap max-w-[200px] truncate">
                            {{ $shop->address }}, {{ $shop->city }}
                        </td>
                        <td class="px-lg py-lg text-on-surface-variant whitespace-nowrap">{{ $shop->contact_person ?: 'N/A' }}</td>
                        <td class="px-lg py-lg text-on-surface-variant whitespace-nowrap font-mono">{{ $shop->contact_phone ?: 'N/A' }}</td>
                        <td class="px-lg py-lg text-center whitespace-nowrap">
                            <x-admin.badge type="{{ $shop->is_active ? 'success' : 'default' }}">
                                {{ $shop->is_active ? 'Active' : 'Inactive' }}
                            </x-admin.badge>
                        </td>
                        <td class="px-lg py-lg text-right whitespace-nowrap">
                            <x-admin.action-menu>
                                <x-admin.action-menu-item wire:click="showDetails({{ $shop->id }})" icon="visibility" label="View Details" />
                                <x-admin.action-menu-item wire:click="edit({{ $shop->id }})" icon="edit" label="Edit" />
                                <x-admin.action-menu-item wire:click="toggleStatus({{ $shop->id }})" icon="{{ $shop->is_active ? 'block' : 'check_circle' }}" label="{{ $shop->is_active ? 'Deactivate' : 'Activate' }}" />
                                <x-admin.action-menu-item wire:click="confirmDelete({{ $shop->id }})" icon="delete" label="Delete" class="text-error hover:text-error hover:bg-error/10" />
                            </x-admin.action-menu>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-lg py-2xl text-center text-on-surface-variant">
                            <div class="flex flex-col items-center justify-center">
                                <span class="material-symbols-outlined text-4xl mb-sm text-outline">storefront</span>
                                <p class="font-body-lg">No retail shops found.</p>
                                <p class="text-sm">Click "Add Shop" to create a new retail shop record.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($shops->hasPages() || $shops->total() > 0)
        <x-slot:footer class="flex justify-between items-center">
            <span class="font-label-md text-on-surface-variant">Showing {{ $shops->firstItem() ?? 0 }} to {{ $shops->lastItem() ?? 0 }} of {{ $shops->total() }} shops</span>
            <div class="flex items-center gap-xs">
                {{ $shops->links(data: ['scrollTo' => false]) }}
            </div>
        </x-slot:footer>
        @endif
    </x-admin.card>

    <!-- Modals -->
    <!-- Form Modal -->
    <x-admin.modal id="shop-form" title="{{ $editingId ? 'Edit Retail Shop' : 'Add New Retail Shop' }}" maxWidth="2xl">
        <form wire:submit.prevent="save" class="space-y-xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                <div class="space-y-xs md:col-span-2">
                    <label class="font-label-md text-on-surface-variant font-bold">Shop Name *</label>
                    <input type="text" wire:model="form.name" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all text-on-surface" placeholder="e.g. Connaught Place Outlet">
                    @error('form.name') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs md:col-span-2">
                    <label class="font-label-md text-on-surface-variant font-bold">Street Address *</label>
                    <textarea wire:model="form.address" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all text-on-surface" rows="2" placeholder="Street, Building, Area details..."></textarea>
                    @error('form.address') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">City</label>
                    <input type="text" wire:model="form.city" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all text-on-surface" placeholder="City">
                    @error('form.city') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">State</label>
                    <input type="text" wire:model="form.state" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all text-on-surface" placeholder="State">
                    @error('form.state') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">PIN Code</label>
                    <input type="text" wire:model="form.pincode" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all text-on-surface" placeholder="e.g. 110001">
                    @error('form.pincode') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">Contact Person</label>
                    <input type="text" wire:model="form.contact_person" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all text-on-surface" placeholder="Contact Person Name">
                    @error('form.contact_person') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs md:col-span-2">
                    <label class="font-label-md text-on-surface-variant">Contact Phone</label>
                    <input type="tel" wire:model="form.contact_phone" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all text-on-surface" placeholder="Mobile / Phone Number">
                    @error('form.contact_phone') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs md:col-span-2 mt-xs">
                    <div class="flex items-center gap-sm">
                        <input type="checkbox" wire:model="form.is_active" id="is_active" class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
                        <label for="is_active" class="font-body-md text-on-surface cursor-pointer font-bold">Active Status</label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-md mt-xl pt-md border-t border-outline-variant/20">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="save">Save Retail Shop</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Details Modal -->
    <x-admin.modal id="shop-details" title="Retail Shop Details" maxWidth="lg">
        @if($selectedShop)
        <div class="space-y-lg text-on-surface">
            <div class="flex items-center justify-between border-b border-outline-variant/20 pb-sm">
                <div>
                    <h4 class="font-headline-sm text-primary">{{ $selectedShop->name }}</h4>
                    <p class="font-mono text-sm text-on-surface-variant">{{ $selectedShop->shop_code }}</p>
                </div>
                <x-admin.badge type="{{ $selectedShop->is_active ? 'success' : 'default' }}">
                    {{ $selectedShop->is_active ? 'Active' : 'Inactive' }}
                </x-admin.badge>
            </div>

            <div class="grid grid-cols-3 gap-md font-body-md">
                <div class="font-bold text-on-surface-variant">Street Address:</div>
                <div class="col-span-2">{{ $selectedShop->address }}</div>

                <div class="font-bold text-on-surface-variant">City / State:</div>
                <div class="col-span-2">{{ $selectedShop->city ?: 'N/A' }} / {{ $selectedShop->state ?: 'N/A' }}</div>

                <div class="font-bold text-on-surface-variant">PIN Code:</div>
                <div class="col-span-2 font-mono">{{ $selectedShop->pincode ?: 'N/A' }}</div>

                <div class="font-bold text-on-surface-variant">Contact Person:</div>
                <div class="col-span-2">{{ $selectedShop->contact_person ?: 'N/A' }}</div>

                <div class="font-bold text-on-surface-variant">Contact Phone:</div>
                <div class="col-span-2 font-mono">{{ $selectedShop->contact_phone ?: 'N/A' }}</div>

                <div class="font-bold text-on-surface-variant">Created At:</div>
                <div class="col-span-2 font-mono text-xs">{{ $selectedShop->created_at->format('Y-m-d H:i') }}</div>
            </div>
        </div>
        @endif
        <x-slot name="footer">
            <x-admin.button variant="primary" @click="show = false">Close</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <!-- Delete Modal -->
    <x-admin.modal id="delete-shop" title="Delete Retail Shop" maxWidth="md">
        <div class="space-y-md">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-error/10 mx-auto mb-lg">
                <span class="material-symbols-outlined text-[32px] text-error">warning</span>
            </div>
            <h3 class="text-center font-title-lg text-on-surface">Are you sure?</h3>
            <p class="text-center font-body-md text-on-surface-variant">
                This retail shop will no longer appear in active lists. Historical transfer records referencing this shop will be preserved.
            </p>
        </div>
        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button wire:click="delete" variant="primary" class="bg-error hover:bg-error/90 text-white border-error">Delete Shop</x-admin.button>
        </x-slot>
    </x-admin.modal>
</div>
