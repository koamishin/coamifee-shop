<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <title>{{ $title ?? 'Goodland Cafe POS' }}</title>
    </head>
    <body class="bg-gradient-to-br from-amber-50 via-orange-50 to-red-50 dark:from-gray-900 dark:via-amber-950 dark:to-orange-950 text-gray-900 dark:text-white min-h-screen flex flex-col">
        <!-- Header -->
        <header class="w-full max-w-4xl mx-auto px-4 pt-3 pb-2">
            <nav class="flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 group" wire:navigate>
                    <div class="w-5 h-5 bg-gradient-to-br from-amber-500 to-orange-600 rounded flex items-center justify-center group-hover:scale-105 transition-transform duration-200">
                        <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C13.1 2 14 2.9 14 4V5H16C17.1 5 18 5.9 18 7V8H20C21.1 8 22 8.9 22 10V12C22 13.1 21.1 14 20 14H18V16C18 17.1 17.1 18 16 18H14V20C14 21.1 13.1 22 12 22S10 21.1 10 20V18H8C6.9 18 6 17.1 6 16V14H4C2.9 14 2 13.1 2 12V10C2 8.9 2.9 8 4 8H6V7C6 5.9 6.9 5 8 5H10V4C10 2.9 10.9 2 12 2Z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-amber-800 dark:text-amber-200">Goodland Cafe POS</span>
                </a>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('dashboard') }}"
                       class="px-3 py-1.5 bg-white/80 hover:bg-white dark:bg-gray-800/80 dark:hover:bg-gray-800 text-gray-900 dark:text-white rounded-md font-medium transition-all duration-200 shadow-md hover:shadow-lg border border-gray-200 dark:border-gray-700 backdrop-blur-sm text-xs">
                        ← Back to Dashboard
                    </a>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center px-4 py-4">
            <div class="max-w-4xl w-full">
                <!-- Auth Card - Horizontal Layout -->
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-xl shadow-lg border border-white/20 dark:border-gray-700/20 overflow-hidden">
                    <div class="flex min-h-[400px]">
                        <!-- Left Side - Branding -->
                        <div class="flex-1 bg-gradient-to-br from-amber-500/10 to-orange-600/10 dark:from-amber-500/5 dark:to-orange-600/5 p-6 flex flex-col justify-center items-center border-r border-white/20 dark:border-gray-700/20">
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl mb-4 shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C13.1 2 14 2.9 14 4V5H16C17.1 5 18 5.9 18 7V8H20C21.1 8 22 8.9 22 10V12C22 13.1 21.1 14 20 14H18V16C18 17.1 17.1 18 16 18H14V20C14 21.1 13.1 22 12 22S10 21.1 10 20V18H8C6.9 18 6 17.1 6 16V14H4C2.9 14 2 13.1 2 12V10C2 8.9 2.9 8 4 8H6V7C6 5.9 6.9 5 8 5H10V4C10 2.9 10.9 2 12 2Z"/>
                                    </svg>
                                </div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Goodland Cafe</h1>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">Professional POS System</p>
                                <div class="mt-6 flex justify-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Fast & Reliable
                                    </span>
                                    <span class="flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Easy to Use
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side - Form -->
                        <div class="flex-1 p-6 flex items-center">
                            <div class="w-full">
                                {{ $slot }}
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <!-- Decorative elements -->
                <div class="absolute -top-3 -right-3 w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full opacity-20 animate-pulse"></div>
                <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-gradient-to-br from-amber-300 to-orange-400 rounded-full opacity-10 animate-pulse" style="animation-delay: 1s;"></div>
            </div>
        </main>

        @fluxScripts
    </body>
</html>
