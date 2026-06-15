<x-admin.modal id="single-creation-success" title="Customer Account Created" maxWidth="md">
    <div class="space-y-lg py-md text-center">
        <div class="w-16 h-16 bg-success/15 rounded-full flex items-center justify-center mx-auto text-success mb-md animate-bounce">
            <span class="material-symbols-outlined text-[36px]">check_circle</span>
        </div>
        
        <h3 class="font-title-lg text-primary">Success!</h3>
        <p class="text-sm text-on-surface-variant">The customer account has been created. Below are the auto-generated login credentials for this user.</p>
        
        <div class="bg-surface-container-low border border-outline-variant/30 rounded-lg p-md text-left mt-lg space-y-sm">
            <div>
                <label class="text-[10px] uppercase font-bold tracking-wider text-on-surface-variant">Username / Mobile</label>
                <div class="font-body-md text-on-surface font-semibold select-all">{{ $form['mobile_number'] ?? '' }}</div>
            </div>
            <div>
                <label class="text-[10px] uppercase font-bold tracking-wider text-on-surface-variant">Generated Password</label>
                <div class="flex items-center justify-between gap-md bg-white border border-outline-variant/30 rounded px-sm py-xs mt-xs">
                    <span class="font-mono text-primary font-bold text-lg select-all" id="single-pass-field">{{ $singleCreatedPassword }}</span>
                    <button type="button" onclick="navigator.clipboard.writeText('{{ $singleCreatedPassword }}');" class="p-xs text-on-surface-variant hover:text-primary transition-colors flex items-center gap-xs">
                        <span class="material-symbols-outlined text-[18px]">content_copy</span>
                        <span class="text-[11px] font-bold">Copy</span>
                    </button>
                </div>
            </div>
        </div>
        
        <p class="text-[11px] text-error font-medium mt-md">⚠️ This password is shown only once and is not stored in plain text. Please copy it before closing.</p>
    </div>
    
    <x-slot name="footer">
        <div class="w-full flex justify-center">
            <x-admin.button type="button" variant="primary" wire:click="closeSuccessModal">Close and Reset</x-admin.button>
        </div>
    </x-slot>
</x-admin.modal>
