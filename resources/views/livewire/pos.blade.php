<div class="min-h-screen bg-gradient-to-br from-[#faf8f3] via-[#f5f1e8] to-[#ede8df] dark:from-[#1a1815] dark:via-[#2a2520] dark:to-[#1f1b17] transition-colors duration-300 font-sans">

    <!-- HEADER -->
    <header class="backdrop-blur-md bg-white/80 dark:bg-[#2a2520]/80 border-b border-[#e8dcc8] dark:border-[#3d3530] sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-8 py-5 flex justify-between items-center">

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
                <div class="flex items-center gap-3 bg-[#f0e6d2] dark:bg-[#3d3530] px-4 py-2 rounded-full border border-[#e8dcc8] dark:border-[#4d4540]">
                    <svg class="w-5 h-5 text-[#c17a4a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span class="text-sm text-[#2c2416] dark:text-[#f5f1e8] font-semibold">{{ $this->getCartItemCount() }}</span>
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN LAYOUT WITH SIDEBAR -->
    <div class="flex">
        <!-- Sidebar Component -->
        @include('livewire.sidebar')

        <!-- MAIN CONTENT -->
        <main class="flex-1 px-8 py-10">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- PRODUCTS -->
                <section class="lg:col-span-2 space-y-6">
                    
                    <!-- Search + Filter + Table Number -->
                    <div class="bg-white/70 dark:bg-[#2a2520]/70 backdrop-blur-lg rounded-2xl shadow-md p-6 border border-[#e8dcc8] dark:border-[#3d3530]">
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-1 relative">
                                    <input 
                                        type="text" 
                                        wire:model.live.debounce.300ms="search"
                                        placeholder="Search products..."
                                        class="w-full pl-11 pr-4 py-3 text-sm border border-[#e8dcc8] dark:border-[#3d3530] rounded-lg 
                                               focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent dark:bg-[#1a1815] dark:text-[#f5f1e8] 
                                               placeholder-[#8b7355] dark:placeholder-[#6b5f52] transition"
                                    >
                                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-[#8b7355]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Category Filter -->
                                <select 
                                    wire:model.live="selectedCategory"
                                    class="px-4 py-3 text-sm border border-[#e8dcc8] dark:border-[#3d3530] rounded-lg 
                                           focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent dark:bg-[#1a1815] dark:text-[#f5f1e8] 
                                           text-[#2c2416] font-medium transition">
                                    <option value="0">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Table/Order Number & Dine-in Toggle -->
                            <div class="flex gap-4 items-end">
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-[#2c2416] dark:text-[#f5f1e8] mb-2 uppercase tracking-wide">Table / Order #</label>
                                    <input 
                                        type="text" 
                                        wire:model.lazy="tableNumber"
                                        placeholder="e.g., Table 5 or Order #123"
                                        class="w-full px-4 py-2 text-sm border border-[#e8dcc8] dark:border-[#3d3530] rounded-lg 
                                               focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent dark:bg-[#1a1815] dark:text-[#f5f1e8] 
                                               placeholder-[#8b7355] dark:placeholder-[#6b5f52] transition"
                                    >
                                </div>
                                <div class="flex gap-2">
                                    <button 
                                        wire:click="$set('orderType', 'dine-in')"
                                        class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $orderType === 'dine-in' ? 'bg-[#c17a4a] text-white' : 'bg-[#e8dcc8] dark:bg-[#3d3530] text-[#2c2416] dark:text-[#f5f1e8]' }}"
                                    >
                                        Dine-in
                                    </button>
                                    <button 
                                        wire:click="$set('orderType', 'takeout')"
                                        class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $orderType === 'takeout' ? 'bg-[#c17a4a] text-white' : 'bg-[#e8dcc8] dark:bg-[#3d3530] text-[#2c2416] dark:text-[#f5f1e8]' }}"
                                    >
                                        Takeout
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-data="{ selected: @entangle('selectedProductId') }" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3 gap-4">
                        @foreach($products as $product)
                            <div 
                                @click="$wire.addToCart({{ $product->id }}); selected = {{ $product->id }}"
                                :class="selected == {{ $product->id }} 
                                    ? 'border-[#c17a4a] ring-2 ring-[#c17a4a]/50 scale-[1.02]' 
                                    : 'border-[#e8dcc8] dark:border-[#4d4540]'"
                                class="cursor-pointer group bg-gradient-to-br from-[#faf8f3] to-[#f0e6d2] 
                                    dark:from-[#3d3530] dark:to-[#2a2520] rounded-xl p-3 border 
                                    hover:border-[#c17a4a] hover:shadow-lg transition-all duration-300 active:scale-95 relative"
                            >
                                <!-- Favorite Button -->
                                <button 
                                    wire:click.stop="toggleFavorite({{ $product->id }})"
                                    class="absolute top-2 right-2 z-10 p-1.5 rounded-full bg-white/80 
                                        dark:bg-[#1a1815]/80 hover:bg-[#c17a4a] hover:text-white transition"
                                >
                                    <svg class="w-4 h-4 {{ in_array($product->id, $favorites ?? []) ? 'fill-[#c17a4a]' : 'text-[#8b7355]' }}" viewBox="0 0 24 24">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 
                                                2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09
                                                C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 
                                                22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                </button>

                                <!-- Product Image -->
                                @if($product->image_url)
                                    <img 
                                        src="{{ $product->image_url }}" 
                                        alt="{{ $product->name }}" 
                                        class="w-full h-24 object-cover rounded-lg mb-2 
                                            transition-transform duration-300 group-hover:scale-105"
                                    >
                                @endif

                                <!-- Product Info -->
                                <h4 class="text-xs font-semibold text-[#2c2416] dark:text-[#f5f1e8] truncate 
                                        group-hover:text-[#c17a4a] transition">
                                    {{ $product->name }}
                                </h4>
                                <p class="text-xs text-[#8b7355] dark:text-[#b8a892] mb-2 line-clamp-1">
                                    {{ $product->category->name ?? 'Uncategorized' }}
                                </p>

                                <div class="flex justify-between items-center">
                                    <span class="text-[#c17a4a] font-bold text-xs">
                                        ${{ number_format($product->price, 2) }}
                                    </span>
                                    @if(($product->stock ?? 999) < 5)
                                        <span class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 
                                                    text-xs px-1.5 py-0.5 rounded font-semibold">
                                            Low
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Order History Section -->
                    <div class="bg-white/70 dark:bg-[#2a2520]/70 backdrop-blur-lg rounded-2xl shadow-md p-6 border border-[#e8dcc8] dark:border-[#3d3530]">
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
                </section>

                <!-- CART -->
                <aside class="lg:col-span-1">
                    <div class="bg-white/70 dark:bg-[#2a2520]/70 backdrop-blur-lg rounded-2xl shadow-md sticky top-24 p-4 
                                h-[calc(100vh-8rem)] border border-[#e8dcc8] dark:border-[#3d3530] 
                                flex flex-col overflow-y-auto">

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

                        <!-- Cart items -->
                        <div class="space-y-2 mb-4">
                            @if(!empty($cart))
                                @foreach($cart as $item)
                                    <div class="flex items-center gap-2 p-2 bg-[#f0e6d2] dark:bg-[#3d3530] 
                                                rounded-lg border border-[#e8dcc8] dark:border-[#4d4540]">
                                        <img src="{{ $item['image'] ?? '/placeholder.png' }}" 
                                            class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                                        
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-[#2c2416] dark:text-[#f5f1e8] truncate">
                                                {{ $item['name'] }}
                                            </p>
                                            <p class="text-xs text-[#8b7355] dark:text-[#b8a892]">
                                                ${{ number_format($item['price'], 2) }}
                                            </p>
                                        </div>
                                        
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            <button wire:click="decrementQuantity({{ $item['id'] }})"
                                                    class="px-1.5 py-0.5 bg-[#e8dcc8] dark:bg-[#4d4540] 
                                                        text-[#2c2416] dark:text-[#f5f1e8] 
                                                        rounded hover:bg-[#d4c4b0] dark:hover:bg-[#5d5550] 
                                                        transition text-xs font-semibold">
                                                −
                                            </button>
                                            
                                            <span class="text-xs font-semibold text-[#2c2416] dark:text-[#f5f1e8] w-5 text-center">
                                                {{ $item['quantity'] }}
                                            </span>
                                            
                                            <button wire:click="incrementQuantity({{ $item['id'] }})"
                                                    class="px-1.5 py-0.5 bg-[#e8dcc8] dark:bg-[#4d4540] 
                                                        text-[#2c2416] dark:text-[#f5f1e8] 
                                                        rounded hover:bg-[#d4c4b0] dark:hover:bg-[#5d5550] 
                                                        transition text-xs font-semibold">
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
                                        placeholder="Search..."
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
                                        Discount
                                    </label>
                                    <div class="flex gap-1">
                                        <input 
                                            type="text" 
                                            wire:model.lazy="couponCode"
                                            placeholder="Code"
                                            class="flex-1 text-xs border border-[#e8dcc8] dark:border-[#3d3530] 
                                                rounded-lg px-2 py-1.5 dark:bg-[#1a1815] dark:text-[#f5f1e8] 
                                                text-[#2c2416] placeholder-[#8b7355] dark:placeholder-[#6b5f52] 
                                                focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent transition">
                                        <button 
                                            wire:click="applyCoupon"
                                            class="px-2 py-1.5 bg-[#c17a4a] text-white rounded-lg text-xs font-semibold 
                                                hover:bg-[#a86a3a] transition whitespace-nowrap">
                                            Apply
                                        </button>
                                    </div>
                                    @if($discountApplied)
                                        <p class="text-xs text-green-600 dark:text-green-400 mt-1 font-semibold">
                                            -${{ number_format($discountAmount, 2) }}
                                        </p>
                                    @endif
                                </div>

                                <!-- Add-ons -->
                                <div>
                                    <label class="block text-xs font-semibold text-[#2c2416] dark:text-[#f5f1e8] mb-1 uppercase tracking-wide">
                                        Add-ons
                                    </label>
                                    <div class="flex items-center gap-1">
                                        <input 
                                            type="text" 
                                            wire:model.lazy="otherLabel" 
                                            placeholder="Name" 
                                            class="flex-1 text-xs border border-[#e8dcc8] dark:border-[#3d3530] 
                                                rounded-lg px-2 py-1.5 dark:bg-[#1a1815] dark:text-[#f5f1e8] 
                                                text-[#2c2416] placeholder-[#8b7355] dark:placeholder-[#6b5f52] 
                                                focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent transition">
                                        <input 
                                            wire:model.lazy="otherAmount" 
                                            placeholder="PHP 0"
                                            class="w-20 text-right text-xs border border-[#e8dcc8] dark:border-[#3d3530] 
                                                rounded-lg px-2 py-1.5 dark:bg-[#1a1815] dark:text-[#f5f1e8] 
                                                text-[#2c2416] placeholder-[#8b7355] dark:placeholder-[#6b5f52] 
                                                focus:ring-2 focus:ring-[#c17a4a] focus:border-transparent 
                                                transition appearance-none overflow-hidden">
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
                                        <span class="text-[#8b7355] dark:text-[#b8a892]">Discount:</span>
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
                                    wire:click="$set('showPaymentPanel', true)"
                                    class="w-full mt-2 bg-gradient-to-r from-[#c17a4a] to-[#a86a3a]
                                        hover:from-[#d4956f] hover:to-[#b87a4a]
                                        text-white font-semibold text-sm py-2 rounded-xl
                                        shadow-md transition-all duration-300 hover:scale-[1.02] active:scale-95">
                                    <div class="flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                        Checkout
                                    </div>
                                </button>
                            </div>
                        @endif
                    </div>
                </aside>    

                <!-- PAYMENT SLIDE PANEL -->
                <div 
                    x-data="{ open: @entangle('showPaymentPanel') }"
                    x-show="open"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="translate-x-full opacity-0"
                    x-transition:enter-end="translate-x-0 opacity-100"
                    x-transition:leave="transition ease-in duration-500"
                    x-transition:leave-start="translate-x-0 opacity-100"
                    x-transition:leave-end="translate-x-full opacity-0"
                    class="fixed top-0 right-0 w-full sm:w-[450px] h-screen bg-white dark:bg-[#1a1815] 
                        shadow-2xl border-l border-[#e8dcc8] dark:border-[#3d3530] z-50 flex flex-col">

                <!-- Header -->
                    <div class="flex justify-between items-center p-4 border-b border-[#e8dcc8] dark:border-[#3d3530]">
                        <h2 class="text-lg font-bold font-serif text-[#2c2416] dark:text-[#f5f1e8]">
                            Select Payment Method
                        </h2>
                        <button @click="open = false" class="text-[#8b7355] hover:text-[#c17a4a] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" 
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="p-6 space-y-3 overflow-y-auto flex-1">
                        <button 
                            wire:click="selectPayment('cash')" 
                            class="w-full text-left px-4 py-3 rounded-lg border transition font-semibold
                                {{ $paymentMethod === 'cash' 
                                    ? 'bg-[#c17a4a] text-white border-transparent shadow-md' 
                                    : 'border-[#e8dcc8] dark:border-[#3d3530] text-[#2c2416] dark:text-[#f5f1e8] hover:bg-[#f4ede0] dark:hover:bg-[#2f2923]' }}">
                            💵 Cash
                        </button>
                    
                        <button 
                            wire:click="selectPayment('gcash')" 
                            class="w-full text-left px-4 py-3 rounded-lg border transition font-semibold
                                {{ $paymentMethod === 'gcash' 
                                    ? 'bg-[#c17a4a] text-white border-transparent shadow-md' 
                                    : 'border-[#e8dcc8] dark:border-[#3d3530] text-[#2c2416] dark:text-[#f5f1e8] hover:bg-[#f4ede0] dark:hover:bg-[#2f2923]' }}">
                            📱 GCash
                        </button>
                    
                        <button 
                            wire:click="selectPayment('card')" 
                            class="w-full text-left px-4 py-3 rounded-lg border transition font-semibold
                                {{ $paymentMethod === 'card' 
                                    ? 'bg-[#c17a4a] text-white border-transparent shadow-md' 
                                    : 'border-[#e8dcc8] dark:border-[#3d3530] text-[#2c2416] dark:text-[#f5f1e8] hover:bg-[#f4ede0] dark:hover:bg-[#2f2923]' }}">
                            💳 Card
                        </button>
                    </div>
                    

                    <!-- Footer -->
                    <div class="p-4 border-t border-[#e8dcc8] dark:border-[#3d3530]">
                        <button wire:click="confirmPayment"
                                class="w-full py-3 bg-[#c17a4a] hover:bg-[#a86a3a] 
                                    text-white rounded-lg font-semibold transition">
                            Confirm Payment
                        </button>
                    </div>
                </div>              
            </div>
        </main>
    </div>

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
                <div class="text-center">
                    <p class="text-sm text-[#8b7355] dark:text-[#b8a892]">{{ now()->format('M j, Y • g:i A') }}</p>
                    <p class="text-sm font-semibold text-[#2c2416] dark:text-[#f5f1e8]">{{ $tableNumber ?? 'Takeout' }}</p>
                </div>

                <div class="space-y-2">
                    @foreach($cart as $item)
                        <div class="flex justify-between text-sm">
                            <span class="text-[#2c2416] dark:text-[#f5f1e8]">{{ $item['name'] }} x{{ $item['quantity'] }}</span>
                            <span class="font-semibold text-[#2c2416] dark:text-[#f5f1e8]">${{ number_format($item['price'] * $item['quantity'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-2 mb-6">
                <div class="flex justify-between text-sm">
                    <span class="text-[#8b7355] dark:text-[#b8a892]">Subtotal:</span>
                    <span class="text-[#2c2416] dark:text-[#f5f1e8]">${{ number_format($subtotal, 2) }}</span>
                </div>
                @if($discountAmount > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-[#8b7355] dark:text-[#b8a892]">Discount:</span>
                        <span class="text-green-600 dark:text-green-400">-${{ number_format($discountAmount, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-lg font-bold pt-2 border-t border-[#e8dcc8] dark:border-[#3d3530]">
                    <span class="text-[#2c2416] dark:text-[#f5f1e8]">Total:</span>
                    <span class="text-[#c17a4a]">${{ number_format($total, 2) }}</span>
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
    // Ctrl+Enter or Cmd+Enter for checkout
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        const checkoutBtn = document.querySelector('[wire\\:click*="showPaymentModal"]');
        if (checkoutBtn) checkoutBtn.click();
    }
});
</script>
