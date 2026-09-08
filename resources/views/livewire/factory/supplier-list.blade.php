<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header / Subtitle Navigation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs text-on-surface-variant font-medium">
            <a href="{{ route('factory.raw-materials.index') }}" wire:navigate class="hover:text-primary transition-colors">Raw Materials</a>
            <span>/</span>
            <span class="font-bold text-on-surface">Suppliers</span>
        </div>
    </div>

    <!-- Page Banner / Header Card -->
    <div class="p-6 bg-surface rounded-2xl border border-outline-variant/60 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-800 text-[10px] font-black uppercase tracking-wider mb-2 border border-amber-500/20">
                <span>NEW</span>
                <span>•</span>
                <span>FROM CLIENT FEEDBACK #2</span>
            </div>
            <h1 class="text-2xl font-black text-on-surface tracking-tight font-display">Suppliers</h1>
            <p class="text-xs text-on-surface-variant max-w-2xl mt-1 leading-relaxed">
                One record per vendor — replaces free-typed supplier names on Purchase Entry, and can grant a login so a supplier uploads their own product photos.
            </p>
        </div>

        <button type="button" wire:click="createSupplier" class="inline-flex items-center gap-2 bg-[#001229] hover:bg-slate-800 text-white font-extrabold text-xs px-5 py-3 rounded-xl transition-all shadow-sm active:scale-95 shrink-0">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>+ Add Supplier</span>
        </button>
    </div>

    <!-- Search & Filter Control Bar -->
    <div class="p-4 bg-surface rounded-2xl border border-outline-variant/60 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="relative w-full sm:w-80">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px]">search</span>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name, email, phone, GST..." class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl pl-10 pr-4 py-2 text-xs font-bold text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20">
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
            <label class="text-xs font-bold text-on-surface-variant shrink-0">Portal Access:</label>
            <select wire:model.live="portalAccessFilter" class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3 py-2 text-xs font-bold text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20">
                <option value="">All Statuses</option>
                <option value="not_invited">Not Invited</option>
                <option value="enabled">Enabled</option>
                <option value="disabled">Disabled</option>
            </select>
        </div>
    </div>

    <!-- Suppliers Table Card -->
    <div class="bg-surface rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant/60 text-on-surface-variant font-bold uppercase tracking-wider text-[10px]">
                        <th class="px-6 py-3.5">Supplier</th>
                        <th class="px-6 py-3.5">WhatsApp / Phone</th>
                        <th class="px-6 py-3.5">Email</th>
                        <th class="px-6 py-3.5 text-center">Portal Access</th>
                        <th class="px-6 py-3.5 text-center">Linked Materials</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-surface-container/40 transition-colors">
                            <!-- Supplier Name & Contact Person -->
                            <td class="px-6 py-4">
                                <div class="font-extrabold text-on-surface text-sm">{{ $supplier->name }}</div>
                                @if($supplier->contact_person)
                                    <div class="text-[11px] text-on-surface-variant font-medium mt-0.5">
                                        Contact: {{ $supplier->contact_person }}
                                    </div>
                                @endif
                                @if($supplier->gstin)
                                    <div class="text-[10px] font-mono text-outline mt-0.5 uppercase">
                                        GSTIN: {{ $supplier->gstin }}
                                    </div>
                                @endif
                            </td>

                            <!-- WhatsApp / Phone -->
                            <td class="px-6 py-4 font-mono font-bold text-on-surface">
                                @if($supplier->whatsapp_number)
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-emerald-600 text-[16px]">chat</span>
                                        {{ $supplier->whatsapp_number }}
                                    </span>
                                @else
                                    <span class="text-outline text-[11px] font-normal italic">-- Not Provided --</span>
                                @endif
                            </td>

                            <!-- Email -->
                            <td class="px-6 py-4 text-on-surface">
                                @if($supplier->email)
                                    <span class="font-semibold text-primary underline underline-offset-2">{{ $supplier->email }}</span>
                                @else
                                    <span class="text-outline text-[11px] italic">-- Not Provided --</span>
                                @endif
                            </td>

                            <!-- Portal Access Badge -->
                            <td class="px-6 py-4 text-center">
                                @if($supplier->portal_access === 'enabled')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-700 border border-emerald-500/20 font-black text-[10px] uppercase tracking-wider">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Enabled
                                    </span>
                                @elseif($supplier->portal_access === 'disabled')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-rose-500/10 text-rose-700 border border-rose-500/20 font-black text-[10px] uppercase tracking-wider">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Disabled
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-amber-500/10 text-amber-800 border border-amber-500/20 font-black text-[10px] uppercase tracking-wider">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Not Invited
                                    </span>
                                @endif
                            </td>

                            <!-- Linked Materials Count -->
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-surface-container-low border border-outline-variant/40 rounded-lg font-black text-primary text-xs">
                                    {{ $supplier->inventory_batches_count ?? 0 }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2 justify-end">
                                    <button type="button" wire:click="editSupplier({{ $supplier->id }})" class="p-2 text-primary hover:bg-primary/10 rounded-xl transition-colors" title="Edit Supplier">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $supplier->id }})" class="p-2 text-rose-600 hover:bg-rose-500/10 rounded-xl transition-colors" title="Delete Supplier">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <span class="material-symbols-outlined text-4xl text-outline">local_shipping</span>
                                    <p class="font-bold text-sm">No suppliers found.</p>
                                    <p class="text-xs text-outline">Click "+ Add Supplier" to create your first vendor record.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($suppliers->hasPages())
            <div class="p-4 border-t border-outline-variant/60 bg-surface-container-lowest">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Supplier Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-surface rounded-2xl border border-outline-variant/60 shadow-xl w-full max-w-xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-surface-container-low border-b border-outline-variant/60 flex items-center justify-between">
                    <div>
                        <h3 class="font-extrabold text-on-surface text-base">
                            {{ $editingSupplierId ? 'Edit Supplier' : 'Add Supplier' }}
                        </h3>
                        <p class="text-[11px] text-on-surface-variant">Configure vendor contact information and portal access settings.</p>
                    </div>
                    <button type="button" wire:click="$set('showModal', false)" class="text-outline hover:text-on-surface p-1 rounded-lg">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Modal Form Body -->
                <form wire:submit.prevent="saveSupplier" class="p-6 space-y-4">
                    <!-- Supplier Name -->
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Supplier / Vendor Name *</label>
                        <input type="text" wire:model="name" placeholder="e.g. Fabric Co. / Thread World" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                        @error('name') <span class="text-rose-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Contact Person & WhatsApp -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Contact Person</label>
                            <input type="text" wire:model="contact_person" placeholder="e.g. Rajesh Kumar" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                            @error('contact_person') <span class="text-rose-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">WhatsApp / Mobile Number</label>
                            <input type="text" wire:model="whatsapp_number" placeholder="e.g. +91 98765 43210" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                            @error('whatsapp_number') <span class="text-rose-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Email & GSTIN -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Email Address</label>
                            <input type="email" wire:model="email" placeholder="e.g. orders@fabricco.in" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                            @error('email') <span class="text-rose-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">GSTIN / Tax ID</label>
                            <input type="text" wire:model="gstin" placeholder="e.g. 07AAAAA0000A1Z5" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-on-surface uppercase focus:ring-2 focus:ring-primary/20">
                            @error('gstin') <span class="text-rose-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Physical Address / City</label>
                        <input type="text" wire:model="address" placeholder="e.g. Plot 45, Textile Hub, Surat, Gujarat" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20">
                    </div>

                    <!-- DUMMY PORTAL ACCESS TOGGLE & STATUS PICKER -->
                    <div class="p-4 bg-surface-container-low border border-outline-variant/60 rounded-xl space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-extrabold text-xs text-on-surface block">Grant Supplier Portal Access</span>
                                <p class="text-[10px] text-on-surface-variant mt-0.5">Allows vendor to login to upload product photos and view supply history.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="checkbox" wire:model.live="grant_portal_access" class="sr-only peer">
                                <div class="w-10 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-2 border-t border-outline-variant/40">
                            <div>
                                <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Access Status</label>
                                <select wire:model="portal_access" class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-lg px-2.5 py-1.5 text-xs font-bold text-on-surface">
                                    <option value="not_invited">Not Invited</option>
                                    <option value="enabled">Enabled</option>
                                    <option value="disabled">Disabled</option>
                                </select>
                            </div>
                            <div class="text-[10px] text-outline flex items-center italic">
                                Note: Portal access authentication is dummy for now.
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-[11px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Notes / Terms</label>
                        <textarea wire:model="notes" rows="2" placeholder="Optional notes regarding supply terms or payment preferences..." class="w-full bg-surface-container-lowest border border-outline-variant/60 rounded-xl px-3.5 py-2 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20"></textarea>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-3 border-t border-outline-variant/40 flex items-center justify-end gap-3">
                        <button type="button" wire:click="$set('showModal', false)" class="px-5 py-2 bg-surface-container-low border border-outline-variant/60 hover:bg-surface-container text-on-surface font-bold text-xs rounded-xl transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-2 bg-[#001229] hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl transition-all shadow-sm">
                            {{ $editingSupplierId ? 'Update Supplier' : 'Create Supplier' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($confirmingDeletionId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-surface rounded-2xl border border-outline-variant/60 shadow-xl w-full max-w-sm p-6 space-y-4 text-center">
                <div class="w-12 h-12 rounded-full bg-rose-500/10 text-rose-600 flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-2xl">warning</span>
                </div>
                <div>
                    <h3 class="font-black text-on-surface text-base">Delete Supplier?</h3>
                    <p class="text-xs text-on-surface-variant mt-1">Are you sure you want to delete this supplier record? Historical inventory batches linked to this supplier will be preserved.</p>
                </div>
                <div class="flex items-center justify-center gap-3 pt-2">
                    <button type="button" wire:click="$set('confirmingDeletionId', null)" class="px-5 py-2 bg-surface-container-low border border-outline-variant/60 text-on-surface font-bold text-xs rounded-xl">
                        Cancel
                    </button>
                    <button type="button" wire:click="deleteSupplier" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-sm">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
