<div>
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-1 bg-primary/10 text-primary text-xs font-bold rounded-lg uppercase tracking-wider">Factory Administration</span>
                <span class="text-outline text-xs font-bold">• Product Master</span>
            </div>
            <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Manufacturing Products</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Manage manufacturing definitions, material consumption, routing, and product mappings.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('factory.products.create') }}" wire:navigate class="flex items-center gap-2 bg-primary text-on-primary px-6 py-3 rounded-xl font-label-md text-label-md font-bold shadow-md hover:bg-primary-container transition-all active:scale-95">
                <span class="material-symbols-outlined">add_box</span>
                Add Manufacturing Product
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant/60 mb-6 flex flex-wrap items-center gap-4 shadow-xs">
        <div class="flex-1 min-w-[240px]">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-on-surface-variant">search</span>
                <input wire:model.live.debounce.300ms="search" class="w-full px-4 py-2.5 bg-surface rounded-xl border border-outline-variant/60 focus:ring-2 focus:ring-primary/20 focus:border-primary font-body-sm text-body-sm" placeholder="Search by Product Name or Code..." type="text"/>
            </div>
        </div>
        <div>
            <select wire:model.live="categoryFilter" class="bg-surface border border-outline-variant/60 rounded-xl font-label-md text-label-md py-2.5 px-4 focus:ring-2 focus:ring-primary/20 focus:border-primary font-bold">
                <option value="">All Categories</option>
                @foreach($allCategories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select wire:model.live="statusFilter" class="bg-surface border border-outline-variant/60 rounded-xl font-label-md text-label-md py-2.5 px-4 focus:ring-2 focus:ring-primary/20 focus:border-primary font-bold">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    <!-- Product Table -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 overflow-hidden shadow-xs">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse font-body-md">
                <thead>
                    <tr class="bg-surface-container-low border-b border-outline-variant/60">
                        <th class="py-4 px-6 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Product Code</th>
                        <th class="py-4 px-6 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Product Name</th>
                        <th class="py-4 px-6 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Category</th>
                        <th class="py-4 px-6 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Tasks Routing</th>
                        <th class="py-4 px-6 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Status</th>
                        <th class="py-4 px-6 font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($products as $product)
                        <tr class="hover:bg-surface-container/50 transition-colors">
                            <td class="py-4 px-6">
                                <p class="font-bold text-primary text-base">{{ $product->code }}</p>
                                <span class="text-xs text-outline">{{ $product->created_at ? $product->created_at->format('d M Y') : '' }}</span>
                            </td>
                            <td class="py-4 px-6 font-bold text-on-surface text-sm">
                                <p>{{ $product->name }}</p>
                                <span class="inline-flex items-center gap-1 mt-0.5 px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                    {{ $product->patterns->count() }} pattern{{ $product->patterns->count() !== 1 ? 's' : '' }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                @if($product->category)
                                    <span class="bg-surface-variant text-on-surface-variant px-3 py-1 rounded-full text-[11px] font-bold uppercase">
                                        {{ $product->category->name }}
                                    </span>
                                @else
                                    <span class="text-outline text-xs font-semibold">Uncategorized</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if($product->tasks->count() > 0)
                                    <span class="font-bold text-sm text-on-surface">{{ $product->tasks->count() }} Stages</span>
                                    <p class="text-xs text-outline font-mono">{{ implode(' → ', $product->tasks->pluck('name')->toArray()) }}</p>
                                @else
                                    <span class="text-error text-xs font-bold inline-flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">warning</span> No routing configured
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if($product->status === 'active')
                                    <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full font-label-sm text-label-sm font-bold inline-flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-secondary"></span> ACTIVE
                                    </span>
                                @else
                                    <span class="bg-surface-container-high text-on-surface-variant px-3 py-1 rounded-full font-label-sm text-label-sm font-bold inline-flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-outline"></span> INACTIVE
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('factory.products.edit', $product->id) }}" wire:navigate class="p-2 hover:bg-primary/10 text-primary rounded-lg transition-colors" title="Edit Product">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </a>
                                    <button wire:click="toggleStatus({{ $product->id }})" class="p-2 hover:bg-primary/10 text-primary rounded-lg transition-colors" title="Toggle Status">
                                        <span class="material-symbols-outlined text-[20px]">
                                            {{ $product->status === 'active' ? 'toggle_on' : 'toggle_off' }}
                                        </span>
                                    </button>
                                    <button onclick="confirm('Are you sure you want to delete this product?') || event.stopImmediatePropagation()" wire:click="deleteProduct({{ $product->id }})" class="p-2 hover:bg-error/10 text-error rounded-lg transition-colors" title="Delete Product">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl text-outline mb-2">inventory_2</span>
                                <p class="font-body-lg text-body-lg">No manufacturing products found.</p>
                                <a href="{{ route('factory.products.create') }}" wire:navigate class="mt-3 text-primary font-bold text-sm inline-block hover:underline">
                                    + Add your first manufacturing product
                                </a>
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
</div>
