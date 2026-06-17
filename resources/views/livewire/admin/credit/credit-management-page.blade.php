<div>
    <x-slot:title>Credit Control & Management</x-slot:title>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Credit Management</h1>
            <p class="font-body-md text-on-surface-variant">Monitor customer credit limits, outstanding balances, track ledgers, and enforce policies.</p>
        </div>
        <div class="flex gap-md w-full sm:w-auto">
            <x-admin.button variant="outline" icon="download" wire:click="export">Export CSV</x-admin.button>
            <x-admin.button variant="outline" icon="refresh" wire:click="$refresh">Refresh</x-admin.button>
        </div>
    </div>

    <!-- Metrics Bento -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-lg mb-xl">
        <div class="bg-surface-container-lowest p-lg rounded-xl shadow-sm border border-outline-variant/30 flex flex-col gap-sm">
            <span class="text-label-md text-on-surface-variant uppercase font-bold tracking-wider">Total Credit Limit</span>
            <div class="flex items-baseline justify-between">
                <span class="text-headline-md text-primary font-extrabold">₹{{ number_format($stats['total_credit_limit'], 2) }}</span>
            </div>
            <span class="text-xs text-on-surface-variant">Assigned pool limit across active accounts</span>
        </div>
        
        <div class="bg-surface-container-lowest p-lg rounded-xl shadow-sm border border-outline-variant/30 flex flex-col gap-sm">
            <span class="text-label-md text-on-surface-variant uppercase font-bold tracking-wider">Total Utilized</span>
            <div class="flex items-baseline justify-between">
                <span class="text-headline-md text-primary font-extrabold">₹{{ number_format($stats['total_outstanding'], 2) }}</span>
                @php
                    $utilPercent = $stats['total_credit_limit'] > 0 ? ($stats['total_outstanding'] / $stats['total_credit_limit']) * 100 : 0;
                @endphp
                <span class="text-label-md font-bold {{ $utilPercent > 85 ? 'text-error' : 'text-[#0F8A46]' }}">
                    {{ number_format($utilPercent, 1) }}%
                </span>
            </div>
            <div class="w-full bg-surface-container h-1.5 rounded-full overflow-hidden mt-2">
                <div class="h-full {{ $utilPercent > 85 ? 'bg-error' : 'bg-[#0F8A46]' }}" style="width: {{ min(100, $utilPercent) }}%"></div>
            </div>
        </div>
        
        <div class="bg-surface-container-lowest p-lg rounded-xl shadow-sm border border-outline-variant/30 flex flex-col gap-sm">
            <span class="text-label-md text-on-surface-variant uppercase font-bold tracking-wider">Available Credit Pool</span>
            <div class="flex items-baseline justify-between">
                <span class="text-headline-md font-extrabold {{ $stats['total_available'] < 0 ? 'text-error' : 'text-[#0F8A46]' }}">
                    ₹{{ number_format($stats['total_available'], 2) }}
                </span>
            </div>
            <span class="text-xs text-on-surface-variant">Net credit remaining for customers</span>
        </div>

        <div class="bg-surface-container-lowest p-lg rounded-xl shadow-sm border border-outline-variant/30 flex flex-col gap-sm">
            <span class="text-label-md text-on-surface-variant uppercase font-bold tracking-wider">Total Overdue</span>
            <div class="flex items-baseline justify-between">
                <span class="text-headline-md font-extrabold text-error">₹{{ number_format($stats['total_overdue'], 2) }}</span>
            </div>
            <span class="text-xs text-error font-medium">Outstanding beyond payment terms</span>
        </div>
    </div>

    <!-- Alert Counters Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-lg mb-xl">
        <div class="bg-surface-container-low p-md rounded-lg flex items-center justify-between border border-outline-variant/20">
            <span class="text-body-sm text-on-surface-variant font-medium">Near Limit (>=85%)</span>
            <span class="px-sm py-xs bg-warning/20 text-warning-variant font-bold text-sm rounded">{{ $stats['near_limit_count'] }}</span>
        </div>
        <div class="bg-surface-container-low p-md rounded-lg flex items-center justify-between border border-outline-variant/20">
            <span class="text-body-sm text-on-surface-variant font-medium">Over Credit Limit</span>
            <span class="px-sm py-xs bg-error/20 text-error font-bold text-sm rounded">{{ $stats['over_limit_count'] }}</span>
        </div>
        <div class="bg-surface-container-low p-md rounded-lg flex items-center justify-between border border-outline-variant/20">
            <span class="text-body-sm text-on-surface-variant font-medium">Accounts on Hold</span>
            <span class="px-sm py-xs bg-error/20 text-error font-bold text-sm rounded">{{ $stats['on_hold_count'] }}</span>
        </div>
        <div class="bg-surface-container-low p-md rounded-lg flex items-center justify-between border border-outline-variant/20">
            <span class="text-body-sm text-on-surface-variant font-medium">Extended Limit Allowed</span>
            <span class="px-sm py-xs bg-secondary-container text-secondary font-bold text-sm rounded">{{ $stats['extended_credit_count'] }}</span>
        </div>
    </div>

    <!-- Filters & Search -->
    <x-admin.card class="mb-xl">
        <x-slot:bodyClass>p-md flex flex-wrap gap-md items-center justify-between</x-slot:bodyClass>
        
        <div class="flex flex-wrap gap-md items-center w-full lg:w-auto">
            <div class="relative w-full sm:w-64">
                <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant/50">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Customer..." class="w-full pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md focus:ring-2 focus:ring-secondary outline-none transition-all">
            </div>
            
            <select wire:model.live="level_id" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">All Customer Levels</option>
                @foreach ($customerLevels as $level)
                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="credit_status" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">All Credit Statuses</option>
                <option value="healthy">Healthy</option>
                <option value="near_limit">Near Limit</option>
                <option value="over_limit">Over Limit</option>
                <option value="on_hold">On Hold</option>
                <option value="no_credit">No Credit Limit</option>
            </select>

            <select wire:model.live="allow_beyond_limit" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">All Enforcement Modes</option>
                <option value="0">Blocking Mode (Within Limit)</option>
                <option value="1">Approval Mode (Over Limit Allowed)</option>
            </select>
        </div>

        <div>
            <x-admin.button variant="ghost" icon="filter_alt_off" wire:click="resetFilters">Reset Filters</x-admin.button>
        </div>
    </x-admin.card>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="overflow-x-auto">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr>
                        <th class="px-lg py-md cursor-pointer select-none" wire:click="sort('company_name')">
                            Customer
                            @if ($sort_field === 'company_name')
                                <span class="material-symbols-outlined text-[14px] align-middle">{{ $sort_order === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </th>
                        <th class="px-lg py-md">Level</th>
                        <th class="px-lg py-md text-right cursor-pointer select-none" wire:click="sort('credit_limit')">
                            Credit Limit
                            @if ($sort_field === 'credit_limit')
                                <span class="material-symbols-outlined text-[14px] align-middle">{{ $sort_order === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </th>
                        <th class="px-lg py-md text-right cursor-pointer select-none" wire:click="sort('outstanding_amount')">
                            Utilized
                            @if ($sort_field === 'outstanding_amount')
                                <span class="material-symbols-outlined text-[14px] align-middle">{{ $sort_order === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </th>
                        <th class="px-lg py-md text-right cursor-pointer select-none" wire:click="sort('available_credit')">
                            Available
                            @if ($sort_field === 'available_credit')
                                <span class="material-symbols-outlined text-[14px] align-middle">{{ $sort_order === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </th>
                        <th class="px-lg py-md text-right cursor-pointer select-none" wire:click="sort('overdue_amount')">
                            Overdue
                            @if ($sort_field === 'overdue_amount')
                                <span class="material-symbols-outlined text-[14px] align-middle">{{ $sort_order === 'asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </th>
                        <th class="px-lg py-md text-center">Privilege Mode</th>
                        <th class="px-lg py-md text-center">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse ($customers as $customer)
                        @php
                            $avail = (float) $customer->available_credit;
                            $limit = (float) $customer->credit_limit;
                            $outstanding = (float) $customer->outstanding_amount;
                            $overdue = (float) $customer->overdue_amount;

                            // Calculate status helper locally for badge mapping
                            if ($customer->credit_hold) {
                                $badgeColor = 'bg-error-container/20 text-error border-error/30';
                                $statusText = 'On Hold';
                            } elseif ($limit <= 0) {
                                $badgeColor = 'bg-surface-container text-on-surface-variant border-outline-variant/30';
                                $statusText = 'No Limit';
                            } elseif ($outstanding > $limit) {
                                $badgeColor = 'bg-error-container/20 text-error border-error/30';
                                $statusText = 'Over Limit';
                            } elseif ($outstanding >= $limit * 0.85) {
                                $badgeColor = 'bg-warning/20 text-warning-variant border-warning/30';
                                $statusText = 'Near Limit';
                            } else {
                                $badgeColor = 'bg-[#0F8A46]/10 text-[#0F8A46] border-[#0F8A46]/20';
                                $statusText = 'Healthy';
                            }
                        @endphp
                        <tr class="hover:bg-primary/[0.02] transition-colors group">
                            <td class="px-lg py-md">
                                <p class="font-bold text-primary">{{ $customer->company_name }}</p>
                                <p class="font-label-md text-on-surface-variant">{{ $customer->customer_number }}</p>
                            </td>
                            <td class="px-lg py-md text-on-surface-variant">{{ $customer->level?->name ?: 'N/A' }}</td>
                            <td class="px-lg py-md text-right font-bold text-primary">₹{{ number_format($limit, 2) }}</td>
                            <td class="px-lg py-md text-right text-on-surface-variant font-medium">₹{{ number_format($outstanding, 2) }}</td>
                            <td class="px-lg py-md text-right font-semibold {{ $avail < 0 ? 'text-error' : 'text-[#0F8A46]' }}">
                                ₹{{ number_format($avail, 2) }}
                            </td>
                            <td class="px-lg py-md text-right font-semibold {{ $overdue > 0 ? 'text-error' : 'text-on-surface-variant' }}">
                                ₹{{ number_format($overdue, 2) }}
                            </td>
                            <td class="px-lg py-md text-center">
                                <button type="button" wire:click="toggleBeyondLimit({{ $customer->id }})" class="inline-flex items-center px-sm py-[2px] rounded-full text-[11px] font-bold border transition-all hover:bg-opacity-30 {{ $customer->allow_credit_beyond_limit ? 'bg-secondary-container/20 text-secondary border-secondary/30' : 'bg-surface-container text-on-surface-variant border-outline-variant/30' }}">
                                    {{ $customer->allow_credit_beyond_limit ? 'Approval Override' : 'Blocking Mode' }}
                                </button>
                            </td>
                            <td class="px-lg py-md text-center">
                                <span class="inline-flex items-center px-sm py-[2px] rounded-full text-[11px] font-bold border {{ $badgeColor }}">
                                    {{ $statusText }}
                                </span>
                            </td>
                            <td class="px-lg py-md text-right">
                                <x-admin.action-menu>
                                    <x-admin.action-menu-item icon="visibility" label="Credit Profile" wire:click="openLedgerModal({{ $customer->id }})" />
                                    <x-admin.action-menu-item icon="edit" label="Update Credit Limit" wire:click="openLimitModal({{ $customer->id }})" />
                                    <x-admin.action-menu-item icon="payments" label="Record Payment" wire:click="openPaymentModal({{ $customer->id }})" />
                                    <x-admin.action-menu-item icon="published_with_changes" label="Adjust Outstanding" wire:click="openAdjustModal({{ $customer->id }})" />
                                    @if ($customer->credit_hold)
                                        <x-admin.action-menu-item icon="lock_open" label="Release Credit Hold" wire:click="openReleaseModal({{ $customer->id }})" />
                                    @else
                                        <x-admin.action-menu-item icon="lock" label="Apply Credit Hold" wire:click="openHoldModal({{ $customer->id }})" />
                                    @endif
                                </x-admin.action-menu>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-lg py-xl text-center text-on-surface-variant">
                                No customer credit records match the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer class="flex justify-between items-center">
            <span class="font-label-md text-on-surface-variant">
                Showing {{ $customers->firstItem() ?: 0 }} to {{ $customers->lastItem() ?: 0 }} of {{ $customers->total() }} Customers
            </span>
            <div>
                {{ $customers->links() }}
            </div>
        </x-slot:footer>
    </x-admin.card>

    <div class="mt-lg p-lg bg-surface-container-low rounded-xl flex items-start gap-md border border-outline-variant/30">
        <span class="material-symbols-outlined text-secondary text-[24px]">lightbulb</span>
        <div>
            <h4 class="font-title-md text-primary mb-xs">Policy Reminder</h4>
            <p class="font-body-md text-on-surface-variant">Customers in <b>'Blocking Mode'</b> cannot proceed to place new credit orders if they exceed their available credit limit. Placing a customer <b>'On Hold'</b> suspends credit checkouts entirely.</p>
        </div>
    </div>

    <!-- Modal: Update Credit Limit -->
    <x-admin.modal id="edit-credit" title="Update Credit Limit" maxWidth="md">
        @if ($selectedCustomer)
            <form class="space-y-md" wire:submit.prevent="saveLimit">
                <div class="bg-surface-container-lowest p-md rounded-lg border border-outline-variant/30 mb-md">
                    <p class="font-title-md text-primary font-bold">{{ $selectedCustomer->company_name }}</p>
                    <p class="font-label-md text-on-surface-variant">Current Limit: ₹{{ number_format($selectedCustomer->credit_limit, 2) }}</p>
                    <p class="font-label-md text-on-surface-variant">Outstanding: ₹{{ number_format($selectedCustomer->outstanding_amount, 2) }}</p>
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant font-bold">New Credit Limit (₹) *</label>
                    <input type="number" step="0.01" min="0" wire:model="limitForm.credit_limit" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    @error('limitForm.credit_limit') <span class="text-error text-xs font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant font-bold">Reason/Remarks *</label>
                    <textarea rows="3" wire:model="limitForm.note" placeholder="Provide reason for adjustment..." class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface"></textarea>
                    @error('limitForm.note') <span class="text-error text-xs font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-md pt-md border-t border-outline-variant/20">
                    <x-admin.button variant="ghost" type="button" x-on:click="$dispatch('close-modal', 'edit-credit')">Cancel</x-admin.button>
                    <x-admin.button variant="primary" type="submit" icon="save">Update Credit</x-admin.button>
                </div>
            </form>
        @endif
    </x-admin.modal>

    <!-- Modal: Record Payment -->
    <x-admin.modal id="record-payment" title="Record Payment Received" maxWidth="md">
        @if ($selectedCustomer)
            <form class="space-y-md" wire:submit.prevent="savePayment">
                <div class="bg-surface-container-lowest p-md rounded-lg border border-outline-variant/30 mb-md">
                    <p class="font-title-md text-primary font-bold">{{ $selectedCustomer->company_name }}</p>
                    <p class="font-label-md text-error font-bold">Outstanding Balance: ₹{{ number_format($selectedCustomer->outstanding_amount, 2) }}</p>
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant font-bold">Payment Amount (₹) *</label>
                    <input type="number" step="0.01" min="0.01" max="{{ $selectedCustomer->outstanding_amount }}" wire:model="paymentForm.amount" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    @error('paymentForm.amount') <span class="text-error text-xs font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant font-bold">Reference / Payment Note</label>
                    <textarea rows="3" wire:model="paymentForm.note" placeholder="Check number, transaction ID, bank details..." class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface"></textarea>
                    @error('paymentForm.note') <span class="text-error text-xs font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-md pt-md border-t border-outline-variant/20">
                    <x-admin.button variant="ghost" type="button" x-on:click="$dispatch('close-modal', 'record-payment')">Cancel</x-admin.button>
                    <x-admin.button variant="primary" type="submit" icon="payments">Record Payment</x-admin.button>
                </div>
            </form>
        @endif
    </x-admin.modal>

    <!-- Modal: Adjust Outstanding -->
    <x-admin.modal id="adjust-outstanding" title="Adjust Outstanding Balance" maxWidth="md">
        @if ($selectedCustomer)
            <form class="space-y-md" wire:submit.prevent="saveAdjust">
                <div class="bg-surface-container-lowest p-md rounded-lg border border-outline-variant/30 mb-md">
                    <p class="font-title-md text-primary font-bold">{{ $selectedCustomer->company_name }}</p>
                    <p class="font-label-md text-on-surface-variant">Current Outstanding: ₹{{ number_format($selectedCustomer->outstanding_amount, 2) }}</p>
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant font-bold">Adjustment Direction *</label>
                    <select wire:model="adjustForm.direction" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                        <option value="increase">Increase Outstanding (Debit/Invoice Charges)</option>
                        <option value="decrease">Decrease Outstanding (Credit/Write-offs)</option>
                    </select>
                    @error('adjustForm.direction') <span class="text-error text-xs font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant font-bold">Amount (₹) *</label>
                    <input type="number" step="0.01" min="0.01" wire:model="adjustForm.amount" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    @error('adjustForm.amount') <span class="text-error text-xs font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant font-bold">Adjustment Reason/Remarks *</label>
                    <textarea rows="3" wire:model="adjustForm.note" placeholder="Detailed reason for this manually ledgered credit adjustment..." class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface"></textarea>
                    @error('adjustForm.note') <span class="text-error text-xs font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-md pt-md border-t border-outline-variant/20">
                    <x-admin.button variant="ghost" type="button" x-on:click="$dispatch('close-modal', 'adjust-outstanding')">Cancel</x-admin.button>
                    <x-admin.button variant="primary" type="submit" icon="published_with_changes">Save Adjustment</x-admin.button>
                </div>
            </form>
        @endif
    </x-admin.modal>

    <!-- Modal: Place Hold -->
    <x-admin.modal id="place-hold" title="Apply Credit Hold" maxWidth="md">
        @if ($selectedCustomer)
            <form class="space-y-md" wire:submit.prevent="saveHold">
                <div class="p-md bg-error-container/10 border border-error/20 rounded-lg text-error mb-md">
                    <p class="font-title-sm font-bold flex items-center gap-xs">
                        <span class="material-symbols-outlined text-[18px]">warning</span>
                        Suspension Warning
                    </p>
                    <p class="text-xs mt-xs">Applying a credit hold will instantly block the customer from checking out new orders using their credit facility.</p>
                </div>

                <div class="bg-surface-container-lowest p-md rounded-lg border border-outline-variant/30 mb-md">
                    <p class="font-title-md text-primary font-bold">{{ $selectedCustomer->company_name }}</p>
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant font-bold">Reason for Suspension *</label>
                    <textarea rows="3" wire:model="holdForm.reason" placeholder="Write why credit is being suspended (e.g. chronic late payments)..." class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface"></textarea>
                    @error('holdForm.reason') <span class="text-error text-xs font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-md pt-md border-t border-outline-variant/20">
                    <x-admin.button variant="ghost" type="button" x-on:click="$dispatch('close-modal', 'place-hold')">Cancel</x-admin.button>
                    <x-admin.button variant="primary" class="bg-error hover:bg-error/90 text-white border-error" type="submit" icon="lock">Enforce Credit Hold</x-admin.button>
                </div>
            </form>
        @endif
    </x-admin.modal>

    <!-- Modal: Release Hold -->
    <x-admin.modal id="release-hold" title="Release Credit Hold" maxWidth="md">
        @if ($selectedCustomer)
            <form class="space-y-md" wire:submit.prevent="saveRelease">
                <div class="bg-surface-container-lowest p-md rounded-lg border border-outline-variant/30 mb-md">
                    <p class="font-title-md text-primary font-bold">{{ $selectedCustomer->company_name }}</p>
                    <p class="text-xs text-error font-medium mt-xs">Hold applied on {{ $selectedCustomer->credit_hold_at ? $selectedCustomer->credit_hold_at->format('M d, Y') : 'N/A' }} by {{ $selectedCustomer->creditHoldBy?->name ?: 'System' }}</p>
                    @if ($selectedCustomer->credit_hold_reason)
                        <p class="text-xs text-on-surface-variant italic mt-sm">"{{ $selectedCustomer->credit_hold_reason }}"</p>
                    @endif
                </div>

                <div class="space-y-xs">
                    <label class="font-label-md text-on-surface-variant font-bold">Release Comments / Remarks</label>
                    <textarea rows="3" wire:model="releaseForm.note" placeholder="Why is this hold being lifted? (e.g. payment received)..." class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface"></textarea>
                    @error('releaseForm.note') <span class="text-error text-xs font-semibold">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-md pt-md border-t border-outline-variant/20">
                    <x-admin.button variant="ghost" type="button" x-on:click="$dispatch('close-modal', 'release-hold')">Cancel</x-admin.button>
                    <x-admin.button variant="primary" type="submit" icon="lock_open">Release Hold</x-admin.button>
                </div>
            </form>
        @endif
    </x-admin.modal>

    <!-- Modal: View Credit Profile & Ledger -->
    <x-admin.modal id="view-ledger" title="Customer Credit Profile" maxWidth="lg">
        @if ($selectedCustomer)
            <div class="space-y-md">
                <!-- Customer Details -->
                <div class="grid grid-cols-2 gap-md bg-surface-container-lowest p-md rounded-lg border border-outline-variant/30">
                    <div>
                        <h4 class="font-bold text-primary text-base">{{ $selectedCustomer->company_name }}</h4>
                        <p class="text-xs text-on-surface-variant mt-[2px]">{{ $selectedCustomer->customer_number }} | {{ $selectedCustomer->level?->name ?: 'N/A' }}</p>
                    </div>
                    <div class="text-right">
                        @php
                            $limit = (float) $selectedCustomer->credit_limit;
                            $outstanding = (float) $selectedCustomer->outstanding_amount;
                            if ($selectedCustomer->credit_hold) {
                                $lblClass = 'bg-error-container/20 text-error';
                                $lblText = 'On Hold';
                            } elseif ($limit <= 0) {
                                $lblClass = 'bg-surface-container text-on-surface-variant';
                                $lblText = 'No Limit';
                            } elseif ($outstanding > $limit) {
                                $lblClass = 'bg-error-container/20 text-error';
                                $lblText = 'Over Limit';
                            } else {
                                $lblClass = 'bg-[#0F8A46]/10 text-[#0F8A46]';
                                $lblText = 'Healthy';
                            }
                        @endphp
                        <span class="inline-flex px-sm py-[2px] rounded-full text-xs font-bold border {{ $lblClass }}">
                            {{ $lblText }}
                        </span>
                        <p class="text-[11px] text-on-surface-variant mt-[4px]">Risk Rating: 
                            <span class="font-bold">
                                @if ($selectedCustomer->credit_hold || $selectedCustomer->overdue_amount > 0)
                                    High
                                @elseif ($limit > 0 && ($outstanding / $limit) >= 0.7)
                                    Medium
                                @else
                                    Low
                                @endif
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Snapshot Info -->
                <div class="grid grid-cols-3 gap-md bg-surface-container-low/50 p-md rounded-lg border border-outline-variant/20 text-center">
                    <div>
                        <span class="text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">Credit Limit</span>
                        <p class="font-bold text-primary text-sm mt-xs">₹{{ number_format($selectedCustomer->credit_limit, 2) }}</p>
                    </div>
                    <div>
                        <span class="text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">Outstanding</span>
                        <p class="font-bold text-primary text-sm mt-xs">₹{{ number_format($selectedCustomer->outstanding_amount, 2) }}</p>
                    </div>
                    <div>
                        <span class="text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">Available</span>
                        <p class="font-bold text-sm mt-xs {{ $selectedCustomer->available_credit < 0 ? 'text-error' : 'text-[#0F8A46]' }}">
                            ₹{{ number_format($selectedCustomer->available_credit, 2) }}
                        </p>
                    </div>
                </div>

                <!-- Ledger Audit Trail -->
                <div>
                    <h4 class="font-bold text-primary mb-sm text-sm">Recent Ledger Activity</h4>
                    <div class="border border-outline-variant/20 rounded-lg overflow-hidden bg-surface-container-lowest">
                        <table class="w-full text-left text-xs font-body-md">
                            <thead class="bg-surface-container text-on-surface-variant font-bold uppercase tracking-wider border-b border-outline-variant/10">
                                <tr>
                                    <th class="px-md py-sm">Date</th>
                                    <th class="px-md py-sm">Transaction Type</th>
                                    <th class="px-md py-sm text-right">Amount</th>
                                    <th class="px-md py-sm text-right">Balance After</th>
                                    <th class="px-md py-sm">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/10">
                                @if ($ledgerEntries && $ledgerEntries->count() > 0)
                                    @foreach ($ledgerEntries as $entry)
                                        <tr class="hover:bg-primary/[0.01]">
                                            <td class="px-md py-sm text-on-surface-variant white-space-nowrap">
                                                {{ $entry->created_at->format('M d, H:i') }}
                                            </td>
                                            <td class="px-md py-sm font-semibold">
                                                {{ $entry->type_label }}
                                            </td>
                                            <td class="px-md py-sm text-right font-bold {{ $entry->direction === 'debit' ? 'text-error' : ($entry->direction === 'credit' ? 'text-[#0F8A46]' : 'text-on-surface-variant') }}">
                                                {{ $entry->direction === 'debit' ? '+' : ($entry->direction === 'credit' ? '-' : '') }}₹{{ number_format($entry->amount, 2) }}
                                            </td>
                                            <td class="px-md py-sm text-right text-on-surface-variant">
                                                ₹{{ number_format($entry->outstanding_after, 2) }}
                                            </td>
                                            <td class="px-md py-sm text-on-surface-variant max-w-[200px] truncate" title="{{ $entry->note }}">
                                                {{ $entry->note ?: '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="px-md py-md text-center text-on-surface-variant">
                                            No recent credit ledger entries found.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end pt-md border-t border-outline-variant/20">
                    <x-admin.button variant="primary" type="button" x-on:click="$dispatch('close-modal', 'view-ledger')">Close Profile</x-admin.button>
                </div>
            </div>
        @endif
    </x-admin.modal>
</div>
