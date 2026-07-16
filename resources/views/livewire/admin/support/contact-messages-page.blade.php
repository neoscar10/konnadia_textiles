<div>
    <x-slot:title>Contact Messages</x-slot:title>
    
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-primary tracking-tight">Contact Messages</h1>
            <p class="font-body-md text-on-surface-variant">View and manage messages sent from the customer portal "Contact Us" form.</p>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="flex flex-col sm:flex-row gap-md mb-lg">
        <div class="w-full sm:w-1/3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name, email, subject..." class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
        </div>
        <div class="w-full sm:w-48">
            <select wire:model.live="status" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant/50 rounded-lg focus:ring-2 focus:ring-secondary outline-none transition-all font-body-md text-on-surface">
                <option value="">All Statuses</option>
                <option value="unread">Unread</option>
                <option value="read">Read</option>
            </select>
        </div>
    </div>

    <!-- Data Card -->
    <x-admin.card>
        <x-slot:bodyClass>p-0</x-slot:bodyClass>

        <div class="w-full overflow-visible pb-32">
            <table class="w-full text-left font-body-md">
                <thead class="bg-surface-container text-on-surface-variant font-label-md uppercase tracking-wider border-b border-outline-variant/20">
                    <tr class="whitespace-nowrap text-xs">
                        <th class="px-lg py-md">Date</th>
                        <th class="px-lg py-md">Sender</th>
                        <th class="px-lg py-md">Subject</th>
                        <th class="px-lg py-md text-center">Status</th>
                        <th class="px-lg py-md text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/10">
                    @forelse($messages as $msg)
                    <tr class="hover:bg-primary/[0.02] transition-colors group {{ !$msg->is_read ? 'bg-primary/[0.01] font-semibold' : '' }}">
                        <td class="px-lg py-lg text-on-surface-variant text-sm whitespace-nowrap">
                            {{ $msg->created_at->format('M d, Y h:i A') }}
                        </td>
                        <td class="px-lg py-lg">
                            <div class="font-bold text-primary">{{ $msg->name }}</div>
                            <div class="text-xs text-on-surface-variant font-mono">{{ $msg->email }}</div>
                        </td>
                        <td class="px-lg py-lg text-on-surface text-sm max-w-[300px] truncate" title="{{ $msg->subject }}">
                            {{ $msg->subject }}
                        </td>
                        <td class="px-lg py-lg text-center">
                            @if(!$msg->is_read)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                    Unread
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                                    Read
                                </span>
                            @endif
                        </td>
                        <td class="px-lg py-lg text-right">
                            <div class="flex items-center justify-end gap-xs">
                                <x-admin.button variant="ghost" class="!px-2 !py-1 text-xs" wire:click="showMessage({{ $msg->id }})">
                                    Open
                                </x-admin.button>
                                <x-admin.action-menu>
                                    @if($msg->is_read)
                                        <x-admin.action-menu-item wire:click="markAsUnread({{ $msg->id }})" icon="mark_email_unread" label="Mark Unread" />
                                    @endif
                                    <x-admin.action-menu-item wire:click="deleteMessage({{ $msg->id }})" icon="delete" label="Delete" danger="true" />
                                </x-admin.action-menu>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-lg py-xl text-center text-on-surface-variant font-body-md">
                            No contact messages found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($messages->hasPages())
        <div class="px-lg py-md border-t border-outline-variant/20">
            {{ $messages->links() }}
        </div>
        @endif
    </x-admin.card>

    <!-- Message Detail Modal (Blade overlay trigger via selectedMessage state) -->
    @if($selectedMessage)
        <div class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-4 z-50">
            <div class="bg-white border border-outline-variant/30 rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold text-[#001229]">Support Message Details</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Submitted on {{ $selectedMessage->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <button type="button" wire:click="closeMessage" class="text-slate-400 hover:text-slate-600">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-lg overflow-y-auto max-h-[60vh]">
                    <div class="grid grid-cols-2 gap-md text-xs border-b border-slate-100 pb-md">
                        <div>
                            <span class="text-slate-400 font-semibold uppercase block">Sender Name</span>
                            <span class="font-bold text-[#001229] text-sm mt-0.5 block">{{ $selectedMessage->name }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-semibold uppercase block">Sender Email</span>
                            <span class="font-bold text-[#001229] text-sm mt-0.5 block font-mono">{{ $selectedMessage->email }}</span>
                        </div>
                    </div>
                    
                    <div>
                        <span class="text-slate-400 text-xs font-semibold uppercase block">Subject</span>
                        <span class="font-bold text-[#001229] text-sm mt-0.5 block">{{ $selectedMessage->subject }}</span>
                    </div>

                    <div class="bg-slate-50 border border-outline-variant/20 rounded-xl p-md">
                        <span class="text-slate-400 text-xs font-semibold uppercase block mb-sm">Message Body</span>
                        <p class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed">{{ $selectedMessage->message }}</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <button type="button" wire:click="deleteMessage({{ $selectedMessage->id }})" class="px-4 py-2 rounded-lg text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition-colors shadow-sm">
                        Delete Message
                    </button>
                    <div class="flex items-center gap-xs">
                        <button type="button" wire:click="markAsUnread({{ $selectedMessage->id }})" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-700 border border-outline-variant/30 hover:bg-slate-100 transition-colors bg-white shadow-xs">
                            Mark Unread
                        </button>
                        <button type="button" wire:click="closeMessage" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-700 border border-outline-variant/30 hover:bg-slate-100 transition-colors bg-white shadow-xs">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
