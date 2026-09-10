<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="text-[11px] font-extrabold uppercase tracking-widest text-amber-800 dark:text-amber-300">
                MANUFACTURING MANAGEMENT · NON-STANDARD WORK ORDERS
            </div>
            <h1 class="text-2xl font-black tracking-tight text-on-surface mt-0.5">Customized Production Hub</h1>
            <p class="text-xs text-on-surface-variant font-medium mt-1 max-w-3xl">
                Track non-standard production runs, define dynamic item specs on the fly, and execute custom task routings with explicit Final Stage marking.
            </p>
        </div>
        <div>
            <button wire:click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary/90 text-on-primary text-xs font-extrabold rounded-2xl shadow-sm transition-all hover:scale-[1.02] active:scale-[0.98]">
                <span class="material-symbols-outlined text-[18px]">add</span>
                <span>Create Customized Production</span>
            </button>
        </div>
    </div>

    <!-- KPI Metric Cards Grid (4 Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Active Custom Orders -->
        <div class="bg-surface-container-lowest p-5 rounded-3xl border border-outline-variant/60 shadow-sm">
            <div class="text-[10px] font-black uppercase tracking-wider text-on-surface-variant/70">ACTIVE CUSTOM ORDERS</div>
            <div class="text-3xl font-black text-on-surface mt-2">{{ $activeOrdersCount }}</div>
        </div>

        <!-- Custom Pcs Produced -->
        <div class="bg-surface-container-lowest p-5 rounded-3xl border border-outline-variant/60 shadow-sm">
            <div class="text-[10px] font-black uppercase tracking-wider text-on-surface-variant/70">CUSTOM PCS PRODUCED</div>
            <div class="text-3xl font-black text-on-surface mt-2">{{ number_format($pcsProducedSum) }} <span class="text-sm font-bold text-on-surface-variant">Pcs</span></div>
        </div>

        <!-- Completed Custom Runs -->
        <div class="bg-surface-container-lowest p-5 rounded-3xl border border-outline-variant/60 shadow-sm">
            <div class="text-[10px] font-black uppercase tracking-wider text-on-surface-variant/70">COMPLETED CUSTOM RUNS</div>
            <div class="text-3xl font-black text-on-surface mt-2">{{ $completedRunsCount }}</div>
        </div>

        <!-- Avg Custom Tasks -->
        <div class="bg-surface-container-lowest p-5 rounded-3xl border border-outline-variant/60 shadow-sm">
            <div class="text-[10px] font-black uppercase tracking-wider text-on-surface-variant/70">AVG CUSTOM TASKS</div>
            <div class="text-3xl font-black text-on-surface mt-2">{{ $avgCustomTasks }} <span class="text-sm font-bold text-on-surface-variant">Tasks</span></div>
        </div>
    </div>

    <!-- Main Card: Customized Production History Log Table -->
    <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/60 shadow-sm overflow-hidden">
        <!-- Card Subheader -->
        <div class="px-6 py-4 border-b border-outline-variant/60 flex flex-col sm:flex-row items-center justify-between gap-3 bg-surface-container-low/40">
            <div>
                <h3 class="text-xs font-black uppercase tracking-wider text-on-surface">CUSTOMIZED PRODUCTION HISTORY LOG</h3>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div class="relative flex-1 sm:w-64">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/60 text-[18px]">search</span>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search Custom ID, title or fabric..." class="w-full pl-9 pr-4 py-1.5 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-medium placeholder:text-on-surface-variant/50" />
                </div>
                <span class="text-xs font-bold text-on-surface-variant/80 hidden sm:inline">Dynamic Task Routings</span>
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-low/70 border-b border-outline-variant/60 text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                        <th class="py-3.5 px-6">CUSTOM ID</th>
                        <th class="py-3.5 px-4">ITEM DESCRIPTION</th>
                        <th class="py-3.5 px-4">FABRIC MATERIAL</th>
                        <th class="py-3.5 px-4">DIMENSIONS (W × L)</th>
                        <th class="py-3.5 px-4">TARGET QTY</th>
                        <th class="py-3.5 px-4">CONFIGURED TASKS</th>
                        <th class="py-3.5 px-4">STATUS</th>
                        <th class="py-3.5 px-6 text-right">ACTION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/30 text-xs font-medium text-on-surface">
                    @forelse($orders as $order)
                        <tr class="hover:bg-surface-container-low/40 transition-colors">
                            <td class="py-4 px-6 font-mono font-extrabold text-on-surface text-xs whitespace-nowrap">
                                {{ $order->custom_order_id }}
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-extrabold text-on-surface text-xs">{{ $order->item_description }}</div>
                                @if($order->notes)
                                    <div class="text-[11px] text-on-surface-variant/80 font-medium line-clamp-1 mt-0.5">{{ $order->notes }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-primary/10 text-primary border border-primary/20">
                                    {{ $order->fabric_name ?: ($order->rawMaterial?->name ?? 'Custom Fabric') }}
                                </span>
                            </td>
                            <td class="py-4 px-4 font-mono font-bold text-on-surface text-xs whitespace-nowrap">
                                {{ $order->dimensions_formatted }}
                            </td>
                            <td class="py-4 px-4 font-mono font-bold text-on-surface text-xs whitespace-nowrap">
                                {{ $order->target_quantity }} Pcs
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap font-semibold text-on-surface-variant text-xs">
                                {{ $order->configured_tasks_count }} Dynamic Tasks
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                @if($order->status === 'completed')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> COMPLETED
                                    </span>
                                @elseif($order->status === 'cancelled')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-red-500/10 text-red-700 dark:text-red-300 border border-red-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> CANCELLED
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-500/10 text-amber-800 dark:text-amber-300 border border-amber-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> IN PROGRESS
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <a href="{{ route('admin.production.customized.detail', $order->id) }}" wire:navigate class="inline-flex items-center gap-1 px-3.5 py-1.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface text-xs font-bold rounded-xl transition-all border border-outline-variant/50">
                                    <span>Open Routing</span>
                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl mb-2 text-on-surface-variant/40">settings_suggest</span>
                                <p class="text-sm font-semibold">No customized production orders found</p>
                                <p class="text-xs text-on-surface-variant/70 mt-1">Click "Create Customized Production" to start a new non-standard work order.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-outline-variant/60">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL: CREATE CUSTOMIZED PRODUCTION ORDER -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs animate-fade-in" x-data x-trap.noscroll="true">
            <div class="bg-surface-container-lowest rounded-3xl border border-outline-variant/60 shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden animate-scale-up">
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-outline-variant/60 flex items-start justify-between bg-surface-container-low/40">
                    <div>
                        <h2 class="text-lg font-black text-on-surface">Create Customized Production Order</h2>
                        <p class="text-xs text-on-surface-variant font-medium mt-0.5">
                            Define non-standard item specs, dynamic dimensions, target quantities, and build out tasks on the fly without a fixed pattern.
                        </p>
                    </div>
                    <button wire:click="$set('showCreateModal', false)" class="p-2 text-on-surface-variant hover:text-on-surface rounded-full hover:bg-surface-container-high transition-colors">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4 overflow-y-auto custom-scrollbar flex-1">
                    <!-- Row 1: Custom Order ID & Target Quantity -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">CUSTOM ORDER ID *</label>
                            <input type="text" wire:model="custom_order_id" readonly class="w-full px-3.5 py-2.5 bg-surface-container-low text-xs rounded-xl border border-outline-variant/60 text-on-surface font-mono font-bold" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">TARGET QUANTITY (PCS) *</label>
                            <input type="number" min="1" wire:model="target_quantity" class="w-full px-3.5 py-2.5 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-mono font-bold" />
                            @error('target_quantity') <span class="text-red-500 text-[10px] block mt-0.5">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Row 2: Custom Item Description / Title -->
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">CUSTOM ITEM DESCRIPTION / TITLE *</label>
                        <input type="text" wire:model="item_description" placeholder="e.g. Royal Palace Custom Velvet Bedcover (Special Length)" class="w-full px-3.5 py-2.5 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-medium placeholder:text-on-surface-variant/40" />
                        @error('item_description') <span class="text-red-500 text-[10px] block mt-0.5">{{ $message }}</span> @enderror
                    </div>

                    <!-- Row 3: Fabric / Material Selection -->
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">FABRIC / MATERIAL SELECTION *</label>
                        <select wire:model="raw_material_id" class="w-full px-3.5 py-2.5 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold">
                            <option value="">Select Fabric Material...</option>
                            @foreach($fabricMaterials as $fab)
                                <option value="{{ $fab->id }}">{{ $fab->name }} ({{ $fab->code }})</option>
                            @endforeach
                        </select>
                        @error('raw_material_id') <span class="text-red-500 text-[10px] block mt-0.5">{{ $message }}</span> @enderror
                    </div>

                    <!-- Row 4: Custom Item Dimensions (On-The-Fly) Card -->
                    <div class="p-4 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-on-surface">Custom Item Dimensions (On-The-Fly) *</h4>
                            <span class="text-[10px] font-bold text-on-surface-variant">Specified per custom order</span>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">WIDTH *</label>
                                <input type="number" step="0.01" min="0.01" wire:model="width" placeholder="108" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-mono font-bold" />
                                @error('width') <span class="text-red-500 text-[10px] block mt-0.5">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">LENGTH *</label>
                                <input type="number" step="0.01" min="0.01" wire:model="length" placeholder="120" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-mono font-bold" />
                                @error('length') <span class="text-red-500 text-[10px] block mt-0.5">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">LENGTH UNIT *</label>
                                <select wire:model="length_unit" class="w-full px-3 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-semibold">
                                    <option value="Inch (in)">Inch (in)</option>
                                    <option value="Meter (m)">Meter (m)</option>
                                    <option value="Yard (yd)">Yard (yd)</option>
                                    <option value="Centimeter (cm)">Centimeter (cm)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Row 5: Special Notes (Optional) -->
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-on-surface-variant mb-1">SPECIAL NOTES / CLIENT INSTRUCTIONS</label>
                        <textarea wire:model="notes" rows="2" placeholder="e.g. Client order for Grand Hotel Suite. Double stitching on edges required." class="w-full px-3.5 py-2 bg-surface-container-lowest text-xs rounded-xl border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20 text-on-surface font-medium placeholder:text-on-surface-variant/40"></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-outline-variant/60 flex items-center justify-end gap-3 bg-surface-container-low/40">
                    <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 bg-surface-container-high text-on-surface-variant hover:text-on-surface text-xs font-bold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="button" wire:click="createCustomOrder" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-extrabold rounded-xl shadow-sm transition-all flex items-center gap-1.5">
                        <span>Create Order & Build Dynamic Routing</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
