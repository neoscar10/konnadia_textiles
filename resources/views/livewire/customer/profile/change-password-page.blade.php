<div class="max-w-xl mx-auto">
    <!-- Page Title -->
    <x-customer.page-title 
        title="Change Security Password" 
        subtitle="Ensure your distributor account security by keeping passwords updated."
        :breadcrumbs="[
            'Home' => route('customer.dashboard'), 
            'Profile' => route('customer.profile.show'),
            'Password' => '#'
        ]"
    />

    <!-- Change Password Form -->
    <x-customer.card bodyClass="p-6">
        <form x-data="{ currentVisible: false, newVisible: false, confirmVisible: false }" class="space-y-5">
            <!-- Current Password -->
            <div>
                <label class="text-xs text-slate-500 font-bold block mb-1.5">Current Account Password</label>
                <div class="relative">
                    <input :type="currentVisible ? 'text' : 'password'" placeholder="••••••••" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-3 pr-10 focus:outline-none focus:ring-1 focus:ring-gold">
                    <button type="button" @click="currentVisible = !currentVisible" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                        <span class="material-symbols-outlined text-lg" x-text="currentVisible ? 'visibility_off' : 'visibility'">visibility</span>
                    </button>
                </div>
            </div>

            <!-- New Password -->
            <div>
                <label class="text-xs text-slate-500 font-bold block mb-1.5">New Password</label>
                <div class="relative">
                    <input :type="newVisible ? 'text' : 'password'" placeholder="••••••••" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-3 pr-10 focus:outline-none focus:ring-1 focus:ring-gold">
                    <button type="button" @click="newVisible = !newVisible" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                        <span class="material-symbols-outlined text-lg" x-text="newVisible ? 'visibility_off' : 'visibility'">visibility</span>
                    </button>
                </div>
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="text-xs text-slate-500 font-bold block mb-1.5">Confirm New Password</label>
                <div class="relative">
                    <input :type="confirmVisible ? 'text' : 'password'" placeholder="••••••••" class="w-full text-xs bg-slate-50 border border-outline-variant/30 rounded-lg p-3 pr-10 focus:outline-none focus:ring-1 focus:ring-gold">
                    <button type="button" @click="confirmVisible = !confirmVisible" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                        <span class="material-symbols-outlined text-lg" x-text="confirmVisible ? 'visibility_off' : 'visibility'">visibility</span>
                    </button>
                </div>
            </div>

            <!-- Security criteria -->
            <div class="p-3 bg-slate-50 border border-outline-variant/10 rounded-xl space-y-2">
                <span class="text-[10px] text-slate-400 font-semibold uppercase block">Security Requirements</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[10px] font-semibold text-slate-600">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                        <span>At least 8 characters long</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                        <span>At least 1 uppercase letter</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-emerald-600 text-sm">check_circle</span>
                        <span>At least 1 number / symbol</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-slate-300 text-sm">circle</span>
                        <span>Passwords match</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="pt-3 flex flex-col sm:flex-row items-center justify-end gap-3 border-t border-slate-50">
                <a href="{{ route('customer.profile.show') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2 rounded-lg text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
                    Cancel
                </a>
                <button type="button" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 rounded-lg text-xs font-bold bg-[#001229] text-white hover:bg-slate-800 transition-colors shadow-sm">
                    Update Password
                </button>
            </div>
        </form>
    </x-customer.card>
</div>
