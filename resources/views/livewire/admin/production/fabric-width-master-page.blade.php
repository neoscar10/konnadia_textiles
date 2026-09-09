<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white p-6 rounded-2xl border border-gray-200/80 shadow-sm">
        <div class="text-xs font-bold text-amber-700 tracking-wider uppercase mb-1">Raw Materials Master</div>
        <h1 class="text-2xl font-extrabold text-slate-900 font-display">Fabric Width Master</h1>
        <p class="text-slate-500 text-sm mt-1">The single list of fabric widths used across the system — select from here when adding a Raw Material or building a Pattern's fabric consumption, instead of typing a width by hand.</p>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="relative min-w-[260px] max-w-md flex-1">
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search width values or units..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-amber-500 transition-all">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="flex items-center gap-3 justify-between sm:justify-end">
                <div class="text-xs text-slate-500 font-semibold">Total Options: {{ $widths->total() }}</div>
                <button wire:click="openCreateModal" class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-full transition-all shadow-2xs cursor-pointer active:scale-95">
                    <span>＋</span> New Fabric Width
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-gray-200 text-xs uppercase font-extrabold text-slate-500 tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Fabric Width</th>
                        <th class="px-6 py-3.5">Numeric Value</th>
                        <th class="px-6 py-3.5">Unit</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium text-slate-800">
                    @forelse($widths as $w)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-extrabold text-slate-900 font-display">{{ $w->name }}</span>
                            </td>
                            <td class="px-6 py-4 font-mono font-semibold text-slate-700">
                                {{ number_format($w->value, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                    {{ $w->unit }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button wire:click="toggleStatus({{ $w->id }})" class="cursor-pointer">
                                    @if($w->status)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactive
                                        </span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button wire:click="editWidth({{ $w->id }})" class="px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-gray-200 rounded-lg hover:bg-slate-50 transition-all cursor-pointer">
                                    Edit
                                </button>
                                <button wire:click="confirmDelete({{ $w->id }})" class="px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-all cursor-pointer">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <p>No fabric widths found matching your criteria.</p>
                                    <button wire:click="openCreateModal" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-full transition-all shadow-2xs mt-1 cursor-pointer">
                                        ＋ Add First Fabric Width
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($widths->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $widths->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    <x-admin.modal id="width-modal" title="{{ $widthId ? 'Edit Fabric Width' : 'Add Fabric Width' }}">
        <form wire:submit="saveWidth" class="space-y-4">
            <p class="text-xs text-slate-500">Adds a new width to the shared master used by Raw Materials and Pattern fabric consumption rows.</p>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Width Value *</label>
                    <input type="number" step="0.01" wire:model="value" placeholder="e.g. 44" class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-amber-500">
                    @error('value') <span class="text-xs text-rose-600 mt-1 font-semibold block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-700 mb-1">Unit *</label>
                    <select wire:model="unit" class="w-full px-3.5 py-2 border border-gray-200 rounded-xl text-sm font-semibold focus:outline-none focus:border-amber-500">
                        <option value="Inch">Inch (in)</option>
                        <option value="cm">Centimeter (cm)</option>
                        <option value="Meter">Meter (m)</option>
                        <option value="Yard">Yard (yd)</option>
                        <option value="Foot">Foot (ft)</option>
                    </select>
                    @error('unit') <span class="text-xs text-rose-600 mt-1 font-semibold block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <span class="text-xs font-extrabold uppercase text-slate-700">Status</span>
                <label class="relative inline-flex items-center cursor-pointer select-none group">
                    <input type="checkbox" wire:model="status" class="sr-only peer">
                    <div class="w-12 h-6 bg-slate-300 border border-slate-300 rounded-full peer peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-amber-500/30 peer-checked:after:translate-x-6 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all after:shadow-md peer-checked:bg-amber-500 peer-checked:border-amber-600 transition-all duration-200 group-hover:border-amber-400"></div>
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" x-on:click="$dispatch('close-modal', 'width-modal')" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-full transition-all cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-sm rounded-full transition-all shadow-md flex items-center gap-1.5 cursor-pointer active:scale-95">
                    <span>✓</span>
                    <span>{{ $widthId ? 'Update Width' : 'Save Fabric Width' }}</span>
                </button>
            </div>
        </form>
    </x-admin.modal>

    <!-- Delete Confirmation Modal -->
    <x-admin.modal id="delete-width-modal" title="Confirm Delete Fabric Width">
        <div class="space-y-4">
            <p class="text-sm text-slate-700">
                Are you sure you want to delete fabric width option <strong class="text-slate-900">{{ $deletingWidthName }}</strong>? This action cannot be undone.
            </p>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" x-on:click="$dispatch('close-modal', 'delete-width-modal')" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-full transition-all">
                    Cancel
                </button>
                <button type="button" wire:click="performDelete" class="px-6 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm rounded-full transition-all shadow-sm">
                    Delete Width
                </button>
            </div>
        </div>
    </x-admin.modal>
</div>
