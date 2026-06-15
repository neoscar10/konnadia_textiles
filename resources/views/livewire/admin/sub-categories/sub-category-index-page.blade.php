<div>
    <x-slot:title>Sub-Categories</x-slot:title>
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Sub-Categories</h1>
            <p class="font-body-md text-on-surface-variant">Manage secondary groupings for granular catalog organization.</p>
        </div>
        <x-admin.button variant="primary" icon="add" x-data @click="$dispatch('open-modal', 'add-sub-category')">Add Sub-Category</x-admin.button>
    </div>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="overflow-x-auto">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr>
                        <th class="px-lg py-md">Sub-Category Name</th>
                        <th class="px-lg py-md">Parent Category</th>
                        <th class="px-lg py-md">Slug</th>
                        <th class="px-lg py-md text-center">Products</th>
                        <th class="px-lg py-md text-center">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-md font-bold text-primary">Organic Cotton</td>
                        <td class="px-lg py-md text-on-surface-variant">Cotton Fabrics</td>
                        <td class="px-lg py-md text-on-surface-variant">organic-cotton</td>
                        <td class="px-lg py-md text-center">
                            <span class="font-medium text-primary">45</span>
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
                        <td class="px-lg py-md font-bold text-primary">Printed Cotton</td>
                        <td class="px-lg py-md text-on-surface-variant">Cotton Fabrics</td>
                        <td class="px-lg py-md text-on-surface-variant">printed-cotton</td>
                        <td class="px-lg py-md text-center">
                            <span class="font-medium text-primary">82</span>
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
    <x-admin.modal id="add-sub-category" title="Add Sub-Category" maxWidth="md">
        <form class="space-y-md" onsubmit="event.preventDefault();">
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Parent Category *</label>
                <select class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    <option value="">Select Parent Category</option>
                    <option value="1">Cotton Fabrics</option>
                    <option value="2">Silk & Blends</option>
                </select>
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Sub-Category Name *</label>
                <input type="text" placeholder="e.g. Raw Silk" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md">
            </div>
            
            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Slug</label>
                <input type="text" placeholder="raw-silk" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md">
                <p class="text-xs text-on-surface-variant mt-1">Leave blank to auto-generate from name.</p>
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Description</label>
                <textarea rows="3" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md"></textarea>
            </div>

            <div class="flex items-center gap-sm mt-md">
                <input type="checkbox" checked class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary cursor-pointer">
                <label class="font-body-md text-on-surface">Active Status</label>
            </div>
        </form>

        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button variant="primary" icon="save">Save Sub-Category</x-admin.button>
        </x-slot>
    </x-admin.modal>

    <x-admin.delete-confirm-modal id="delete-sub-category" title="Delete Sub-Category" message="Are you sure you want to delete this sub-category? Ensure no products are currently assigned to it." />
</div>
