<div>
    <x-slot:title>Credit Management</x-slot:title>
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Credit Management</h1>
            <p class="font-body-md text-on-surface-variant">Monitor customer credit limits, outstanding balances, and enforce policies.</p>
        </div>
        <div class="flex gap-md w-full sm:w-auto">
            <x-admin.button variant="outline" icon="download">Export Report</x-admin.button>
        </div>
    </div>

    <!-- Metrics Bento -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-lg mb-xl">
        <div class="bg-surface-container-lowest p-lg rounded-xl shadow-sm border border-outline-variant/30 flex flex-col gap-sm">
            <span class="text-label-md text-on-surface-variant uppercase">Total Outstanding Credit</span>
            <div class="flex items-baseline justify-between">
                <span class="text-headline-md text-primary">₹1.42 Cr</span>
                <span class="text-[#0F8A46] font-bold text-label-md">Healthy</span>
            </div>
            <div class="w-full bg-surface-container h-1 rounded-full overflow-hidden mt-2">
                <div class="bg-[#0F8A46] w-2/5 h-full"></div>
            </div>
        </div>
        
        <div class="bg-surface-container-lowest p-lg rounded-xl shadow-sm border border-outline-variant/30 flex flex-col gap-sm">
            <span class="text-label-md text-on-surface-variant uppercase">Customers Over Limit</span>
            <div class="flex items-baseline justify-between">
                <span class="text-headline-md text-error">12</span>
                <span class="text-error font-bold text-label-md">Action Required</span>
            </div>
            <div class="w-full bg-surface-container h-1 rounded-full overflow-hidden mt-2">
                <div class="bg-error w-1/4 h-full"></div>
            </div>
        </div>
        
        <div class="bg-surface-container-lowest p-lg rounded-xl shadow-sm border border-outline-variant/30 flex flex-col gap-sm">
            <span class="text-label-md text-on-surface-variant uppercase">Available Credit Pool</span>
            <div class="flex items-baseline justify-between">
                <span class="text-headline-md text-primary">₹3.85 Cr</span>
            </div>
            <div class="w-full bg-surface-container h-1 rounded-full overflow-hidden mt-2">
                <div class="bg-primary w-3/5 h-full"></div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <x-admin.card class="mb-xl">
        <x-slot:bodyClass>p-md flex flex-wrap gap-md items-center justify-between</x-slot:bodyClass>
        
        <div class="flex flex-wrap gap-md items-center w-full lg:w-auto">
            <div class="relative w-full sm:w-64">
                <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-on-surface-variant/50">search</span>
                <input type="text" placeholder="Search Customer..." class="w-full pl-xl pr-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md focus:ring-2 focus:ring-secondary outline-none transition-all">
            </div>
            
            <select class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">All Customer Levels</option>
                <option value="retail">Retail</option>
                <option value="wholesale">Wholesale</option>
            </select>

            <select class="px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg font-body-md text-on-surface focus:ring-2 focus:ring-secondary outline-none">
                <option value="">Credit Status</option>
                <option value="healthy">Healthy</option>
                <option value="warning">Warning</option>
                <option value="over_limit">Over Limit</option>
            </select>
        </div>
    </x-admin.card>

    <!-- Data Table -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="overflow-x-auto">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr>
                        <th class="px-lg py-md">Customer</th>
                        <th class="px-lg py-md">Level</th>
                        <th class="px-lg py-md text-right">Credit Limit</th>
                        <th class="px-lg py-md text-right">Utilized</th>
                        <th class="px-lg py-md text-right">Available</th>
                        <th class="px-lg py-md text-right">Overdue</th>
                        <th class="px-lg py-md text-center">Enforcement Mode</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-md">
                            <p class="font-bold text-primary">Raj Garments</p>
                            <p class="font-label-md text-on-surface-variant">KT-CUST-0101</p>
                        </td>
                        <td class="px-lg py-md text-on-surface-variant">Level 2 Distributor</td>
                        <td class="px-lg py-md text-right font-bold text-primary">₹10,00,000</td>
                        <td class="px-lg py-md text-right text-on-surface-variant">₹3,20,000</td>
                        <td class="px-lg py-md text-right text-[#0F8A46]">₹6,80,000</td>
                        <td class="px-lg py-md text-right font-semibold text-error">₹50,000</td>
                        <td class="px-lg py-md text-center">
                            <span class="inline-flex items-center px-sm py-[2px] rounded-full text-[11px] font-bold bg-secondary-container/20 text-secondary border border-secondary/30">
                                Approval Mode
                            </span>
                        </td>
                        <td class="px-lg py-md text-right">
    <x-admin.action-menu>
        <x-admin.action-menu-item icon="visibility" label="View Details" />
        <x-admin.action-menu-item icon="edit" label="Edit" />
    </x-admin.action-menu>
