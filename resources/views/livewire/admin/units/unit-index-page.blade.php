<div>
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-on-surface tracking-tight">Units Management</h1>
            <p class="text-on-surface-variant text-sm mt-1">Configure dynamic unit groups, base measurement units, and conversion relationships across the factory system.</p>
        </div>
        <button wire:click="openCreateGroupModal" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold text-xs shadow-xs transition-all active:scale-95 cursor-pointer">
            <span class="material-symbols-outlined text-[18px]">add_circle</span>
            <span class="font-bold">Add Unit Group</span>
        </button>
    </div>

    <!-- Search Bar -->
    <div class="bg-surface-container-lowest rounded-2xl p-4 shadow-xs border border-outline-variant/60 mb-6">
        <div class="relative max-w-md">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search unit groups by name or code..." class="w-full pl-10 pr-4 py-2.5 bg-surface border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
            <span class="material-symbols-outlined text-outline absolute left-3 top-3 text-[20px]">search</span>
        </div>
    </div>

    <!-- Unit Groups Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($groups as $group)
            <div class="bg-surface-container-lowest rounded-2xl p-6 shadow-xs border border-outline-variant/60 hover:border-primary/40 hover:shadow-md transition-all duration-200 flex flex-col justify-between relative group">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-primary/10 text-primary border border-primary/20">
                            {{ $group->code }}
                        </span>
                        <div class="flex items-center space-x-1">
                            <button wire:click="editGroup({{ $group->id }})" class="p-1.5 text-on-surface-variant hover:text-primary rounded-lg hover:bg-surface transition-colors" title="Edit Group">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </button>
                            <button onclick="confirm('Delete this unit group?') || event.stopImmediatePropagation()" wire:click="deleteGroup({{ $group->id }})" class="p-1.5 text-on-surface-variant hover:text-error rounded-lg hover:bg-error-container/30 transition-colors" title="Delete Group">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-on-surface">{{ $group->name }}</h3>
                    <p class="text-xs text-on-surface-variant mt-1 line-clamp-2">{{ $group->description ?: 'No description provided.' }}</p>

                    <!-- Base Unit Badge -->
                    <div class="mt-4 pt-3 border-t border-outline-variant/40 flex items-center justify-between text-xs">
                        <span class="text-on-surface-variant font-medium">Base Unit:</span>
                        @if($group->baseUnit)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg font-bold bg-secondary-container/40 text-secondary border border-secondary/30">
                                🌟 {{ $group->baseUnit->name }} ({{ $group->baseUnit->short_code }})
                            </span>
                        @else
                            <span class="text-warning font-semibold">Not set</span>
                        @endif
                    </div>
                </div>

                <div class="mt-5 pt-3 flex items-center justify-between border-t border-outline-variant/40">
                    <span class="text-xs text-on-surface-variant font-medium">{{ $group->units->count() }} Units defined</span>
                    <button wire:click="manageUnits({{ $group->id }})" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-primary bg-primary/10 hover:bg-primary/20 rounded-xl transition-colors">
                        Manage Units &rarr;
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-surface-container-lowest rounded-2xl p-12 text-center border border-outline-variant/60 shadow-xs">
                <p class="text-on-surface-variant text-sm">No unit groups found. Click "Add Unit Group" to create your first dynamic group.</p>
            </div>
        @endforelse
    </div>

    <!-- Group Modal -->
    @if($showGroupModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-scrim/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-surface-container-lowest rounded-2xl max-w-md w-full p-6 shadow-2xl border border-outline-variant/60 transform transition-all">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-on-surface">{{ $editingGroupId ? 'Edit Unit Group' : 'Create Unit Group' }}</h3>
                    <button wire:click="$set('showGroupModal', false)" class="text-outline hover:text-on-surface text-xl font-bold">&times;</button>
                </div>
                
                <form wire:submit.prevent="saveGroup" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Group Name *</label>
                        <input type="text" wire:model="groupName" placeholder="e.g. Length Units, Zip Units" class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/60 rounded-xl text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('groupName') <span class="text-error text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Group Code *</label>
                        <input type="text" wire:model="groupCode" placeholder="e.g. LENGTH, ZIP, COUNT" class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/60 rounded-xl text-sm uppercase font-bold text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        @error('groupCode') <span class="text-error text-xs mt-1 block font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1">Description</label>
                        <textarea wire:model="groupDescription" rows="2" placeholder="Brief description of this unit group..." class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/60 rounded-xl text-sm text-on-surface focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-outline-variant/40">
                        <button type="button" wire:click="$set('showGroupModal', false)" class="px-4 py-2 text-sm text-on-surface-variant hover:bg-surface rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 text-xs bg-primary text-on-primary rounded-xl font-bold hover:bg-primary-container shadow-xs transition-all cursor-pointer">Save Group</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Units Drawer / Modal for Selected Group -->
    @if($selectedGroup)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-scrim/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-surface-container-lowest rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-outline-variant/60">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-on-surface">Units in {{ $selectedGroup->name }}</h3>
                        <p class="text-xs text-on-surface-variant">Configure base unit and conversion ratios relative to the base unit.</p>
                    </div>
                    <button wire:click="closeUnitsDrawer" class="text-outline hover:text-on-surface text-xl font-bold">&times;</button>
                </div>

                <div class="flex justify-between items-center mb-4 pt-2">
                    <span class="text-xs font-bold uppercase text-on-surface-variant tracking-wider">Defined Units ({{ $selectedGroup->units->count() }})</span>
                    <button wire:click="openCreateUnitModal" class="inline-flex items-center gap-1.5 bg-primary text-on-primary px-4 py-2 rounded-xl font-bold text-xs shadow-xs transition-all hover:bg-primary-container cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        <span class="font-bold">Add Unit</span>
                    </button>
                </div>

                <div class="divide-y divide-outline-variant/40 max-h-96 overflow-y-auto pr-1">
                    @forelse($selectedGroup->units as $unit)
                        <div class="py-3 flex items-center justify-between">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold text-on-surface text-sm">{{ $unit->name }}</span>
                                    <span class="text-xs px-2 py-0.5 bg-surface text-on-surface-variant rounded border border-outline-variant/60 font-mono font-bold">{{ $unit->short_code }}</span>
                                    @if($unit->is_base)
                                        <span class="text-[10px] px-2.5 py-0.5 bg-secondary-container/40 text-secondary border border-secondary/30 font-extrabold rounded-full uppercase">Base Unit (1.0)</span>
                                    @endif
                                </div>
                                <p class="text-xs text-outline mt-0.5">
                                    1 {{ $unit->name }} = <strong>{{ (float)$unit->ratio_to_base }}</strong> {{ $selectedGroup->baseUnit ? $selectedGroup->baseUnit->name : 'Base Unit' }}
                                </p>
                            </div>

                            <div class="flex items-center space-x-2">
                                @if(!$unit->is_base)
                                    <button wire:click="setBaseUnit({{ $unit->id }})" class="text-xs text-primary font-bold hover:underline">Set as Base</button>
                                @endif
                                <button wire:click="editUnit({{ $unit->id }})" class="p-1.5 text-on-surface-variant hover:text-primary rounded-lg hover:bg-surface"><span class="material-symbols-outlined text-[18px]">edit</span></button>
                                @if(!$unit->is_base)
                                    <button wire:click="deleteUnit({{ $unit->id }})" class="p-1.5 text-on-surface-variant hover:text-error rounded-lg hover:bg-error-container/30"><span class="material-symbols-outlined text-[18px]">delete</span></button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-on-surface-variant">No units defined yet for this group.</div>
                    @endforelse
                </div>

                <div class="mt-6 pt-4 border-t border-outline-variant/40 text-right">
                    <button wire:click="closeUnitsDrawer" class="px-5 py-2 bg-surface text-on-surface border border-outline-variant/60 text-sm font-bold rounded-xl hover:bg-surface-container-high">Done</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Add/Edit Unit Sub-Modal -->
    @if($showUnitModal)
        @php
            $baseUnitForGroup = $selectedGroup ? $selectedGroup->units->firstWhere('is_base', true) : null;
            $baseName = $baseUnitForGroup ? $baseUnitForGroup->name : 'Base Unit';
            $baseCode = $baseUnitForGroup ? $baseUnitForGroup->short_code : 'Base';
        @endphp
        <div wire:key="unit-modal-{{ $editingUnitId ?? 'new' }}"
             x-data="{
                unitName: $wire.entangle('unitName').live,
                unitShortCode: $wire.entangle('unitShortCode').live,
                unitIsBase: $wire.entangle('unitIsBase').live,
                unitRatio: $wire.entangle('unitRatio').live,
                baseName: '{{ addslashes($baseName) }}',
                baseCode: '{{ addslashes($baseCode) }}',
                get displayName() { return (this.unitName || '').trim() || 'Unit'; },
                get displayCode() { return (this.unitShortCode || '').trim() || 'code'; },
                get parsedRatio() {
                    let r = parseFloat(this.unitRatio);
                    return (!isNaN(r) && r > 0) ? r : 1;
                },
                get formattedRatio() {
                    let r = this.parsedRatio;
                    return (r % 1 === 0) ? r.toLocaleString() : r.toString();
                },
                get primaryText() {
                    return `1 ${this.displayName} (${this.displayCode}) = ${this.formattedRatio} ${this.baseName} (${this.baseCode})`;
                },
                get explanationText() {
                    if (this.parsedRatio < 1 && this.parsedRatio > 0) {
                        let recip = 1 / this.parsedRatio;
                        let formattedRecip = (recip % 1 === 0) ? recip.toLocaleString() : recip.toFixed(4);
                        return `1 ${this.baseName} (${this.baseCode}) = ${formattedRecip} ${this.displayName} (${this.displayCode})`;
                    }
                    return `Every 1 ${this.displayName} (${this.displayCode}) used in manufacturing or stock counts as ${this.formattedRatio} ${this.baseName} (${this.baseCode})`;
                }
             }"
             class="fixed inset-0 z-50 overflow-y-auto bg-scrim/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-surface-container-lowest rounded-2xl max-w-md w-full p-6 shadow-2xl border border-outline-variant/60">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-base font-bold text-on-surface">{{ $editingUnitId ? 'Edit Unit' : 'Add Unit' }}</h4>
                    <button type="button" wire:click="$set('showUnitModal', false)" class="text-outline hover:text-on-surface text-xl font-bold">
                        &times;
                    </button>
                </div>

                <form wire:submit.prevent="saveUnit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">Unit Name *</label>
                        <input type="text" x-model="unitName" wire:model.live="unitName" placeholder="e.g. Centimeters, Boxes, petermeter" class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/60 rounded-xl text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20">
                        @error('unitName') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">Short Code / Symbol *</label>
                        <input type="text" x-model="unitShortCode" wire:model.live="unitShortCode" placeholder="e.g. cm, box, PM" class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/60 rounded-xl text-sm font-semibold text-on-surface focus:ring-2 focus:ring-primary/20">
                        @error('unitShortCode') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center space-x-2 py-2 px-3 bg-surface rounded-xl border border-outline-variant/60">
                        <input type="checkbox" id="unitIsBase" x-model="unitIsBase" wire:model.live="unitIsBase" class="rounded text-primary w-4 h-4 cursor-pointer">
                        <label for="unitIsBase" class="text-xs font-bold text-on-surface cursor-pointer">Set as Base Unit for Group</label>
                    </div>

                    <div x-show="!unitIsBase">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">Ratio to Base Unit *</label>
                        <input type="number" step="any" x-model="unitRatio" wire:model.live="unitRatio" placeholder="1.0" class="w-full px-3.5 py-2.5 bg-surface border border-outline-variant/60 rounded-xl text-sm font-bold text-primary focus:ring-2 focus:ring-primary/20">
                        <span class="text-[11px] text-outline mt-1 block">How many base units equal 1 of this unit (e.g. 1 Box = 100 Pieces, 1 cm = 0.01 Meters).</span>
                        @error('unitRatio') <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <!-- LIVE RELATIONSHIP PREVIEW CARD -->
                    <div class="p-3.5 rounded-xl border transition-all shadow-xs"
                         :class="unitIsBase ? 'bg-primary/10 border-primary/30 text-primary' : 'bg-secondary-container/20 border-secondary/30 text-on-secondary-container'">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="flex h-2 w-2 rounded-full" :class="unitIsBase ? 'bg-primary animate-pulse' : 'bg-secondary animate-pulse'"></span>
                            <span class="text-[11px] font-extrabold uppercase tracking-wider opacity-90" x-text="unitIsBase ? 'Base Unit Designation' : 'Live Relationship Preview'"></span>
                        </div>

                        <template x-if="unitIsBase">
                            <div>
                                <div class="text-xs font-bold mb-0.5" x-text="`1 ${displayName} (${displayCode}) will be set as the Base Unit for {{ addslashes($selectedGroup->name ?? 'Group') }}.`"></div>
                                <div class="text-[11px] opacity-80 font-medium" x-text="`All other units in this group will calculate their quantities relative to 1 ${displayCode}.`"></div>
                            </div>
                        </template>

                        <template x-if="!unitIsBase">
                            <div>
                                <div class="text-sm font-black tracking-tight mb-1 text-on-surface" x-text="primaryText">
                                    {{ $this->unitRelationshipPreview['primary'] ?? '' }}
                                </div>
                                <div class="text-xs font-semibold text-secondary italic" x-text="explanationText">
                                    {{ $this->unitRelationshipPreview['explanation'] ?? '' }}
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-end space-x-2 pt-3 border-t border-outline-variant/40">
                        <button type="button" wire:click="$set('showUnitModal', false)" class="px-3.5 py-2 text-xs font-bold text-on-surface-variant hover:bg-surface rounded-xl transition-colors">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs bg-primary text-on-primary rounded-xl font-bold hover:bg-primary-container shadow-xs transition-all cursor-pointer">Save Unit</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
