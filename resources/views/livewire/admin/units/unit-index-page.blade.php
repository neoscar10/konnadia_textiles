<div>
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white tracking-tight">Units Management</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Configure dynamic unit groups, base measurement units, and conversion relationships across the factory system.</p>
        </div>
        <button wire:click="openCreateGroupModal" class="inline-flex items-center gap-2 bg-primary hover:opacity-90 text-on-primary px-5 py-2.5 rounded-xl font-bold text-xs shadow-md transition-all active:scale-95 cursor-pointer" style="background-color: #0f172a !important; color: #ffffff !important;">
            <span class="material-symbols-outlined text-[18px]" style="color: #ffffff !important;">add_circle</span>
            <span style="color: #ffffff !important; font-weight: 700;">Add Unit Group</span>
        </button>
    </div>

    <!-- Search Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700/60 mb-6">
        <div class="relative max-w-md">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search unit groups by name or code..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
            <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </div>

    <!-- Unit Groups Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($groups as $group)
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700/60 hover:shadow-md transition-all duration-200 flex flex-col justify-between relative group">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200/50 dark:border-indigo-800/40">
                            {{ $group->code }}
                        </span>
                        <div class="flex items-center space-x-1">
                            <button wire:click="editGroup({{ $group->id }})" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" title="Edit Group">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>
                            <button onclick="confirm('Delete this unit group?') || event.stopImmediatePropagation()" wire:click="deleteGroup({{ $group->id }})" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" title="Delete Group">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">{{ $group->name }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">{{ $group->description ?: 'No description provided.' }}</p>

                    <!-- Base Unit Badge -->
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-xs">
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Base Unit:</span>
                        @if($group->baseUnit)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                🌟 {{ $group->baseUnit->name }} ({{ $group->baseUnit->short_code }})
                            </span>
                        @else
                            <span class="text-amber-500 font-medium">Not set</span>
                        @endif
                    </div>
                </div>

                <div class="mt-5 pt-3 flex items-center justify-between border-t border-slate-100 dark:border-slate-700/60">
                    <span class="text-xs text-slate-400 font-medium">{{ $group->units->count() }} Units defined</span>
                    <button wire:click="manageUnits({{ $group->id }})" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/40 hover:bg-indigo-100 rounded-lg transition-colors">
                        Manage Units &rarr;
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-800 rounded-2xl p-12 text-center border border-slate-100 dark:border-slate-700/60">
                <p class="text-slate-400 text-sm">No unit groups found. Click "Add Unit Group" to create your first dynamic group.</p>
            </div>
        @endforelse
    </div>

    <!-- Group Modal -->
    @if($showGroupModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 dark:border-slate-700 transform transition-all">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">{{ $editingGroupId ? 'Edit Unit Group' : 'Create Unit Group' }}</h3>
                    <button wire:click="$set('showGroupModal', false)" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                
                <form wire:submit.prevent="saveGroup" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Group Name *</label>
                        <input type="text" wire:model="groupName" placeholder="e.g. Length Units, Zip Units" class="w-full px-3 py-2 border rounded-xl text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white">
                        @error('groupName') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Group Code *</label>
                        <input type="text" wire:model="groupCode" placeholder="e.g. LENGTH, ZIP, COUNT" class="w-full px-3 py-2 border rounded-xl text-sm uppercase dark:bg-slate-900 dark:border-slate-700 dark:text-white">
                        @error('groupCode') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1">Description</label>
                        <textarea wire:model="groupDescription" rows="2" placeholder="Brief description of this unit group..." class="w-full px-3 py-2 border rounded-xl text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white"></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" wire:click="$set('showGroupModal', false)" class="px-4 py-2 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 rounded-xl">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 text-xs bg-primary text-on-primary rounded-xl font-bold hover:opacity-90 shadow-sm transition-all cursor-pointer" style="background-color: #0f172a !important; color: #ffffff !important;">Save Group</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Units Drawer / Modal for Selected Group -->
    @if($selectedGroup)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">Units in {{ $selectedGroup->name }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Configure base unit and conversion ratios relative to the base unit.</p>
                    </div>
                    <button wire:click="closeUnitsDrawer" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                </div>

                <div class="flex justify-between items-center mb-4 pt-2">
                    <span class="text-xs font-semibold uppercase text-slate-500 tracking-wider">Defined Units ({{ $selectedGroup->units->count() }})</span>
                    <button wire:click="openCreateUnitModal" class="inline-flex items-center gap-1.5 bg-primary text-on-primary px-4 py-2 rounded-xl font-bold text-xs shadow-sm transition-all hover:opacity-90 cursor-pointer" style="background-color: #0f172a !important; color: #ffffff !important;">
                        <span class="material-symbols-outlined text-[16px]" style="color: #ffffff !important;">add</span>
                        <span style="color: #ffffff !important; font-weight: 700;">Add Unit</span>
                    </button>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-700 max-h-96 overflow-y-auto pr-1">
                    @forelse($selectedGroup->units as $unit)
                        <div class="py-3 flex items-center justify-between">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="font-semibold text-slate-800 dark:text-white text-sm">{{ $unit->name }}</span>
                                    <span class="text-xs px-2 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded font-mono">{{ $unit->short_code }}</span>
                                    @if($unit->is_base)
                                        <span class="text-[10px] px-2 py-0.5 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300 font-bold rounded-full uppercase">Base Unit (1.0)</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    1 {{ $unit->name }} = <strong>{{ (float)$unit->ratio_to_base }}</strong> {{ $selectedGroup->baseUnit ? $selectedGroup->baseUnit->name : 'Base Unit' }}
                                </p>
                            </div>

                            <div class="flex items-center space-x-2">
                                @if(!$unit->is_base)
                                    <button wire:click="setBaseUnit({{ $unit->id }})" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">Set as Base</button>
                                @endif
                                <button wire:click="editUnit({{ $unit->id }})" class="p-1 text-slate-400 hover:text-indigo-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                @if(!$unit->is_base)
                                    <button wire:click="deleteUnit({{ $unit->id }})" class="p-1 text-slate-400 hover:text-rose-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-slate-400">No units defined yet for this group.</div>
                    @endforelse
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700 text-right">
                    <button wire:click="closeUnitsDrawer" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl">Done</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Add/Edit Unit Sub-Modal -->
    @if($showUnitModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-base font-bold text-slate-800 dark:text-white">{{ $editingUnitId ? 'Edit Unit' : 'Add Unit' }}</h4>
                    <button type="button" wire:click="$set('showUnitModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveUnit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Unit Name *</label>
                        <input type="text" wire:model.live="unitName" placeholder="e.g. Centimeters, Boxes, petermeter" class="w-full px-3.5 py-2.5 border rounded-xl text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500">
                        @error('unitName') <span class="text-rose-500 text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Short Code / Symbol *</label>
                        <input type="text" wire:model.live="unitShortCode" placeholder="e.g. cm, box, PM" class="w-full px-3.5 py-2.5 border rounded-xl text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500">
                        @error('unitShortCode') <span class="text-rose-500 text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center space-x-2 py-2 px-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200/60 dark:border-slate-700">
                        <input type="checkbox" id="unitIsBase" wire:model.live="unitIsBase" class="rounded text-indigo-600 w-4 h-4 cursor-pointer">
                        <label for="unitIsBase" class="text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer">Set as Base Unit for Group</label>
                    </div>

                    @if(!$unitIsBase)
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Ratio to Base Unit *</label>
                            <input type="number" step="0.000001" wire:model.live="unitRatio" placeholder="1.0" class="w-full px-3.5 py-2.5 border rounded-xl text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500">
                            <span class="text-[11px] text-slate-400 mt-1 block">How many base units equal 1 of this unit (e.g. 1 Box = 100 Pieces, 1 cm = 0.01 Meters).</span>
                            @error('unitRatio') <span class="text-rose-500 text-xs block mt-1 font-semibold">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <!-- LIVE RELATIONSHIP PREVIEW CARD -->
                    @if($this->unitRelationshipPreview)
                        @php $preview = $this->unitRelationshipPreview; @endphp
                        <div class="p-3.5 rounded-xl border transition-all shadow-xs {{ $preview['type'] === 'base' ? 'bg-indigo-50/90 border-indigo-200 text-indigo-950 dark:bg-indigo-950/60 dark:border-indigo-800 dark:text-indigo-200' : 'bg-emerald-50/90 border-emerald-200 text-emerald-950 dark:bg-emerald-950/60 dark:border-emerald-800 dark:text-emerald-200' }}">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="flex h-2 w-2 rounded-full {{ $preview['type'] === 'base' ? 'bg-indigo-600 animate-pulse' : 'bg-emerald-500 animate-pulse' }}"></span>
                                <span class="text-[11px] font-extrabold uppercase tracking-wider opacity-90">{{ $preview['title'] }}</span>
                            </div>

                            @if($preview['type'] === 'relationship')
                                <div class="text-sm font-black tracking-tight mb-1 text-slate-900 dark:text-white">
                                    {{ $preview['primary'] }}
                                </div>
                                <div class="text-xs font-semibold text-emerald-800/90 dark:text-emerald-300/90 italic">
                                    {{ $preview['explanation'] }}
                                </div>
                            @else
                                <div class="text-xs font-bold mb-0.5">
                                    {{ $preview['description'] }}
                                </div>
                                <div class="text-[11px] opacity-80 font-medium">
                                    {{ $preview['subtext'] }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" wire:click="$set('showUnitModal', false)" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700 rounded-xl transition-colors">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs bg-slate-900 text-white dark:bg-indigo-600 rounded-xl font-bold hover:opacity-90 shadow-md transition-all cursor-pointer">Save Unit</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
