<div>
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none !important;
        }
        .no-scrollbar {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }
    </style>
    <x-slot:title>Admins Management</x-slot:title>

    <!-- ── Header ── -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Admins Management</h1>
            <p class="font-body-md text-on-surface-variant">Create and manage administrative accounts and assign page permissions.</p>
        </div>
        <x-admin.button variant="primary" icon="add" wire:click="create">
            New Admin
        </x-admin.button>
    </div>

    <!-- ── Filters ── -->
    <div class="bg-white rounded-xl card-shadow border border-outline-variant/30 p-lg mb-xl select-none relative">
        <!-- Off-screen dummy inputs to trap browser autocomplete / credential autofill -->
        <div style="position: absolute; left: -9999px; top: -9999px; width: 1px; height: 1px; overflow: hidden;">
            <input type="text" name="fake_email_autofill" autocomplete="username" tabindex="-1" readonly />
            <input type="password" name="fake_password_autofill" autocomplete="current-password" tabindex="-1" readonly />
        </div>

        <div class="flex flex-row items-center gap-md w-full">
            <!-- Search -->
            <div class="flex items-center gap-sm bg-surface-container-low border border-outline-variant/50 rounded-full px-sm focus-within:ring-2 focus-within:ring-secondary focus-within:border-secondary transition-all" style="width: 41.6666%; flex-shrink: 0;">
                <span class="material-symbols-outlined text-on-surface-variant/60 text-[18px] select-none pl-xs">search</span>
                <input type="search" name="admin_search_field_query" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Search by name, email, phone..." class="w-full bg-transparent border-none py-xs pr-xs font-body-md focus:ring-0 focus:outline-none outline-none text-on-surface text-sm">
            </div>
            
            <!-- Status Filter -->
            <select wire:model.live="status" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-full font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none text-sm" style="width: 25%; flex-shrink: 0;">
                <option value="">All Statuses</option>
                <option value="1">Active</option>
                <option value="0">Restricted</option>
            </select>
        </div>
    </div>

    <!-- ── Admins Table ── -->
    <div class="bg-white rounded-xl card-shadow border border-outline-variant/30 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-sm">
                <thead>
                    <tr class="bg-surface-container-low/30 border-b border-outline-variant/30 text-on-surface-variant font-label-md select-none">
                        <th class="px-lg py-md">Name</th>
                        <th class="px-lg py-md">Email / Phone</th>
                        <th class="px-lg py-md">Permissions</th>
                        <th class="px-lg py-md">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 font-body-md text-on-surface">
                    @forelse($admins as $admin)
                        <tr class="hover:bg-surface-container-lowest/50 transition-colors">
                            <td class="px-lg py-md font-bold text-primary">{{ $admin->name }}</td>
                            <td class="px-lg py-md">
                                <div class="font-semibold">{{ $admin->email }}</div>
                                <div class="text-xs text-on-surface-variant mt-xxs">{{ $admin->mobile_number ?: 'No phone number' }}</div>
                            </td>
                            <td class="px-lg py-md">
                                <div class="flex flex-wrap gap-xs select-none">
                                    @php $perms = $admin->permissions->pluck('name')->toArray(); @endphp
                                    @forelse($perms as $perm)
                                        <span class="px-xs py-0.5 bg-secondary/10 text-secondary border border-secondary/20 text-[10px] font-bold rounded-md uppercase">
                                            {{ str_replace('access ', '', $perm) }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-on-surface-variant/60 italic">No page access</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-lg py-md">
                                <button type="button" wire:click="toggleStatus({{ $admin->id }})" class="px-sm py-xs text-xs font-bold rounded-full select-none cursor-pointer {{ $admin->is_active ? 'bg-success/10 text-success border border-success/20' : 'bg-error/10 text-error border border-error/20' }}">
                                    {{ $admin->is_active ? 'Active' : 'Restricted' }}
                                </button>
                            </td>
                            <td class="px-lg py-md text-right">
                                <div class="flex justify-end gap-sm">
                                    <button type="button" wire:click="edit({{ $admin->id }})" class="p-xs text-primary hover:text-secondary transition-colors" title="Edit Admin">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $admin->id }})" class="p-xs text-error hover:text-error/80 transition-colors" title="Delete Admin">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-lg py-xl text-center select-none">
                                <span class="material-symbols-outlined text-[48px] text-on-surface-variant/40 block mb-sm">manage_accounts</span>
                                <p class="font-title-md text-on-surface-variant">No Admin Accounts Found</p>
                                <p class="text-xs text-on-surface-variant/60 mt-xxs">Try adjusting your filters or search query.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($admins->hasPages())
            <div class="px-lg py-md border-t border-outline-variant/20 bg-surface-container-low/20">
                {{ $admins->links() }}
            </div>
        @endif
    </div>

    <!-- ── Add / Edit Admin Modal ── -->
    <x-admin.modal id="add-admin" title="{{ $editingId ? 'Edit Admin' : 'New Admin Account' }}" maxWidth="2.5xl">
        <div class="space-y-xl overflow-y-auto max-h-[550px] p-md no-scrollbar">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                <!-- Name -->
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">Admin Name *</label>
                    <input type="text" wire:model="form.name" placeholder="e.g. John Doe" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    @error('form.name') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Email -->
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">Email Address *</label>
                    <input type="email" wire:model="form.email" placeholder="e.g. john@domain.com" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    @error('form.email') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Mobile -->
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">Mobile Number</label>
                    <input type="text" wire:model="form.mobile_number" placeholder="e.g. +123456789" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    @error('form.mobile_number') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Status Checkbox -->
                <div class="flex items-center gap-xs select-none h-full pt-md md:pt-lg">
                    <input type="checkbox" id="admin-is-active" wire:model="form.is_active" class="w-4 h-4 text-secondary border-outline-variant focus:ring-secondary rounded cursor-pointer">
                    <label for="admin-is-active" class="font-label-md text-on-surface-variant cursor-pointer font-bold">Account is Active</label>
                </div>

                <!-- Password with eye toggle -->
                <div class="space-y-xs" x-data="{ showPass: false }">
                    <label class="font-label-md text-on-surface-variant">
                        Password {{ $editingId ? '(leave blank to keep unchanged)' : '*' }}
                    </label>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'" wire:model="form.password" placeholder="Enter secure password" class="w-full pl-md pr-xl py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface text-sm">
                        <button type="button" @click="showPass = !showPass" class="absolute right-md top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary transition-colors flex items-center justify-center focus:outline-none">
                            <span class="material-symbols-outlined text-[20px]" x-text="showPass ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                    @error('form.password') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Confirm Password with eye toggle -->
                <div class="space-y-xs" x-data="{ showConfirmPass: false }">
                    <label class="font-label-md text-on-surface-variant">
                        Confirm Password {{ $editingId ? '' : '*' }}
                    </label>
                    <div class="relative">
                        <input :type="showConfirmPass ? 'text' : 'password'" wire:model="form.password_confirmation" placeholder="Confirm secure password" class="w-full pl-md pr-xl py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface text-sm">
                        <button type="button" @click="showConfirmPass = !showConfirmPass" class="absolute right-md top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary transition-colors flex items-center justify-center focus:outline-none">
                            <span class="material-symbols-outlined text-[20px]" x-text="showConfirmPass ? 'visibility_off' : 'visibility'"></span>
                        </button>
                    </div>
                    @error('form.password_confirmation') <span class="text-error text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Page Permissions Checklist -->
            <div class="space-y-md border-t border-outline-variant/20 pt-lg">
                <div>
                    <h4 class="font-title-md text-primary">Page Access Authorization</h4>
                    <p class="text-xs text-on-surface-variant mt-xxs">Specify which dashboard sections this administrator is authorized to access.</p>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-md bg-surface-container-low/40 p-lg border border-outline-variant/20 rounded-lg">
                    @foreach($availablePermissions as $permission => $label)
                        <div class="flex items-center gap-xs select-none">
                            <input type="checkbox" id="perm-{{ $permission }}" value="{{ $permission }}" wire:model="selectedPermissions" class="w-4 h-4 text-secondary border-outline-variant focus:ring-secondary rounded cursor-pointer">
                            <label for="perm-{{ $permission }}" class="font-label-md text-on-surface-variant cursor-pointer font-medium">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button variant="primary" icon="save" wire:click="save">Save Account</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <!-- ── Delete Admin Modal ── -->
    <x-admin.modal id="delete-admin" title="Delete Admin Account" maxWidth="md">
        <div class="space-y-md text-center p-md">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-error/10 mx-auto mb-lg text-error select-none">
                <span class="material-symbols-outlined text-[32px]">delete_forever</span>
            </div>
            <h3 class="font-title-lg text-on-surface">Permanently delete account?</h3>
            <p class="font-body-md text-on-surface-variant">
                This administrator user will be permanently deleted and will lose all access immediately. This action cannot be undone.
            </p>
        </div>
        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button wire:click="delete" variant="primary" class="bg-error hover:bg-error/90 text-white border-error">Delete Account</x-admin.button>
        </x-slot>
    </x-admin.modal>
</div>
