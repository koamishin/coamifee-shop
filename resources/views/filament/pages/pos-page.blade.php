<x-filament-panels::page>
    {{-- Coffee Shop POS Layout - Optimized for Desktop & Tablet --}}
    <div class="bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 -m-6 h-[calc(100vh-4rem)] overflow-hidden">
        <div class="h-full flex flex-col lg:flex-row gap-3 p-3 overflow-hidden">

            {{-- LEFT SECTION: Products & Categories --}}
            <div class="flex-1 lg:w-[68%] flex flex-col gap-3 min-h-0">

                {{-- Search Bar & Mode Toggle --}}
                <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-3">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <x-filament::icon icon="heroicon-o-magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Search coffee, pastries, snacks..."
                                class="w-full pl-10 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-200 focus:border-orange-400 transition-all bg-white"
                            >
                        </div>
                        <button
                            wire:click="toggleMode"
                            class="flex items-center gap-2 px-3 py-2 rounded-lg border-2 transition-all {{ $isTabletMode ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-300 bg-gray-50 text-gray-700' }}"
                            title="{{ $isTabletMode ? 'Switch to Desktop Mode' : 'Switch to Tablet Mode' }}"
                        >
                            @if($isTabletMode)
                                <x-filament::icon icon="heroicon-o-device-tablet" class="w-5 h-5" />
                                <span class="text-xs font-medium">Tablet</span>
                            @else
                                <x-filament::icon icon="heroicon-o-computer-desktop" class="w-5 h-5" />
                                <span class="text-xs font-medium">Desktop</span>
                            @endif
                        </button>
                    </div>
                </div>

                {{-- Categories --}}
                <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-3">
                    <div class="overflow-x-auto scrollbar-hide -mx-1 px-1">
                        <div class="flex gap-2 pb-1">
                            {{-- All Categories Button --}}
                            <button
                                wire:click="selectCategory(null)"
                                class="flex-shrink-0 px-4 py-2 rounded-lg font-medium text-xs transition-all min-w-[80px] {{ $selectedCategoryId === null ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                            >
                                <div class="flex items-center justify-center gap-1.5">
                                    <span class="text-sm">☕</span>
                                    <span>All</span>
                                </div>
                            </button>

                            {{-- Category Buttons --}}
                            @if(isset($categories) && $categories->count() > 0)
                                @foreach($categories as $category)
                                    <button
                                        wire:click="selectCategory({{ $category->id }})"
                                        wire:key="category-{{ $category->id }}"
                                        class="flex-shrink-0 px-4 py-2 rounded-lg font-medium text-xs transition-all min-w-[80px] {{ $selectedCategoryId === $category->id ? 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                                    >
                                        <div class="flex items-center justify-center gap-1.5">
                                            @if($category->icon)
                                                <x-filament::icon icon="{{ $category->icon }}" class="w-3 h-3" />
                                            @endif
                                            <span class="whitespace-nowrap">{{ $category->name }}</span>
                                        </div>
                                    </button>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Products Grid --}}
                <div class="flex-1 bg-white rounded-xl shadow-sm border border-orange-100 p-3 overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-semibold text-sm text-gray-900">Products</h2>
                        <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                            {{ $products?->count() ?? 0 }} items
                        </span>
                    </div>

                    <div class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-orange-300 scrollbar-track-gray-100">
                        @if(isset($products) && $products->count() > 0)
                            <div class="grid grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-2">
                                @foreach($products as $product)
                                    @php
                                        $isInCart = collect($this->cartItems)->contains('product_id', $product->id);
                                        $stockStatus = $this->getStockStatus($product->id);
                                        $cartItem = collect($this->cartItems)->firstWhere('product_id', $product->id);
                                    @endphp

                                    <button
                                        wire:click="addToCart({{ $product->id }})"
                                        wire:key="product-{{ $product->id }}"
                                        {{ $stockStatus === 'out_of_stock' ? 'disabled' : '' }}
                                        class="group relative bg-white border-2 rounded-xl p-2 transition-all touch-manipulation {{ $isInCart ? 'border-orange-400 bg-orange-50 shadow-md' : 'border-gray-200 hover:border-orange-300 hover:shadow-md' }} {{ $stockStatus === 'out_of_stock' ? 'opacity-50 cursor-not-allowed' : 'active:scale-95' }}"
                                    >
                                        {{-- Product Image --}}
                                        <div class="aspect-square mb-2 rounded-lg overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200 relative">
                                            @if($product->image_url)
                                                <img
                                                    src="{{ $product->image_url }}"
                                                    alt="{{ $product->name }}"
                                                    class="w-full h-full object-cover transition-transform duration-300"
                                                >
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <x-filament::icon icon="heroicon-o-photo" class="w-8 h-8 text-gray-400" />
                                                </div>
                                            @endif

                                            {{-- Stock Badge --}}
                                            @if($stockStatus === 'out_of_stock')
                                                <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                                    <span class="bg-red-500 text-white px-2 py-0.5 rounded text-xs font-bold">Out</span>
                                                </div>
                                            @endif

                                            {{-- In Cart Badge --}}
                                            @if($isInCart)
                                                <div class="absolute top-1 left-1 bg-orange-500 text-white px-1.5 py-0.5 rounded text-xs font-bold flex items-center gap-0.5">
                                                    <span>{{ $cartItem['quantity'] }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Product Info --}}
                                        <h3 class="font-semibold text-xs text-gray-900 mb-1 line-clamp-2 text-left leading-tight">
                                            {{ $product->name }}
                                        </h3>

                                        {{-- Price --}}
                                        <div class="text-sm font-bold {{ $isInCart ? 'text-orange-600' : 'text-gray-900' }}">
                                            {{ $this->formatCurrency($product->price) }}
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center h-full text-gray-400">
                                <x-filament::icon icon="heroicon-o-inbox" class="w-16 h-16 mb-3" />
                                <h3 class="text-sm font-medium text-gray-600">No products found</h3>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- RIGHT SECTION: Cart & Checkout --}}
            <div class="lg:w-[32%] flex flex-col gap-3 min-h-0 overflow-hidden">

                {{-- Order Type - Simplified --}}
                <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-3">
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            wire:click="$set('orderType', 'dine_in')"
                            class="flex flex-col items-center justify-center p-2 rounded-lg border-2 transition-all {{ $orderType === 'dine_in' ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 hover:border-gray-300 text-gray-600' }}"
                        >
                            <span class="text-xl mb-0.5">🍽️</span>
                            <span class="text-xs font-medium">Dine In</span>
                        </button>
                        <button
                            wire:click="$set('orderType', 'takeaway')"
                            class="flex flex-col items-center justify-center p-2 rounded-lg border-2 transition-all {{ $orderType === 'takeaway' ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 hover:border-gray-300 text-gray-600' }}"
                        >
                            <span class="text-xl mb-0.5">🥡</span>
                            <span class="text-xs font-medium">Takeaway</span>
                        </button>
                        <button
                            wire:click="$set('orderType', 'delivery')"
                            class="flex flex-col items-center justify-center p-2 rounded-lg border-2 transition-all {{ $orderType === 'delivery' ? 'border-orange-500 bg-orange-50 text-orange-700' : 'border-gray-200 hover:border-gray-300 text-gray-600' }}"
                        >
                            <span class="text-xl mb-0.5">🚗</span>
                            <span class="text-xs font-medium">Delivery</span>
                        </button>
                    </div>
                </div>

                {{-- Cart Items --}}
                <div class="flex-1 bg-white rounded-xl shadow-sm border border-orange-100 p-3 flex flex-col min-h-0 overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-shopping-cart" class="w-4 h-4 text-orange-600" />
                            <h2 class="font-semibold text-sm text-gray-900">Cart</h2>
                            @if(!empty($this->cartItems))
                                <span class="bg-orange-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">
                                    {{ count($this->cartItems) }}
                                </span>
                            @endif
                        </div>
                        @if(!empty($this->cartItems))
                            <button
                                wire:click="clearCart"
                                class="text-xs text-red-600 hover:text-red-700 font-medium px-2 py-1 rounded-lg hover:bg-red-50 transition-colors"
                            >
                                <x-filament::icon icon="heroicon-o-trash" class="w-3 h-3" />
                            </button>
                        @endif
                    </div>

                    <div class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-orange-300 scrollbar-track-gray-100">
                        @if(!empty($this->cartItems))
                            <div class="space-y-2">
                                @foreach($this->cartItems as $index => $item)
                                    <div class="bg-gradient-to-r from-gray-50 to-orange-50/30 rounded-lg p-2 border border-gray-200">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-xs text-gray-900">{{ $item['name'] }}</h4>
                                                <p class="text-xs text-gray-500">{{ $this->formatCurrency($item['price']) }}</p>
                                            </div>
                                            <button
                                                wire:click="removeFromCart({{ $index }})"
                                                class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-100 transition-colors"
                                            >
                                                <x-filament::icon icon="heroicon-o-x-mark" class="w-3 h-3" />
                                            </button>
                                        </div>

                                        {{-- Quantity Controls --}}
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <button
                                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                                    class="w-7 h-7 rounded-lg bg-white border border-gray-300 flex items-center justify-center hover:bg-orange-50 hover:border-orange-400 transition-all active:scale-95 touch-manipulation"
                                                >
                                                    <x-filament::icon icon="heroicon-o-minus" class="w-3 h-3 text-gray-700" />
                                                </button>
                                                <span class="w-8 text-center font-bold text-sm text-gray-900">{{ $item['quantity'] }}</span>
                                                <button
                                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                                    class="w-7 h-7 rounded-lg bg-gradient-to-r from-orange-500 to-orange-600 border border-orange-600 flex items-center justify-center hover:from-orange-600 hover:to-orange-700 transition-all active:scale-95 touch-manipulation"
                                                >
                                                    <x-filament::icon icon="heroicon-o-plus" class="w-3 h-3 text-white" />
                                                </button>
                                            </div>
                                            <span class="font-bold text-sm text-orange-600">
                                                {{ $this->formatCurrency($item['subtotal']) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center h-full text-gray-400">
                                <x-filament::icon icon="heroicon-o-shopping-cart" class="w-12 h-12 mb-2" />
                                <h3 class="text-sm font-medium text-gray-600">Cart is empty</h3>
                                <p class="text-xs text-gray-500">Add items to start</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Checkout Summary --}}
                @if(!empty($this->cartItems))
                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg border-2 border-orange-400 p-3">
                        {{-- Order Summary --}}
                        <div class="bg-white/20 backdrop-blur-sm rounded-lg p-2 mb-3">
                            <div class="space-y-1 text-white text-xs">
                                <div class="flex justify-between">
                                    <span class="text-orange-100">Subtotal</span>
                                    <span class="font-medium">{{ $this->formatCurrency($totalAmount) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-orange-100">Tax (10%)</span>
                                    <span class="font-medium">{{ $this->formatCurrency($totalAmount * 0.10) }}</span>
                                </div>
                                <div class="flex justify-between text-base font-bold border-t border-white/30 pt-1">
                                    <span>TOTAL</span>
                                    <span>{{ $this->formatCurrency($totalAmount * 1.10) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Place Order Button --}}
                        <button
                            wire:click="mountAction('placeOrder')"
                            class="w-full bg-white text-orange-600 hover:bg-orange-50 font-bold py-3 rounded-lg transition-all flex items-center justify-center text-sm shadow-lg active:scale-95 touch-manipulation"
                        >
                            <x-filament::icon icon="heroicon-o-shopping-bag" class="w-5 h-5 mr-2" />
                            Place Order
                        </button>

                        <x-filament-actions::modals />
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        /* Hide scrollbar for Chrome, Safari and Opera */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Custom thin scrollbar */
        .scrollbar-thin::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            @apply bg-gray-100 rounded-full;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            @apply bg-orange-300 rounded-full;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            @apply bg-orange-400;
        }

        /* Touch-friendly improvements */
        @media (hover: none) and (pointer: coarse) {
            button {
                min-height: 44px;
            }
        }
    </style>
</x-filament-panels::page>
