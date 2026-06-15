<div>
    <x-slot:title>Variant Parameters</x-slot:title>
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Variant Parameters</h1>
            <p class="font-body-md text-on-surface-variant">Manage product attributes like Color, Size, and Material.</p>
        </div>
        <x-admin.button variant="primary" icon="add" x-data @click="$dispatch('open-modal', 'add-variant-parameter')">Add Parameter</x-admin.button>
    </div>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="overflow-x-auto">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr>
                        <th class="px-lg py-md">Parameter Name</th>
                        <th class="px-lg py-md text-center">Type</th>
                        <th class="px-lg py-md text-center">Assigned Values</th>
                        <th class="px-lg py-md text-center">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-md font-bold text-primary">Color</td>
                        <td class="px-lg py-md text-center">
                            <span class="inline-flex items-center px-sm py-xs rounded bg-surface-container-high text-on-surface text-[10px] uppercase tracking-wider">Color Swatch</span>
                        </td>
                        <td class="px-lg py-md text-center">
                            <span class="font-medium text-primary">24</span>
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
                        <td class="px-lg py-md font-bold text-primary">Size</td>
                        <td class="px-lg py-md text-center">
                            <span class="inline-flex items-center px-sm py-xs rounded bg-surface-container-high text-on-surface text-[10px] uppercase tracking-wider">Text/Button</span>
                        </td>
                        <td class="px-lg py-md text-center">
                            <span class="font-medium text-primary">8</span>
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
    <x-admin.modal id="add-variant-parameter" title="Add Variant Parameter" maxWidth="md">
        <form class="space-y-md" onsubmit="event.preventDefault();">
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Parameter Name *</label>
                <input type="text" placeholder="e.g. Color, Size, Material" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md">
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Display Type *</label>
                <select class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    <option value="text">Text/Button</option>
                    <option value="color">Color Swatch</option>
                    <option value="image">Image Thumbnail</option>
                    <option value="dropdown">Dropdown Select</option>
                </select>
            </div>

            <div class="flex items-center gap-sm mt-md">
                <input type="checkbox" checked class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
                <label class="font-body-md text-on-surface">Active Status</label>
            </div>
        </form>

        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button variant="primary" icon="save">Save Parameter</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <x-admin.delete-confirm-modal id="delete-variant-parameter" title="Delete Variant Parameter" message="Are you sure you want to delete this parameter? This will remove it from all associated products." />
</div>
