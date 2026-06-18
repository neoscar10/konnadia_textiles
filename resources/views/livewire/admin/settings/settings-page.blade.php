<div>
    <x-slot:title>Settings</x-slot:title>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Settings</h1>
            <p class="font-body-md text-on-surface-variant">Manage your account security and preferences.</p>
        </div>
    </div>

    <!-- Password Change Section -->
    <x-admin.card>
        <div class="border-b border-outline-variant/20 px-xl py-lg">
            <h2 class="font-title-lg text-primary">Change Password</h2>
            <p class="font-body-sm text-on-surface-variant mt-xs">Update your password to keep your account secure.</p>
        </div>

        <form wire:submit="changePassword" class="p-xl">
            <div class="space-y-lg">
                <!-- Current Password -->
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">Current Password *</label>
                    <input 
                        type="password" 
                        wire:model="currentPassword" 
                        placeholder="Enter your current password"
                        class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface"
                    >
                    @error('currentPassword') 
                        <span class="text-error text-xs">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- New Password -->
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">New Password *</label>
                    <input 
                        type="password" 
                        wire:model="newPassword" 
                        placeholder="Enter a new password (min 8 characters)"
                        class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface"
                    >
                    @error('newPassword') 
                        <span class="text-error text-xs">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant">Confirm New Password *</label>
                    <input 
                        type="password" 
                        wire:model="confirmPassword" 
                        placeholder="Confirm your new password"
                        class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface"
                    >
                    @error('confirmPassword') 
                        <span class="text-error text-xs">{{ $message }}</span> 
                    @enderror
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-md mt-lg pt-lg border-t border-outline-variant/20">
                <button 
                    type="button" 
                    wire:click="resetForm"
                    class="px-lg py-sm rounded-lg border border-outline-variant/50 font-label-md text-on-surface hover:bg-surface-container-low transition-colors"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-lg py-sm rounded-lg bg-primary text-on-primary font-label-md hover:bg-primary/90 transition-colors"
                >
                    Update Password
                </button>
            </div>
        </form>
    </x-admin.card>
</div>
