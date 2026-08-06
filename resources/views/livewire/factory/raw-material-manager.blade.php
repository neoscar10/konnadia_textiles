<div>
    @if($showModal)
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40" wire:click="closeModal"></div>

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

                        <!-- Category Selection -->
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
                                        {{ $cat->name }} ({{ $cat->code }}) — {{ $cat->unit_type->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('raw_material_category_id')
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

                        <!-- Unit Selection (dynamic based on category) -->
                        <div>
                            <label for="rm-unit" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                Unit of Measurement <span class="text-error">*</span>
                            </label>
                            @if(!empty($availableUnits))
                                <div class="flex flex-wrap gap-2">
                                    @foreach($availableUnits as $unitOption)
                                        <button
                                            type="button"
                                            wire:click="$set('unit', '{{ $unitOption }}')"
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
                                    Select a category first to see available units.
                                </p>
                            @endif
                            @error('unit')
                                <p class="text-error text-xs font-semibold mt-1">{{ $message }}</p>
                            @enderror

                            <!-- Unit type hint -->
                            @if($raw_material_category_id)
                                @php
                                    $selectedCat = $categories->find($raw_material_category_id);
                                @endphp
                                @if($selectedCat)
                                    <div class="mt-2 flex items-center gap-1.5 text-[11px] text-on-surface-variant/70 font-semibold">
                                        <span class="material-symbols-outlined text-[14px] text-secondary">info</span>
                                        <span>{{ $selectedCat->name }} uses <strong>{{ $selectedCat->unit_type->label() }}</strong> units.</span>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <!-- Width Configuration (Only for Length-based) -->
                        @if($this->isLengthBased())
                            <div class="grid grid-cols-2 gap-4 bg-surface-container-low/30 border border-outline-variant/40 rounded-xl p-4">
                                <div>
                                    <label for="rm-standard-width" class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                        Standard Width <span class="text-error">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        id="rm-standard-width"
                                        wire:model="standard_width"
                                        placeholder="e.g., 44, 58"
                                        class="w-full py-2 px-3 rounded-xl border font-body-md text-sm focus:outline-none transition-colors
                                            {{ $errors->has('standard_width') ? 'border-error focus:border-error focus:ring-1 focus:ring-error' : 'border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary' }}"
                                    />
                                    @error('standard_width')
                                        <p class="text-error text-[10px] font-semibold mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-1.5">
                                        Width Unit <span class="text-error">*</span>
                                    </label>
                                    <div class="flex gap-4 items-center h-[38px]">
                                        <label class="inline-flex items-center text-sm font-medium text-on-surface cursor-pointer">
                                            <input type="radio" wire:model="width_unit" value="Inch" class="text-primary focus:ring-primary mr-2" />
                                            Inch
                                        </label>
                                        <label class="inline-flex items-center text-sm font-medium text-on-surface cursor-pointer">
                                            <input type="radio" wire:model="width_unit" value="CM" class="text-primary focus:ring-primary mr-2" />
                                            CM
                                        </label>
                                    </div>
                                    @error('width_unit')
                                        <p class="text-error text-[10px] font-semibold mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
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
