<div>
    <!-- Header Section -->
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary">Labor Management</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Manage factory workers, payment methods, and authorized production tasks.</p>
        </div>
        <button type="button" wire:click="create" class="flex items-center gap-2 bg-primary text-on-primary px-6 py-3 rounded-xl font-label-md text-label-md hover:shadow-lg transition-all active:scale-95">
            <span class="material-symbols-outlined">add</span>
            Add Labor
        </button>
    </div>

    <!-- Summary Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-surface-container-lowest p-6 rounded-xl border border-outline-variant hover:border-primary transition-colors cursor-default group">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-lg bg-primary-fixed flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined">groups</span>
                </div>
            </div>
            <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-1">Total Workers</p>
            <h3 class="font-headline-lg text-headline-lg text-on-surface">{{ $labors->total() }}</h3>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-xl border border-outline-variant mb-6 flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-on-surface-variant">search</span>
                <input wire:model.live.debounce.300ms="search" class="w-full px-4 py-2.5 bg-surface rounded-lg border border-outline-variant focus:ring-1 focus:ring-primary font-body-sm text-body-sm" placeholder="Search Name, Code, Mobile..." type="text"/>
            </div>
        </div>
        <div class="flex gap-4">
            <select wire:model.live="payment_method_filter" class="bg-surface border-outline-variant rounded-lg font-label-md text-label-md py-2.5 px-4 focus:ring-1 focus:ring-primary">
                <option value="">Payment Method (All)</option>
                <option value="monthly_salary">Monthly Salary</option>
                <option value="job_work">Job Work</option>
            </select>
            <select wire:model.live="status_filter" class="bg-surface border-outline-variant rounded-lg font-label-md text-label-md py-2.5 px-4 focus:ring-1 focus:ring-primary">
                <option value="">Status (All)</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
    </div>

    <!-- Labor Table -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-container-low border-b border-outline-variant">
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Labor Code</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Worker Name</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Payment</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse ($labors as $labor)
                    <tr class="hover:bg-surface-container transition-colors">
                        <td class="px-6 py-5 font-label-md text-label-md font-bold text-primary">
                            <a href="{{ route('admin.labor.show', $labor->id) }}" wire:navigate class="hover:underline inline-flex items-center gap-1 font-mono">
                                {{ $labor->code }}
                                <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                            </a>
                        </td>
                        <td class="px-6 py-5">
                            <a href="{{ route('admin.labor.show', $labor->id) }}" wire:navigate class="font-body-md text-body-md font-semibold text-on-surface hover:text-primary hover:underline">
                                {{ $labor->name }}
                            </a>
                        </td>
                        <td class="px-6 py-5 font-body-sm text-body-sm text-on-surface-variant">{{ $labor->mobile_number ?? '-' }}</td>
                        <td class="px-6 py-5">
                            @if($labor->payment_method === 'monthly_salary')
                                <span class="bg-amber-500/10 text-amber-900 border border-amber-500/30 px-3 py-1 rounded-full font-label-sm text-label-sm font-bold">
                                    Monthly Salary (₹{{ number_format($labor->monthly_salary, 2) }})
                                </span>
                            @else
                                <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full font-label-sm text-label-sm font-bold">
                                    Job Work / Piece Rate
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            @if($labor->status)
                                <div class="flex items-center gap-1.5 text-green-600">
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                    <span class="font-label-sm text-label-sm font-bold">ACTIVE</span>
                                </div>
                            @else
                                <div class="flex items-center gap-1.5 text-on-surface-variant opacity-50">
                                    <span class="w-2 h-2 rounded-full bg-outline-variant"></span>
                                    <span class="font-label-sm text-label-sm font-bold">INACTIVE</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-right flex justify-end gap-2 items-center">
                            <a href="{{ route('admin.labor.show', $labor->id) }}" wire:navigate class="p-2 rounded-lg text-primary hover:bg-primary/10 transition-colors" title="View Full Audit & Earnings Profile">
                                <span class="material-symbols-outlined text-[20px]">visibility</span>
                            </a>
                            <button type="button" wire:click="edit({{ $labor->id }})" class="p-2 rounded-lg text-outline hover:bg-surface-container transition-colors" title="Edit Configuration">
                                <span class="material-symbols-outlined text-[20px]">edit</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-on-surface-variant">
                            No labor records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant">
            {{ $labors->links() }}
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <x-admin.modal id="labor-form-modal" title="{{ $editingId ? 'Edit Labor Profile' : 'Add New Labor' }}" maxWidth="2xl">
        <form wire:submit.prevent="save" class="space-y-md">
            <p class="text-on-surface-variant font-body-md text-body-md mb-6">Configure labor rules, payment methods, and production standards.</p>

            <div class="grid grid-cols-1 gap-6 mb-8">
                <!-- Basic Details -->
                <div class="bg-white border border-outline-variant rounded-xl p-5 shadow-sm space-y-4">
                    <h4 class="font-bold text-on-surface mb-4">Basic Details</h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-label-md font-bold text-on-surface-variant mb-1">Name *</label>
                            <input type="text" wire:model="name" class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-primary" placeholder="Worker Name">
                            @error('name') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-label-md font-bold text-on-surface-variant mb-1">Mobile Number</label>
                            <input type="text" wire:model="mobile_number" class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-primary" placeholder="+91 98765 43210">
                            @error('mobile_number') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="status" class="rounded border-outline-variant text-primary focus:ring-primary">
                            <span class="text-label-md font-bold text-on-surface-variant">Active Status</span>
                        </label>
                    </div>
                </div>

                <!-- Payment Configuration -->
                <div class="bg-white border border-outline-variant rounded-xl p-5 shadow-sm space-y-4" x-data="{ paymentMethod: @entangle('payment_method') }">
                    <h4 class="font-bold text-on-surface mb-4">Payment Configuration</h4>
                    
                    <div class="flex flex-col sm:flex-row gap-4 w-full">
                        <div class="w-full sm:w-[60%] transition-all duration-300">
                            <label class="block text-label-md font-bold text-on-surface-variant mb-1">Payment Method *</label>
                            <select wire:model.live="payment_method" class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-primary">
                                <option value="monthly_salary">Monthly Salary</option>
                                <option value="job_work">Job Work</option>
                            </select>
                            @error('payment_method') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="w-full sm:w-[40%]" x-show="paymentMethod === 'monthly_salary'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak>
                            <label class="block text-label-md font-bold text-on-surface-variant mb-1">Monthly Salary (₹) *</label>
                            <input type="number" step="0.01" wire:model="monthly_salary" class="w-full bg-surface-container-low border border-outline-variant rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-primary" placeholder="0.00">
                            @error('monthly_salary') <span class="text-error text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Authorized Tasks -->
            <div class="bg-white border border-outline-variant rounded-xl p-5 shadow-sm space-y-4 mb-8">
                <h4 class="font-bold text-on-surface mb-2">Authorized Tasks</h4>
                <p class="text-sm text-on-surface-variant mb-4">Select the production tasks this worker is authorized to perform.</p>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach($allTasks as $task)
                    <label class="flex items-center gap-3 p-3 border border-outline-variant rounded-lg hover:bg-surface-container transition-colors cursor-pointer">
                        <input type="checkbox" wire:model="authorized_tasks" value="{{ $task->id }}" class="rounded border-outline-variant text-primary focus:ring-primary">
                        <span class="text-sm font-semibold text-on-surface">{{ $task->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-md mt-xl pt-md border-t border-outline-variant/20">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="save">Save Configuration</x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>
