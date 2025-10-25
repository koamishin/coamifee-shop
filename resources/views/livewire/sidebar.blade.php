<aside class="w-80 bg-white/70 dark:bg-[#2a2520]/70 backdrop-blur-lg rounded-2xl shadow-md border border-[#e8dcc8] dark:border-[#3d3530] sticky top-24 flex flex-col">

    <!-- Header -->
    <div class="flex justify-between items-center p-3 pb-2">
        <h3 class="text-base font-serif font-bold text-[#2c2416] dark:text-[#f5f1e8]">
            Categories
        </h3>
    </div>

    <div class="px-3 pb-3 flex flex-col space-y-2">

        <!-- All Items Button -->
        <button
        wire:click="$set('selectedCategory', 0)"
        class="w-full px-2 py-2 rounded-md font-medium text-xs transition-all duration-300
        {{ $selectedCategory == 0
        ? 'bg-[#c17a4a] text-white shadow-md'
        : 'bg-[#f0e6d2] dark:bg-[#3d3530] text-[#2c2416] dark:text-[#f5f1e8]
        hover:bg-[#e8dcc8] dark:hover:bg-[#4d4540]' }}">
        <div class="flex items-center justify-between">
        <span>All Items</span>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
        </div>
        </button>

        <!-- Categories List -->
        <nav class="space-y-1">
        @forelse($categories as $category)
        <button
        wire:click="$set('selectedCategory', {{ $category->id }})"
        class="w-full text-left px-2 py-1.5 rounded-md font-medium text-xs transition-all duration-300
        {{ $selectedCategory == $category->id
        ? 'bg-[#c17a4a] text-white shadow-md'
        : 'text-[#2c2416] dark:text-[#f5f1e8]
        hover:bg-[#f0e6d2] dark:hover:bg-[#3d3530]' }}">
        <div class="flex items-center justify-between">
        <span class="truncate text-xs">{{ $category->name }}</span>
        @if($selectedCategory == $category->id)
        <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
        </svg>
        @endif
        </div>
        </button>
        @empty
        <p class="text-xs text-[#8b7355] dark:text-[#b8a892] px-2 py-1">No categories available</p>
        @endforelse
        </nav>
    </div>

    <!-- 🔻 Logout Button (Positioned higher) -->
    <div class="mt-4 p-2 border-t border-[#e8dcc8] dark:border-[#3d3530] bg-[#fdfaf4]/50 dark:bg-[#1f1b17]/50">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="w-full flex items-center justify-center gap-1.5 px-2 py-2
                       rounded-md text-xs font-medium transition-all duration-300
                       bg-[#e4d5b7] dark:bg-[#3d3530] text-[#2c2416] dark:text-[#f5f1e8]
                       hover:bg-[#c17a4a] hover:text-white dark:hover:bg-[#c17a4a]
                       shadow-sm">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5"/>
                </svg>
                Logout
            </button>
        </form>
    </div>

</aside>