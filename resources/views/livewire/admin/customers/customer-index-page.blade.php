<div>
    <x-slot:title>Customers</x-slot:title>
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Customer Directory</h1>
            <p class="font-body-md text-on-surface-variant">Manage wholesale partners, retail distributors, and credit limits.</p>
        </div>
        <div class="flex gap-md w-full sm:w-auto">
            <x-admin.button wire:click="export" variant="outline" icon="download">Export CSV</x-admin.button>
            <x-admin.button wire:click="create" variant="primary" icon="add">Add Customer</x-admin.button>
        </div>
    </div>

    <!-- Filters & Search -->
    <x-admin.card class="mb-xl">
        <x-slot:bodyClass>p-md</x-slot:bodyClass>
        
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-md w-full">
            <div class="flex items-center gap-sm px-md py-xs bg-surface-container-low border border-outline-variant/50 rounded-lg focus-within:ring-2 focus-within:ring-secondary w-full sm:col-span-6 transition-all">
                <span class="material-symbols-outlined text-on-surface-variant/70 text-[20px] select-none">search</span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search ID, Name, Phone..." class="w-full bg-transparent border-none p-0 font-body-md text-on-surface placeholder:text-on-surface-variant/50 focus:ring-0 outline-none h-8">
            </div>
            
            <select wire:model.live="level_id" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none w-full sm:col-span-3">
                <option value="">All Levels</option>
                @foreach($levels as $level)
                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="status" class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none w-full sm:col-span-3">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </x-admin.card>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="w-full overflow-x-auto pb-32">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr class="whitespace-nowrap text-xs">
                        <th class="px-lg py-md whitespace-nowrap">Customer ID</th>
                        <th class="px-lg py-md whitespace-nowrap">Contact Name</th>
                        <th class="px-lg py-md whitespace-nowrap">Company</th>
                        <th class="px-lg py-md whitespace-nowrap">Phone</th>
                        <th class="px-lg py-md whitespace-nowrap">Level</th>
                        <th class="px-lg py-md text-right whitespace-nowrap">Credit Limit</th>
                        <th class="px-lg py-md text-center whitespace-nowrap">Status</th>
                        <th class="px-lg py-md text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-lg font-bold text-primary whitespace-nowrap">{{ $customer->customer_number }}</td>
                        <td class="px-lg py-lg text-on-surface whitespace-nowrap">{{ $customer->contact_person }}</td>
                        <td class="px-lg py-lg text-on-surface-variant whitespace-nowrap">{{ $customer->company_name }}</td>
                        <td class="px-lg py-lg text-on-surface-variant whitespace-nowrap">{{ $customer->mobile_number }}</td>
                        <td class="px-lg py-lg whitespace-nowrap">
                            <span class="inline-flex items-center px-sm py-xs rounded bg-surface-container-high text-on-surface text-[10px] font-bold uppercase tracking-wider border border-outline-variant/30">{{ $customer->level->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-lg py-lg text-right font-medium whitespace-nowrap">₹{{ number_format($customer->credit_limit, 2) }}</td>
                        <td class="px-lg py-lg text-center whitespace-nowrap">
                            <x-admin.badge type="{{ $customer->is_active ? 'success' : 'default' }}">
                                {{ $customer->is_active ? 'Active' : 'Inactive' }}
                            </x-admin.badge>
                        </td>
                        <td class="px-lg py-lg text-right whitespace-nowrap">
                            <x-admin.action-menu>
                                <x-admin.action-menu-item href="{{ route('admin.customers.show', $customer) }}" icon="visibility" label="View Details" />
                                <x-admin.action-menu-item wire:click="edit({{ $customer->id }})" icon="edit" label="Edit" />
                                <x-admin.action-menu-item wire:click="toggleStatus({{ $customer->id }})" icon="{{ $customer->is_active ? 'block' : 'check_circle' }}" label="{{ $customer->is_active ? 'Deactivate' : 'Activate' }}" />
                                <x-admin.action-menu-item wire:click="confirmDelete({{ $customer->id }})" icon="delete" label="Delete" class="text-error hover:text-error hover:bg-error/10" />
                            </x-admin.action-menu>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-lg py-2xl text-center text-on-surface-variant">
                            <div class="flex flex-col items-center justify-center">
                                <span class="material-symbols-outlined text-4xl mb-sm text-outline">group_off</span>
                                <p class="font-body-lg">No customers found.</p>
                                <p class="text-sm">Adjust your search or filters to find what you're looking for.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages() || $customers->total() > 0)
        <x-slot:footer class="flex justify-between items-center">
            <span class="font-label-md text-on-surface-variant">Showing {{ $customers->firstItem() ?? 0 }} to {{ $customers->lastItem() ?? 0 }} of {{ $customers->total() }} customers</span>
            <div class="flex items-center gap-xs">
                {{ $customers->links(data: ['scrollTo' => false]) }}
            </div>
        </x-slot:footer>
        @endif
    </x-admin.card>

    <!-- Dashboard Snapshot Widget -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg mt-xl">
        <div class="lg:col-span-2 bg-surface-container-lowest p-lg rounded-xl card-shadow border border-outline-variant/30 relative overflow-hidden h-48 group">
            <div class="relative z-10">
                <h3 class="font-title-md text-primary mb-sm">Customer Acquisition Strategy</h3>
                <p class="font-body-md text-on-surface-variant max-w-md">Our recent platinum client onboarding has increased regional credit utilization by 12%. Review the updated credit policy for high-volume wholesalers.</p>
                <button class="mt-lg font-button text-secondary flex items-center gap-xs hover:gap-md transition-all">
                    View Credit Insights <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </div>
            <div class="absolute -right-8 -bottom-8 w-64 h-64 bg-secondary/5 rounded-full blur-3xl group-hover:bg-secondary/10 transition-colors duration-500"></div>
        </div>
        <div class="bg-primary p-lg rounded-xl shadow-xl flex flex-col justify-between text-on-primary h-48">
            <div>
                <p class="font-label-md text-on-primary/60 uppercase tracking-widest">Total Outstanding</p>
                <h3 class="font-display-lg font-bold leading-tight mt-sm">₹1.28 Cr</h3>
            </div>
            <div class="flex items-center gap-sm mt-md">
                <span class="material-symbols-outlined text-secondary">trending_up</span>
                <span class="font-label-md text-label-md">8.4% vs last month</span>
            </div>
        </div>
    </div>

    <!-- Modals -->
    @include('admin.modals.customer-form')
    @include('admin.modals.add-choice')
    @include('admin.modals.bulk-upload')
    @include('admin.modals.edit-bulk-row')
    @include('admin.modals.single-creation-success')
    
    <x-admin.modal id="delete-customer" title="Delete Customer" maxWidth="md">
        <div class="space-y-md">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-error/10 mx-auto mb-lg">
                <span class="material-symbols-outlined text-[32px] text-error">warning</span>
            </div>
            <h3 class="text-center font-title-lg text-on-surface">Are you sure?</h3>
            <p class="text-center font-body-md text-on-surface-variant">
                This customer will no longer appear in active customer listings. Historical records may still reference this customer later.
            </p>
        </div>
        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button wire:click="delete" variant="primary" class="bg-error hover:bg-error/90 text-white border-error">Delete Customer</x-admin.button>
        </x-slot>
    </x-admin.modal>
</div>
