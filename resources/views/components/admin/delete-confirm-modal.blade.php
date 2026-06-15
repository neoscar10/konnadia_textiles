@props(['id', 'title' => 'Confirm Deletion', 'message' => 'Are you sure you want to delete this item? This action cannot be undone.'])

<x-admin.modal :id="$id" :title="$title" maxWidth="md">
    <div class="flex items-start gap-md">
        <div class="w-12 h-12 rounded-full bg-error-container/50 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-error text-[24px]">warning</span>
        </div>
        <div class="pt-sm">
            <p class="font-body-md text-on-surface-variant">{{ $message }}</p>
        </div>
    </div>

    <x-slot name="footer">
        <x-admin.button variant="ghost" @click="show = false">
            Cancel
        </x-admin.button>
        <x-admin.button variant="danger" icon="delete">
            Delete
        </x-admin.button>
    </x-slot>
</x-admin.modal>
