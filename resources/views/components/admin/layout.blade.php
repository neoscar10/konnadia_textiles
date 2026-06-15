@props(['title' => 'Dashboard'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} | Kannodia Textiles Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-body-md text-on-surface bg-background antialiased overflow-hidden">
    
    <div class="flex h-screen w-full" x-data="{ sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false' }" x-init="$watch('sidebarOpen', val => localStorage.setItem('sidebarOpen', val))">
        <!-- Sidebar Navigation -->
        <x-admin.sidebar />

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden relative transition-all duration-300" :class="sidebarOpen ? 'ml-[260px]' : 'ml-[70px]'">
            <!-- Top Navigation Bar -->
            <x-admin.topbar :title="$title" />

            <!-- Scrollable Page Content -->
            <main class="flex-1 overflow-y-auto p-gutter relative custom-scrollbar">
                <div class="max-w-[1440px] mx-auto w-full pb-xl">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <!-- Notification Toast Component -->
    <x-admin.toast />

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    @livewireScripts
</body>
</html>
