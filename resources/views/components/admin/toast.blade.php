<div
    x-data="{ show: false, message: '', type: 'success' }"
    x-on:toast.window="
        message = $event.detail.message;
        type = $event.detail.type || 'success';
        show = true;
        setTimeout(() => show = false, 3000);
    "
    class="fixed bottom-4 right-4 z-50 transition-all duration-300 transform"
    x-show="show"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
    x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
    x-transition:leave-end="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
    style="display: none;"
>
    <div class="rounded-md p-4 shadow-ambient flex items-center gap-3 bg-surface border"
         :class="{
             'border-green-500': type === 'success',
             'border-error': type === 'error',
             'border-gold': type === 'warning',
             'border-primary': type === 'info'
         }">
        <span class="material-symbols-outlined text-green-500" x-show="type === 'success'">check_circle</span>
        <span class="material-symbols-outlined text-error" x-show="type === 'error'">error</span>
        <span class="material-symbols-outlined text-gold" x-show="type === 'warning'">warning</span>
        <span class="material-symbols-outlined text-primary" x-show="type === 'info'">info</span>
        <p class="text-sm font-medium text-on-surface" x-text="message"></p>
        <button @click="show = false" class="ml-auto text-on-surface-variant hover:text-on-surface">
            <span class="material-symbols-outlined text-sm">close</span>
        </button>
    </div>
</div>
