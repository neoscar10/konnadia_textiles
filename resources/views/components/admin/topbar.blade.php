@props(['title' => 'Dashboard'])

<header class="sticky top-0 h-14 w-full bg-surface/80 backdrop-blur-md z-40 px-gutter flex justify-between items-center border-b border-outline-variant/30">
    <div class="flex items-center gap-xl">
        <h2 class="font-headline-md text-primary truncate max-w-xs">{{ $title }}</h2>
    </div>

    <div class="flex items-center gap-sm md:gap-lg">
        <div class="flex items-center gap-md pl-md border-l border-outline-variant/30 ml-sm">
            <div class="text-right hidden md:block">
                <p class="font-title-md text-sm text-primary">{{ auth()->user()->name ?? 'Admin User' }}</p>
                <p class="font-label-md text-[10px] text-on-surface-variant">{{ auth()->user()?->roles->first()?->name ?? 'Administrator' }}</p>
            </div>
            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Admin') }}&background=0F2744&color=fff" alt="Profile" class="w-10 h-10 rounded-full border-2 border-surface-container-high object-cover">
        </div>
    </div>
</header>

