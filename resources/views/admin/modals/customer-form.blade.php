<x-admin.modal id="add-customer" title="{{ $editingId ? 'Edit Customer' : 'Add New Customer' }}" maxWidth="2xl">
    <form wire:submit="save" class="space-y-xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
            
            @if(count($levels) === 0)
            <div class="md:col-span-2 bg-error/10 border border-error/20 p-md rounded-lg text-error text-sm">
                Create at least one active customer level before adding customers.
            </div>
            @endif

            <!-- Company Information -->
            <div class="space-y-sm md:col-span-2">
                <h4 class="font-title-md text-primary border-b border-outline-variant/30 pb-xs">Company Information</h4>
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Company Name *</label>
                <input type="text" wire:model="form.company_name" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                @error('form.company_name') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">GST Number *</label>
                <input type="text" wire:model="form.gst_number" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all uppercase">
                @error('form.gst_number') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- Contact Information -->
            <div class="space-y-sm md:col-span-2 mt-md">
                <h4 class="font-title-md text-primary border-b border-outline-variant/30 pb-xs">Primary Contact</h4>
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Contact Person *</label>
                <input type="text" wire:model="form.contact_person" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                @error('form.contact_person') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Mobile Number *</label>
                <input type="tel" wire:model="form.mobile_number" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                @error('form.mobile_number') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs md:col-span-2">
                <label class="font-label-md text-on-surface-variant">Email Address</label>
                <input type="email" wire:model="form.email" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                @error('form.email') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- Business Terms -->
            <div class="space-y-sm md:col-span-2 mt-md">
                <h4 class="font-title-md text-primary border-b border-outline-variant/30 pb-xs">Business Terms</h4>
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Customer Level *</label>
                <select wire:model.live="form.customer_level_id" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                    <option value="">Select Level</option>
                    @foreach($levels as $level)
                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                    @endforeach
                </select>
                @error('form.customer_level_id') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- Credit Limit inputs bypassed -->
            
            <div class="space-y-xs md:col-span-2">
                <label class="font-label-md text-on-surface-variant">Street Address *</label>
                <textarea wire:model="form.address" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all" rows="2" placeholder="Street, building, area..."></textarea>
                @error('form.address') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">City *</label>
                <input type="text" wire:model="form.city" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all" placeholder="City">
                @error('form.city') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">State *</label>
                <input type="text" wire:model="form.state" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all" placeholder="State">
                @error('form.state') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs md:col-span-2">
                <label class="font-label-md text-on-surface-variant">PIN Code *</label>
                <input type="text" wire:model="form.pincode" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all" placeholder="6-digit PIN code">
                @error('form.pincode') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>
            
            <div class="space-y-xs md:col-span-2 mt-xs">
                <div class="flex items-center gap-sm">
                    <input type="checkbox" wire:model="form.is_active" id="is_active" class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
                    <label for="is_active" class="font-body-md text-on-surface cursor-pointer">Active Status</label>
                </div>
            </div>

            @if(!$editingId)
            <!-- Password Setup -->
            <div class="space-y-sm md:col-span-2 mt-md">
                <h4 class="font-title-md text-primary border-b border-outline-variant/30 pb-xs">Password Setup</h4>
            </div>

            <div class="space-y-xs md:col-span-2">
                <div class="flex items-center gap-lg">
                    <label class="flex items-center gap-xs cursor-pointer font-body-md text-on-surface">
                        <input type="radio" wire:model.live="form.password_mode" value="auto" class="w-4 h-4 text-secondary focus:ring-secondary">
                        Generate password automatically
                    </label>
                    <label class="flex items-center gap-xs cursor-pointer font-body-md text-on-surface">
                        <input type="radio" wire:model.live="form.password_mode" value="manual" class="w-4 h-4 text-secondary focus:ring-secondary">
                        Set password manually
                    </label>
                </div>
            </div>

            @if(($form['password_mode'] ?? 'auto') === 'manual')
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Password *</label>
                <input type="password" wire:model="form.password" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                @error('form.password') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Confirm Password *</label>
                <input type="password" wire:model="form.password_confirmation" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all">
                @error('form.password_confirmation') <span class="text-error text-xs">{{ $message }}</span> @enderror
            </div>
            @endif
            @endif
        </div>

        <div class="flex justify-end gap-md mt-xl pt-md border-t border-outline-variant/20">
            <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button type="submit" variant="primary" icon="save" :disabled="count($levels) === 0">Save Customer</x-admin.button>
        </div>
    </form>
</x-admin.modal>
