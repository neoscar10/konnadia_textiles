<!-- resources/views/livewire/factory/inventory-batch-detail.blade.php -->
<div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-headline-lg text-2xl font-extrabold text-primary">
            Batch Details – {{ $batch->batch_number }}
        </h1>
        <a href="{{ route('factory.raw-materials.batches') }}" wire:navigate class="text-sm font-medium text-primary underline hover:text-primary/80">
            ← Back to list
        </a>
    </div>

    <!-- Batch Summary Card -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-sm border border-outline-variant/30">
            <h2 class="text-sm font-semibold text-on-surface-variant mb-2">Material & Category</h2>
            <p class="text-base font-medium text-primary">
                {{ $batch->rawMaterial?->name }}
                <span class="ml-2 text-xs font-mono text-on-surface-variant">
                    ({{ $batch->rawMaterial?->category?->code ?: '—' }})
                </span>
            </p>
        </div>
        <div class="bg-surface-container-lowest rounded-2xl p-5 shadow-sm border border-outline-variant/30">
            <h2 class="text-sm font-semibold text-on-surface-variant mb-2">Quantities</h2>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div>
                    <span class="block text-xs text-on-surface-variant">Received</span>
                    <span class="block font-medium text-primary">{{ number_format($batch->quantity_received, 2) }} {{ $batch->unit }}</span>
                </div>
                <div>
                    <span class="block text-xs text-on-surface-variant">Consumed</span>
                    <span class="block font-medium text-primary">{{ number_format($batch->quantity_consumed, 2) }} {{ $batch->unit }}</span>
                </div>
                <div>
                    <span class="block text-xs text-on-surface-variant">Balance</span>
                    <span class="block font-medium text-primary">{{ number_format($batch->balance_quantity, 2) }} {{ $batch->unit }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs Section -->
    <section class="mb-8">
        <h2 class="font-headline-sm text-lg font-bold text-primary mb-4">Lifecycle Logs</h2>
        <div class="overflow-x-auto rounded-xl shadow-sm border border-outline-variant/20 bg-surface-container-lowest">
            <table class="w-full text-left border-collapse text-sm">
                <thead class="bg-surface-container-low/40">
                    <tr class="text-on-surface-variant uppercase text-xs font-medium">
                        <th class="px-4 py-2">Date & Time</th>
                        <th class="px-4 py-2">User</th>
                        <th class="px-4 py-2">Action</th>
                        <th class="px-4 py-2 text-right">Quantity</th>
                        <th class="px-4 py-2">Related Production</th>
                        <th class="px-4 py-2">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    @forelse($batch->logs->sortByDesc('created_at') as $log)
                        <tr class="hover:bg-surface-container-low/10 transition-colors">
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-on-surface-variant">
                                {{ $log->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-primary">
                                {{ $log->user?->name ?? 'System' }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-primary/80">
                                {{ ucfirst($log->action) }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right font-mono text-sm text-primary">
                                @if($log->quantity)
                                    {{ number_format($log->quantity, 2) }} {{ $batch->unit }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-secondary">
                                @if($log->related_production_batch_id)
                                    <a href="{{ route('production.batches.show', ['id' => $log->related_production_batch_id]) }}" wire:navigate class="underline hover:text-secondary/80">
                                        Batch #{{ $log->related_production_batch_id }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-on-surface-variant">
                                {{ $log->description ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-on-surface-variant">
                                No logs available for this batch.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Consumption History Section -->
    <section>
        <h2 class="font-headline-sm text-lg font-bold text-primary mb-4">Consumption History</h2>
        <div class="overflow-x-auto rounded-xl shadow-sm border border-outline-variant/20 bg-surface-container-lowest">
            <table class="w-full text-left border-collapse text-sm">
                <thead class="bg-surface-container-low/40">
                    <tr class="text-on-surface-variant uppercase text-xs font-medium">
                        <th class="px-4 py-2">Date</th>
                        <th class="px-4 py-2">Job / Production</th>
                        <th class="px-4 py-2 text-right">Qty Used</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    @forelse($batch->consumptions->sortByDesc('created_at') as $consumption)
                        <tr class="hover:bg-surface-container-low/10 transition-colors">
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-on-surface-variant">
                                {{ $consumption->created_at->format('d M Y') }}
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-primary">
                                @if($consumption->job)
                                    <a href="{{ route('admin.production.jobs.show', ['id' => $consumption->job->id]) }}" wire:navigate class="underline hover:text-primary/80">
                                        Job #{{ $consumption->job->id }} – {{ $consumption->job->title ?? 'Untitled' }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right font-mono text-sm text-primary">
                                {{ number_format($consumption->quantity, 2) }} {{ $batch->unit }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-on-surface-variant">
                                No consumption records for this batch.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
