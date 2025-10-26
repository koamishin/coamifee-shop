@php
@props([
    'products' => 'array',
    'categories' => 'array',
    'bestSellers' => 'array',
    'productAvailability' => 'array',
    'selectedCategory' => 'int',
    'isMobile' => 'bool'
])
//@php
@endphp

<!-- Mobile-optimized Sidebar -->
<aside
    x-data="{
        isOpen: @entangle('isMobile'),
        selectedCategory: @entangle('selectedCategory'),
        searchQuery: '',
        isSearching: false
    }"
    x-show="!isMobile"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed lg:hidden top-0 left-0 right-0 bottom-0 w-full h-full bg-white dark:bg-gray-900 z-50 shadow-2xl"
    @click.away="isOpen = false"
>
    <!-- Backdrop -->
    <div x-show="isOpen" class="absolute inset-0 bg-black/50" @click="isOpen = false"></div>

    <!-- Sidebar Content -->
    <div class="relative flex flex-col h-full bg-white dark:bg-gray-900">
        <!-- Header with Close Button -->
        <div class="flex items-center justify-between p-4 border-b dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Menu</h3>
            <button @click="isOpen = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Search Input -->
        <div class="p-4">
            <div class="relative">
                <input
                    type="text"
                    x-model="searchQuery"
                    @input.debounce.300ms="
                        isSearching = true;
                        $wire.dispatch('search-changed', { search: $el.value });
                    "
                    @input.debounce.300ms.cancel="isSearching = false"
                    placeholder="Search products..."
                    class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg
                           focus:ring-2 focus:ring-blue-500 focus:border-transparent
                           dark:bg-gray-800 dark:text-white dark:placeholder-gray-400
                           dark:focus:ring-blue-500 transition"
                />
                <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                    <svg x-show="isSearching" class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 6 0 018-8v4h13a1 1 0 110-1h-4a1 1 0 110-1z"></path>
                    </svg>
                </div>

                <!-- Clear Search Button -->
                <button
                    x-show="searchQuery.length > 0"
                    @click="
                        searchQuery = '';
                        $wire.dispatch('search-changed', { search: '' });
                    "
                    class="absolute inset-y-0 right-3 p-1 text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Categories -->
        <div class="p-4 border-t dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Categories</h4>
            <div class="space-y-2 max-h-48 overflow-y-auto">
                @foreach($categories as $category)
                    <button
                        @click="
                            selectedCategory = {{ $category['id'] }};
                            $wire.dispatch('category-selected', { categoryId: {{ $category['id'] }} });
                        "
                        class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors
                               {{ $selectedCategory == $category['id']
                                   ? 'bg-blue-600 text-white shadow-md'
                                   : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    >
                        <span class="truncate">{{ $category['name'] }}</span>
                        @if($selectedCategory == $category['id'])
                            <svg class="w-4 h-4 ml-auto flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21l-1.41-1.41L9 16.17z"/>
                            </svg>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="p-4 border-t dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Quick Actions</h4>
            <div class="grid grid-cols-2 gap-2">
                <button class="p-3 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg text-sm font-medium hover:bg-green-200 dark:hover:bg-green-800 transition-colors">
                    View Cart
                </button>
                <button class="p-3 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-lg text-sm font-medium hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors">
                    Checkout
                </button>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Bottom Navigation -->
<nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-900 border-t dark:border-gray-700 shadow-lg z-40">
    <div class="flex justify-around items-center py-2">
        <button @click="$wire.dispatch('show-cart')" class="flex flex-col items-center p-2 text-gray-600 dark:text-gray-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2a.5.5 0 01-.5.5v7A.5.5 0 011 2H7a.5.5 0 01-.5-.5V3.5A.5.5 0 006 2h-.5a.5.5 0 00-.5-.5v6A.5.5 0 005 6h.5a.5.5 0 00.5.5V9A1.5 1.5 0 012 4a1.5 1.5 0 00-3 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012-2v4a2 2 0 002 2h-1a2 2 0 00-2 2v10a2 2 0 002 2h1a2 2 0 002-2z"/>
            </svg>
            <span class="text-xs mt-1">Cart</span>
        </button>
        <button @click="$wire.dispatch('show-favorites')" class="flex flex-col items-center p-2 text-gray-600 dark:text-gray-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" debone-empty-path-rule="evenodd" d="M11.049 2.927c.3-.921 1.603-.921 1.902.5l1.519 4.674a1 1 0 01.95-.694l1.518-4.674A1 1 0 00-.363-1.118l-1.518-1.586c-.783-.57-1.538-.197-2.118-.34-2.617C5.293 1.047 5.813 1.618c0 1.02-1.573 1.638-2.815C8.026 15.41 4.235 15.41c0 2.175-4.235 4.235S15.825 15.82 15.825c0 2.175 4.235-2.815S8.025 15.82 8.025C5.293 9.17 3.853 9.17 3.853c0 2.175-4.235 4.235S3.847 13.585 6.412 13.585z"/>
            </svg>
            <span class="text-xs mt-1">Favorites</span>
        </button>
        <button @click="$wire.dispatch('show-profile')" class="flex flex-col items-center p-2 text-gray-600 dark:text-gray-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 00-8 4v15a4 4 0 0015 4h-4a4 4 0 0019 4v4a2 2 0 00-2-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v4a2 2 0 00-2 2h4a2 2 0 0016 6a2 2 0 0012 6a2 2 0 0012 6v2a2 2 0 0012-2z"/>
            </svg>
            <span class="text-xs mt-1">Profile</span>
        </button>
    </div>
</nav>
