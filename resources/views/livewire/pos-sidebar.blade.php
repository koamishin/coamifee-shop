<aside
    class="w-72 bg-white/90 dark:bg-[#2a2520]/90 backdrop-blur-xl rounded-2xl shadow-2xl border-2 border-[#e8dcc8] dark:border-[#3d3530] sticky top-24 flex flex-col">

    <!-- Header -->
    <div class="p-4 pb-3 border-b-2 border-[#e8dcc8] dark:border-[#3d3530]">
        <h3 class="text-lg font-serif font-bold text-[#2c2416] dark:text-[#f5f1e8] flex items-center gap-2">
            <svg class="w-5 h-5 text-[#c17a4a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            Menu Categories
        </h3>
        <p class="text-xs text-[#8b7355] dark:text-[#b8a892] mt-1">Browse our offerings</p>
    </div>

    <div class="px-4 py-3 flex flex-col space-y-3">

        <!-- Search Bar - Enhanced -->
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search menu items..."
                class="w-full pl-10 pr-4 py-3 text-sm border-2 border-[#e8dcc8] dark:border-[#3d3530] rounded-xl
                focus:ring-4 focus:ring-[#c17a4a]/20 focus:border-[#c17a4a] dark:bg-[#1a1815] dark:text-[#f5f1e8]
                placeholder-[#8b7355] dark:placeholder-[#6b5f52] transition-all font-medium">
            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-[#c17a4a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <!-- All Items Button - Prominent -->
        <button
            wire:click="selectCategory(0)"
            class="w-full px-4 py-3 rounded-xl font-bold text-sm transition-all duration-300
{{ $selectedCategory == 0
   ? 'bg-gradient-to-r from-[#c17a4a] to-[#a86a3a] text-white shadow-lg scale-[1.02]'
: 'bg-[#f0e6d2] dark:bg-[#3d3530] text-[#2c2416] dark:text-[#f5f1e8]
      hover:bg-[#e8dcc8] dark:hover:bg-[#4d4540] hover:scale-[1.01]' }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span>All Items</span>
                </div>
                @if($selectedCategory == 0)
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                    </svg>
                @endif
            </div>
        </button>

        <!-- Categories List -->
        <div class="mb-2">
            <label class="block text-xs font-bold text-[#8b7355] dark:text-[#b8a892] mb-2 px-1 uppercase tracking-wider">
                Categories
            </label>
            <nav class="space-y-2">
                @forelse($categories as $category)
                    <button
                        wire:key="category-{{ $category->id }}"
                        x-data="{ visible: false }"
                        x-init="setTimeout(() => { visible = true }, {{ $loop->index * 40 }})"
                        x-show="visible"
                        x-transition:enter="transition ease-in-out duration-250 transform"
                        x-transition:enter-start="opacity-0 -translate-x-3 scale-95"
                        x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                        wire:click="selectCategory({{ $category->id }})"
                        class="w-full text-left px-3 py-2.5 rounded-xl font-semibold text-sm transition-all duration-300
    {{ $selectedCategory == $category->id
    ? 'bg-gradient-to-r from-[#c17a4a] to-[#a86a3a] text-white shadow-lg scale-[1.02]'
    : 'text-[#2c2416] dark:text-[#f5f1e8] bg-[#f0e6d2]/50 dark:bg-[#3d3530]/50
                  hover:bg-[#f0e6d2] dark:hover:bg-[#3d3530] hover:scale-[1.01]' }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                <x-filament::icon
                                    :icon="$category->icon ?? 'heroicon-o-tag'"
                                    class="w-5 h-5 flex-shrink-0"
                                />
                                <span class="truncate">{{ $category->name }}</span>
                            </div>
                            @if($selectedCategory == $category->id)
                                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                </svg>
                            @endif
                        </div>
                    </button>
                @empty
                    <p class="text-xs text-[#8b7355] dark:text-[#b8a892] px-3 py-2">No categories available</p>
                @endforelse
            </nav>
        </div>
    </div>

    <!-- Best Sellers Section -->
    <div class="mt-4 px-4 py-3 border-t-2 border-[#e8dcc8] dark:border-[#3d3530] bg-gradient-to-b from-[#fdfaf4]/50 to-transparent dark:from-[#1f1b17]/50">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-[#2c2416] dark:text-[#f5f1e8] flex items-center gap-2">
                <svg class="w-4 h-4 text-[#c17a4a]" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                Best Sellers
            </h3>
            <span class="text-xs px-2 py-0.5 bg-[#c17a4a]/20 text-[#c17a4a] rounded-full font-semibold">Top</span>
        </div>

        <div class="space-y-2">
            @forelse($bestSellers ?? [] as $product)
                <button
                    wire:key="bestseller-{{ $product->id }}"
                    x-data="{ visible: false }"
                    x-init="setTimeout(() => { visible = true }, {{ $loop->index * 60 }})"
                    x-show="visible"
                    x-transition:enter="transition ease-in-out duration-300 transform"
                    x-transition:enter-start="opacity-0 translate-x-4 scale-95"
                    x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                    wire:click="addToCart({{ $product->id }})"
                    class="w-full text-left p-2.5 rounded-xl border-2 border-[#e8dcc8] dark:border-[#4d4540]
        bg-gradient-to-br from-[#faf8f3] to-[#f5f1e8] dark:from-[#3d3530] dark:to-[#2a2520]
        hover:border-[#c17a4a] hover:shadow-md transition-all duration-200 group"
                >
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-lg overflow-hidden bg-[#e8dcc8] dark:bg-[#4d4540] flex-shrink-0 ring-2 ring-white/50 dark:ring-black/20">
                            @if($product->image_url)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_url) }}"
                                     alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-[#8b7355] dark:text-[#b8a892]" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-[#2c2416] dark:text-[#f5f1e8] truncate group-hover:text-[#c17a4a] transition-colors">
                                {{ $product->name }}
                            </p>
                            <p class="text-sm text-[#c17a4a] font-bold">
                                ${{ number_format($product->price, 2) }}
                            </p>
                        </div>

                        <div class="flex-shrink-0 w-7 h-7 rounded-lg bg-[#c17a4a]/10 group-hover:bg-[#c17a4a] flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4 text-[#c17a4a] group-hover:text-white transition-colors"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                    </div>
                </button>
            @empty
                <div class="text-center py-4 px-3 bg-[#f0e6d2]/30 dark:bg-[#3d3530]/30 rounded-xl">
                    <p class="text-xs text-[#8b7355] dark:text-[#b8a892]">No best sellers yet</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Logout Button -->
    <div class="mt-auto px-4 py-3 border-t-2 border-[#e8dcc8] dark:border-[#3d3530] bg-gradient-to-b from-transparent to-[#fdfaf4]/50 dark:to-[#1f1b17]/50">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="w-full flex items-center justify-center gap-2 px-4 py-3
            rounded-xl text-sm font-bold transition-all duration-300
            bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20
            text-red-600 dark:text-red-400
            hover:from-red-500 hover:to-red-600 hover:text-white dark:hover:from-red-500 dark:hover:to-red-600
            border-2 border-red-200 dark:border-red-800 hover:border-red-500
            shadow-sm hover:shadow-lg hover:scale-[1.02] active:scale-95">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5"/>
                </svg>
                <span>Sign Out</span>
            </button>
        </form>
    </div>

</aside>
