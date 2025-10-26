<div class="min-h-screen bg-gradient-to-br from-[#faf8f3] via-[#f5f1e8] to-[#ede8df] dark:from-[#1a1815] dark:via-[#2a2520] dark:to-[#1f1b17] transition-colors duration-300 font-sans">

    <!-- HEADER -->
    <header class="backdrop-blur-md bg-white/80 dark:bg-[#2a2520]/80 border-b border-[#e8dcc8] dark:border-[#3d3530] sticky top-0 z-50 shadow-sm">
        <div class="w-full px-4 py-3 flex justify-between items-center">

            <!-- Brand -->
            <div class="flex items-center gap-4">
                <div class="flex flex-col">
                    <h1 class="text-2xl font-serif font-bold text-[#2c2416] dark:text-[#f5f1e8] tracking-tight">
                        Goodland Café
                    </h1>
                    <p class="text-xs text-[#8b7355] dark:text-[#b8a892] font-medium tracking-wide">POS SYSTEM</p>
                </div>
                <div class="flex items-center gap-2 ml-4 pl-4 border-l border-[#e8dcc8] dark:border-[#3d3530]">
                    <div class="w-2.5 h-2.5 bg-[#c17a4a] rounded-full animate-pulse"></div>
                    <span class="text-xs text-[#8b7355] dark:text-[#b8a892] font-medium">Online</span>
                </div>
            </div>

            <!-- Center: Sales Summary -->
            <div class="hidden lg:flex items-center gap-8 text-sm">
                <div class="text-center">
                    <p class="text-[#8b7355] dark:text-[#b8a892] text-xs uppercase tracking-wide">Today's Sales</p>
                    <p class="text-lg font-bold text-[#c17a4a]">${{ number_format($todaySales ?? 0, 2) }}</p>
                </div>
                <div class="w-px h-8 bg-[#e8dcc8] dark:bg-[#3d3530]"></div>
                <div class="text-center">
                    <p class="text-[#8b7355] dark:text-[#b8a892] text-xs uppercase tracking-wide">Orders</p>
                    <p class="text-lg font-bold text-[#2c2416] dark:text-[#f5f1e8]">{{ $todayOrders ?? 0 }}</p>
                </div>
            </div>

            <!-- Right Side -->
            <div class="flex items-center gap-6">
                <!-- Date -->
                <div class="text-sm text-[#8b7355] dark:text-[#b8a892] font-medium hidden sm:block">
                    {{ now()->format('M j, Y • g:i A') }}
                </div>

                <!-- Theme Toggle -->
                <button id="theme-toggle" class="w-12 h-6 rounded-full bg-[#e8dcc8] dark:bg-[#3d3530] relative focus:ring-2 focus:ring-[#c17a4a] transition">
                    <span id="theme-thumb" class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white dark:bg-[#2a2520] transform transition-transform duration-300"></span>
                </button>

                <!-- Cart Count -->
                <div class="flex items-center gap-3 bg-gradient-to-r from-[#f0e6d2] to-[#ede3d0] dark:from-[#3d3530] dark:to-[#454035]
                px-5 py-3 rounded-full border border-[#e8dcc8] dark:border-[#4d4540]
                shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 group">
                <svg class="w-5 h-5 text-[#c17a4a] group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span class="text-sm text-[#2c2416] dark:text-[#f5f1e8] font-bold">{{ $this->getCartItemCount() }}</span>
                    @if($this->getCartItemCount() > 0)
                        <div class="w-2 h-2 bg-[#c17a4a] rounded-full animate-ping"></div>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN LAYOUT WITH SIDEBAR -->
    <div class="flex">
        <!-- Sidebar Component -->
        <livewire:sidebar />

        <!-- MAIN CONTENT -->
        <main class="flex-1 px-4 py-6">
            <div class="w-full grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- PRODUCTS -->
                <section class="lg:col-span-2 space-y-4">
                    <!-- Loading Indicator -->
                    <div wire:loading wire:target="search,selectedCategory" class="flex justify-center items-center py-8">
                        <div class="flex items-center gap-2 text-[#c17a4a]">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm font-medium">Loading products...</span>
                        </div>
                    </div>

                <div wire:loading.remove wire:target="search,selectedCategory" x-data="{ selected: @entangle('selectedProductId') }" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3 gap-6 min-h-[200px]">
                        @foreach($products as $product)
                            <div
                            wire:key="product-{{ $product->id }}"
                            x-data="{ visible: false }"
                            x-init="$nextTick(() => { visible = true })"
                            x-show="visible"
                            x-transition:enter="transition ease-in-out duration-300 transform"
                            x-transition:enter-start="opacity-0 scale-90 -translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in-out duration-200 transform"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-90 translate-y-2"
                            @click="$wire.addToCart({{ $product->id }}); selected = {{ $product->id }}"
                            :class="selected == {{ $product->id }}
                            ? 'border-[#c17a4a] ring-2 ring-[#c17a4a]/50 scale-[1.02] shadow-xl'
                            : 'border-[#e8dcc8] dark:border-[#4d4540]'"
                            class="cursor-pointer group bg-gradient-to-br from-[#faf8f3] to-[#f0e6d2]
                            dark:from-[#3d3530] dark:to-[#2a2520] rounded-2xl p-4 border
                            hover:border-[#c17a4a] hover:shadow-2xl hover:shadow-[#c17a4a]/10
                                    transition-all duration-300 active:scale-95 relative overflow-hidden
                                    before:absolute before:inset-0 before:bg-gradient-to-r before:from-transparent
                                    before:via-white/5 before:to-transparent before:translate-x-[-100%]
                                    hover:before:translate-x-[100%] before:transition-transform before:duration-700">
                                <!-- Favorite Button -->
                                <button
                                    wire:click.stop="toggleFavorite({{ $product->id }})"
                                    class="absolute top-2 right-2 z-10 p-1.5 rounded-full bg-white/80
                                        dark:bg-[#1a1815]/80 hover:bg-[#c17a4a] hover:text-white transition">
                                    <svg class="w-4 h-4 {{ in_array($product->id, $favorites ?? []) ? 'fill-[#c17a4a]' : 'text-[#8b7355]' }}" viewBox="0 0 24 24">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5
                                                2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09
                                                C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42
                                                22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                </button>

                                <!-- Product Image -->
                                @if($product->image_url)
                                <div class="relative mb-3">
                                <img
                                src="{{ \Illuminate\Support\Facades\Storage::url($product->image_url) }}"
                                alt="{{ $product->name }}"
                                class="w-full h-28 object-cover rounded-xl shadow-sm
                                transition-all duration-500 group-hover:scale-110 group-hover:shadow-lg
                                group-hover:shadow-[#c17a4a]/20">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/10 to-transparent rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                </div>
                                @else
                                    <div class="w-full h-28 bg-gradient-to-br from-[#e8dcc8] to-[#d4c4b0] dark:from-[#4d4540] dark:to-[#3d3530]
                                        rounded-xl mb-3 flex items-center justify-center shadow-sm">
                                        <svg class="w-10 h-10 text-[#8b7355] dark:text-[#b8a892]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif

                                <!-- Product Info -->
                                <h4 class="text-sm font-semibold text-[#2c2416] dark:text-[#f5f1e8] truncate
                                group-hover:text-[#c17a4a] transition-colors duration-300 leading-tight mb-1">
                                {{ $product->name }}
                                </h4>
                                <p class="text-xs text-[#8b7355] dark:text-[#b8a892] mb-3 line-clamp-1 font-medium">
                                {{ $product->category->name ?? 'Uncategorized' }}
                                </p>

                                <div class="flex justify-between items-center transition-transform duration-300 group-hover:scale-105">
                                    <span class="text-[#c17a4a] font-bold text-xs">
                                        ${{ number_format($product->price, 2) }}
                                    </span>
                                    @if(($product->stock ?? 999) < 5)
                                        <span class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400
                                                        text-xs px-1.5 py-0.5 rounded font-semibold animate-pulse">
                                            Low
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Order History Section -->
                    <div class="bg-white/70 dark:bg-[#2a2520]/70 backdrop-blur-lg rounded-2xl shadow-md p-4 border border-[#e8dcc8] dark:border-[#3d3530]">
                        <h3 class="text-lg font-serif font-bold text-[#2c2416] dark:text-[#f5f1e8] mb-4">Recent Orders</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-48 overflow-y-auto">
                            @forelse($recentOrders ?? [] as $order)
                                <button
                                    wire:click="loadOrder({{ $order->id }})"
                                    class="text-left p-3 bg-[#f0e6d2] dark:bg-[#3d3530] rounded-lg border border-[#e8dcc8] dark:border-[#4d4540] hover:border-[#c17a4a] transition"
                                >
                                    <p class="text-sm font-semibold text-[#2c2416] dark:text-[#f5f1e8]">{{ $order->customer_name ?? 'Order #' . $order->id }}</p>
                                    <p class="text-xs text-[#8b7355] dark:text-[#b8a892]">${{ number_format($order->total, 2) }} • {{ $order->created_at->format('g:i A') }}</p>
                                </button>
                            @empty
                                <p class="text-sm text-[#8b7355] dark:text-[#b8a892] col-span-2">No recent orders</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Quick Actions Panel -->
                    <div class="bg-gradient-to-r from-[#faf8f3] to-[#f5f1e8] dark:from-[#2a2520] dark:to-[#1f1b17]
                                backdrop-blur-lg rounded-2xl shadow-md p-6 border border-[#e8dcc8] dark:border-[#3d3530]">
                        <h3 class="text-base font-serif font-bold text-[#2c2416] dark:text-[#f5f1e8] mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#c17a4a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Quick Actions
                        </h3>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Print Report -->
                            <button
                                wire:click="printReport"
                                class="flex items-center justify-center gap-2 p-3 bg-gradient-to-r from-[#c17a4a] to-[#a86a3a]
                                    hover:from-[#d4956f] hover:to-[#b87a4a] text-white rounded-xl font-semibold text-sm
                                    shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105 active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                <span>Print Report</span>
                            </button>

                            <!-- Clear All Orders -->
                            <button
                                wire:click="clearAllOrders"
                                class="flex items-center justify-center gap-2 p-3 bg-gradient-to-r from-[#dc2626] to-[#b91c1c]
                                    hover:from-[#ef4444] hover:to-[#dc2626] text-white rounded-xl font-semibold text-sm
                                    shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105 active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span>Clear All</span>
                            </button>

                            <!-- Sales Summary -->
                            <button
                                wire:click="showSalesSummary"
                                class="flex items-center justify-center gap-2 p-3 bg-gradient-to-r from-[#059669] to-[#047857]
                                    hover:from-[#10b981] hover:to-[#059669] text-white rounded-xl font-semibold text-sm
                                    shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105 active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <span>Sales Summary</span>
                            </button>

                            <!-- Settings -->
                            <button
                                wire:click="openSettings"
                                class="flex items-center justify-center gap-2 p-3 bg-gradient-to-r from-[#6b7280] to-[#4b5563]
                                    hover:from-[#9ca3af] hover:to-[#6b7280] text-white rounded-xl font-semibold text-sm
                                    shadow-md hover:shadow-lg transition-all duration-300 hover:scale-105 active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Settings</span>
                            </button>
                        </div>
                    </div>
                </section>

                <!-- CART -->
                <aside class="lg:col-span-1">
                    <div class="bg-white/70 dark:bg-[#2a2520]/70 backdrop-blur-lg rounded-2xl shadow-md sticky top-24 p-4
                                h-[calc(100vh-8rem)] border border-[#e8dcc8] dark:border-[#3d3530]
                                flex flex-col overflow-y-auto overflow-x-hidden">

                        <!-- Header -->
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-serif font-bold text-[#2c2416] dark:text-[#f5f1e8]">
                                Order Summary
                            </h3>
                            <button wire:click="clearCart"
                                    class="text-xs text-[#8b7355] hover:text-[#c17a4a]
                                        dark:text-[#b8a892] dark:hover:text-[#d4956f] transition font-medium">
                                Clear
                            </button>
                        </div>

                        <!-- Table/Order Number & Order Type -->
                        <div class="mb-4 space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-[#2c2416] dark:text-[#f5f1e8] mb-2 uppercase tracking-wide">Table / Order #</label>
                                <input
                                    type="text"
                                    wire:model.lazy="tableNumber"
                                    placeholder="e.g., Table 5 or Order #123"
                                    class="w-full px-3 py-2 text-sm border border-[#e8dcc8] dark:border-[#3d3530] rounded-lg
                                           focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent dark:bg-[#1a1815] dark:text-[#f5f1e8]
                                           placeholder-[#8b7355] dark:placeholder-[#6b5f52] transition"
                                >
                            </div>
                            <div class="flex gap-3">
                                <button
                                    wire:click="$set('orderType', 'dine-in')"
                                    class="flex-1 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-300
                                        {{ $orderType === 'dine-in'
                                            ? 'bg-gradient-to-r from-[#c17a4a] to-[#a86a3a] text-white shadow-lg scale-105'
                                            : 'bg-[#f0e6d2] dark:bg-[#3d3530] text-[#2c2416] dark:text-[#f5f1e8] hover:bg-[#e8dcc8] dark:hover:bg-[#4d4540] hover:scale-102' }}"
                                >
                                    <span class="flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h6a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        Dine-in
                                    </span>
                                </button>
                                <button
                                    wire:click="$set('orderType', 'takeout')"
                                    class="flex-1 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-300
                                        {{ $orderType === 'takeout'
                                            ? 'bg-gradient-to-r from-[#c17a4a] to-[#a86a3a] text-white shadow-lg scale-105'
                                            : 'bg-[#f0e6d2] dark:bg-[#3d3530] text-[#2c2416] dark:text-[#f5f1e8] hover:bg-[#e8dcc8] dark:hover:bg-[#4d4540] hover:scale-102' }}"
                                >
                                    <span class="flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                        Takeout
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Cart items -->
                        <div class="space-y-2 mb-4">
                            @if(!empty($cart))
                            @foreach($cart as $item)
                            <div class="flex items-center gap-3 p-3 bg-gradient-to-r from-[#f0e6d2] to-[#ede3d0]
                            dark:from-[#3d3530] dark:to-[#454035] rounded-xl border border-[#e8dcc8] dark:border-[#4d4540]
                                                hover:shadow-md transition-all duration-300 hover:scale-[1.01]">
                                        <img src="{{ isset($item['image']) && $item['image'] ? \Illuminate\Support\Facades\Storage::url($item['image']) : '/placeholder.png' }}"
                                            class="w-10 h-10 rounded-lg object-cover flex-shrink-0">

                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-[#2c2416] dark:text-[#f5f1e8] truncate">
                                                {{ $item['name'] }}
                                            </p>
                                            <p class="text-xs text-[#8b7355] dark:text-[#b8a892]">
                                                ${{ number_format($item['price'], 2) }}
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-1 flex-shrink-0 bg-[#f8f5f0] dark:bg-[#454035] rounded-lg p-1">
                                        <button wire:click="decrementQuantity({{ $item['id'] }})"
                                        class="w-6 h-6 flex items-center justify-center bg-[#e8dcc8] dark:bg-[#4d4540]
                                        text-[#2c2416] dark:text-[#f5f1e8] rounded-md
                                        hover:bg-[#d4c4b0] dark:hover:bg-[#5d5550] hover:scale-110
                                        transition-all duration-200 text-sm font-bold shadow-sm">
                                        −
                                        </button>

                                        <span class="text-sm font-bold text-[#2c2416] dark:text-[#f5f1e8] w-8 text-center bg-white dark:bg-[#2a2520] rounded-md py-0.5 mx-0.5">
                                        {{ $item['quantity'] }}
                                        </span>

                                        <button wire:click="incrementQuantity({{ $item['id'] }})"
                                        class="w-6 h-6 flex items-center justify-center bg-[#c17a4a] text-white rounded-md
                                        hover:bg-[#a86a3a] hover:scale-110
                                        transition-all duration-200 text-sm font-bold shadow-sm">
                                        +
                                        </button>

                                            <button wire:click="removeItem({{ $item['id'] }})"
                                                    class="p-1 text-[#a86a3a] hover:text-[#c17a4a]
                                                        dark:text-[#b8875c] dark:hover:text-[#d4956f] transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-8 text-[#8b7355] dark:text-[#b8a892]">
                                    <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                    <p class="text-xs font-medium">Cart is empty</p>
                                </div>
                            @endif
                        </div>

                        @if(!empty($cart))
                            <div class="border-t border-[#e8dcc8] dark:border-[#3d3530] pt-3 space-y-3 flex-1 overflow-y-visible">

                                <!-- Customer Lookup -->
                                <div>
                                    <label class="block text-xs font-semibold text-[#2c2416] dark:text-[#f5f1e8] mb-1 uppercase tracking-wide">
                                        Customer
                                    </label>
                                    <input
                                        type="text"
                                        wire:model.live.debounce.300ms="customerSearch"
                                        placeholder="Name..."
                                        class="w-full text-xs border border-[#e8dcc8] dark:border-[#3d3530]
                                            rounded-lg px-2 py-1.5 dark:bg-[#1a1815] dark:text-[#f5f1e8]
                                            text-[#2c2416] placeholder-[#8b7355] dark:placeholder-[#6b5f52]
                                            focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent transition"
                                    >
                                    @if($customerSearch && $customers->count())
                                        <div class="mt-1 space-y-1 max-h-24 overflow-y-auto">
                                            @foreach($customers as $customer)
                                                <button
                                                    wire:click="selectCustomer({{ $customer->id }})"
                                                    class="w-full text-left px-2 py-1 text-xs bg-[#f0e6d2] dark:bg-[#3d3530]
                                                        rounded hover:bg-[#e8dcc8] dark:hover:bg-[#4d4540] transition">
                                                    {{ $customer->name }} ({{ $customer->phone ?? 'N/A' }})
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <!-- Payment Method Selection -->
                                <div x-data="{ expanded: false }">
                                    <label class="block text-xs font-semibold text-[#2c2416] dark:text-[#f5f1e8] mb-2 uppercase tracking-wide">
                                        Payment Method
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button wire:click="$set('paymentMethod', 'cash')"
                                                class="flex items-center justify-center gap-2 px-3 py-2 rounded-lg border-2 transition-all duration-300
                                                       {{ $paymentMethod === 'cash'
                                                          ? 'border-[#c17a4a] bg-[#c17a4a]/10 text-[#c17a4a] shadow-md'
                                                          : 'border-[#e8dcc8] dark:border-[#3d3530] text-[#8b7355] dark:text-[#b8a892] hover:border-[#c17a4a]/50' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            <span class="text-xs font-semibold">Cash</span>
                                        </button>

                                        <button wire:click="$set('paymentMethod', 'card')"
                                                class="flex items-center justify-center gap-2 px-3 py-2 rounded-lg border-2 transition-all duration-300
                                                       {{ $paymentMethod === 'card'
                                                          ? 'border-[#c17a4a] bg-[#c17a4a]/10 text-[#c17a4a] shadow-md'
                                                          : 'border-[#e8dcc8] dark:border-[#3d3530] text-[#8b7355] dark:text-[#b8a892] hover:border-[#c17a4a]/50' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                            <span class="text-xs font-semibold">Card</span>
                                        </button>

                                        <button wire:click="$set('paymentMethod', 'gcash')"
                                                class="flex items-center justify-center gap-2 px-3 py-2 rounded-lg border-2 transition-all duration-300
                                                       {{ $paymentMethod === 'gcash'
                                                          ? 'border-[#c17a4a] bg-[#c17a4a]/10 text-[#c17a4a] shadow-md'
                                                          : 'border-[#e8dcc8] dark:border-[#3d3530] text-[#8b7355] dark:text-[#b8a892] hover:border-[#c17a4a]/50' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="text-xs font-semibold">GCash</span>
                                        </button>

                                        <button wire:click="$set('paymentMethod', 'paypal')"
                                                class="flex items-center justify-center gap-2 px-3 py-2 rounded-lg border-2 transition-all duration-300
                                                       {{ $paymentMethod === 'paypal'
                                                          ? 'border-[#c17a4a] bg-[#c17a4a]/10 text-[#c17a4a] shadow-md'
                                                          : 'border-[#e8dcc8] dark:border-[#3d3530] text-[#8b7355] dark:text-[#b8a892] hover:border-[#c17a4a]/50' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                            <span class="text-xs font-semibold">PayPal</span>
                                        </button>
                                    </div>

                                    <!-- Cash Amount Input (shown only for cash payment) -->
                                    @if($paymentMethod === 'cash')
                                    <div x-show="true"
                                         x-transition:enter="transition ease-out duration-300"
                                         x-transition:enter-start="opacity-0 -translate-y-2"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         class="mt-3">
                                        <label class="block text-xs font-semibold text-[#2c2416] dark:text-[#f5f1e8] mb-1">
                                            Amount Tendered
                                        </label>
                                        <input type="number"
                                               wire:model.live="amountTendered"
                                               step="0.01"
                                               min="0"
                                               placeholder="0.00"
                                               class="w-full text-sm border border-[#e8dcc8] dark:border-[#3d3530]
                                                      rounded-lg px-3 py-2 dark:bg-[#1a1815] dark:text-[#f5f1e8]
                                                      text-[#2c2416] placeholder-[#8b7355] dark:placeholder-[#6b5f52]
                                                      focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent transition">
                                        @if($amountTendered > 0 && $amountTendered >= $total)
                                        <p class="text-xs text-green-600 dark:text-green-400 mt-1 font-semibold">
                                            Change: ${{ number_format($this->getChangeAmount(), 2) }}
                                        </p>
                                        @elseif($amountTendered > 0 && $amountTendered < $total)
                                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                                            Insufficient amount
                                        </p>
                                        @endif
                                    </div>
                                    @endif
                                </div>

                                <!-- Instructions -->
                                <div>
                                    <label class="block text-xs font-semibold text-[#2c2416] dark:text-[#f5f1e8] mb-1 uppercase tracking-wide">
                                        Instructions
                                    </label>
                                    <textarea
                                        wire:model.lazy="otherNote"
                                        placeholder="e.g. no sugar..."
                                        rows="1"
                                        class="w-full text-xs border border-[#e8dcc8] dark:border-[#3d3530]
                                            rounded-lg px-2 py-1.5 dark:bg-[#1a1815] dark:text-[#f5f1e8]
                                            text-[#2c2416] placeholder-[#8b7355] dark:placeholder-[#6b5f52]
                                            focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent
                                            resize-none transition"
                                    ></textarea>
                                </div>

                                <!-- Discount -->
                                <div>
                                    <label class="block text-xs font-semibold text-[#2c2416] dark:text-[#f5f1e8] mb-1 uppercase tracking-wide">
                                        Discount (%)
                                    </label>
                                    <div class="flex gap-1">
                                        <input
                                            type="number"
                                            wire:model.lazy="discountPercentage"
                                            placeholder="0"
                                            min="0"
                                            max="100"
                                            step="1"
                                            class="flex-1 text-xs border border-[#e8dcc8] dark:border-[#3d3530]
                                                rounded-lg px-2 py-1.5 dark:bg-[#1a1815] dark:text-[#f5f1e8]
                                                text-[#2c2416] placeholder-[#8b7355] dark:placeholder-[#6b5f52]
                                                focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent transition">
                                        <button
                                            wire:click="applyDiscount"
                                            class="px-2 py-1.5 bg-[#c17a4a] text-white rounded-lg text-xs font-semibold
                                                hover:bg-[#a86a3a] transition whitespace-nowrap">
                                            Apply
                                        </button>
                                    </div>
                                    @if($discountApplied)
                                        <div class="flex items-center justify-between mt-1">
                                            <p class="text-xs text-green-600 dark:text-green-400 font-semibold">
                                                -${{ number_format($discountAmount, 2) }} ({{ $discountPercentage }}%)
                                            </p>
                                            <button
                                                wire:click="removeDiscount"
                                                class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <!-- Add-ons -->
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-xs font-semibold text-[#2c2416] dark:text-[#f5f1e8] uppercase tracking-wide">
                                            Add-ons
                                        </label>
                                        <button
                                        wire:click="addAddOn"
                                        class="text-xs bg-gradient-to-r from-[#c17a4a] to-[#a86a3a] text-white px-3 py-2 rounded-lg
                                                hover:from-[#d4956f] hover:to-[#b87a4a] hover:scale-105 hover:shadow-md
                                            transition-all duration-300 font-semibold shadow-sm"
                                        >
                                            <span class="flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Add Item
                                            </span>
                                        </button>
                                    </div>
                                    <div class="space-y-2 max-h-32 overflow-y-auto">
                                        @foreach($addOns as $index => $addOn)
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="text"
                                                    wire:model.lazy="addOns.{{ $index }}.label"
                                                    placeholder="Name"
                                                    class="flex-1 text-xs border border-[#e8dcc8] dark:border-[#3d3530]
                                                        rounded-lg px-2 py-1.5 dark:bg-[#1a1815] dark:text-[#f5f1e8]
                                                        text-[#2c2416] placeholder-[#8b7355] dark:placeholder-[#6b5f52]
                                                        focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent transition">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    wire:model.lazy="addOns.{{ $index }}.amount"
                                                    placeholder="0.00"
                                                    class="w-16 text-right text-xs border border-[#e8dcc8] dark:border-[#3d3530]
                                                        rounded-lg px-2 py-1.5 dark:bg-[#1a1815] dark:text-[#f5f1e8]
                                                        text-[#2c2416] placeholder-[#8b7355] dark:placeholder-[#6b5f52]
                                                        focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent
                                                        transition appearance-none overflow-hidden">
                                                <button
                                                    wire:click="removeAddOn({{ $index }})"
                                                    class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 p-1"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                        @if(empty($addOns))
                                            <p class="text-xs text-[#8b7355] dark:text-[#b8a892] italic">No add-ons added</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Totals & Checkout -->
                            <div class="pt-3 space-y-2 border-t border-[#e8dcc8] dark:border-[#3d3530] mt-3 flex-shrink-0">
                                <div class="flex justify-between text-xs">
                                    <span class="text-[#8b7355] dark:text-[#b8a892]">Subtotal:</span>
                                    <span class="font-semibold text-[#2c2416] dark:text-[#f5f1e8]">
                                        ${{ number_format($subtotal, 2) }}
                                    </span>
                                </div>

                                @if($discountAmount > 0)
                                <div class="flex justify-between text-xs">
                                <span class="text-[#8b7355] dark:text-[#b8a892]">Discount ({{ $discountPercentage }}%):</span>
                                <span class="font-semibold text-green-600 dark:text-green-400">
                                -${{ number_format($discountAmount, 2) }}
                                </span>
                                </div>
                                @endif

                                <div class="flex justify-between text-base font-serif font-bold">
                                    <span class="text-[#2c2416] dark:text-[#f5f1e8]">Total:</span>
                                    <span class="text-[#c17a4a]">
                                        ${{ number_format($total, 2) }}
                                    </span>
                                </div>

                                <!-- Checkout Button -->
                                <button
                                wire:click="confirmPayment"
                                wire:loading.attr="disabled"
                                @if(!$paymentMethod) disabled @endif
                                class="w-full mt-3 bg-gradient-to-r from-[#c17a4a] via-[#d4956f] to-[#a86a3a]
                                hover:from-[#d4956f] hover:via-[#e6b08a] hover:to-[#b87a4a]
                                text-white font-bold text-sm py-3 rounded-2xl
                                shadow-lg shadow-[#c17a4a]/30 hover:shadow-xl hover:shadow-[#c17a4a]/40
                                    transition-all duration-500 hover:scale-[1.02] active:scale-95
                                relative overflow-hidden group
                                before:absolute before:inset-0 before:bg-gradient-to-r
                                before:from-transparent before:via-white/20 before:to-transparent
                                before:translate-x-[-100%] hover:before:translate-x-[100%]
                                before:transition-transform before:duration-700
                                disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                                <div class="flex items-center justify-center gap-2 relative z-10">
                                        <span wire:loading.remove wire:target="confirmPayment">
                                            <svg class="w-5 h-5 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                     d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </span>
                                        <span wire:loading wire:target="confirmPayment">
                                            <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                        <span wire:loading.remove wire:target="confirmPayment" class="tracking-wide">
                                            {{ !$paymentMethod ? 'Select Payment Method' : 'Complete Order' }}
                                        </span>
                                        <span wire:loading wire:target="confirmPayment" class="tracking-wide">Processing...</span>
                                    </div>
                                </button>
                            </div>
                        @endif
                    </div>
                </aside>

            </div>


        </main>
    </div>

    <!-- Success Slide Drawer -->
    @if($showSuccessAnimation && $completedOrder)
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[60]" wire:click="$set('showSuccessAnimation', false)"></div>
    <div x-data="{ show: true }"
         x-show="show"
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 w-full sm:w-[450px] h-screen bg-white dark:bg-[#1a1815]
                shadow-2xl border-l-4 border-green-500 z-[70] flex flex-col overflow-hidden">

        <!-- Header with Success Icon -->
        <div class="bg-gradient-to-r from-green-500 to-green-600 p-6 text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-green-400/20 to-transparent"></div>
            <div class="relative flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">Order Complete!</h2>
                        <p class="text-sm text-green-50">Successfully processed</p>
                    </div>
                </div>
                <button wire:click="$set('showSuccessAnimation', false)"
                        class="text-white/80 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <!-- Order Number Badge -->
            <div class="bg-gradient-to-br from-[#faf8f3] to-[#f0e6d2] dark:from-[#3d3530] dark:to-[#2a2520]
                        rounded-2xl p-6 border-2 border-[#c17a4a]/30 text-center">
                <p class="text-sm text-[#8b7355] dark:text-[#b8a892] mb-2">Order Number</p>
                <p class="text-4xl font-bold text-[#c17a4a]">#{{ $completedOrder['order_number'] ?? 'N/A' }}</p>
            </div>

            <!-- Order Details -->
            <div class="space-y-3">
                <h3 class="text-sm font-bold text-[#2c2416] dark:text-[#f5f1e8] uppercase tracking-wide">Order Details</h3>

                <div class="bg-[#faf8f3] dark:bg-[#2a2520] rounded-xl p-4 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-[#8b7355] dark:text-[#b8a892]">Customer</span>
                        <span class="font-semibold text-[#2c2416] dark:text-[#f5f1e8]">
                            {{ $completedOrder['customer_name'] ?? 'Guest' }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-sm text-[#8b7355] dark:text-[#b8a892]">Items</span>
                        <span class="font-semibold text-[#2c2416] dark:text-[#f5f1e8]">
                            {{ $completedOrder['items_count'] ?? 0 }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center pt-3 border-t border-[#e8dcc8] dark:border-[#3d3530]">
                        <span class="text-lg font-bold text-[#2c2416] dark:text-[#f5f1e8]">Total</span>
                        <span class="text-2xl font-bold text-[#c17a4a]">
                            ${{ number_format($completedOrder['total'] ?? 0, 2) }}
                        </span>
                    </div>

                    @if(($completedOrder['change'] ?? 0) > 0)
                    <div class="flex justify-between items-center bg-green-50 dark:bg-green-900/20 p-3 rounded-lg mt-2">
                        <span class="text-sm font-semibold text-green-700 dark:text-green-400">Change</span>
                        <span class="text-lg font-bold text-green-700 dark:text-green-400">
                            ${{ number_format($completedOrder['change'], 2) }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Success Message -->
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm text-green-700 dark:text-green-300">
                        Your order has been sent to the kitchen and will be prepared shortly.
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="p-6 border-t border-[#e8dcc8] dark:border-[#3d3530] space-y-3 bg-[#faf8f3] dark:bg-[#2a2520]">
            <button wire:click="clearCart; $set('showSuccessAnimation', false)"
                    class="w-full px-6 py-3 bg-gradient-to-r from-[#c17a4a] to-[#d4956f] hover:from-[#a86a3a] hover:to-[#c17a4a]
                           text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300">
                Start New Order
            </button>
            <button wire:click="$set('showReceiptModal', true)"
                    class="w-full px-6 py-3 border-2 border-[#c17a4a] text-[#c17a4a] hover:bg-[#c17a4a] hover:text-white
                           rounded-xl font-semibold transition-all duration-300">
                View Receipt
            </button>
        </div>
    </div>
    @endif

    <!-- Payment Confirmation Modal -->
    @if($showPaymentConfirmationModal)
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#2a2520] rounded-2xl shadow-2xl max-w-md w-full p-8 border border-[#e8dcc8] dark:border-[#3d3530] relative">
            <!-- Success Icon -->
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center animate-bounce">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>

            <!-- Header -->
            <h2 class="text-2xl font-serif font-bold text-[#2c2416] dark:text-[#f5f1e8] mb-2 text-center">Payment Confirmed!</h2>
            <p class="text-sm text-[#8b7355] dark:text-[#b8a892] text-center mb-6">Your order has been successfully processed.</p>

            <!-- Order Details -->
            <div class="space-y-3 mb-6">
                <div class="flex justify-between items-center py-2 border-b border-[#e8dcc8] dark:border-[#3d3530]">
                    <span class="text-sm text-[#8b7355] dark:text-[#b8a892]">Order Number:</span>
                    <span class="font-semibold text-[#2c2416] dark:text-[#f5f1e8]">{{ $paymentConfirmationData['order_number'] ?? 'N/A' }}</span>
                </div>

                <div class="flex justify-between items-center py-2 border-b border-[#e8dcc8] dark:border-[#3d3530]">
                    <span class="text-sm text-[#8b7355] dark:text-[#b8a892]">Total Amount:</span>
                    <span class="font-semibold text-[#c17a4a]">${{ number_format($paymentConfirmationData['total'] ?? 0, 2) }}</span>
                </div>

                <div class="flex justify-between items-center py-2 border-b border-[#e8dcc8] dark:border-[#3d3530]">
                    <span class="text-sm text-[#8b7355] dark:text-[#b8a892]">Payment Method:</span>
                    <span class="font-semibold text-[#2c2416] dark:text-[#f5f1e8] capitalize">{{ $paymentConfirmationData['payment_method'] ?? 'N/A' }}</span>
                </div>

                <div class="flex justify-between items-center py-2 border-b border-[#e8dcc8] dark:border-[#3d3530]">
                    <span class="text-sm text-[#8b7355] dark:text-[#b8a892]">Customer:</span>
                    <span class="font-semibold text-[#2c2416] dark:text-[#f5f1e8]">{{ $paymentConfirmationData['customer_name'] ?? 'Guest' }}</span>
                </div>

                @if($paymentConfirmationData['order_type'] ?? null)
                <div class="flex justify-between items-center py-2 border-b border-[#e8dcc8] dark:border-[#3d3530]">
                    <span class="text-sm text-[#8b7355] dark:text-[#b8a892]">Order Type:</span>
                    <span class="font-semibold text-[#2c2416] dark:text-[#f5f1e8] capitalize">{{ $paymentConfirmationData['order_type'] }}</span>
                </div>
                @endif

                @if($paymentConfirmationData['table_number'] ?? null)
                <div class="flex justify-between items-center py-2 border-b border-[#e8dcc8] dark:border-[#3d3530]">
                    <span class="text-sm text-[#8b7355] dark:text-[#b8a892]">Table:</span>
                    <span class="font-semibold text-[#2c2416] dark:text-[#f5f1e8]">{{ $paymentConfirmationData['table_number'] }}</span>
                </div>
                @endif

                <div class="flex justify-between items-center py-2">
                    <span class="text-sm text-[#8b7355] dark:text-[#b8a892]">Items Ordered:</span>
                    <span class="font-semibold text-[#2c2416] dark:text-[#f5f1e8]">{{ $paymentConfirmationData['items_count'] ?? 0 }}</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <button
                    wire:click="closePaymentConfirmationModal"
                    class="flex-1 px-4 py-3 bg-[#e8dcc8] dark:bg-[#3d3530] text-[#2c2416] dark:text-[#f5f1e8] rounded-lg font-semibold hover:bg-[#d4c4b0] dark:hover:bg-[#4d4540] transition"
                >
                    Continue Shopping
                </button>
                <button
                    wire:click="$set('showReceiptModal', true); closePaymentConfirmationModal()"
                    class="flex-1 px-4 py-3 bg-[#c17a4a] text-white rounded-lg font-semibold hover:bg-[#a86a3a] transition"
                >
                    View Receipt
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Payment Methods Modal -->
    @if($showPaymentModal)
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#2a2520] rounded-2xl shadow-2xl max-w-md w-full p-8 border border-[#e8dcc8] dark:border-[#3d3530]">
            <h2 class="text-2xl font-serif font-bold text-[#2c2416] dark:text-[#f5f1e8] mb-6">Select Payment Method</h2>

            <div class="space-y-3 mb-6">
                <button
                    wire:click="processPayment('cash')"
                    class="w-full p-4 border-2 border-[#e8dcc8] dark:border-[#3d3530] rounded-lg hover:border-[#c17a4a] hover:bg-[#f0e6d2] dark:hover:bg-[#3d3530] transition text-left"
                >
                    <p class="font-semibold text-[#2c2416] dark:text-[#f5f1e8]">Cash</p>
                    <p class="text-sm text-[#8b7355] dark:text-[#b8a892]">Pay with cash</p>
                </button>

                <button
                    wire:click="processPayment('card')"
                    class="w-full p-4 border-2 border-[#e8dcc8] dark:border-[#3d3530] rounded-lg hover:border-[#c17a4a] hover:bg-[#f0e6d2] dark:hover:bg-[#3d3530] transition text-left"
                >
                    <p class="font-semibold text-[#2c2416] dark:text-[#f5f1e8]">Card</p>
                    <p class="text-sm text-[#8b7355] dark:text-[#b8a892]">Credit or debit card</p>
                </button>

                <button
                    wire:click="processPayment('mobile')"
                    class="w-full p-4 border-2 border-[#e8dcc8] dark:border-[#3d3530] rounded-lg hover:border-[#c17a4a] hover:bg-[#f0e6d2] dark:hover:bg-[#3d3530] transition text-left"
                >
                    <p class="font-semibold text-[#2c2416] dark:text-[#f5f1e8]">Mobile Pay</p>
                    <p class="text-sm text-[#8b7355] dark:text-[#b8a892]">Apple Pay, Google Pay, etc.</p>
                </button>
            </div>

            <button
                wire:click="$set('showPaymentModal', false)"
                class="w-full px-4 py-2 bg-[#e8dcc8] dark:bg-[#3d3530] text-[#2c2416] dark:text-[#f5f1e8] rounded-lg font-semibold hover:bg-[#d4c4b0] dark:hover:bg-[#4d4540] transition"
            >
                Cancel
            </button>
        </div>
    </div>
    @endif

    <!-- Receipt Preview Modal -->
    @if($showReceiptModal)
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#2a2520] rounded-2xl shadow-2xl max-w-md w-full p-8 border border-[#e8dcc8] dark:border-[#3d3530] max-h-[80vh] overflow-y-auto">
            <h2 class="text-2xl font-serif font-bold text-[#2c2416] dark:text-[#f5f1e8] mb-6 text-center">Order Receipt</h2>

            <div class="space-y-4 mb-6 pb-6 border-b border-[#e8dcc8] dark:border-[#3d3530]">
            <div class="text-center mb-4">
            <p class="text-sm text-[#8b7355] dark:text-[#b8a892]">{{ now()->format('M j, Y • g:i A') }}</p>
            <p class="text-sm font-semibold text-[#2c2416] dark:text-[#f5f1e8]">{{ $receiptData['table_number'] ?? $receiptData['order_type'] ?? 'Takeout' }}</p>
            @if($receiptData['customer_name'] ?? null)
            <p class="text-xs text-[#8b7355] dark:text-[#b8a892]">Customer: {{ $receiptData['customer_name'] }}</p>
            @endif
            <p class="text-xs text-[#8b7355] dark:text-[#b8a892]">Order #{{ $receiptData['order_number'] }}</p>
            </div>

            <div class="space-y-2">
            @foreach($receiptData['cart_items'] ?? [] as $item)
            <div class="flex items-center gap-3 text-sm mb-2">
            @if($item['image'])
            <img src="{{ \Illuminate\Support\Facades\Storage::url($item['image']) }}"
            class="w-8 h-8 rounded object-cover flex-shrink-0">
            @endif
            <div class="flex-1">
            <span class="text-[#2c2416] dark:text-[#f5f1e8]">{{ $item['name'] }} x{{ $item['quantity'] }}</span>
            </div>
            <span class="font-semibold text-[#2c2416] dark:text-[#f5f1e8]">${{ number_format($item['price'] * $item['quantity'], 2) }}</span>
            </div>
            @endforeach

            @foreach($receiptData['add_ons'] ?? [] as $addOn)
            @if(!empty($addOn['label']) && ($addOn['amount'] ?? 0) > 0)
            <div class="flex justify-between text-sm">
            <span class="text-[#2c2416] dark:text-[#f5f1e8]">{{ $addOn['label'] }}</span>
            <span class="font-semibold text-[#2c2416] dark:text-[#f5f1e8]">${{ number_format($addOn['amount'], 2) }}</span>
            </div>
            @endif
            @endforeach
            </div>

            @if(!empty(array_filter($receiptData['add_ons'] ?? [], fn($addOn) => !empty($addOn['label']) && ($addOn['amount'] ?? 0) > 0)))
            <div class="mt-3 p-3 bg-[#f0e6d2] dark:bg-[#3d3530] rounded-lg border border-[#e8dcc8] dark:border-[#4d4540]">
            <h4 class="text-sm font-semibold text-[#2c2416] dark:text-[#f5f1e8] mb-2">Add-on Details:</h4>
            <div class="space-y-1">
            @foreach($receiptData['add_ons'] ?? [] as $addOn)
            @if(!empty($addOn['label']) && ($addOn['amount'] ?? 0) > 0)
            <div class="flex justify-between text-sm">
            <span class="text-[#8b7355] dark:text-[#b8a892]">{{ $addOn['label'] }}:</span>
            <span class="text-[#2c2416] dark:text-[#f5f1e8]">${{ number_format($addOn['amount'], 2) }}</span>
            </div>
            @endif
            @endforeach
            </div>
            </div>
            @endif

            @if($receiptData['instructions'] ?? null)
            <div class="mt-3 p-3 bg-[#f8f5f0] dark:bg-[#2a2520] rounded-lg border border-[#e8dcc8] dark:border-[#3d3530]">
            <h4 class="text-sm font-semibold text-[#2c2416] dark:text-[#f5f1e8] mb-2">Special Instructions:</h4>
            <p class="text-sm text-[#8b7355] dark:text-[#b8a892]">{{ $receiptData['instructions'] }}</p>
            </div>
            @endif
            </div>

            <div class="space-y-2 mb-6">
            <div class="space-y-2 text-sm bg-[#faf8f3] dark:bg-[#2a2520] p-4 rounded-lg border border-[#e8dcc8] dark:border-[#3d3530]">
            <h4 class="font-semibold text-[#2c2416] dark:text-[#f5f1e8] mb-3">Order Summary</h4>

            <div class="space-y-1">
            <div class="flex justify-between">
            <span class="text-[#8b7355] dark:text-[#b8a892]">Items Subtotal:</span>
            <span class="text-[#2c2416] dark:text-[#f5f1e8]">${{ number_format($receiptData['subtotal'] ?? 0, 2) }}</span>
            </div>

            @php
                $addOnTotal = 0;
                foreach($receiptData['add_ons'] ?? [] as $addOn) {
                    if(!empty($addOn['label']) && ($addOn['amount'] ?? 0) > 0) {
                        $addOnTotal += $addOn['amount'];
                }
            }
            @endphp
            @if($addOnTotal > 0)
            <div class="flex justify-between">
            <span class="text-[#8b7355] dark:text-[#b8a892]">Add-ons Total:</span>
            <span class="text-[#2c2416] dark:text-[#f5f1e8]">${{ number_format($addOnTotal, 2) }}</span>
            </div>
            @endif

            @if(($receiptData['discount_amount'] ?? 0) > 0)
            <div class="flex justify-between text-green-600 dark:text-green-400 font-medium border-t border-green-200 dark:border-green-800 pt-1 mt-2">
            <span>Discount Applied ({{ $receiptData['discount_percentage'] ?? 0 }}%):</span>
            <span>-${{ number_format($receiptData['discount_amount'] ?? 0, 2) }}</span>
            </div>
            @endif

            <div class="flex justify-between text-lg font-bold pt-3 mt-3 border-t-2 border-[#e8dcc8] dark:border-[#3d3530]">
            <span class="text-[#2c2416] dark:text-[#f5f1e8]">Grand Total:</span>
            <span class="text-[#c17a4a]">${{ number_format($receiptData['total'] ?? 0, 2) }}</span>
            </div>
            </div>
            </div>

            <div class="mt-4 pt-4 border-t border-[#e8dcc8] dark:border-[#3d3530]">
            <div class="text-center text-xs text-[#8b7355] dark:text-[#b8a892]">
            <p>Payment Method: {{ ucfirst($receiptData['payment_method'] ?? 'cash') }}</p>
            <p class="mt-1">Thank you for visiting Goodland Café!</p>
            </div>
            </div>
            </div>

            <div class="flex gap-3">
                <button
                    wire:click="$set('showReceiptModal', false)"
                    class="flex-1 px-4 py-3 bg-[#e8dcc8] dark:bg-[#3d3530] text-[#2c2416] dark:text-[#f5f1e8] rounded-lg font-semibold hover:bg-[#d4c4b0] dark:hover:bg-[#4d4540] transition"
                >
                    Back
                </button>
                <button
                    wire:click="printReceipt"
                    class="flex-1 px-4 py-3 bg-[#c17a4a] text-white rounded-lg font-semibold hover:bg-[#a86a3a] transition"
                >
                    Print
                </button>
            </div>
        </div>
    </div>
    @endif

</div>

<!-- Theme Toggle Script -->
<script>
(function () {
    const root = document.documentElement;
    const storageKey = 'theme';
    const setTheme = (dark) => {
        root.classList.toggle('dark', dark);
        localStorage.setItem(storageKey, dark ? 'dark' : 'light');
        document.getElementById('theme-thumb').style.transform = dark ? 'translateX(1.5rem)' : 'translateX(0)';
    };
    document.getElementById('theme-toggle')?.addEventListener('click', () => {
        setTheme(!root.classList.contains('dark'));
    });
    setTheme(localStorage.getItem(storageKey) === 'dark');
})();

// Keyboard Shortcuts
document.addEventListener('keydown', (e) => {
    // Number keys 1-9 for quick product selection
    if (e.key >= '1' && e.key <= '9' && !e.ctrlKey && !e.metaKey) {
        const products = document.querySelectorAll('[wire\\:click*="openQuantityModal"]');
        const index = parseInt(e.key) - 1;
        if (products[index]) {
            products[index].click();
        }
    }

    // Ctrl+Enter or Cmd+Enter for next step
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        @this.call('nextStep');
    }

    // Escape to go back
    if (e.key === 'Escape') {
        @this.call('previousStep');
    }
});

// Toast notification system
document.addEventListener('livewire:init', () => {
    Livewire.on('show-toast', (event) => {
        const toast = document.createElement('div');
        const type = event.type || 'info';
        const message = event.message || 'Notification';

        const colors = {
            success: 'from-green-500 to-green-600',
            error: 'from-red-500 to-red-600',
            warning: 'from-amber-500 to-amber-600',
            info: 'from-blue-500 to-blue-600'
        };

        toast.className = `fixed top-20 right-4 z-[100] px-6 py-4 rounded-xl shadow-2xl text-white bg-gradient-to-r ${colors[type]} transform transition-all duration-300 translate-x-full`;
        toast.innerHTML = `
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${type === 'success' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' :
                      type === 'error' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>' :
                      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'}
                </svg>
                <span class="font-medium">${message}</span>
            </div>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 10);

        setTimeout(() => {
            toast.style.transform = 'translateX(150%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    });

    Livewire.on('step-changed', (event) => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});
</script>
