<div>
    <!-- Page Header & Actions -->
    <div class="bg-surface-container-lowest border-b border-outline-variant/60 p-6 rounded-2xl mb-6 shadow-xs flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">Raw Material Master</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Manage manufacturing raw materials, units of measurement, and category assignments.</p>
        </div>
        <div class="flex items-center gap-3">
            <a
                href="{{ route('factory.raw-materials.purchase') }}"
                wire:navigate
                class="inline-flex items-center gap-2 border border-outline-variant/60 hover:bg-surface-container-high/30 text-on-surface px-5 py-3 rounded-xl font-bold text-xs shadow-sm transition-all active:scale-95"
            >
                <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
                Record Purchase
            </a>
            <button
                type="button"
                wire:click="$dispatch('open-raw-material-modal')"
                class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary px-5 py-3 rounded-xl font-bold text-xs shadow-md transition-all active:scale-95"
            >
                <span class="material-symbols-outlined text-[18px]">add_circle</span>
                Add Raw Material
            </button>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-surface-container-lowest border border-outline-variant/60 p-4 rounded-2xl mb-6 shadow-xs flex flex-col sm:flex-row items-center justify-start gap-4">
        <div class="relative w-full max-w-md">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-outline material-symbols-outlined text-[20px]">search</span>
            <input
                type="text"
                wire:model.live.debounce.250ms="search"
                placeholder="Search by name or code..."
                class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-outline-variant/60 bg-surface font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
            />
        </div>
        <select
            wire:model.live="categoryFilter"
            class="w-full sm:w-56 py-2.5 px-4 rounded-xl border border-outline-variant/60 bg-surface font-body-md text-sm focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
        >
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }} ({{ $cat->code }})</option>
            @endforeach
        </select>
    </div>


    <!-- Data Table Card -->
    <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr class="bg-surface-container-low/30 border-b border-outline-variant/60 text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                        <th class="px-6 py-4">Code</th>
                        <th class="px-6 py-4">Name</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Width</th>
                        <th class="px-6 py-4">Unit</th>
                        <th class="px-6 py-4">Unit Type</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/40">
                    @forelse($materials as $material)
                        <tr class="hover:bg-surface-container-low/20 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('factory.raw-materials.show', ['material' => $material->id]) }}" wire:navigate class="font-mono font-black text-primary text-xs bg-primary/10 px-2.5 py-1 rounded-lg hover:underline inline-block">
                                    {{ $material->code }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('factory.raw-materials.show', ['material' => $material->id]) }}" wire:navigate class="font-bold text-sm text-on-surface hover:text-primary transition-colors">
                                    {{ $material->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($material->category)
                                    <span class="inline-flex items-center text-[10px] font-extrabold bg-secondary-container text-on-secondary-container px-2.5 py-0.5 rounded-full border border-secondary/20 font-mono">
                                        {{ $material->category->code }}
                                    </span>
                                @else
                                    <span class="text-xs text-on-surface-variant/50">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($material->standard_width)
                                    <span class="text-xs font-semibold text-on-surface">
                                        {{ $material->standard_width }} {{ $material->width_unit }}
                                    </span>
                                @else
                                    <span class="text-xs text-on-surface-variant/40">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-bold text-on-surface bg-surface-container-high/50 px-2 py-1 rounded-lg border border-outline-variant/30">
                                    {{ $material->unit }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($material->category)
                                    @if($material->category->unit_type->value === 'length_based')
                                        <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                                            <span class="material-symbols-outlined text-[12px]">straighten</span>
                                            Length
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="material-symbols-outlined text-[12px]">inventory_2</span>
                                            Other
                                        </span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    type="button"
                                    wire:click="toggleStatus({{ $material->id }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold border transition-all cursor-pointer {{ $material->is_active ? 'bg-secondary-container/20 text-secondary border-secondary/20 hover:bg-secondary-container/30' : 'bg-outline-variant/20 text-on-surface-variant border-outline-variant/40 hover:bg-outline-variant/30' }}"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full {{ $material->is_active ? 'bg-secondary' : 'bg-on-surface-variant' }}"></span>
                                    {{ $material->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('factory.raw-materials.show', ['material' => $material->id]) }}"
                                        wire:navigate
                                        class="w-9 h-9 rounded-xl border border-outline-variant/60 text-secondary hover:bg-secondary-container/25 flex items-center justify-center transition-colors"
                                        title="Audit Material Stock Ledger"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">analytics</span>
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="$dispatch('open-raw-material-modal', { materialId: {{ $material->id }} })"
                                        class="w-9 h-9 rounded-xl border border-outline-variant/60 text-primary hover:bg-primary-container/25 flex items-center justify-center transition-colors"
                                        title="Edit Raw Material"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="confirmDelete({{ $material->id }})"
                                        class="w-9 h-9 rounded-xl border border-outline-variant/60 text-error/70 hover:bg-error-container/20 hover:text-error flex items-center justify-center transition-colors cursor-pointer"
                                        title="Delete Raw Material"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">delete_outline</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="material-symbols-outlined text-4xl text-outline mb-2">inventory_2</span>
                                    <p class="text-sm font-semibold text-on-surface">No raw materials configured</p>
                                    <p class="text-xs text-on-surface-variant mt-1">Add your first raw material to begin tracking factory inventory.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($materials->hasPages())
            <div class="px-6 py-4 border-t border-outline-variant/60 bg-surface-container-low/20">
                {{ $materials->links() }}
            </div>
        @endif
    </div>

    <!-- Manager Modal (embedded) -->
    <livewire:factory.raw-material-manager />

    <!-- Delete Confirmation Modal -->
    <x-admin.modal id="delete-raw-material-modal" title="Delete Raw Material" maxWidth="md">
        <div class="space-y-4">
            <p class="font-body-md text-on-surface">Are you sure you want to delete raw material <strong class="text-primary font-bold">[{{ $deletingMaterialName }}]</strong>?</p>
            <p class="font-body-md text-on-surface-variant text-xs">This action cannot be undone. Historical procurement records and job consumption ledgers will be preserved.</p>
            
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-outline-variant/30">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="button" variant="primary" wire:click="delete" class="!bg-error hover:!bg-error/90 !text-white" icon="delete">Confirm Delete</x-admin.button>
            </div>
        </div>
    </x-admin.modal>
</div>
