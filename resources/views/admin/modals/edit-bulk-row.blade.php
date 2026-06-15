<x-admin.modal id="edit-bulk-row" title="Edit Row Information" maxWidth="2xl">
    <form wire:submit.prevent="saveBulkRow" class="space-y-xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Company Name *</label>
                <input type="text" wire:model="editingRow.company_name" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                @error('editingRow.company_name') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">GST Number *</label>
                <input type="text" wire:model="editingRow.gst_number" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all uppercase">
                @error('editingRow.gst_number') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Contact Person *</label>
                <input type="text" wire:model="editingRow.contact_person" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                @error('editingRow.contact_person') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Mobile Number *</label>
                <input type="text" wire:model="editingRow.mobile_number" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                @error('editingRow.mobile_number') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs md:col-span-2">
                <label class="font-label-md text-on-surface-variant">Email Address</label>
                <input type="email" wire:model="editingRow.email" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                @error('editingRow.email') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Customer Level *</label>
                <input type="text" wire:model="editingRow.customer_level_name" placeholder="e.g. Retailer" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                @error('editingRow.customer_level_name') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Credit Limit</label>
                <input type="number" step="0.01" wire:model="editingRow.credit_limit" placeholder="Leave blank for level default" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                @error('editingRow.credit_limit') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Allow Credit Beyond Limit</label>
                <select wire:model="editingRow.allow_credit_beyond_limit" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Active Status</label>
                <select wire:model="editingRow.active_status" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div class="space-y-xs md:col-span-2">
                <label class="font-label-md text-on-surface-variant">Billing Address</label>
                <textarea wire:model="editingRow.billing_address" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all" rows="2"></textarea>
            </div>

            <div class="space-y-xs md:col-span-2">
                <label class="font-label-md text-on-surface-variant">Manual Password (Optional)</label>
                <input type="text" wire:model="editingRow.password" placeholder="Leave blank to generate automatically" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                @error('editingRow.password') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex justify-end gap-md mt-xl pt-md border-t border-outline-variant/20">
            <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button type="submit" variant="primary" icon="save">Save Row</x-admin.button>
        </div>
    </form>
</x-admin.modal>
