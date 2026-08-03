<div>
    <!-- Page Header -->
    <div class="bg-surface-container-lowest border-b border-outline-variant/60 p-6 rounded-2xl mb-6 shadow-xs">
        <nav class="flex mb-2 text-xs text-on-surface-variant font-semibold space-x-2">
            <a href="{{ route('admin.dashboard') }}" wire:navigate class="hover:text-primary transition-colors">Dashboard</a>
            <span>&gt;</span>
            <a href="{{ route('factory.tasks.index') }}" wire:navigate class="hover:text-primary transition-colors">Task Master</a>
            <span>&gt;</span>
            <span class="text-primary font-bold">{{ $taskId ? 'Edit Task' : 'Create Task' }}</span>
        </nav>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-headline-lg text-headline-lg text-primary font-extrabold tracking-tight">
                    {{ $taskId ? 'Edit Task Details' : 'Configure New Task Master' }}
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant mt-1">Setup reusable manufacturing steps, consumption permissions, and labor requirements.</p>
            </div>
            <a href="{{ route('factory.tasks.index') }}" wire:navigate class="inline-flex items-center gap-2 bg-surface-container-high text-primary px-5 py-2.5 rounded-xl font-bold text-xs hover:bg-surface-container-highest shadow-xs transition-all">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                Back to List
            </a>
        </div>
    </div>

    <!-- Form Layout -->
    <form wire:submit="save" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Side Cards (8 Cols) -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- Bento Card 1: Basic Information -->
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
                <div class="flex items-center gap-3 px-6 py-4 border-b border-outline-variant/60 bg-surface-container-low/30">
                    <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">info</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-primary">Basic Task Information</h3>
                        <p class="text-on-surface-variant text-xs">Specify the name, identity code, and operational status of this manufacturing step.</p>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block font-label-md text-xs font-bold text-on-surface-variant mb-2">Task Name *</label>
                        <input
                            type="text"
                            wire:model="name"
                            placeholder="e.g. Cut & Align Fabric Sheets"
                            class="w-full rounded-xl border border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary font-body-md px-4 py-3 bg-surface text-sm font-semibold"
                        />
                        @error('name')
                            <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block font-label-md text-xs font-bold text-on-surface-variant mb-2">Task Code (Auto)</label>
                        <input
                            type="text"
                            wire:model="code"
                            placeholder="TSK-XXXX"
                            {{ $taskId ? 'disabled' : '' }}
                            class="w-full rounded-xl border border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary font-body-md px-4 py-3 bg-surface-container-high/40 text-on-surface-variant/75 text-sm font-mono font-bold disabled:bg-surface-container-high/40"
                        />
                        @error('code')
                            <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block font-label-md text-xs font-bold text-on-surface-variant mb-2">Default Sequence Number (Optional)</label>
                        <input
                            type="number"
                            step="1"
                            min="1"
                            wire:model="sequence_number"
                            placeholder="e.g. 10"
                            class="w-full rounded-xl border border-outline-variant/60 focus:border-primary focus:ring-1 focus:ring-primary font-body-md px-4 py-3 bg-surface text-sm font-semibold"
                        />
                        @error('sequence_number')
                            <span class="text-error text-xs block mt-1 font-semibold">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Bento Card 2: Material Consumption Configuration -->
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/60 bg-surface-container-low/30">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">category</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-secondary">Raw Material Consumption Setup</h3>
                            <p class="text-on-surface-variant text-xs">Configure if jobs execution for this task triggers raw material inventory picker deductions.</p>
                        </div>
                    </div>
                    
                    <label class="relative inline-flex items-center cursor-pointer select-none">
                        <input type="checkbox" wire:model.live="consumes_raw_material" class="sr-only peer">
                        <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-secondary"></div>
                        <span class="font-label-md text-xs font-bold ml-2 {{ $consumes_raw_material ? 'text-secondary' : 'text-on-surface-variant' }}">
                            {{ $consumes_raw_material ? 'Consumes' : 'Does not consume' }}
                        </span>
                    </label>
                </div>

                <div class="p-6">
                    @if($consumes_raw_material)
                        <div class="space-y-4">
                            <p class="text-xs text-on-surface-variant font-semibold uppercase tracking-wider">Select Allowed Raw Material Categories for Consumption picker</p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach($categories as $cat)
                                    @php $checked = in_array((string)$cat->id, $selected_category_ids); @endphp
                                    <label
                                        class="flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all {{ $checked ? 'border-secondary bg-secondary-container/20' : 'border-outline-variant/40 bg-surface-container-low/20 hover:border-secondary/50' }}"
                                    >
                                        <input
                                            type="checkbox"
                                            wire:model.live="selected_category_ids"
                                            value="{{ $cat->id }}"
                                            class="w-4 h-4 rounded border-outline-variant text-secondary focus:ring-secondary"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-sm truncate">{{ $cat->name }}</p>
                                            <p class="text-xs text-on-surface-variant font-mono">{{ $cat->code }}</p>
                                        </div>
                                        @if($checked)
                                            <span class="material-symbols-outlined text-secondary text-[18px] flex-shrink-0">check_circle</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>

                            @error('selected_category_ids')
                                <span class="text-error text-xs block mt-2 font-semibold">{{ $message }}</span>
                            @enderror

                            <div class="bg-secondary-container/10 border border-secondary/20 rounded-xl px-4 py-3 flex items-start gap-2 mt-4">
                                <span class="material-symbols-outlined text-secondary text-[16px] mt-0.5 flex-shrink-0">info</span>
                                <p class="text-xs text-on-surface-variant">
                                    <strong class="text-on-surface">Rule check:</strong> The selected categories restrict which inventory items supervisors can pick and deduct during real-time job tracking.
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center text-center py-6">
                            <span class="material-symbols-outlined text-4xl text-outline mb-2">disabled_by_default</span>
                            <p class="text-sm text-on-surface-variant">This task operates without consuming any raw material stocks.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Bento Card 3: Labor Involvement Configuration -->
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 shadow-xs overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/60 bg-surface-container-low/30">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-tertiary/10 text-tertiary flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">groups</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-tertiary">Labor Involvement & Wages Setup</h3>
                            <p class="text-on-surface-variant text-xs">Configure if the factory stage demands operator assignments and pays labor wages.</p>
                        </div>
                    </div>
                    
                    <label class="relative inline-flex items-center cursor-pointer select-none">
                        <input type="checkbox" wire:model.live="is_labor_required" class="sr-only peer">
                        <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-tertiary"></div>
                        <span class="font-label-md text-xs font-bold ml-2 {{ $is_labor_required ? 'text-tertiary' : 'text-on-surface-variant' }}">
                            {{ $is_labor_required ? 'Labor Dependent' : 'Non-Labor Stage' }}
                        </span>
                    </label>
                </div>

                <div class="p-6">
                    @if($is_labor_required)
                        <div class="bg-tertiary-container/10 border border-tertiary/20 rounded-xl px-4 py-3 flex items-start gap-2">
                            <span class="material-symbols-outlined text-tertiary text-[16px] mt-0.5 flex-shrink-0">check_circle</span>
                            <p class="text-xs text-on-surface-variant">
                                <strong class="text-on-surface">Labor-dependent:</strong> Enabling this flag marks the stage as available for labor logs, factory attendance logs, and standard wage rollups. Labor rates are defined per-product routing.
                            </p>
                        </div>
                    @else
                        <div class="bg-surface-container-high/30 border border-outline-variant/30 rounded-xl px-4 py-3 flex items-start gap-2">
                            <span class="material-symbols-outlined text-outline text-[16px] mt-0.5 flex-shrink-0">info</span>
                            <p class="text-xs text-on-surface-variant">
                                <strong class="text-on-surface">Non-Labor:</strong> This stage bypasses labor assignment, check-ins, or labor costing records during production job executions.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <!-- Right Side Settings & Action Sidebar (4 Cols) -->
        <div class="lg:col-span-4 space-y-6">
            
            <!-- Settings Card -->
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 p-6 shadow-xs">
                <h4 class="font-headline-sm text-headline-sm font-bold text-sm mb-4">Task Parameters</h4>
                
                <div class="space-y-4">
                    <!-- Status Parameter -->
                    <div>
                        <label class="block font-label-md text-xs font-bold text-on-surface-variant mb-2">Status</label>
                        <select wire:model="status" class="w-full bg-surface border border-outline-variant/60 rounded-xl px-4 py-3 text-sm font-bold text-on-surface focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <!-- Meta specifications -->
                    <div class="pt-4 border-t border-outline-variant/40 space-y-2 text-xs text-on-surface-variant font-semibold">
                        <div class="flex justify-between">
                            <span>Type</span>
                            <span class="text-primary font-bold">Reusable Master Task</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Allows Multiple Categories</span>
                            <span class="text-secondary font-bold">Yes</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit card -->
            <div class="bg-surface-container-lowest rounded-2xl border border-outline-variant/60 p-6 shadow-xs">
                <button
                    type="submit"
                    class="w-full bg-primary hover:bg-primary-container text-on-primary font-bold py-3.5 rounded-xl transition-all shadow-md active:scale-95 flex items-center justify-center gap-2 text-xs"
                >
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    {{ $taskId ? 'Save Task Configuration' : 'Create Task Master' }}
                </button>
                <a
                    href="{{ route('factory.tasks.index') }}"
                    wire:navigate
                    class="w-full mt-3 inline-flex justify-center border border-outline-variant hover:bg-surface-container-low text-on-surface-variant font-bold py-3 rounded-xl transition-all text-xs text-center"
                >
                    Cancel
                </a>
            </div>

        </div>

    </form>
</div>
