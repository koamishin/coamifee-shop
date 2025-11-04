<x-filament-panels::page>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-gray-100">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b border-gray-200">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <h1 class="text-2xl font-bold text-gray-900">Point of Sale</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                            {{ now()->format('M j, Y H:i') }}
                        </span>
                        @if(auth()->user())
                            <div class="flex items-center space-x-2">
                                @if(auth()->user()->avatar_url)
                                    <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-600">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <span class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex h-[calc(100vh-80px)]">
            <!-- Left Side - Products -->
            <div class="flex-1 flex flex-col p-6 space-y-4">
                <!-- Categories -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Categories</h3>
                        <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs font-medium">
                            {{ $categories?->count() ?? 0 }} categories
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            wire:click="selectCategory(null)"
                            wire:key="category-all"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 {{
                                $selectedCategoryId === null
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            }}"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                            All Products
                        </button>
                        @if(isset($categories) && $categories->count() > 0)
                            @foreach($categories as $category)
                                <button
                                    wire:click="selectCategory({{ $category->id }})"
                                    wire:key="category-{{ $category->id }}"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 {{
                                        $selectedCategoryId === $category->id
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }}"
                                >
                                    @if($category->icon)
                                        <span>{{ $category->icon }}</span>
                                    @endif
                                    {{ $category->name }}
                                </button>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input
                            wire:model.live="search"
                            type="text"
                            placeholder="Search products..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="flex-1 bg-white rounded-xl shadow-sm border border-gray-200 p-4 overflow-hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Products</h3>
                        <div class="flex items-center space-x-2">
                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs font-medium">
                                {{ $products?->count() ?? 0 }} items
                            </span>
                            <div class="flex bg-gray-100 rounded-lg p-1">
                                <button class="p-1 rounded bg-white shadow-sm">
                                    <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                    </svg>
                                </button>
                                <button class="p-1 rounded">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="h-[calc(100%-60px)] overflow-y-auto">
                        @if(isset($products) && $products->count() > 0)
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                @foreach($products as $product)
                                    <div
                                        wire:click="addToCart({{ $product->id }})"
                                        wire:key="product-{{ $product->id }}"
                                        class="group cursor-pointer bg-gradient-to-br from-gray-50 to-white border-2 border-gray-200 rounded-xl p-4 hover:border-blue-400 hover:shadow-lg transition-all duration-300 hover:scale-105"
                                    >
                                        <div class="aspect-square mb-3 rounded-lg overflow-hidden bg-gray-100">
                                            @if($product->image_url)
                                                <img
                                                    src="{{ $product->image_url }}"
                                                    alt="{{ $product->name }}"
                                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                                >
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="space-y-1">
                                            <h4 class="font-semibold text-gray-900 text-sm truncate">{{ $product->name }}</h4>
                                            <p class="text-lg font-bold text-blue-600">${{ number_format($product->price, 2) }}</p>
                                            @if($product->category)
                                                <span class="inline-block px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full">
                                                    {{ $product->category->name }}
                                                </span>
                                            @endif
                                            @if(isset($product->stock_quantity) && $product->stock_quantity <= 10)
                                                <span class="inline-block px-2 py-1 text-xs bg-red-100 text-red-600 rounded-full">
                                                    Low Stock: {{ $product->stock_quantity }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center h-full py-16">
                                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p class="text-gray-500 text-lg font-medium">No products found</p>
                                <p class="text-gray-400 text-sm mt-1">Try adjusting your search or category filter</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Side - Order Panel -->
            <div class="w-96 bg-white border-l border-gray-200 flex flex-col">
                <!-- Order Information -->
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Details</h3>
                    <div class="space-y-3">
                        <!-- Customer Selection -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Customer</label>
                            <select wire:model.live="customerId" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Walk-in Customer</option>
                                @if(isset($customers) && $customers->count() > 0)
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <!-- Customer Name (if not selected) -->
                        @if(!$customerId)
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Customer Name</label>
                                <input
                                    type="text"
                                    wire:model.live="customerName"
                                    placeholder="Enter customer name"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                            </div>
                        @endif

                        <!-- Order Type -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Order Type</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    wire:click="$set('orderType', 'dine_in')"
                                    class="p-2 text-xs rounded border-2 transition-colors text-center {{
                                        $orderType === 'dine_in'
                                            ? 'border-blue-500 bg-blue-50 text-blue-700'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }}"
                                >
                                    🍽️ Dine In
                                </button>
                                <button
                                    wire:click="$set('orderType', 'takeaway')"
                                    class="p-2 text-xs rounded border-2 transition-colors text-center {{
                                        $orderType === 'takeaway'
                                            ? 'border-blue-500 bg-blue-50 text-blue-700'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }}"
                                >
                                    🥡 Takeaway
                                </button>
                                <button
                                    wire:click="$set('orderType', 'delivery')"
                                    class="p-2 text-xs rounded border-2 transition-colors text-center {{
                                        $orderType === 'delivery'
                                            ? 'border-blue-500 bg-blue-50 text-blue-700'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }}"
                                >
                                    🚗 Delivery
                                </button>
                            </div>
                        </div>

                        <!-- Table Number (for dine-in) -->
                        @if($orderType === 'dine_in')
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Table Number</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        wire:model.live="tableNumber"
                                        placeholder="e.g., T1, A12"
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                </div>
                            </div>
                        @endif

                        <!-- Order Notes -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Order Notes</label>
                            <textarea
                                wire:model.live="notes"
                                placeholder="Special instructions..."
                                rows="2"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Cart Items -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <div class="p-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Shopping Cart</h3>
                            @if(!empty($cartItems))
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                    {{ array_sum(array_column($cartItems, 'quantity')) }} items
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4">
                        @if(!empty($cartItems))
                            <div class="space-y-3">
                                @foreach($cartItems as $index => $item)
                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                        <div class="flex justify-between items-start mb-2">
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-900 text-sm">{{ $item['name'] }}</h4>
                                                <p class="text-xs text-gray-500">${{ number_format($item['price'], 2) }} each</p>
                                            </div>
                                            <button
                                                wire:click="removeFromCart({{ $index }})"
                                                class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                <button
                                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                                    class="w-6 h-6 rounded border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                    </svg>
                                                </button>
                                                <input
                                                    type="number"
                                                    wire:model.live="cartItems.{{ $index }}.quantity"
                                                    wire:change="updateQuantity({{ $index }}, $event.target.value)"
                                                    class="w-12 text-center border border-gray-300 rounded px-1 py-0.5 text-sm"
                                                    min="1"
                                                    value="{{ $item['quantity'] }}"
                                                >
                                                <button
                                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                                    class="w-6 h-6 rounded border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            <p class="font-bold text-gray-900">${{ number_format($item['subtotal'], 2) }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center h-full text-center">
                                <svg class="w-16 h-16 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <p class="text-gray-500 text-sm font-medium">Cart is empty</p>
                                <p class="text-gray-400 text-xs mt-1">Add products to start an order</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Payment Section -->
                @if(!empty($cartItems))
                    <div class="border-t border-gray-200 bg-gray-50 p-4 space-y-4">
                        <!-- Payment Method -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-2">Payment Method</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    wire:click="$set('paymentMethod', 'cash')"
                                    class="p-3 rounded-lg border-2 text-sm font-medium transition-colors flex items-center justify-center gap-2 {{
                                        $paymentMethod === 'cash'
                                            ? 'border-blue-500 bg-blue-50 text-blue-700'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }}"
                                >
                                    💵 Cash
                                </button>
                                <button
                                    wire:click="$set('paymentMethod', 'card')"
                                    class="p-3 rounded-lg border-2 text-sm font-medium transition-colors flex items-center justify-center gap-2 {{
                                        $paymentMethod === 'card'
                                            ? 'border-blue-500 bg-blue-50 text-blue-700'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }}"
                                >
                                    💳 Card
                                </button>
                            </div>
                        </div>

                        <!-- Amount Input (for cash payments) -->
                        @if($paymentMethod === 'cash')
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-2">Paid Amount</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <input
                                        type="number"
                                        wire:model.live="paidAmount"
                                        placeholder="0.00"
                                        step="0.01"
                                        min="0"
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                </div>
                            </div>
                        @endif

                        <!-- Order Summary -->
                        <div class="bg-white rounded-lg p-4 border border-gray-200 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">${{ number_format($totalAmount, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Tax (10%):</span>
                                <span class="font-medium">${{ number_format($totalAmount * 0.10, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-200">
                                <span>Total:</span>
                                <span class="text-blue-600">${{ number_format($totalAmount * 1.10, 2) }}</span>
                            </div>
                            @if($paymentMethod === 'cash' && $paidAmount > 0)
                                <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                                    <span class="text-gray-600">Change:</span>
                                    <span class="font-bold {{ $changeAmount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        ${{ number_format(abs($changeAmount - ($totalAmount * 0.10)), 2) }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Complete Order Button -->
                        <button
                            wire:click="completeOrder"
                            wire:loading.attr="disabled"
                            wire:target="completeOrder"
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                        >
                            <span wire:loading.remove class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Complete Order
                            </span>
                            <span wire:loading class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
