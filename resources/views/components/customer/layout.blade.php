@props(['title' => 'Portal'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title }} | Sapnay Lifestyle</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
            line-height: 1;
        }
    </style>
</head>
<body class="font-sans text-on-surface bg-[#faf9fc] antialiased min-h-screen flex flex-col">
    <!-- Header Navigation -->
    <x-customer.header />

    <!-- Main Content Area -->
    <main class="flex-1 w-full max-w-[1440px] mx-auto px-4 md:px-8 py-6 pb-24 lg:pb-12">
        {{ $slot }}
    </main>

    <!-- Footer (Desktop Only) -->
    <x-customer.footer />

    <!-- Mobile Bottom Navigation (Mobile Only) -->
    <x-customer.bottom-nav />

    <!-- Toast Component for notifications if any -->
    <x-admin.toast />

    @livewireScripts
</body>
</html>
