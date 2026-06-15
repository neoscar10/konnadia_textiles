<div>
    <x-slot:title>Variant Values</x-slot:title>
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Variant Values</h1>
            <p class="font-body-md text-on-surface-variant">Manage the specific values for your variant parameters (e.g., Red, Blue, XL, XXL).</p>
        </div>
        <x-admin.button variant="primary" icon="add" x-data @click="$dispatch('open-modal', 'add-variant-value')">Add Value</x-admin.button>
    </div>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="overflow-x-auto">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr>
                        <th class="px-lg py-md">Value Name</th>
                        <th class="px-lg py-md">Parent Parameter</th>
                        <th class="px-lg py-md text-center">Color Code / Meta</th>
                        <th class="px-lg py-md text-center">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-md font-bold text-primary">Deep Navy</td>
                        <td class="px-lg py-md text-on-surface-variant">Color</td>
                        <td class="px-lg py-md text-center">
                            <div class="flex items-center justify-center gap-xs">
                                <div class="w-6 h-6 rounded-full shadow-sm border border-outline-variant/30" style="background-color: #0F2744;"></div>
                                <span class="text-[10px] uppercase text-on-surface-variant">#0F2744</span>
                            </div>
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
                        <td class="px-lg py-md font-bold text-primary">Extra Large (XL)</td>
                        <td class="px-lg py-md text-on-surface-variant">Size</td>
                        <td class="px-lg py-md text-center">
                            <span class="text-[10px] uppercase text-on-surface-variant">-</span>
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
    <x-admin.modal id="add-variant-value" title="Add Variant Value" maxWidth="md">
        <form class="space-y-md" onsubmit="event.preventDefault();">
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Parent Parameter *</label>
                <select class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    <option value="">Select Parameter</option>
                    <option value="1">Color</option>
                    <option value="2">Size</option>
                </select>
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Value Name *</label>
                <input type="text" placeholder="e.g. Deep Navy, XL" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md">
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Color HEX Code (Optional)</label>
                <div class="flex items-center gap-sm">
                    <input type="color" class="w-10 h-10 rounded border-none cursor-pointer p-0" value="#0F2744">
                    <input type="text" placeholder="#000000" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md uppercase">
                </div>
            </div>

            <div class="flex items-center gap-sm mt-md">
                <input type="checkbox" checked class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
                <label class="font-body-md text-on-surface">Active Status</label>
            </div>
        </form>

        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button variant="primary" icon="save">Save Value</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <x-admin.delete-confirm-modal id="delete-variant-value" title="Delete Variant Value" message="Are you sure you want to delete this value? This will remove it from all associated products." />
</div>
