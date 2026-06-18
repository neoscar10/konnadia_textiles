<div>
    <x-slot:title>Customer Details</x-slot:title>
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div class="flex items-center gap-md">
            <a href="{{ route('admin.customers.index') }}" class="w-10 h-10 bg-surface-container-low rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <div>
                <h1 class="font-headline-lg text-primary tracking-tight flex items-center gap-sm">
                    KT-002: Anita Desai
                    <x-admin.badge type="success" class="ml-sm text-xs">Active</x-admin.badge>
                </h1>
                <p class="font-body-md text-on-surface-variant">Desai Textiles</p>
            </div>
        </div>
        <div class="flex gap-md w-full sm:w-auto">
            <x-admin.button variant="outline" icon="print">Print</x-admin.button>
            <x-admin.button variant="primary" icon="save">Save Changes</x-admin.button>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-xl pb-xl">
        <!-- Left Column: Core Information -->
        <div class="col-span-12 lg:col-span-8 space-y-xl">
            <!-- Basic Information -->
            <x-admin.card>
                <x-slot:header class="flex items-center gap-sm bg-surface-container-low/30">
                    <span class="material-symbols-outlined text-primary-fixed-dim">person</span>
                    <h3 class="font-title-md text-primary">Basic Information</h3>
                </x-slot:header>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-on-surface-variant">Primary Contact</label>
                        <p class="font-body-md text-on-surface">{{ $customer->contact_person }}</p>
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-on-surface-variant">Phone Number</label>
                        <p class="font-body-md text-on-surface">{{ $customer->mobile_number }}</p>
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-on-surface-variant">Email Address</label>
                        <p class="font-body-md text-on-surface">{{ $customer->email ?: 'N/A' }}</p>
                    </div>
                </div>
            </x-admin.card>

            <!-- Address Information -->
            <x-admin.card>
                <x-slot:header class="flex items-center gap-sm bg-surface-container-low/30">
                    <span class="material-symbols-outlined text-primary-fixed-dim">location_on</span>
                    <h3 class="font-title-md text-primary">Address Information</h3>
                </x-slot:header>

                <div class="grid grid-cols-1 gap-lg">
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-on-surface-variant">Billing Address</label>
                        <p class="font-body-md text-on-surface">{{ $customer->billing_address ?: 'N/A' }}</p>
                    </div>
                </div>
            </x-admin.card>
        </div>

        <!-- Right Column: Config & Status -->
        <div class="col-span-12 lg:col-span-4 space-y-xl">
            <!-- Business Details -->
            <x-admin.card>
                <x-slot:header class="flex items-center gap-sm bg-surface-container-low/30">
                    <span class="material-symbols-outlined text-primary-fixed-dim">business_center</span>
                    <h3 class="font-title-md text-primary">Business Details</h3>
                </x-slot:header>

                <div class="flex flex-col gap-xs">
                    <label class="font-label-md text-on-surface-variant">GST Number</label>
                    <p class="font-body-md text-on-surface uppercase">{{ $customer->gst_number }}</p>
                </div>
            </x-admin.card>

            <!-- Configuration -->
            <x-admin.card>
                <x-slot:header class="flex items-center gap-sm bg-surface-container-low/30">
                    <span class="material-symbols-outlined text-primary-fixed-dim">settings_suggest</span>
                    <h3 class="font-title-md text-primary">Configuration</h3>
                </x-slot:header>

                <div class="space-y-lg">
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-on-surface-variant">Customer Level</label>
                        <p class="font-body-md text-on-surface">{{ $customer->level->name ?? 'N/A' }}</p>
                    </div>
                    <div class="flex flex-col gap-xs">
                        <label class="font-label-md text-on-surface-variant">Allow Credit Beyond Limit</label>
                        <p class="font-body-md text-on-surface">{{ $customer->allow_credit_beyond_limit ? 'Yes' : 'No' }}</p>
                    </div>
                </div>
            </x-admin.card>

            <!-- Audit Information -->
            <div class="bg-surface-container-low rounded-xl p-lg border border-dashed border-outline-variant">
                <div class="flex items-center gap-sm mb-md">
                    <span class="material-symbols-outlined text-on-surface-variant text-[18px]">history</span>
                    <h3 class="font-label-md text-on-surface-variant uppercase tracking-wider">Audit Log</h3>
                </div>
                <div class="space-y-md">
                    <div class="flex justify-between">
                        <span class="font-label-md text-on-surface-variant">Created On:</span>
                        <span class="font-body-md text-primary">{{ $customer->created_at ? $customer->created_at->format('d M Y, h:i A') : 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-label-md text-on-surface-variant">Last Updated:</span>
                        <span class="font-body-md text-primary">{{ $customer->updated_at ? $customer->updated_at->format('d M Y, h:i A') : 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
