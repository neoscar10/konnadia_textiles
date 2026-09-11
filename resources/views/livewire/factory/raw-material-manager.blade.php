<div>
    @if($showModal)
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/25 backdrop-blur-xs z-40" wire:click="closeModal"></div>

        <!-- Modal -->
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-surface-container-lowest border border-outline-variant/60 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden my-8" @click.outside="$wire.closeModal()">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-outline-variant/60 flex items-center justify-between bg-surface-container-low/20">
                        <div>
                            <h3 class="font-title-lg text-on-surface font-extrabold tracking-tight text-base">
                                {{ $materialId ? 'Edit Raw Material' : 'Add New Raw Material' }}
                            </h3>
                            <p class="font-body-md text-on-surface-variant text-xs mt-0.5">
                                {{ $materialId ? 'Update material details and unit configuration.' : 'Register a new manufacturing raw material.' }}
                            </p>
                        </div>
                        <button type="button" wire:click="closeModal" class="w-8 h-8 rounded-lg text-on-surface-variant hover:bg-surface-container-high flex items-center justify-center transition-colors">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <!-- Form Body -->
                    <form wire:submit="save" class="p-6 space-y-5">
                        <!-- Auto-generated Code (read-only) -->
                        @if($materialId && $code)
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">Material Code</label>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-black text-primary text-sm bg-primary/10 px-3 py-2 rounded-lg border border-primary/20">
                                        {{ $code }}
                                    </span>
                                    <span class="text-[10px] text-on-surface-variant/50 font-medium">Auto-generated</span>
                                </div>
                            </div>
                        @endif

                        <!-- Category Selection (Clean: without unit_type suffix) -->
                        <div>
                            <label for="rm-category" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Category <span class="text-error">*</span>
                            </label>
                            <select
                                id="rm-category"
                                wire:model.live="raw_material_category_id"
                                class="w-full py-2.5 px-4 rounded-xl border font-body-md text-sm focus:outline-none transition-colors
                                    {{ $errors->has('raw_material_category_id') ? 'border-error focus:border-error focus:ring-1 focus:ring-error' : 'border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary' }}"
                            >
                                <option value="">— Select Category —</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">
                                        {{ $cat->name }} ({{ $cat->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('raw_material_category_id')
                                <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Unit Class / Measurement Type Selection -->
                        <div>
                            <label for="rm-unit-group" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Unit Class / Measurement Type <span class="text-error">*</span>
                            </label>
                            <select
                                id="rm-unit-group"
                                wire:model.live="unit_group_id"
                                class="w-full py-2.5 px-4 rounded-xl border font-body-md text-sm focus:outline-none transition-colors
                                    {{ $errors->has('unit_group_id') ? 'border-error focus:border-error focus:ring-1 focus:ring-error' : 'border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary' }}"
                            >
                                <option value="">— Select Measurement Class —</option>
                                @foreach($unitGroups as $group)
                                    <option value="{{ $group->id }}">
                                        {{ $group->name }} ({{ $group->activeUnits->pluck('short_code')->implode(', ') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('unit_group_id')
                                <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Material Name -->
                        <div>
                            <label for="rm-name" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Material Name <span class="text-error">*</span>
                            </label>
                            <input
                                type="text"
                                id="rm-name"
                                wire:model="name"
                                placeholder="e.g., Cotton Poplin Fabric, YKK Zip #5"
                                class="w-full py-2.5 px-4 rounded-xl border font-body-md text-sm focus:outline-none transition-colors
                                    {{ $errors->has('name') ? 'border-error focus:border-error focus:ring-1 focus:ring-error' : 'border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary' }}"
                            />
                            @error('name')
                                <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Supplier Aliases Repeater -->
                        <div class="bg-surface-container-low/30 border border-outline-variant/40 rounded-xl p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">SUPPLIER ALIASES</label>
                                    <span class="text-[9px] font-black uppercase text-amber-700 bg-amber-500/10 border border-amber-500/20 px-1.5 py-0.5 rounded">MULTIPLE SUPPLIERS ALLOWED</span>
                                </div>
                            </div>
                            <p class="text-xs text-on-surface-variant/70">Create an alias and assign one or multiple suppliers that use this name.</p>

                            @if(empty($supplierAliases))
                                <div class="p-3 bg-surface-container-lowest border border-outline-variant/40 rounded-xl text-center text-xs text-on-surface-variant/70 italic font-medium">
                                    No aliases yet — click below to add an alias and link suppliers to it.
                                </div>
                            @else
                                <div class="space-y-3">
                                    @foreach($supplierAliases as $aIdx => $alias)
                                        <div wire:key="supplier-alias-row-{{ $aIdx }}" class="p-3 bg-surface border border-outline-variant/60 rounded-xl space-y-3">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex-1">
                                                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider mb-1">Supplier Alias Name / Code *</label>
                                                    <input type="text" wire:model="supplierAliases.{{ $aIdx }}.alias_name" placeholder="e.g. Poplin Premium 44in / scar" class="w-full py-1.5 px-3 rounded-lg border border-outline-variant/60 bg-surface-container-lowest text-xs font-semibold text-on-surface">
                                                </div>
                                                <div class="pt-4">
                                                    <button type="button" wire:click="removeSupplierAliasRow({{ $aIdx }})" class="p-1.5 text-error hover:bg-error-container/20 rounded-lg transition-colors" title="Remove Alias">
                                                        <span class="material-symbols-outlined text-base">delete</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <div>
                                                <div class="flex items-center justify-between mb-1.5">
                                                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-wider">Associated Suppliers (multiple allowed) *</label>
                                                    <span class="text-[9px] font-bold text-on-surface-variant/70">
                                                        {{ count($alias['supplier_ids'] ?? []) }} selected
                                                    </span>
                                                </div>

                                                @if($suppliers->isNotEmpty())
                                                    <div class="flex flex-wrap gap-1.5 pt-0.5">
                                                        @foreach($suppliers as $sup)
                                                            @php
                                                                $isSupSelected = in_array((string)$sup->id, $alias['supplier_ids'] ?? []) || in_array($sup->id, $alias['supplier_ids'] ?? []);
                                                            @endphp
                                                            <button
                                                                type="button"
                                                                wire:click="toggleSupplierForAlias({{ $aIdx }}, {{ $sup->id }})"
                                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-[11px] font-bold transition-all cursor-pointer select-none
                                                                    {{ $isSupSelected
                                                                        ? 'bg-primary text-on-primary border-primary shadow-xs'
                                                                        : 'bg-surface-container-lowest text-on-surface border-outline-variant/60 hover:border-primary/60 hover:bg-surface-container-high/40' }}"
                                                            >
                                                                <span class="material-symbols-outlined text-[14px] {{ $isSupSelected ? 'text-on-primary' : 'text-outline' }}">
                                                                    {{ $isSupSelected ? 'check_circle' : 'add_circle' }}
                                                                </span>
                                                                <span>{{ $sup->name }}</span>
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <p class="text-xs text-on-surface-variant/50 italic">No suppliers available. Create suppliers first.</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <button type="button" wire:click="addSupplierAliasRow" class="w-full py-2 bg-surface-container-lowest hover:bg-surface-container-high border border-outline-variant/60 rounded-xl text-xs font-bold text-primary transition-all shadow-xs flex items-center justify-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">add</span>
                                <span>Add Alias</span>
                            </button>
                        </div>

                        <!-- Unit Selection (dynamic based on selected Unit Class / Category) -->
                        <div>
                            <label for="rm-unit" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Unit of Measurement <span class="text-error">*</span>
                            </label>
                            @if(!empty($availableUnits))
                                <div class="flex flex-wrap gap-2">
                                    @foreach($availableUnits as $unitOption)
                                        <button
                                            type="button"
                                            wire:click="selectUnit('{{ $unitOption }}')"
                                            class="px-4 py-2 rounded-xl text-xs font-bold border transition-all
                                                {{ $unit === $unitOption
                                                    ? 'bg-primary text-on-primary border-primary shadow-md ring-2 ring-primary/20'
                                                    : 'bg-surface-container-low/30 text-on-surface border-outline-variant/60 hover:border-primary hover:bg-primary-container/10' }}"
                                        >
                                            {{ $unitOption }}
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-on-surface-variant/50 italic font-medium bg-surface-container-high/30 px-4 py-3 rounded-xl border border-outline-variant/30">
                                    <span class="material-symbols-outlined text-[14px] align-middle mr-1">info</span>
                                    Select a Unit Class or Category first to see available units.
                                </p>
                            @endif
                            @error('unit')
                                <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                            @enderror

                            <!-- Unit type hint -->
                            @if($unit_group_id)
                                @php
                                    $selectedGroup = $unitGroups->find($unit_group_id);
                                @endphp
                                @if($selectedGroup)
                                    <div class="mt-2 flex items-center gap-1.5 text-[11px] text-on-surface-variant/70 font-semibold">
                                        <span class="material-symbols-outlined text-[14px] text-secondary">info</span>
                                        <span>Measuring using <strong>{{ $selectedGroup->name }}</strong> class.</span>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <!-- Width Configuration (Multi-Select from Fabric Width Master) -->
                        @if($this->isLengthBased())
                            <div class="bg-surface-container-low/30 border border-outline-variant/40 rounded-xl p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                                        FABRIC STANDARD WIDTHS <span class="text-error">*</span>
                                    </label>
                                    <span class="text-[9px] font-black uppercase text-emerald-800 bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.5 rounded">MULTIPLE ALLOWED</span>
                                </div>
                                <p class="text-xs text-on-surface-variant/70">
                                    Select all standard widths this raw material fabric can be produced on. When opening a bale, users will pick from these configured widths.
                                </p>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 pt-1">
                                    @foreach($fabricWidthOptions as $fw)
                                        @php
                                            $isSelected = in_array($fw->id, $selected_fabric_width_ids);
                                        @endphp
                                        <button
                                            type="button"
                                            wire:click="toggleFabricWidth({{ $fw->id }})"
                                            class="flex items-center justify-between px-3.5 py-2.5 rounded-xl border text-xs font-bold transition-all text-left cursor-pointer
                                                {{ $isSelected
                                                    ? 'bg-primary/10 text-primary border-primary ring-2 ring-primary/20 shadow-xs'
                                                    : 'bg-surface-container-lowest text-on-surface border-outline-variant/60 hover:border-primary/50 hover:bg-surface-container-high/40' }}"
                                        >
                                            <div class="flex items-center gap-2">
                                                <span class="material-symbols-outlined text-[18px] {{ $isSelected ? 'text-primary font-bold' : 'text-outline' }}">
                                                    {{ $isSelected ? 'check_box' : 'check_box_outline_blank' }}
                                                </span>
                                                <span>{{ $fw->name }}</span>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>

                                @error('selected_fabric_width_ids')
                                    <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                                @enderror
                                @error('fabric_width_id')
                                    <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                                @enderror
                                @error('standard_width')
                                    <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <!-- Status Toggle -->
                        <div class="flex items-center justify-between bg-surface-container-low/20 border border-outline-variant/40 rounded-xl px-4 py-3">
                            <div>
                                <p class="text-xs font-bold text-on-surface uppercase tracking-wider">Status</p>
                                <p class="text-[10px] text-on-surface-variant mt-0.5">
                                    {{ $is_active ? 'Material is available for production use.' : 'Material is inactive and hidden from production.' }}
                                </p>
                            </div>
                            <button
                                type="button"
                                wire:click="$toggle('is_active')"
                                class="relative w-12 h-6 rounded-full transition-colors duration-200 focus:outline-none
                                    {{ $is_active ? 'bg-secondary' : 'bg-outline-variant' }}"
                            >
                                <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200 {{ $is_active ? 'translate-x-6' : 'translate-x-0' }}"></span>
                            </button>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-2 border-t border-outline-variant/40">
                            <button
                                type="button"
                                wire:click="closeModal"
                                class="px-5 py-2.5 rounded-xl border border-outline-variant/60 text-on-surface-variant text-xs font-bold hover:bg-surface-container-high/30 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-on-primary text-xs font-bold shadow-md hover:bg-primary-container transition-all active:scale-95"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-60 cursor-wait"
                            >
                                <span wire:loading wire:target="save" class="material-symbols-outlined text-[16px] animate-spin">progress_activity</span>
                                <span wire:loading.remove wire:target="save" class="material-symbols-outlined text-[16px]">save</span>
                                {{ $materialId ? 'Update Material' : 'Create Material' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
