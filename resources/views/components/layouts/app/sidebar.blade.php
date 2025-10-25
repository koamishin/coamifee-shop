<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">

        <main class="flex-1">
            {{ $slot }}
        </main>

        @livewireScripts
        @fluxScripts
    </body>
</html>
