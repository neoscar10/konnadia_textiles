<x-admin.modal id="add-choice" title="Add Customers" maxWidth="xl">
    <div class="space-y-lg py-md">
        <div class="text-center">
            <h3 class="font-title-lg text-primary">How would you like to add customers?</h3>
            <p class="text-sm text-on-surface-variant mt-xs">Choose between manual single creation or quick bulk upload via CSV/Excel template.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-lg mt-xl">
            <!-- Option 1: Single Customer -->
            <div class="p-lg bg-surface-container-low hover:bg-surface-container border border-outline-variant/30 rounded-xl transition-all duration-300 flex flex-col justify-between h-48 group cursor-pointer hover:-translate-y-1 shadow-sm" wire:click="startSingleCreation">
                <div>
                    <div class="w-10 h-10 bg-primary/10 rounded flex items-center justify-center mb-md group-hover:bg-primary group-hover:text-white transition-all text-primary">
                        <span class="material-symbols-outlined text-[24px]">person_add</span>
                    </div>
                    <h4 class="font-title-md text-primary">Single Customer</h4>
                    <p class="text-xs text-on-surface-variant mt-sm">Create one customer account manually in the dashboard.</p>
                </div>
                <button class="text-xs font-button text-primary flex items-center gap-xs mt-md group-hover:gap-sm transition-all text-left">
                    Continue <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </button>
            </div>

            <!-- Option 2: Bulk Upload -->
            <div class="p-lg bg-surface-container-low hover:bg-surface-container border border-outline-variant/30 rounded-xl transition-all duration-300 flex flex-col justify-between h-48 group cursor-pointer hover:-translate-y-1 shadow-sm" wire:click="startBulkUpload">
                <div>
                    <div class="w-10 h-10 bg-secondary/10 rounded flex items-center justify-center mb-md group-hover:bg-secondary group-hover:text-white transition-all text-secondary">
                        <span class="material-symbols-outlined text-[24px]">upload_file</span>
                    </div>
                    <h4 class="font-title-md text-primary">Bulk Upload</h4>
                    <p class="text-xs text-on-surface-variant mt-sm">Upload multiple customer profiles using our standard Excel/CSV template.</p>
                </div>
                <button class="text-xs font-button text-secondary flex items-center gap-xs mt-md group-hover:gap-sm transition-all text-left">
                    Upload File <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </button>
            </div>
        </div>
    </div>
</x-admin.modal>
