<x-admin.modal id="customer-details" title="Customer Details" maxWidth="5xl">
    @if($selectedCustomer)
        <!-- Header Profile Info -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center pb-xl border-b border-outline-variant/30 gap-md mb-xl">
            <div class="flex items-center gap-lg">
                <div class="w-14 h-14 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[32px]">business</span>
                </div>
                <div>
                    <h2 class="font-headline-md text-primary flex flex-wrap items-center gap-sm">
                        {{ $selectedCustomer->company_name }}
                        <span class="text-on-surface-variant font-body-md">({{ $selectedCustomer->customer_number }})</span>
                    </h2>
                    <p class="font-body-md text-on-surface-variant flex items-center gap-xs">
                        <span class="material-symbols-outlined text-[16px] text-primary-fixed-dim">person</span>
                        {{ $selectedCustomer->contact_person }}
                    </p>
                </div>
            </div>
            
            <div class="flex flex-wrap gap-sm">
                <!-- Level Badge -->
                @if($selectedCustomer->level)
                    <span class="inline-flex items-center px-md py-xs rounded-full bg-primary-fixed-dim/20 text-primary-fixed-variant text-xs font-bold uppercase tracking-wider border border-primary-fixed-dim/30">
                        {{ $selectedCustomer->level->name }}
                    </span>
                @endif
                
                <!-- Status Badge -->
                <x-admin.badge type="{{ $selectedCustomer->is_active ? 'success' : 'default' }}" class="px-md py-xs rounded-full text-xs font-bold">
                    {{ $selectedCustomer->is_active ? 'Active' : 'Inactive' }}
                </x-admin.badge>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-xl">
            <!-- Basic Info & Address -->
            <div class="space-y-lg">
                <!-- Contact & Billing Information Card -->
                <div class="bg-surface-container-low rounded-xl border border-outline-variant/30 p-lg">
                    <div class="flex items-center gap-sm mb-lg pb-sm border-b border-outline-variant/20">
                        <span class="material-symbols-outlined text-primary">contact_page</span>
                        <h4 class="font-title-md text-primary">Contact & Billing Details</h4>
                    </div>
                    
                    <div class="space-y-md">
                        <div class="flex flex-col gap-xs">
                            <span class="text-xs font-label-md text-on-surface-variant uppercase tracking-wider">Primary Contact</span>
                            <span class="font-body-md text-on-surface">{{ $selectedCustomer->contact_person }}</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <span class="text-xs font-label-md text-on-surface-variant uppercase tracking-wider">Phone Number</span>
                                <span class="font-body-md text-on-surface flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-[16px] text-on-surface-variant">phone</span>
                                    {{ $selectedCustomer->mobile_number }}
                                </span>
                            </div>
                            <div class="flex flex-col gap-xs">
                                <span class="text-xs font-label-md text-on-surface-variant uppercase tracking-wider">Email Address</span>
                                <span class="font-body-md text-on-surface truncate flex items-center gap-xs" title="{{ $selectedCustomer->email ?: 'N/A' }}">
                                    <span class="material-symbols-outlined text-[16px] text-on-surface-variant">mail</span>
                                    {{ $selectedCustomer->email ?: 'N/A' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <span class="text-xs font-label-md text-on-surface-variant uppercase tracking-wider">GST Number</span>
                            <span class="font-body-md text-primary font-mono uppercase bg-surface-container-high px-sm py-xs rounded border border-outline-variant/30 inline-self-start">
                                {{ $selectedCustomer->gst_number ?: 'N/A' }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-xs pt-md border-t border-outline-variant/25">
                            <span class="text-xs font-label-md text-on-surface-variant uppercase tracking-wider">Billing Address</span>
                            <span class="font-body-md text-on-surface leading-relaxed">
                                {{ $selectedCustomer->billing_address ?: 'No billing address provided.' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Audit Log Card -->
                <div class="bg-surface-container-low rounded-xl border border-outline-variant/30 p-md flex justify-between items-center text-xs text-on-surface-variant">
                    <div class="flex items-center gap-xs">
                        <span class="material-symbols-outlined text-[16px]">history</span>
                        <span>Created: {{ $selectedCustomer->created_at ? $selectedCustomer->created_at->format('d M Y') : 'N/A' }}</span>
                    </div>
                    <div>
                        <span>Last Updated: {{ $selectedCustomer->updated_at ? $selectedCustomer->updated_at->format('d M Y, h:i A') : 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <x-slot name="footer">
        <x-admin.button variant="ghost" @click="show = false">Close</x-admin.button>
        @if($selectedCustomer)
            <x-admin.button variant="primary" icon="edit" wire:click="edit({{ $selectedCustomer->id }})">Edit Customer</x-admin.button>
        @endif
    </x-slot>
</x-admin.modal>
