<div>
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary font-bold">Labor Production Tracking History</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Audit log of labor stage allocations, quantity outputs, and calculated piece-rate wages.</p>
        </div>
        <a href="{{ route('admin.production.jobs.index') }}" wire:navigate class="flex items-center gap-2 bg-primary text-on-primary px-6 py-3 rounded-xl font-label-md text-label-md hover:shadow-lg transition-all active:scale-95 font-bold">
            <span class="material-symbols-outlined">precision_manufacturing</span>
            Production Jobs Hub
        </a>
    </div>

    <!-- Filters Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-xl border border-outline-variant mb-6 flex flex-wrap items-center gap-4 shadow-sm">
        <div class="flex-1 min-w-[240px]">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-on-surface-variant">search</span>
                <input wire:model.live.debounce.300ms="search" class="w-full px-4 py-2.5 bg-surface rounded-lg border border-outline-variant focus:ring-1 focus:ring-primary font-body-sm text-body-sm" placeholder="Search Batch, Job ID, Worker Name, Task..." type="text"/>
            </div>
        </div>
        <div>
            <select wire:model.live="paymentMethodFilter" class="bg-surface border-outline-variant rounded-lg font-label-md text-label-md py-2.5 px-4 focus:ring-1 focus:ring-primary font-bold">
                <option value="">Payment Method (All)</option>
                <option value="monthly_salary">Monthly Salary</option>
                <option value="job_work">Job Work (Piece Rate)</option>
            </select>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-surface-container-lowest rounded-xl border border-outline-variant overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse font-body-md">
            <thead>
                <tr class="bg-surface-container-low border-b border-outline-variant">
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Date & Time</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Batch & Job ID</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Labor Worker</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Task Stage</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Product</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-center">Qty Processed</th>
                    <th class="px-6 py-4 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-right">Calculated Wage</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($allocations as $allocation)
                    <tr class="hover:bg-surface-container transition-colors">
                        <td class="px-6 py-4 text-xs font-mono text-on-surface-variant whitespace-nowrap">
                            {{ $allocation->created_at ? $allocation->created_at->format('d M Y, h:i A') : '-' }}
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-primary text-sm">{{ $allocation->job_id }}</p>
                            @if($allocation->production_batch_id)
                                <p class="text-xs font-mono text-outline">{{ $allocation->production_batch_id }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-on-surface text-sm">{{ $allocation->labor?->name ?? 'Unknown Worker' }}</p>
                            <span class="text-xs text-outline">{{ $allocation->labor?->code }}</span>
                            @if($allocation->labor?->payment_method === 'monthly_salary')
                                <span class="ml-2 bg-primary/10 text-primary px-2 py-0.5 rounded text-[10px] font-bold">Monthly</span>
                            @else
                                <span class="ml-2 bg-secondary/10 text-secondary px-2 py-0.5 rounded text-[10px] font-bold">Piece Rate</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 bg-surface-container-high rounded-full text-xs font-bold text-on-surface">
                                {{ $allocation->task?->name ?? 'Task #'.$allocation->task_id }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-on-surface-variant">
                            {{ $allocation->manufacturingProduct?->name ?? ($allocation->manufacturing_product_id ? 'Product #'.$allocation->manufacturing_product_id : 'N/A') }}
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-on-surface">
                            {{ number_format($allocation->quantity_processed) }} <span class="text-xs font-normal text-outline">Units</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if(is_null($allocation->calculated_wage))
                                <span class="text-xs text-outline italic">Salaried (No Piece Rate)</span>
                            @else
                                <span class="font-bold text-secondary text-base">₹{{ number_format($allocation->calculated_wage, 2) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-4xl text-outline mb-2">assignment_late</span>
                            <p class="font-body-lg text-body-lg">No labor production tracking records found.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($allocations->hasPages())
            <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant">
                {{ $allocations->links() }}
            </div>
        @endif
    </div>
</div>