</td>
                    </tr>
                    <tr class="hover:bg-primary/[0.02] transition-colors group">
                        <td class="px-lg py-md">
                            <p class="font-bold text-primary">Modern Stitch Hub</p>
                            <p class="font-label-md text-on-surface-variant">KT-CUST-1104</p>
                        </td>
                        <td class="px-lg py-md text-on-surface-variant">Wholesaler</td>
                        <td class="px-lg py-md text-right font-bold text-primary">₹5,00,000</td>
                        <td class="px-lg py-md text-right text-error font-medium">₹4,90,000</td>
                        <td class="px-lg py-md text-right text-warning">₹10,000</td>
                        <td class="px-lg py-md text-right font-semibold text-error">₹1,20,000</td>
                        <td class="px-lg py-md text-center">
                            <span class="inline-flex items-center px-sm py-[2px] rounded-full text-[11px] font-bold bg-error-container/20 text-error border border-error/30">
                                Blocking Mode
                            </span>
                        </td>
                        <td class="px-lg py-md text-right">
    <x-admin.action-menu>
        <x-admin.action-menu-item icon="visibility" label="View Details" />
        <x-admin.action-menu-item icon="edit" label="Edit" />
    </x-admin.action-menu>
</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <x-slot:footer class="flex justify-between items-center">
            <span class="font-label-md text-on-surface-variant">Showing 1 to 2 of 248 Customers</span>
            <div class="flex items-center gap-xs">
                <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-outline-variant text-outline hover:bg-surface-container transition-colors disabled:opacity-50" disabled>
                    <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                </button>
                <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary text-on-primary font-label-md shadow-sm">1</button>
                <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container font-label-md transition-colors">2</button>
                <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-colors">
                    <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                </button>
            </div>
        </x-slot:footer>
    </x-admin.card>

    <div class="mt-lg p-lg bg-surface-container-low rounded-xl flex items-start gap-md border border-outline-variant/30">
        <span class="material-symbols-outlined text-secondary text-[24px]">lightbulb</span>
        <div>
            <h4 class="font-title-md text-primary mb-xs">Policy Reminder</h4>
            <p class="font-body-md text-on-surface-variant">Customers in <b>'Blocking Mode'</b> will automatically have their new sales orders placed on hold. Credit limits are reviewed quarterly by the financial board.</p>
        </div>
    </div>

    <!-- Modals -->
    <x-admin.modal id="edit-credit" title="Update Credit Limit" maxWidth="md">
        <form class="space-y-md" onsubmit="event.preventDefault();">
            <div class="bg-surface-container-lowest p-md rounded-lg border border-outline-variant/30 mb-md">
                <p class="font-title-md text-primary">Modern Stitch Hub</p>
                <p class="font-label-md text-on-surface-variant">Current Limit: ₹5,00,000</p>
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">New Credit Limit (₹) *</label>
                <input type="number" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md" value="500000">
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Enforcement Mode *</label>
                <select class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                    <option value="warning">Warning Mode (Alert only)</option>
                    <option value="approval">Approval Mode (Needs Admin approval if exceeded)</option>
                    <option value="blocking" selected>Blocking Mode (Auto-rejects if exceeded)</option>
                </select>
            </div>

            <div class="space-y-xs">
                <label class="font-label-md text-on-surface-variant">Remarks/Reason for Change</label>
                <textarea rows="3" class="w-full px-md py-sm bg-surface-container-low border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md"></textarea>
            </div>
        </form>

        <x-slot name="footer">
            <x-admin.button variant="ghost" @click="show = false">Cancel</x-admin.button>
            <x-admin.button variant="primary" icon="save">Update Credit</x-admin.button>
        </x-slot>
    </x-admin.modal>
</div>
