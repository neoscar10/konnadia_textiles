<div>
    <x-slot:title>Customers</x-slot:title>
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Customer Directory</h1>
            <p class="font-body-md text-on-surface-variant">Manage wholesale partners and retail distributors.</p>
        </div>
        <div class="flex gap-md w-full sm:w-auto">
            <x-admin.button wire:click="export" variant="outline" icon="download">Export CSV</x-admin.button>
            <x-admin.button wire:click="create" variant="primary" icon="add">Add Customer</x-admin.button>
        </div>
    </div>

    <!-- Filters & Search -->
    <x-admin.card class="mb-xl">
        <x-slot:bodyClass>p-md</x-slot:bodyClass>
        
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-md w-full">
            <div class="flex items-center gap-sm px-md py-xs bg-surface-container-low border border-outline-variant/50 rounded-lg focus-within:ring-2 focus-within:ring-secondary w-full sm:col-span-6 transition-all">
                <span class="material-symbols-outlined text-on-surface-variant/70 text-[20px] select-none">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search ID, Name, Phone..." class="w-full bg-transparent border-none p-0 font-body-md text-on-surface placeholder:text-on-surface-variant/50 focus:ring-0 outline-none h-8">
            </div>
            
            <select wire:model.live="level_id" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none w-full sm:col-span-3">
                <option value="">All Levels</option>
                @foreach($levels as $level)
                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="status" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none w-full sm:col-span-3">
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
                        <th class="px-lg py-md whitespace-nowrap">Customer ID</th>
                        <th class="px-lg py-md whitespace-nowrap">Contact Name</th>
                        <th class="px-lg py-md whitespace-nowrap">Company</th>
                        <th class="px-lg py-md whitespace-nowrap">Phone</th>
                        <th class="px-lg py-md whitespace-nowrap">Level</th>
                        <th class="px-lg py-md text-center whitespace-nowrap">Status</th>
                        <th class="px-lg py-md text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-lg font-bold text-primary whitespace-nowrap">{{ $customer->customer_number }}</td>
                        <td class="px-lg py-lg text-on-surface whitespace-nowrap">{{ $customer->contact_person }}</td>
                        <td class="px-lg py-lg text-on-surface-variant whitespace-nowrap">{{ $customer->company_name }}</td>
                        <td class="px-lg py-lg text-on-surface-variant whitespace-nowrap">{{ $customer->mobile_number }}</td>
                        <td class="px-lg py-lg whitespace-nowrap">
                            <span class="inline-flex items-center px-sm py-xs rounded bg-surface-container-high text-on-surface text-[10px] font-bold uppercase tracking-wider border border-outline-variant/30">{{ $customer->level->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-lg py-lg text-center whitespace-nowrap">
                            <x-admin.badge type="{{ $customer->is_active ? 'success' : 'default' }}">
                                {{ $customer->is_active ? 'Active' : 'Inactive' }}
                            </x-admin.badge>
                        </td>
                        <td class="px-lg py-lg text-right whitespace-nowrap">
                            <x-admin.action-menu>
                                <x-admin.action-menu-item wire:click="showDetails({{ $customer->id }})" icon="visibility" label="View Details" />
                                <x-admin.action-menu-item wire:click="edit({{ $customer->id }})" icon="edit" label="Edit" />
                                <x-admin.action-menu-item wire:click="startResetPassword({{ $customer->id }})" icon="lock_reset" label="Reset Password" />
                                <x-admin.action-menu-item wire:click="toggleStatus({{ $customer->id }})" icon="{{ $customer->is_active ? 'block' : 'check_circle' }}" label="{{ $customer->is_active ? 'Deactivate' : 'Activate' }}" />
                                <x-admin.action-menu-item wire:click="confirmDelete({{ $customer->id }})" icon="delete" label="Delete" class="text-error hover:text-error hover:bg-error/10" />
                            </x-admin.action-menu>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-lg py-2xl text-center text-on-surface-variant">
                            <div class="flex flex-col items-center justify-center">
                                <span class="material-symbols-outlined text-4xl mb-sm text-outline">group_off</span>
                                <p class="font-body-lg">No customers found.</p>
                                <p class="text-sm">Adjust your search or filters to find what you're looking for.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages() || $customers->total() > 0)
        <x-slot:footer class="flex justify-between items-center">
            <span class="font-label-md text-on-surface-variant">Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} customers</span>
            <div class="flex items-center gap-xs">
                {{ $customers->links(data: ['scrollTo' => false]) }}
            </div>
        </x-slot:footer>
        @endif
    </x-admin.card>

    <!-- Modals -->
    @include('admin.modals.customer-details')
    @include('admin.modals.customer-form')
    @include('admin.modals.add-choice')
    @include('admin.modals.bulk-upload')
    @include('admin.modals.edit-bulk-row')
    @include('admin.modals.single-creation-success')
    
    <x-admin.modal id="delete-customer" title="Delete Customer" maxWidth="md">
        <div class="space-y-md">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-error/10 mx-auto mb-lg">
                <span class="material-symbols-outlined text-[32px] text-error">warning</span>
            </div>
            <h3 class="text-center font-title-lg text-on-surface">Are you sure?</h3>
            <p class="text-center font-body-md text-on-surface-variant">
                This customer will no longer appear in active customer listings. Historical records may still reference this customer later.
            </p>
        </div>
        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button wire:click="delete" variant="primary" class="bg-error hover:bg-error/90 text-white border-error">Delete Customer</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <x-admin.modal id="reset-password-modal" title="Reset Customer Password" maxWidth="md">
        <form wire:submit="resetPassword" class="space-y-lg">
            <div class="space-y-xs" x-data="{ showPassword: false }">
                <label class="font-label-md text-on-surface-variant font-bold">New Password *</label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" wire:model="resetForm.password" class="w-full pl-md pr-10 py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all text-on-surface">
                    <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/70 hover:text-on-surface select-none">
                        <span class="material-symbols-outlined text-[20px]" x-text="showPassword ? 'visibility_off' : 'visibility'"></span>
                    </button>
                </div>
                @error('resetForm.password') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs" x-data="{ showConfirmPassword: false }">
                <label class="font-label-md text-on-surface-variant font-bold">Confirm New Password *</label>
                <div class="relative">
                    <input :type="showConfirmPassword ? 'text' : 'password'" wire:model="resetForm.password_confirmation" class="w-full pl-md pr-10 py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all text-on-surface">
                    <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface-variant/70 hover:text-on-surface select-none">
                        <span class="material-symbols-outlined text-[20px]" x-text="showConfirmPassword ? 'visibility_off' : 'visibility'"></span>
                    </button>
                </div>
                @error('resetForm.password_confirmation') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end gap-md pt-md border-t border-outline-variant/20">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="submit" variant="primary">Reset Password</x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
