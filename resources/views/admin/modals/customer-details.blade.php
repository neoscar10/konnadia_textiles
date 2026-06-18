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

                <!-- Credit Hold Badge -->
                @if($selectedCustomer->credit_hold)
                    <x-admin.badge type="danger" class="px-md py-xs rounded-full text-xs font-bold">
                        Credit Hold
                    </x-admin.badge>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-xl">
            <!-- Left Column: Basic Info & Address -->
            <div class="md:col-span-6 space-y-lg">
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
                        <div class="grid grid-cols-2 gap-md">
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
            </div>

            <!-- Right Column: Financials & Status -->
            <div class="md:col-span-6 space-y-lg">
                <!-- Financial Summary Card -->
                <div class="bg-surface-container-low rounded-xl border border-outline-variant/30 p-lg">
                    <div class="flex items-center gap-sm mb-lg pb-sm border-b border-outline-variant/20">
                        <span class="material-symbols-outlined text-primary">payments</span>
                        <h4 class="font-title-md text-primary">Financial Summary</h4>
                    </div>

                    <div class="grid grid-cols-2 gap-lg">
                        <div class="flex flex-col gap-xs p-md bg-surface-container-lowest rounded border border-outline-variant/20">
                            <span class="text-xs font-label-md text-on-surface-variant uppercase tracking-wider">Credit Limit</span>
                            <span class="font-title-lg text-on-surface font-semibold">₹{{ number_format($selectedCustomer->credit_limit, 2) }}</span>
                        </div>
                        <div class="flex flex-col gap-xs p-md bg-surface-container-lowest rounded border border-outline-variant/20">
                            <span class="text-xs font-label-md text-on-surface-variant uppercase tracking-wider">Outstanding</span>
                            <span class="font-title-lg text-error font-semibold">₹{{ number_format($selectedCustomer->outstanding_amount, 2) }}</span>
                        </div>
                        <div class="flex flex-col gap-xs p-md bg-surface-container-lowest rounded border border-outline-variant/20">
                            <span class="text-xs font-label-md text-on-surface-variant uppercase tracking-wider">Available Credit</span>
                            <span class="font-title-lg {{ $selectedCustomer->available_credit > 0 ? 'text-[#0F8A46]' : 'text-error' }} font-semibold">
                                ₹{{ number_format($selectedCustomer->available_credit, 2) }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-xs p-md bg-surface-container-lowest rounded border border-outline-variant/20">
                            <span class="text-xs font-label-md text-on-surface-variant uppercase tracking-wider">Overdue Amount</span>
                            <span class="font-title-lg {{ $selectedCustomer->overdue_amount > 0 ? 'text-error font-bold' : 'text-on-surface-variant' }} font-semibold">
                                ₹{{ number_format($selectedCustomer->overdue_amount, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Rules & Compliance Card -->
                <div class="bg-surface-container-low rounded-xl border border-outline-variant/30 p-lg">
                    <div class="flex items-center gap-sm mb-lg pb-sm border-b border-outline-variant/20">
                        <span class="material-symbols-outlined text-primary">gavel</span>
                        <h4 class="font-title-md text-primary">Rules & Controls</h4>
                    </div>

                    <div class="space-y-md">
                        <div class="flex justify-between items-center py-xs border-b border-outline-variant/10">
                            <span class="font-body-md text-on-surface-variant">Allow Credit Beyond Limit</span>
                            <x-admin.badge type="{{ $selectedCustomer->allow_credit_beyond_limit ? 'success' : 'default' }}">
                                {{ $selectedCustomer->allow_credit_beyond_limit ? 'Yes' : 'No' }}
                            </x-admin.badge>
                        </div>
                        
                        <div class="flex justify-between items-center py-xs border-b border-outline-variant/10">
                            <span class="font-body-md text-on-surface-variant">Credit Hold Status</span>
                            <x-admin.badge type="{{ $selectedCustomer->credit_hold ? 'danger' : 'success' }}">
                                {{ $selectedCustomer->credit_hold ? 'On Hold' : 'Clear' }}
                            </x-admin.badge>
                        </div>

                        @if($selectedCustomer->credit_hold)
                            <div class="p-md bg-error-container/20 rounded border border-error/20 space-y-xs">
                                <div class="flex flex-col">
                                    <span class="text-xs font-label-sm text-error uppercase tracking-wider">Reason for Hold</span>
                                    <p class="font-body-md text-on-surface-variant italic">"{{ $selectedCustomer->credit_hold_reason ?: 'No reason specified.' }}"</p>
                                </div>
                                <div class="grid grid-cols-2 gap-sm pt-xs text-xs text-on-surface-variant">
                                    <div>
                                        <span class="font-semibold">Held By:</span> {{ $selectedCustomer->creditHoldBy->name ?? 'System' }}
                                    </div>
                                    <div>
                                        <span class="font-semibold">Held On:</span> {{ $selectedCustomer->credit_hold_at ? $selectedCustomer->credit_hold_at->format('d M Y') : 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($selectedCustomer->last_credit_review_at)
                            <div class="flex justify-between items-center py-xs text-xs text-on-surface-variant">
                                <span>Last Credit Review:</span>
                                <span class="font-mono">{{ $selectedCustomer->last_credit_review_at->format('d M Y, h:i A') }}</span>
                            </div>
                        @endif
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
