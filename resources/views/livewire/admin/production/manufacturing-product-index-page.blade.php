<div>
    <!-- Page Header & Breadcrumbs -->
    <div class="bg-surface-container-lowest border-b border-outline-variant/60 p-6 rounded-2xl mb-6 shadow-xs">
        <nav class="flex mb-2 text-xs text-on-surface-variant font-semibold space-x-2">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-primary transition-colors">Dashboard</a>
            <span>&gt;</span>
            <a href="{{ route('admin.production.product-categories.index') }}" wire:navigate class="hover:text-primary transition-colors">Categories</a>
            <span>&gt;</span>
            <span class="text-primary font-bold">Manufacturing Products & Routing</span>
        </nav>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Manufacturing Product Catalog</h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">Configure finished textile products, stage routing sequences, and piece-rate labor wages.</p>
            </div>
            <button type="button" wire:click="openCreateProductModal" class="inline-flex items-center gap-2 bg-primary text-on-primary px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-primary-container shadow-md transition-all active:scale-95">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Add Manufacturing Product
            </button>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/60 mb-6 shadow-xs">
        <div class="flex items-center gap-3 max-w-sm">
            <span class="material-symbols-outlined text-on-surface-variant shrink-0">search</span>
            <input wire:model.live.debounce.300ms="search" class="w-full px-4 py-3 bg-surface-container-low rounded-xl border border-outline-variant/60 focus:ring-2 focus:ring-primary/20 focus:border-primary font-semibold text-sm" placeholder="Search Product Name or Code..." type="text"/>
        </div>
    </div>

    <!-- Products Data Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 overflow-hidden shadow-xs mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse font-body-md">
                <thead>
                    <tr class="bg-surface-container-low text-xs text-on-surface-variant uppercase tracking-wider border-b border-outline-variant/60">
                        <th class="px-6 py-4 font-bold">Product Code</th>
                        <th class="px-6 py-4 font-bold">Product Name</th>
                        <th class="px-6 py-4 font-bold">Configured Routing Sequence & Rates</th>
                        <th class="px-6 py-4 font-bold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($products as $product)
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-extrabold font-mono text-primary text-sm">{{ $product->code }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-extrabold text-on-surface text-sm">{{ $product->name }}</p>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    @if($product->category)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-primary/8 border border-primary/20 rounded-lg text-[11px] font-bold text-primary">
                                            <span class="material-symbols-outlined text-[12px]">label</span>
                                            {{ $product->category->name }}
                                        </span>
                                    @endif
                                    <span class="text-xs text-outline">{{ $product->tasks->count() }} stage(s) configured</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($product->tasks->count() > 0)
                                    <div class="flex items-center gap-2">
                                        <!-- Compact stage count badge -->
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-surface-container border border-outline-variant/60 rounded-lg text-xs font-bold text-on-surface-variant">
                                            <span class="material-symbols-outlined text-[14px] text-primary">route</span>
                                            {{ $product->tasks->count() }} stage{{ $product->tasks->count() !== 1 ? 's' : '' }}
                                        </span>
                                        <button type="button"
                                            wire:click="openViewRoutingModal({{ $product->id }})"
                                            class="inline-flex items-center gap-1 text-primary hover:bg-primary/10 px-2.5 py-1 rounded-lg text-xs font-bold transition-all border border-primary/20 hover:border-primary/40">
                                            <span class="material-symbols-outlined text-[14px]">visibility</span>
                                            View
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-outline italic">Not configured</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" wire:click="openRoutingModal({{ $product->id }})" class="inline-flex items-center gap-1 bg-secondary-container/40 text-on-secondary-container border border-secondary/20 hover:bg-secondary-container px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all active:scale-95">
                                        <span class="material-symbols-outlined text-[16px]">route</span>
                                        Configure Routing
                                    </button>
                                    <button type="button" wire:click="editProduct({{ $product->id }})" class="p-2 text-primary hover:bg-primary/10 rounded-xl transition-all" title="Edit Product">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl text-outline mb-2">precision_manufacturing</span>
                                <p class="font-body-lg text-body-lg font-bold">No manufacturing products found.</p>
                                <button type="button" wire:click="openCreateProductModal" class="mt-3 text-primary font-bold text-sm hover:underline">
                                    + Add your first manufacturing product
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/60">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <!-- View Routing Modal (read-only) -->
    <x-admin.modal id="view-routing-modal" title="Task Routing Sequence" maxWidth="lg">
        @if($viewRoutingProduct)
            {{-- Product header --}}
            <div class="mb-5 p-4 bg-primary/5 rounded-xl border border-primary/20 flex items-start justify-between gap-3">
                <div>
                    <h4 class="font-extrabold text-primary text-base">{{ $viewRoutingProduct->name }}</h4>
                    <p class="text-xs text-outline font-mono mt-0.5">{{ $viewRoutingProduct->code }}</p>
                    @if($viewRoutingProduct->category)
                        <span class="inline-flex items-center gap-1 mt-2 px-2 py-0.5 bg-primary/8 border border-primary/20 rounded-lg text-[11px] font-bold text-primary">
                            <span class="material-symbols-outlined text-[12px]">label</span>
                            {{ $viewRoutingProduct->category->name }}
                        </span>
                    @endif
                </div>
                <button type="button" wire:click="openRoutingModal({{ $viewRoutingProduct->id }})"
                    class="inline-flex items-center gap-1.5 shrink-0 bg-secondary-container/40 text-on-secondary-container border border-secondary/20 hover:bg-secondary-container px-3 py-1.5 rounded-xl text-xs font-bold transition-all active:scale-95">
                    <span class="material-symbols-outlined text-[15px]">edit</span>
                    Edit Routing
                </button>
            </div>

            {{-- Routing steps --}}
            @if($viewRoutingProduct->tasks->count() > 0)
                <div class="space-y-2">
                    @foreach($viewRoutingProduct->tasks as $idx => $task)
                        <div class="flex items-center gap-3 p-3 rounded-xl border transition-all
                            {{ $task->pivot->is_final_step ? 'bg-secondary/5 border-secondary/30' : 'bg-surface border-outline-variant/60' }}">

                            {{-- Step number bubble --}}
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 font-extrabold text-sm
                                {{ $task->pivot->is_final_step ? 'bg-secondary text-on-secondary' : 'bg-primary/10 text-primary' }}">
                                {{ $task->pivot->sequence_number ?? ($idx + 1) }}
                            </div>

                            {{-- Connector line (not on last item) --}}
                            {{-- Task details --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-extrabold text-on-surface text-sm">{{ $task->name }}</span>
                                    @if($task->pivot->is_final_step)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-secondary text-on-secondary rounded-lg text-[10px] font-bold uppercase tracking-wider">
                                            <span class="material-symbols-outlined text-[12px]">flag</span>
                                            Final Step
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-outline mt-0.5 font-mono">{{ $task->code ?? '—' }}</p>
                            </div>

                            {{-- Rate --}}
                            <div class="text-right shrink-0">
                                <p class="font-black text-secondary text-sm">₹{{ number_format((float)($task->pivot->standard_labor_rate ?? $viewRoutingProduct->standard_labor_rate), 2) }}</p>
                                <p class="text-[10px] text-outline font-semibold">per unit</p>
                            </div>
                        </div>

                        {{-- Arrow connector between steps --}}
                        @if(!$loop->last)
                            <div class="flex justify-center py-0.5">
                                <span class="material-symbols-outlined text-outline text-[18px]">arrow_downward</span>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Summary footer --}}
                <div class="mt-4 pt-4 border-t border-outline-variant/40 grid grid-cols-2 gap-3">
                    <div class="p-3 bg-surface-container rounded-xl text-center">
                        <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Stages</p>
                        <p class="text-xl font-black text-primary mt-0.5">{{ $viewRoutingProduct->tasks->count() }}</p>
                    </div>
                    <div class="p-3 bg-surface-container rounded-xl text-center">
                        <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Total Max Rate</p>
                        <p class="text-xl font-black text-secondary mt-0.5">₹{{ number_format($viewRoutingProduct->tasks->sum(fn($t) => (float)($t->pivot->standard_labor_rate ?? 0)), 2) }}</p>
                    </div>
                </div>
            @else
                <div class="py-10 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-4xl text-outline mb-2 block">route</span>
                    <p class="font-bold text-sm">No routing configured for this product.</p>
                    <button type="button" wire:click="openRoutingModal({{ $viewRoutingProduct->id }})"
                        class="mt-3 inline-flex items-center gap-1.5 bg-primary text-on-primary px-4 py-2 rounded-xl text-xs font-bold hover:bg-primary-container transition-all">
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        Configure Routing
                    </button>
                </div>
            @endif
        @endif

        <div class="flex justify-end pt-4 border-t border-outline-variant/40 mt-4">
            <x-admin.button type="button" variant="ghost" @click="show = false">Close</x-admin.button>
        </div>
    </x-admin.modal>

    <!-- Product Create / Edit Modal -->
    <x-admin.modal id="product-modal" title="{{ $productId ? 'Edit Manufacturing Product' : 'Add New Manufacturing Product' }}" maxWidth="lg">
        <form wire:submit.prevent="saveProduct" class="space-y-4">
            <!-- Product Name -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Product Name *</label>
                <input type="text" wire:model="name" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="e.g. Queen Size Bedsheet">
                @error('name') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
            </div>

            <!-- Category Select & Code in grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Product Category</label>
                    <select wire:model="manufacturing_product_category_id"
                        class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">-- Uncategorised --</option>
                        @foreach($allCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @if($allCategories->isEmpty())
                        <p class="text-[11px] text-outline mt-1">
                            No active categories yet.
                            <a href="{{ route('admin.production.product-categories.index') }}" wire:navigate class="text-primary font-bold hover:underline">Create a category first →</a>
                        </p>
                    @endif
                    @error('manufacturing_product_category_id') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Product Code *</label>
                    <input type="text" wire:model="code" class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-mono font-bold text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary" placeholder="MP-BED-001">
                    @error('code') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40 mt-6">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="save">Save Manufacturing Product</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Configure Task Routing & Piece-Rate Wages Modal -->
    <x-admin.modal id="routing-modal" title="Task Routing & Piece-Rate Wage Configuration" maxWidth="5xl">
        @if($routingProduct)
            <div class="mb-5 p-4 sm:p-5 bg-primary/5 rounded-2xl border border-primary/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="font-extrabold text-primary text-base sm:text-lg">{{ $routingProduct->name }}</h4>
                        <span class="px-2.5 py-1 bg-primary/10 text-primary border border-primary/20 rounded-lg text-xs font-mono font-bold">
                            {{ $routingProduct->code }}
                        </span>
                    </div>
                    <p class="text-xs text-on-surface-variant font-medium mt-1">
                        Configure production stages, task sequences, and piece-rate labor pay rates.
                    </p>
                </div>
                <button type="button" wire:click="addRoutingTaskRow" class="inline-flex items-center gap-2 bg-primary text-on-primary px-4 py-2.5 rounded-xl text-xs font-bold hover:bg-primary-container transition-all active:scale-95 shadow-sm shrink-0">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    Add Routing Stage
                </button>
            </div>
        @endif

        <form wire:submit.prevent="saveRouting" class="space-y-4">
            <p class="text-xs text-on-surface-variant leading-relaxed mb-2">
                Define the ordered task sequence, task-specific piece-rate labor rates, and designate the <span class="font-bold text-secondary">Final Production Step</span> that triggers Finished Goods conversion.
            </p>

            @if($errors->has('routingTasks'))
                <div class="bg-error-container/40 border border-error/30 text-error p-3.5 rounded-xl flex items-center gap-2 text-xs font-semibold">
                    <span class="material-symbols-outlined text-[20px] shrink-0">error</span>
                    {{ $errors->first('routingTasks') }}
                </div>
            @endif

            <!-- Column Header Labels -->
            <div class="hidden sm:grid grid-cols-12 gap-4 items-center px-4 py-2.5 bg-surface-container-low/60 rounded-xl border border-outline-variant/30 text-xs font-extrabold uppercase tracking-wider text-on-surface-variant">
                <div class="col-span-2 text-center min-w-0">Order &amp; Sequence</div>
                <div class="col-span-5 min-w-0">Manufacturing Task *</div>
                <div class="col-span-2 min-w-0">Piece Rate (₹) *</div>
                <div class="col-span-3 text-right min-w-0">Final Step &amp; Actions</div>
            </div>

            <!-- Task Rows Container -->
            <div class="space-y-3 max-h-[460px] overflow-y-auto pr-1">
                @foreach($routingTasks as $index => $row)
                    <div class="grid grid-cols-12 gap-4 items-center p-3.5 sm:p-4 rounded-2xl border shadow-2xs transition-all
                        {{ !empty($row['is_final_step']) ? 'bg-secondary/8 border-secondary/40 shadow-xs' : 'bg-surface border-outline-variant/60 hover:border-outline-variant' }}">

                        <!-- Sequence / Move Controls -->
                        <div class="col-span-4 sm:col-span-2 min-w-0 flex items-center justify-center gap-2 bg-surface-container-low/80 py-1.5 px-3 rounded-xl border border-outline-variant/40 shrink-0">
                            <button type="button" wire:click="moveRoutingTaskUp({{ $index }})"
                                @disabled($index === 0)
                                class="p-1 rounded-lg hover:bg-primary/10 text-primary disabled:opacity-20 disabled:hover:bg-transparent transition-all"
                                title="Move Up">
                                <span class="material-symbols-outlined text-[16px] block">arrow_upward</span>
                            </button>

                            <span class="w-6 h-6 rounded-full bg-primary/15 text-primary font-black text-xs flex items-center justify-center shrink-0">
                                {{ $index + 1 }}
                            </span>

                            <button type="button" wire:click="moveRoutingTaskDown({{ $index }})"
                                @disabled($index === count($routingTasks) - 1)
                                class="p-1 rounded-lg hover:bg-primary/10 text-primary disabled:opacity-20 disabled:hover:bg-transparent transition-all"
                                title="Move Down">
                                <span class="material-symbols-outlined text-[16px] block">arrow_downward</span>
                            </button>
                        </div>

                        <!-- Task Select -->
                        <div class="col-span-8 sm:col-span-5 min-w-0">
                            <label class="block text-[10px] font-bold text-on-surface-variant uppercase sm:hidden mb-1">Manufacturing Stage Task *</label>
                            <select wire:model.live="routingTasks.{{ $index }}.task_id"
                                class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl px-4 py-2.5 text-xs font-bold text-on-surface focus:ring-2 focus:ring-primary/20 cursor-pointer">
                                <option value="">-- Select Task --</option>
                                @foreach($allTasks as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }})</option>
                                @endforeach
                            </select>
                            @error("routingTasks.{$index}.task_id")
                                <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Piece-Rate Wage -->
                        <div class="col-span-6 sm:col-span-2 min-w-0">
                            <label class="block text-[10px] font-bold text-on-surface-variant uppercase sm:hidden mb-1">Labor Rate (₹/Pcs) *</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-black text-secondary pointer-events-none select-none">₹</span>
                                <input type="number" step="0.50" min="0" wire:model.live="routingTasks.{{ $index }}.standard_labor_rate"
                                    class="w-full bg-surface-container-low border border-outline-variant/60 rounded-xl pl-10 pr-3 py-2.5 text-xs font-black text-secondary text-right focus:ring-2 focus:ring-secondary/20">
                            </div>
                            @error("routingTasks.{$index}.standard_labor_rate")
                                <span class="text-error text-[10px] block mt-0.5 font-semibold">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Final Step Toggle + Delete -->
                        <div class="col-span-6 sm:col-span-3 min-w-0 flex items-center justify-end gap-2 pt-2 sm:pt-0 border-t sm:border-t-0 border-outline-variant/30">
                            <!-- Final Step Button -->
                            <button type="button" wire:click="setFinalStep({{ $index }})"
                                title="Designate as Final Production Step"
                                class="inline-flex items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold transition-all shrink-0
                                    {{ !empty($row['is_final_step']) ? 'bg-secondary text-on-secondary shadow-xs' : 'bg-surface-container-low text-on-surface-variant border border-outline-variant/60 hover:border-secondary/40 hover:text-secondary' }}">
                                <span class="material-symbols-outlined text-[16px]">
                                    {{ !empty($row['is_final_step']) ? 'flag' : 'outlined_flag' }}
                                </span>
                                <span>{{ !empty($row['is_final_step']) ? 'Final Step' : 'Set Final' }}</span>
                            </button>

                            @if(count($routingTasks) > 1)
                                <button type="button" wire:click="removeRoutingTaskRow({{ $index }})"
                                    class="p-2 text-error hover:bg-error-container/30 rounded-xl transition-colors shrink-0" title="Remove Stage">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Final Step Legend -->
            <div class="flex items-center gap-3 mt-3 p-3.5 bg-secondary/5 border border-secondary/20 rounded-2xl">
                <span class="material-symbols-outlined text-secondary text-[22px] shrink-0">flag</span>
                <p class="text-xs text-on-surface-variant font-medium leading-relaxed">
                    <span class="font-bold text-secondary">Final Production Step</span> — The designated task triggers <strong>Finished Goods conversion</strong> when the job is completed. Exactly <strong>one</strong> task must be selected.
                </p>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-outline-variant/40 mt-6">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="submit" variant="primary" icon="check">Save Routing & Wage Rates</x-admin.button>
            </div>
        </form>
    </x-admin.modal>
</div>

