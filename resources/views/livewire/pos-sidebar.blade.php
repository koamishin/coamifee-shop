<aside class="w-80 bg-white/70 dark:bg-[#2a2520]/70 backdrop-blur-lg rounded-2xl shadow-md border border-[#e8dcc8] dark:border-[#3d3530] sticky top-24 flex flex-col">

<!-- Header -->
    <div class="flex justify-between items-center p-3 pb-2">
    <h3 class="text-base font-serif font-bold text-[#2c2416] dark:text-[#f5f1e8]">
        POS Navigation
    </h3>
</div>

<div class="px-3 pb-3 flex flex-col space-y-2">

    <!-- Search Bar -->
    <div class="relative mb-2">
        <input
        type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search products..."
                    class="w-full pl-8 pr-3 py-2 text-xs border border-[#e8dcc8] dark:border-[#3d3530] rounded-md
                focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent dark:bg-[#1a1815] dark:text-[#f5f1e8]
        placeholder-[#8b7355] dark:placeholder-[#6b5f52] transition">
        <div class="absolute inset-y-0 left-2 flex items-center pointer-events-none">
                <svg class="h-3 h-3 text-[#8b7355]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
</div>

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

        <!-- Best Sellers Section -->
        <div class="mt-4 p-2 border-t border-[#e8dcc8] dark:border-[#3d3530] bg-[#fdfaf4]/50 dark:bg-[#1f1b17]/50">
        <h3 class="text-xs font-bold text-[#2c2416] dark:text-[#f5f1e8] mb-2 flex items-center gap-1">
        <svg class="w-3 h-3 text-[#c17a4a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
        </svg>
        Best Sellers
        </h3>

        <div class="space-y-1">
        @forelse($bestSellers ?? [] as $product)
        <button
        wire:click="addToCart({{ $product->id }})"
        class="w-full text-left p-1.5 rounded-md border border-[#e8dcc8] dark:border-[#4d4540]
        bg-[#faf8f3] dark:bg-[#3d3530] hover:border-[#c17a4a] transition-all duration-200 group"
        >
        <div class="flex items-center gap-1.5">
        <div class="w-6 h-6 rounded overflow-hidden bg-[#e8dcc8] dark:bg-[#4d4540] flex-shrink-0">
        @if($product->image_url)
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
        @else
        <div class="w-full h-full flex items-center justify-center">
        <svg class="w-3 h-3 text-[#8b7355] dark:text-[#b8a892]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        </div>
        @endif
        </div>

                        <div class="flex-1 min-w-0">
        <p class="text-xs font-medium text-[#2c2416] dark:text-[#f5f1e8] truncate group-hover:text-[#c17a4a] transition-colors">
        {{ $product->name }}
        </p>
        <p class="text-xs text-[#c17a4a] font-bold">
        ${{ number_format($product->price, 2) }}
        </p>
        </div>

                        <div class="flex-shrink-0">
        <svg class="w-3 h-3 text-[#c17a4a] opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        </div>
        </div>
        </button>
        @empty
        <div class="text-center py-2">
        <p class="text-xs text-[#8b7355] dark:text-[#b8a892]">No best sellers yet</p>
        </div>
        @endforelse
        </div>
        </div>

    <!-- Logout Button -->
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
