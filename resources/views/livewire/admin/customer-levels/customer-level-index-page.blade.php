<div>
    <x-slot:title>Customer Levels</x-slot:title>
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Customer Levels</h1>
            <p class="font-body-md text-on-surface-variant">Define discount tiers and ordering rules.</p>
        </div>
        <x-admin.button variant="primary" icon="add" wire:click="create">Add Level</x-admin.button>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-md mb-lg">
        <div class="w-full sm:w-1/3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search levels by name..." class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md">
        </div>
        <div class="w-full sm:w-1/4">
            <select wire:model.live="status" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="w-full overflow-visible pb-32">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr class="whitespace-nowrap text-xs">
                        <th class="px-lg py-md">Level Name</th>
                        <th class="px-lg py-md">Discount %</th>
                        <th class="px-lg py-md text-center">Active Customers</th>
                        <th class="px-lg py-md text-center">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse($levels as $level)
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-lg font-bold text-primary">{{ $level->name }}</td>
                        <td class="px-lg py-lg text-on-surface">
                            @php $disc = (float) $level->discount_percentage; @endphp
                            @if($disc >= 0)
                                <span class="text-success font-semibold">{{ number_format($disc, 2) }}% discount</span>
                            @else
                                <span class="text-error font-semibold">{{ number_format(abs($disc), 2) }}% markup</span>
                            @endif
                        </td>
                        <td class="px-lg py-lg text-center">
                            <span class="font-bold text-on-surface">{{ $level->active_customers_count ?? 0 }}</span>
                        </td>
                        <td class="px-lg py-lg text-center">
                            <x-admin.badge type="{{ $level->is_active ? 'success' : 'default' }}">
                                {{ $level->is_active ? 'Active' : 'Inactive' }}
                            </x-admin.badge>
                        </td>
                        <td class="px-lg py-lg text-right">
                            <x-admin.action-menu>
                                <x-admin.action-menu-item wire:click="edit({{ $level->id }})" icon="edit" label="Edit" />
                                <x-admin.action-menu-item wire:click="toggleStatus({{ $level->id }})" icon="{{ $level->is_active ? 'block' : 'check_circle' }}" label="{{ $level->is_active ? 'Deactivate' : 'Activate' }}" />
                                <x-admin.action-menu-item wire:click="confirmDelete({{ $level->id }})" icon="delete" label="Delete" danger="true" />
                            </x-admin.action-menu>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-lg py-xl text-center text-on-surface-variant font-body-md">
                            No customer levels found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($levels->hasPages())
        <div class="px-lg py-md border-t border-outline-variant/20">
            {{ $levels->links() }}
        </div>
        @endif
    </x-admin.card>

    <!-- Add/Edit Modal -->
    <x-admin.modal id="add-customer-level" title="{{ $editingId ? 'Edit Customer Level' : 'Add Customer Level' }}" maxWidth="2xl">
        <form wire:submit="save" class="space-y-md">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">Level Name *</label>
                    <input type="text" wire:model="form.name" placeholder="e.g. Diamond Partner" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md">
                    @error('form.name') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">Discount / Markup (%) *</label>
                    <input type="number" step="0.01" min="-100" max="100" wire:model="form.discount_percentage" placeholder="0" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md">
                    <p class="text-[11px] text-on-surface-variant/70 leading-snug">
                        <span class="text-success font-semibold">Positive</span> = discount off base price &nbsp;|&nbsp;
                        <span class="text-error font-semibold">Negative</span> = markup above base price (e.g. <em>-10</em> means customers pay 10% more)
                    </p>
                    @error('form.discount_percentage') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Description</label>
                <textarea wire:model="form.description" rows="3" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md"></textarea>
                @error('form.description') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center gap-sm mt-md">
                <input type="checkbox" wire:model="form.is_active" class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
                <label class="font-body-md text-on-surface">Active Status</label>
            </div>
            
            <div class="flex justify-end gap-md mt-xl pt-md border-t border-outline-variant/20">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="save">Save Level</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Delete Confirmation Modal -->
    <x-admin.modal id="delete-customer-level" title="Delete Customer Level" maxWidth="sm">
        <div class="space-y-md">
            <p class="font-body-md text-on-surface">Are you sure you want to delete this level? Ensure no customers are currently assigned to it.</p>
            <p class="font-body-md text-on-surface-variant text-sm">This customer level will no longer be available for assignment. This action can be reversed only if restore functionality is later added.</p>
            
            <div class="flex justify-end gap-md mt-xl pt-md border-t border-outline-variant/20">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="button" variant="primary" wire:click="delete" class="!bg-error hover:!bg-error/90 !text-white" icon="delete">Confirm Delete</x-admin.button>
            </div>
        </div>
    </x-admin.modal>
</div>
