<div>
    <x-slot:title>Units of Measurement</x-slot:title>
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Units of Measurement</h1>
            <p class="font-body-md text-on-surface-variant">Define standard units for inventory and pricing (e.g., Meters, Pieces).</p>
        </div>
        <x-admin.button variant="primary" icon="add" x-data @click="$dispatch('open-modal', 'add-unit')">Add Unit</x-admin.button>
    </div>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="overflow-x-auto">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr>
                        <th class="px-lg py-md">Unit Name</th>
                        <th class="px-lg py-md text-center">Short Code</th>
                        <th class="px-lg py-md text-center">Allow Decimals</th>
                        <th class="px-lg py-md text-center">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-md font-bold text-primary">Pieces</td>
                        <td class="px-lg py-md text-center font-mono text-on-surface-variant">PCS</td>
                        <td class="px-lg py-md text-center">
                            <span class="material-symbols-outlined text-error">close</span>
                        </td>
                        <td class="px-lg py-md text-center"><x-admin.badge type="success">Active</x-admin.badge></td>
                        <td class="px-lg py-md text-right">
    <x-admin.action-menu>
        <x-admin.action-menu-item icon="edit" label="Edit" />
        <x-admin.action-menu-item icon="delete" label="Delete" danger="true" />
    </x-admin.action-menu>
</td>
                    </tr>
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-md font-bold text-primary">Meters</td>
                        <td class="px-lg py-md text-center font-mono text-on-surface-variant">MTR</td>
                        <td class="px-lg py-md text-center">
                            <span class="material-symbols-outlined text-success">check</span>
                        </td>
                        <td class="px-lg py-md text-center"><x-admin.badge type="success">Active</x-admin.badge></td>
                        <td class="px-lg py-md text-right">
    <x-admin.action-menu>
        <x-admin.action-menu-item icon="edit" label="Edit" />
        <x-admin.action-menu-item icon="delete" label="Delete" danger="true" />
    </x-admin.action-menu>
</td>
                    </tr>
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-md font-bold text-primary">Kilograms</td>
                        <td class="px-lg py-md text-center font-mono text-on-surface-variant">KG</td>
                        <td class="px-lg py-md text-center">
                            <span class="material-symbols-outlined text-success">check</span>
                        </td>
                        <td class="px-lg py-md text-center"><x-admin.badge type="success">Active</x-admin.badge></td>
                        <td class="px-lg py-md text-right">
    <x-admin.action-menu>
        <x-admin.action-menu-item icon="edit" label="Edit" />
        <x-admin.action-menu-item icon="delete" label="Delete" danger="true" />
    </x-admin.action-menu>
</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-admin.card>

    <!-- Modals -->
    <x-admin.modal id="add-unit" title="Add Unit" maxWidth="md">
        <form class="space-y-md" onsubmit="event.preventDefault();">
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Unit Name *</label>
                <input type="text" placeholder="e.g. Boxes" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md">
            </div>
            
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Short Code *</label>
                <input type="text" placeholder="e.g. BOX" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md uppercase">
            </div>

            <div class="flex items-center gap-sm mt-md">
                <input type="checkbox" class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
                <label class="font-body-md text-on-surface">Allow Decimal Quantities</label>
            </div>

            <div class="flex items-center gap-sm mt-sm">
                <input type="checkbox" checked class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
                <label class="font-body-md text-on-surface">Active Status</label>
            </div>
        </form>

        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button variant="primary" icon="save">Save Unit</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <x-admin.delete-confirm-modal id="delete-unit" title="Delete Unit" message="Are you sure you want to delete this unit? Ensure no products are currently using it." />
</div>
