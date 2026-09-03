<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-[#001229] tracking-tight">Admin WhatsApp Notification Contacts</h1>
            <p class="text-xs text-slate-500 mt-1">Manage admin team phone numbers to receive automated WhatsApp notifications for New Orders, Goods Transfers, and Dispatches.</p>
        </div>
        <button wire:click="openCreateModal" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-[#001229] text-white hover:bg-slate-800 font-bold text-xs rounded-xl shadow-sm transition-all">
            <span class="material-symbols-outlined text-sm">add_call</span> Add Admin Contact
        </button>
    </div>

    <!-- Alert / Flash Message -->
    @if (session()->has('message'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-800 text-xs font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
    @endif

    <!-- Controls Bar -->
    <div class="bg-white rounded-2xl border border-outline-variant/30 p-4 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="relative flex-1 max-w-md">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search contact name, phone number..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-outline-variant/40 rounded-xl focus:bg-white focus:border-[#001229] focus:outline-none transition-all">
        </div>
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <span class="material-symbols-outlined text-sm text-emerald-600">notifications_active</span>
            <span>Total Contacts: {{ $contacts->total() }}</span>
        </div>
    </div>

    <!-- Contacts Table Card -->
    <div class="bg-white rounded-2xl border border-outline-variant/30 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-outline-variant/20 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Admin Contact</th>
                        <th class="py-3.5 px-4">Phone Number</th>
                        <th class="py-3.5 px-4">Notification Preferences</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($contacts as $contact)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <!-- Contact Details -->
                            <td class="py-3.5 px-4">
                                <div class="font-extrabold text-[#001229]">{{ $contact->name }}</div>
                                @if($contact->notes)
                                    <div class="text-[10px] text-slate-400 mt-0.5 line-clamp-1">{{ $contact->notes }}</div>
                                @endif
                            </td>

                            <!-- Phone Number -->
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-700">
                                {{ $contact->phone_number }}
                            </td>

                            <!-- Preferences Tags -->
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @if($contact->notify_new_orders)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60 inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[11px]">shopping_bag</span> New Orders
                                        </span>
                                    @endif

                                    @if($contact->notify_goods_transfers)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200/60 inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[11px]">swap_horiz</span> Goods Transfers
                                        </span>
                                    @endif

                                    @if($contact->notify_order_dispatches)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/60 inline-flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[11px]">local_shipping</span> Dispatches
                                        </span>
                                    @endif

                                    @if(!$contact->notify_new_orders && !$contact->notify_goods_transfers && !$contact->notify_order_dispatches)
                                        <span class="text-[10px] text-slate-400 italic">None selected</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3.5 px-4">
                                <button type="button" wire:click="toggleStatus({{ $contact->id }})" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold transition-all {{ $contact->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $contact->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $contact->is_active ? 'Active' : 'Disabled' }}
                                </button>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" wire:click="editContact({{ $contact->id }})" class="p-1.5 text-slate-500 hover:text-[#001229] hover:bg-slate-100 rounded-lg transition-colors" title="Edit Contact">
                                        <span class="material-symbols-outlined text-sm">edit</span>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $contact->id }})" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors" title="Delete Contact">
                                        <span class="material-symbols-outlined text-sm">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl block mb-2 text-slate-300">contact_phone</span>
                                <p class="font-bold text-slate-600">No Admin Notification Contacts Found</p>
                                <p class="text-xs text-slate-400 mt-1">Click "Add Admin Contact" to register team phone numbers for WhatsApp alerts.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($contacts->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $contacts->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Contact Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-outline-variant/30 space-y-5 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-extrabold text-[#001229]">
                        {{ $editingContactId ? 'Edit Admin Contact' : 'Add New Admin Contact' }}
                    </h3>
                    <button type="button" wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>

                <form wire:submit.prevent="saveContact" class="space-y-4 text-xs">
                    <!-- Contact Name -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Contact / Staff Name <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="name" placeholder="e.g. Operations Manager - Rajesh" class="w-full px-3.5 py-2.5 bg-slate-50 border border-outline-variant/40 rounded-xl focus:bg-white focus:border-[#001229] focus:outline-none transition-all">
                        @error('name') <span class="text-rose-600 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">WhatsApp Phone Number <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="phone_number" placeholder="e.g. +919911041964" class="w-full px-3.5 py-2.5 bg-slate-50 border border-outline-variant/40 rounded-xl focus:bg-white focus:border-[#001229] focus:outline-none font-mono transition-all">
                        <span class="text-[10px] text-slate-400 mt-1 block">Include country code (e.g. +91 for India).</span>
                        @error('phone_number') <span class="text-rose-600 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Subscribed Events Checkboxes -->
                    <div class="space-y-2 bg-slate-50/80 p-3.5 rounded-xl border border-slate-100">
                        <span class="block font-bold text-slate-800 text-[11px] uppercase tracking-wider mb-1">WhatsApp Alert Events</span>
                        
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" wire:model="notify_new_orders" class="w-4 h-4 rounded text-[#001229] focus:ring-0">
                            <span class="font-bold text-slate-700">New Customer Orders</span>
                        </label>

                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" wire:model="notify_goods_transfers" class="w-4 h-4 rounded text-[#001229] focus:ring-0">
                            <span class="font-bold text-slate-700">Stock & Goods Transfers</span>
                        </label>

                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" wire:model="notify_order_dispatches" class="w-4 h-4 rounded text-[#001229] focus:ring-0">
                            <span class="font-bold text-slate-700">Order Dispatch Updates</span>
                        </label>
                    </div>

                    <!-- Active Toggle -->
                    <div class="flex items-center justify-between pt-1">
                        <span class="font-bold text-slate-700">Enable Active Notifications</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Notes / Description (Optional)</label>
                        <textarea wire:model="notes" rows="2" placeholder="e.g. Warehouse lead for dispatched items..." class="w-full px-3.5 py-2 bg-slate-50 border border-outline-variant/40 rounded-xl focus:bg-white focus:border-[#001229] focus:outline-none transition-all"></textarea>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-[#001229] hover:bg-slate-800 rounded-xl transition-colors shadow-xs">
                            {{ $editingContactId ? 'Save Changes' : 'Create Contact' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($confirmingDeletion)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-outline-variant/30 space-y-4">
                <div class="flex items-center gap-3 text-rose-600">
                    <span class="material-symbols-outlined text-2xl">warning</span>
                    <h3 class="text-base font-extrabold text-[#001229]">Confirm Delete Contact</h3>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Are you sure you want to remove this admin contact? They will no longer receive automated WhatsApp alerts for orders, transfers, or dispatches.
                </p>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" wire:click="$set('confirmingDeletion', false)" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="button" wire:click="deleteContact" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-colors shadow-xs">
                        Yes, Delete Contact
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
