<x-admin.modal id="bulk-upload" title="Bulk Import Customers" maxWidth="6xl">
    <div class="py-md space-y-lg">
        <!-- Progress Stepper -->
        <div class="flex items-center justify-center gap-xl mb-lg">
            <div class="flex items-center gap-sm">
                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $bulkStep === 1 ? 'bg-primary text-white' : 'bg-success text-white' }}">
                    @if($bulkStep > 1) <span class="material-symbols-outlined text-sm">check</span> @else 1 @endif
                </div>
                <span class="font-label-md {{ $bulkStep === 1 ? 'text-primary' : 'text-on-surface-variant' }}">Upload File</span>
            </div>
            <div class="w-16 h-px bg-outline-variant/50"></div>
            <div class="flex items-center gap-sm">
                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $bulkStep === 2 ? 'bg-primary text-white' : ($bulkStep > 2 ? 'bg-success text-white' : 'bg-surface-container text-on-surface-variant') }}">
                    @if($bulkStep > 2) <span class="material-symbols-outlined text-sm">check</span> @else 2 @endif
                </div>
                <span class="font-label-md {{ $bulkStep === 2 ? 'text-primary' : 'text-on-surface-variant' }}">Review & Edit</span>
            </div>
            <div class="w-16 h-px bg-outline-variant/50"></div>
            <div class="flex items-center gap-sm">
                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs {{ $bulkStep === 3 ? 'bg-success text-white' : 'bg-surface-container text-on-surface-variant' }}">3</div>
                <span class="font-label-md {{ $bulkStep === 3 ? 'text-primary' : 'text-on-surface-variant' }}">Report</span>
            </div>
        </div>

        @if($bulkStep === 1)
        <!-- Step 1: File Selection -->
        <div class="max-w-xl mx-auto space-y-lg text-center py-lg"
             x-data="{ isUploading: false, progress: 0 }"
             x-on:livewire-upload-start="isUploading = true"
             x-on:livewire-upload-finish="isUploading = false"
             x-on:livewire-upload-error="isUploading = false"
             x-on:livewire-upload-progress="progress = $event.detail.progress">
            <div class="border-2 border-dashed border-outline-variant/60 rounded-xl p-2xl hover:border-primary/50 transition-colors bg-surface-container-lowest relative group">
                <input type="file" wire:model="bulkFile" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                <div class="space-y-sm">
                    <span class="material-symbols-outlined text-5xl text-outline-variant group-hover:text-primary transition-colors">upload_file</span>
                    <h4 class="font-title-md text-primary">Drag & Drop or Click to Upload</h4>
                    <p class="text-xs text-on-surface-variant">CSV or Excel files (.xlsx, .xls) up to 10MB</p>
                </div>
                @if($bulkFile)
                <div class="mt-lg p-sm bg-surface-container-high rounded text-sm text-primary font-medium flex items-center justify-center gap-sm">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    Uploaded: {{ $bulkFile->getClientOriginalName() }}
                </div>
                @endif
            </div>

            <!-- Upload Progress Bar -->
            <div x-show="isUploading" class="w-full mt-md bg-surface-container p-sm rounded-lg border border-outline-variant/20" x-cloak>
                <div class="w-full bg-surface-container-high rounded-full h-2 overflow-hidden">
                    <div class="bg-primary h-full rounded-full transition-all duration-150" :style="`width: ${progress}%`"></div>
                </div>
                <div class="text-[11px] text-primary font-semibold mt-xs text-center" x-text="`Uploading Template: ${progress}%`"></div>
            </div>

            @error('bulkFile')
            <div class="text-error text-sm font-medium mt-sm">{{ $message }}</div>
            @endif

            <div class="border-t border-outline-variant/20 pt-lg flex items-center justify-between">
                <div class="text-left">
                    <h5 class="font-title-sm text-primary">Need the standard spreadsheet format?</h5>
                    <p class="text-xs text-on-surface-variant">Download our pre-styled template containing reference levels.</p>
                </div>
                <x-admin.button type="button" variant="outline" icon="download" wire:click="downloadTemplate">Download Template</x-admin.button>
            </div>
        </div>

        <x-slot name="footer">
            <div class="w-full flex justify-end gap-md">
                <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                <x-admin.button type="button" variant="primary" icon="navigate_next" wire:click="uploadBulkFile" :disabled="!$bulkFile">Analyze File</x-admin.button>
            </div>
        </x-slot>

        @elseif($bulkStep === 2)
        <!-- Step 2: Review Screen -->
        <div class="space-y-md">
            <!-- Review Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-md">
                <div class="p-md bg-surface-container rounded-lg border border-outline-variant/20">
                    <div class="text-xs text-on-surface-variant font-medium">Total Rows</div>
                    <div class="text-xl font-bold text-primary">{{ count($validatedRows) }}</div>
                </div>
                <div class="p-md bg-[#E6F4EA] rounded-lg border border-[#0F8A46]/20">
                    <div class="text-xs text-[#0F8A46] font-semibold">Valid Rows</div>
                    <div class="text-xl font-bold text-[#0F8A46]">{{ collect($validatedRows)->where('is_valid', true)->count() }}</div>
                </div>
                <div class="p-md bg-error-container/40 rounded-lg border border-error/20">
                    <div class="text-xs text-error font-semibold">Invalid Rows</div>
                    <div class="text-xl font-bold text-error">{{ collect($validatedRows)->where('is_valid', false)->count() }}</div>
                </div>
                <div class="p-md bg-secondary-container/30 rounded-lg border border-secondary/20">
                    <div class="text-xs text-secondary font-semibold">Auto Passwords</div>
                    <div class="text-xl font-bold text-secondary">{{ collect($validatedRows)->where('password', '')->count() }}</div>
                </div>
            </div>

            <!-- Review Rows Table -->
            <div class="w-full overflow-x-auto border border-outline-variant/30 rounded-lg max-h-[400px] overflow-y-auto pb-4 custom-scrollbar">
                <table class="w-full text-left font-body-md border-collapse">
                    <thead class="bg-surface-container sticky top-0 text-on-surface-variant font-label-md uppercase tracking-wider text-xs border-b border-outline-variant/30 z-10">
                        <tr>
                            <th class="px-md py-sm">Status</th>
                            <th class="px-md py-sm">Company</th>
                            <th class="px-md py-sm">GST Number</th>
                            <th class="px-md py-sm">Contact Person</th>
                            <th class="px-md py-sm">Mobile</th>
                            <th class="px-md py-sm">Email</th>
                            <th class="px-md py-sm">Level</th>
                            <th class="px-md py-sm">Limit</th>
                            <th class="px-md py-sm">Password</th>
                            <th class="px-md py-sm text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10 bg-white">
                        @foreach($validatedRows as $index => $row)
                        <tr class="hover:bg-primary/[0.01] transition-colors {{ !$row['is_valid'] ? 'bg-error/[0.02]' : '' }}">
                            <td class="px-md py-sm whitespace-nowrap">
                                <span class="inline-flex items-center px-sm py-xs rounded text-[10px] font-bold uppercase tracking-wider border {{ $row['is_valid'] ? 'bg-success/10 text-success border-success/30' : 'bg-error/10 text-error border-error/30' }}">
                                    {{ $row['is_valid'] ? 'Valid' : 'Error' }}
                                </span>
                            </td>
                            <td class="px-md py-sm whitespace-nowrap font-medium text-primary">{{ $row['company_name'] }}</td>
                            <td class="px-md py-sm whitespace-nowrap font-mono text-xs">{{ $row['gst_number'] }}</td>
                            <td class="px-md py-sm whitespace-nowrap text-on-surface">{{ $row['contact_person'] }}</td>
                            <td class="px-md py-sm whitespace-nowrap text-on-surface-variant font-mono text-xs">{{ $row['mobile_number'] }}</td>
                            <td class="px-md py-sm whitespace-nowrap text-on-surface-variant">{{ $row['email'] ?: 'N/A' }}</td>
                            <td class="px-md py-sm whitespace-nowrap">
                                <span class="px-sm py-xs bg-surface-container text-on-surface rounded text-xs">{{ $row['customer_level_name'] }}</span>
                            </td>
                            <td class="px-md py-sm whitespace-nowrap font-medium">{{ $row['credit_limit'] ? '₹' . number_format($row['credit_limit'], 2) : 'Default' }}</td>
                            <td class="px-md py-sm whitespace-nowrap text-xs font-mono text-on-surface-variant">
                                {{ $row['password'] ?: 'Auto-generated' }}
                            </td>
                            <td class="px-md py-sm text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-sm">
                                    <button type="button" wire:click="editBulkRow({{ $row['temp_id'] }})" class="p-xs text-on-surface-variant hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button type="button" wire:click="removeBulkRow({{ $row['temp_id'] }})" class="p-xs text-on-surface-variant hover:text-error transition-colors">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @if(!$row['is_valid'] || !empty($row['warnings']))
                        <tr class="bg-surface-container-lowest">
                            <td colspan="10" class="px-md py-xs">
                                <div class="space-y-xs pb-sm pl-md">
                                    @foreach($row['errors'] as $err)
                                    <div class="text-error text-xs flex items-center gap-xs font-medium">
                                        <span class="material-symbols-outlined text-[14px]">error</span>
                                        {{ $err }}
                                    </div>
                                    @endforeach
                                    @foreach($row['warnings'] as $warn)
                                    <div class="text-secondary text-xs flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-[14px]">warning</span>
                                        {{ $warn }}
                                    </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <x-slot name="footer">
            <div class="w-full flex justify-between gap-md">
                <x-admin.button type="button" variant="ghost" wire:click="$set('bulkStep', 1)">Back to Upload</x-admin.button>
                <div class="flex gap-md">
                    <x-admin.button type="button" variant="ghost" @click="show = false">Cancel</x-admin.button>
                    <x-admin.button type="button" variant="primary" icon="check_circle" wire:click="importBulkRows" :disabled="collect($validatedRows)->where('is_valid', true)->count() === 0">
                        Import Valid Rows ({{ collect($validatedRows)->where('is_valid', true)->count() }})
                    </x-admin.button>
                </div>
            </div>
        </x-slot>

        @elseif($bulkStep === 3)
        <!-- Step 3: Ingestion Report -->
        <div class="space-y-lg">
            <div class="text-center py-md">
                <div class="w-14 h-14 bg-success/10 rounded-full flex items-center justify-center mx-auto text-success mb-sm">
                    <span class="material-symbols-outlined text-[32px]">task_alt</span>
                </div>
                <h3 class="font-title-lg text-primary">Import Completed</h3>
                <p class="text-sm text-on-surface-variant">Review the batch execution results below. Copy any auto-generated passwords before closing.</p>
            </div>

            <!-- Report Summary -->
            <div class="grid grid-cols-3 gap-md max-w-xl mx-auto">
                <div class="p-sm bg-[#E6F4EA] border border-[#0F8A46]/20 rounded-lg text-center">
                    <div class="text-[10px] uppercase font-bold text-[#0F8A46]">Succeeded</div>
                    <div class="text-lg font-bold text-[#0F8A46]">{{ collect($importReport)->where('status', 'Success')->count() }}</div>
                </div>
                <div class="p-sm bg-error-container/40 border border-error/20 rounded-lg text-center">
                    <div class="text-[10px] uppercase font-bold text-error">Failed</div>
                    <div class="text-lg font-bold text-error">{{ collect($importReport)->where('status', 'Failed')->count() }}</div>
                </div>
                <div class="p-sm bg-surface-container border border-outline-variant/30 rounded-lg text-center">
                    <div class="text-[10px] uppercase font-bold text-on-surface-variant">Total Loaded</div>
                    <div class="text-lg font-bold text-primary">{{ count($importReport) }}</div>
                </div>
            </div>

            <!-- Report Table -->
            <div class="w-full overflow-x-auto border border-outline-variant/30 rounded-lg max-h-[300px] overflow-y-auto pb-4 custom-scrollbar">
                <table class="w-full text-left font-body-md border-collapse">
                    <thead class="bg-surface-container sticky top-0 text-on-surface-variant font-label-md uppercase tracking-wider text-xs border-b border-outline-variant/30 z-10">
                        <tr>
                            <th class="px-md py-sm">Company</th>
                            <th class="px-md py-sm">Contact Person</th>
                            <th class="px-md py-sm">Customer ID</th>
                            <th class="px-md py-sm">Status</th>
                            <th class="px-md py-sm">Message</th>
                            <th class="px-md py-sm">Generated Password</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/10 bg-white">
                        @foreach($importReport as $rep)
                        <tr>
                            <td class="px-md py-sm font-semibold text-primary">{{ $rep['company_name'] }}</td>
                            <td class="px-md py-sm text-on-surface">{{ $rep['contact_person'] }}</td>
                            <td class="px-md py-sm font-bold text-secondary font-mono text-xs">{{ $rep['customer_id'] }}</td>
                            <td class="px-md py-sm">
                                <span class="px-sm py-xs text-[10px] font-bold rounded {{ $rep['status'] === 'Success' ? 'bg-success/15 text-success' : 'bg-error/15 text-error' }}">
                                    {{ $rep['status'] }}
                                </span>
                            </td>
                            <td class="px-md py-sm text-xs text-on-surface-variant">{{ $rep['message'] }}</td>
                            <td class="px-md py-sm whitespace-nowrap">
                                @if($rep['status'] === 'Success' && $rep['generated_password'] !== 'Provided by admin')
                                <div class="flex items-center gap-sm bg-surface-container-low px-xs py-xxs rounded border border-outline-variant/30">
                                    <span class="font-mono text-xs font-bold text-primary select-all">{{ $rep['generated_password'] }}</span>
                                    <button type="button" onclick="navigator.clipboard.writeText('{{ $rep['generated_password'] }}');" class="p-xxs hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-[14px]">content_copy</span>
                                    </button>
                                </div>
                                @else
                                <span class="text-xs text-on-surface-variant/50 italic">{{ $rep['generated_password'] }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-surface-container-low p-md rounded-lg flex items-center justify-between border border-outline-variant/30">
                <div>
                    <h5 class="font-title-sm text-primary">Download Complete Password Report</h5>
                    <p class="text-xs text-on-surface-variant">Get the full spreadsheet containing all credentials and statuses to save locally.</p>
                </div>
                <x-admin.button type="button" variant="outline" icon="download" wire:click="downloadImportReport">Download Report</x-admin.button>
            </div>
        </div>

        <x-slot name="footer">
            <div class="w-full flex justify-center">
                <x-admin.button type="button" variant="primary" @click="show = false">Done & Close</x-admin.button>
            </div>
        </x-slot>
        @endif
    </div>
</x-admin.modal>
