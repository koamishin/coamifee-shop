<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Goodland Cafe - POS System</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="bg-gradient-to-br from-amber-50 via-orange-50 to-red-50 dark:from-gray-900 dark:via-amber-950 dark:to-orange-950 text-gray-900 dark:text-white min-h-screen flex flex-col">
        <header class="w-full max-w-6xl mx-auto px-6 pt-6 pb-4">
            @if (Route::has('login'))
                <nav class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 bg-gradient-to-br from-amber-500 to-orange-600 rounded-md flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C13.1 2 14 2.9 14 4V5H16C17.1 5 18 5.9 18 7V8H20C21.1 8 22 8.9 22 10V12C22 13.1 21.1 14 20 14H18V16C18 17.1 17.1 18 16 18H14V20C14 21.1 13.1 22 12 22S10 21.1 10 20V18H8C6.9 18 6 17.1 6 16V14H4C2.9 14 2 13.1 2 12V10C2 8.9 2.9 8 4 8H6V7C6 5.9 6.9 5 8 5H10V4C10 2.9 10.9 2 12 2Z"/>
                            </svg>
                        </div>
                        <span class="text-base font-semibold text-amber-800 dark:text-amber-200">Goodland Cafe POS</span>
                    </div>

                    <div class="flex items-center space-x-3">
                        @auth
                            <a
                                href="{{ url('/dashboard') }}"
                                class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white rounded-lg font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 text-sm"
                            >
                                Dashboard
                            </a>
                        @else
                            <a
                                href="{{ route('login') }}"
                                class="px-4 py-2 bg-white/80 hover:bg-white dark:bg-gray-800/80 dark:hover:bg-gray-800 text-gray-900 dark:text-white rounded-lg font-medium transition-all duration-200 shadow-md hover:shadow-lg border border-gray-200 dark:border-gray-700 backdrop-blur-sm text-sm"
                            >
                                Log in
                            </a>

                            @if (Route::has('register'))
                                <a
                                    href="{{ route('register') }}"
                                    class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white rounded-lg font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 text-sm"
                                >
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    </div>
                </nav>
            @endif
        </header>
        <main class="flex-1 flex items-center justify-center px-6 py-8">
            <div class="max-w-5xl w-full">
                <div class="text-center mb-10">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl mb-6 shadow-xl">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C13.1 2 14 2.9 14 4V5H16C17.1 5 18 5.9 18 7V8H20C21.1 8 22 8.9 22 10V12C22 13.1 21.1 14 20 14H18V16C18 17.1 17.1 18 16 18H14V20C14 21.1 13.1 22 12 22S10 21.1 10 20V18H8C6.9 18 6 17.1 6 16V14H4C2.9 14 2 13.1 2 12V10C2 8.9 2.9 8 4 8H6V7C6 5.9 6.9 5 8 5H10V4C10 2.9 10.9 2 12 2Z"/>
                        </svg>
                    </div>

                    <h1 class="text-4xl lg:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                        Welcome to <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 to-orange-600">Goodland Cafe</span>
                    </h1>

                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-xl mx-auto leading-relaxed">
                        Your complete Point of Sale system for seamless cafe operations.
                        Manage orders, inventory, and customers with ease.
                    </p>
                </div>

                <div class="grid lg:grid-cols-2 gap-8 items-center">
                    <!-- Features Card -->
                    <div class="bg-white/70 dark:bg-gray-800/70 backdrop-blur-lg rounded-2xl p-6 shadow-xl border border-white/20 dark:border-gray-700/20">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Quick Access</h2>

                        <div class="space-y-3">
                            <a href="{{ route('pos') }}"
                               class="group flex items-center p-3 bg-gradient-to-r from-amber-50 to-orange-50 dark:from-gray-700 dark:to-gray-600 rounded-lg border border-amber-200 dark:border-gray-600 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                                <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center mr-3 group-hover:scale-105 transition-transform duration-200">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">POS Terminal</h3>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Process orders and manage transactions</p>
                                </div>
                            </a>

                            <a href="{{ route('dashboard') }}"
                               class="group flex items-center p-3 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-600 rounded-lg border border-blue-200 dark:border-gray-600 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3 group-hover:scale-105 transition-transform duration-200">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Dashboard & Analytics</h3>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Monitor sales, inventory, and performance</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Hero Image/Illustration -->
                    <div class="relative">
                        <div class="bg-gradient-to-br from-amber-400/20 to-orange-500/20 dark:from-amber-500/10 dark:to-orange-600/10 rounded-2xl p-6 backdrop-blur-sm">
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-amber-500 to-orange-600 rounded-full mb-4 shadow-xl">
                                    <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C13.1 2 14 2.9 14 4V5H16C17.1 5 18 5.9 18 7V8H20C21.1 8 22 8.9 22 10V12C22 13.1 21.1 14 20 14H18V16C18 17.1 17.1 18 16 18H14V20C14 21.1 13.1 22 12 22S10 21.1 10 20V18H8C6.9 18 6 17.1 6 16V14H4C2.9 14 2 13.1 2 12V10C2 8.9 2.9 8 4 8H6V7C6 5.9 6.9 5 8 5H10V4C10 2.9 10.9 2 12 2Z"/>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">Goodland Cafe</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Professional POS System</p>

                                <div class="flex justify-center space-x-3 text-xs text-gray-500 dark:text-gray-400">
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

                        <!-- Decorative elements -->
                        <div class="absolute -top-3 -right-3 w-16 h-16 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full opacity-20 animate-pulse"></div>
                        <div class="absolute -bottom-4 -left-4 w-20 h-20 bg-gradient-to-br from-amber-300 to-orange-400 rounded-full opacity-10 animate-pulse" style="animation-delay: 1s;"></div>
                    </div>
                </div>
            </div>
        </main>

        @if (Route::has('login'))
            <div class="h-14.5 hidden lg:block"></div>
        @endif
    </body>
</html>
