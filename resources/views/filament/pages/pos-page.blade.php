<x-filament-panels::page>
    <div class="min-h-screen bg-gray-50 flex flex-col border-red-500">
        <!-- Main Content Grid - Tablet Optimized -->
        <div class="flex-1 grid grid-cols-1 lg:grid-cols-10 gap-4 p-4 overflow-hidden">
            <!-- Left Side - Products Section (Tablet: Full Width, Desktop: 8 cols) -->
            <div class="lg:col-span-8 flex flex-col space-y-4 min-h-0">
                <!-- Categories - Touch-Friendly Tabs -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Categories</h3>
                        <div class="bg-primary-100 text-primary-800 px-3 py-1 rounded-full text-sm font-medium">
                            {{ $categories?->count() ?? 0 }} categories
                        </div>
                    </div>

                    <!-- Scrollable Categories for Tablets -->
                    <div class="overflow-x-auto pb-2">
                        <div class="flex gap-3 min-w-max">
                            <!-- All Categories Button -->
                            <button
                                wire:click="selectCategory(null)"
                                wire:key="category-all"
                                class="inline-flex items-center px-6 py-3 rounded-xl text-base font-medium transition-all duration-200 cursor-pointer min-w-[120px] justify-center {{
                                    $selectedCategoryId === null
                                        ? 'bg-primary-600 text-white shadow-lg scale-105 border-2 border-primary-600'
                                        : 'bg-white border-2 border-gray-200 text-gray-700 hover:border-primary-300 hover:bg-primary-50'
                                }}"
                            >
                                <x-filament::icon icon="heroicon-o-squares-2x2" class="w-5 h-5 mr-2 shrink-0" />
                                <span>All</span>
                                @if($selectedCategoryId === null)
                                    <div class="ml-2 w-2 h-2 bg-white rounded-full"></div>
                                @endif
                            </button>

                            <!-- Category Buttons -->
                            @if(isset($categories) && $categories->count() > 0)
                                @foreach($categories as $category)
                                    <button
                                        wire:click="selectCategory({{ $category->id }})"
                                        wire:key="category-{{ $category->id }}"
                                        class="inline-flex items-center px-6 py-3 rounded-xl text-base font-medium transition-all duration-200 cursor-pointer min-w-[120px] justify-center {{
                                            $selectedCategoryId === $category->id
                                                ? 'bg-primary-600 text-white shadow-lg scale-105 border-2 border-primary-600'
                                                : 'bg-white border-2 border-gray-200 text-gray-700 hover:border-primary-300 hover:bg-primary-50'
                                        }}"
                                    >
                                        @if($category->icon)
                                            @if(str_starts_with($category->icon, '<svg'))
                                                <div class="w-5 h-5 mr-2 shrink-0">{!! $category->icon !!}</div>
                                            @else
                                                <x-filament::icon icon="{{ $category->icon }}" class="w-5 h-5 mr-2 shrink-0" />
                                            @endif
                                        @else
                                            <x-filament::icon icon="heroicon-o-tag" class="w-5 h-5 mr-2 shrink-0" />
                                        @endif
                                        <span>{{ $category->name }}</span>
                                        @if($selectedCategoryId === $category->id)
                                            <div class="ml-2 w-2 h-2 bg-white rounded-full"></div>
                                        @endif
                                    </button>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Search Bar - Large Touch Target -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="relative">
                        <x-filament::icon icon="heroicon-o-magnifying-glass" class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search products..."
                            class="w-full pl-12 pr-4 py-4 text-base border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                    </div>
                </div>

                <!-- Products Grid - Touch-Friendly Cards -->
                <div class="flex-1 bg-white rounded-xl shadow-sm border border-gray-200 p-4 overflow-hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Products</h3>
                        <div class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm font-medium">
                            {{ $products?->count() ?? 0 }} items
                        </div>
                    </div>

                    <div class="h-full overflow-y-auto">
                        @if(isset($products) && $products->count() > 0)
                            <!-- Grid: Always 3 columns for better visibility -->
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                @foreach($products as $product)
                                    @php
                                        $isInCart = collect($this->cartItems)->contains('product_id', $product->id);
                                        $stockStatus = $this->getStockStatus($product->id);
                                    @endphp

                                    <div wire:key="product-{{ $product->id }}">
                                        <!-- Product Card -->
                                        <div
                                            class="bg-linear-to-br from-gray-50 to-white border-2 {{
                                                $isInCart
                                                    ? 'border-primary-500 bg-primary-50 shadow-lg'
                                                    : 'border-gray-200'
                                            }} rounded-xl p-4 hover:border-primary-400 hover:shadow-xl transition-all duration-300 hover:scale-105 {{
                                                $stockStatus === 'out_of_stock'
                                                    ? 'opacity-60 cursor-not-allowed'
                                                    : 'cursor-pointer'
                                            }}"
                                            wire:click="addToCart({{ $product->id }})"
                                            {{ $stockStatus === 'out_of_stock' ? 'wire:click.prevent' : '' }}
                                        >
                                            <!-- Product Image - Large Touch Area -->
                                            <div class="aspect-square mb-4 rounded-lg overflow-hidden bg-gray-100 relative">
                                                @if($product->image_url)
                                                    <img
                                                        src="{{ $product->image_url }}"
                                                        alt="{{ $product->name }}"
                                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                                    >
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center bg-linear-to-br from-gray-100 to-gray-200">
                                                        <x-filament::icon icon="heroicon-o-photo" class="w-12 h-12 text-gray-400" />
                                                    </div>
                                                @endif

                                                <!-- Stock Status Badge -->
                                                @if($stockStatus === 'low_stock')
                                                    <div class="absolute top-2 right-2 bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
                                                        Low Stock
                                                    </div>
                                                @elseif($stockStatus === 'out_of_stock')
                                                    <div class="absolute top-2 right-2 bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium">
                                                        Out of Stock
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Product Info -->
                                            <h4 class="font-semibold text-gray-900 mb-2 line-clamp-2 text-base">{{ $product->name }}</h4>
                                            <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ $product->description ?? 'No description' }}</p>

                                            <!-- Price and Stock -->
                                            <div class="flex items-center justify-between">
                                                <span class="text-xl font-bold {{ $isInCart ? 'text-primary-600' : 'text-gray-900' }}">
                                                    {{ $this->formatCurrency($product->price) }}
                                                </span>
                                                @if($stockStatus === 'in_stock')
                                                    <div class="text-sm text-gray-500">
                                                        Available
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Cart Indicator -->
                                            @if($isInCart)
                                                <div class="absolute top-2 left-2 bg-primary-600 text-white px-2 py-1 rounded-full text-xs font-medium">
                                                    <x-filament::icon icon="heroicon-o-check" class="w-3 h-3 mr-1 inline" />
                                                    In Cart
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center h-64 text-gray-500">
                                <x-filament::icon icon="heroicon-o-inbox" class="w-20 h-20 mb-6 text-gray-300" />
                                <h3 class="text-xl font-medium text-gray-900 mb-2">No products found</h3>
                                <p class="text-base text-gray-500">Try adjusting your category selection or search terms</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>


                <!-- Order Information - Fixed Height -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4 shrink-0">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <x-filament::icon icon="heroicon-o-clipboard-document-list" class="w-5 h-5 mr-2" />
                        Order Details
                    </h3>

                    <div class="space-y-3">
                        <!-- Customer Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Customer</label>
                            <div class="relative">
                                <x-filament::icon icon="heroicon-o-users" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                <select
                                    wire:model.live="customerId"
                                    class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 appearance-none bg-white"
                                >
                                    <option value="">Walk-in Customer</option>
                                    @if(isset($customers) && $customers->count() > 0)
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <!-- Customer Name (if not selected) -->
                        @if(!$customerId)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Customer Name</label>
                                <div class="relative">
                                    <x-filament::icon icon="heroicon-o-user" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                    <input
                                        type="text"
                                        wire:model.live="customerName"
                                        placeholder="Enter customer name"
                                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    >
                                </div>
                            </div>
                        @endif

                        <!-- Order Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Order Type</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    wire:click="$set('orderType', 'dine_in')"
                                    class="px-2 py-2 text-sm rounded-lg border transition-all duration-200 text-center flex flex-col items-center {{
                                        $orderType === 'dine_in'
                                            ? 'border-primary-500 bg-primary-50 text-primary-700'
                                            : 'border-gray-200 hover:border-gray-300 text-gray-700'
                                    }}"
                                >
                                    <span class="text-lg mb-1">🍽️</span>
                                    <span class="text-xs font-medium">Dine In</span>
                                </button>
                                <button
                                    wire:click="$set('orderType', 'takeaway')"
                                    class="px-2 py-2 text-sm rounded-lg border transition-all duration-200 text-center flex flex-col items-center {{
                                        $orderType === 'takeaway'
                                            ? 'border-primary-500 bg-primary-50 text-primary-700'
                                            : 'border-gray-200 hover:border-gray-300 text-gray-700'
                                    }}"
                                >
                                    <span class="text-lg mb-1">🥡</span>
                                    <span class="text-xs font-medium">Takeaway</span>
                                </button>
                                <button
                                    wire:click="$set('orderType', 'delivery')"
                                    class="px-2 py-2 text-sm rounded-lg border transition-all duration-200 text-center flex flex-col items-center {{
                                        $orderType === 'delivery'
                                            ? 'border-primary-500 bg-primary-50 text-primary-700'
                                            : 'border-gray-200 hover:border-gray-300 text-gray-700'
                                    }}"
                                >
                                    <span class="text-lg mb-1">🚗</span>
                                    <span class="text-xs font-medium">Delivery</span>
                                </button>
                            </div>
                        </div>

                        <!-- Table Number (for dine-in) -->
                        @if($orderType === 'dine_in')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Table Number</label>
                                <div class="relative">
                                    <x-filament::icon icon="heroicon-o-rectangle-stack" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                    <input
                                        type="text"
                                        wire:model.live="tableNumber"
                                        placeholder="e.g., T1, A12"
                                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    >
                                </div>
                            </div>
                        @endif

                        <!-- Order Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Order Notes</label>
                            <textarea
                                wire:model.live="notes"
                                placeholder="Special instructions..."
                                rows="2"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Cart - Fixed Height with Internal Scroll -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-4 flex flex-col h-[45vh]">
                    <div class="flex items-center justify-between mb-3 shrink-0">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                            <x-filament::icon icon="heroicon-o-shopping-cart" class="w-5 h-5 mr-2" />
                            Cart
                        </h3>
                        @if(!empty($this->cartItems))
                            <button
                                wire:click="clearCart"
                                class="text-sm text-red-600 hover:text-red-700 font-medium flex items-center px-3 py-1 rounded-lg hover:bg-red-50 transition-colors"
                            >
                                <x-filament::icon icon="heroicon-o-trash" class="w-4 h-4 mr-2" />
                                Clear
                            </button>
                        @endif
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        @if(!empty($this->cartItems))
                            <div class="space-y-3">
                                @foreach($this->cartItems as $index => $item)
                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex-1">
                                                <h4 class="font-medium text-sm text-gray-900">{{ $item['name'] }}</h4>
                                                <p class="text-xs text-gray-500">{{ $this->formatCurrency($item['price']) }} each</p>
                                            </div>
                                            <button
                                                wire:click="removeFromCart({{ $index }})"
                                                class="text-red-500 hover:text-red-700 p-1 rounded-lg hover:bg-red-50 transition-colors"
                                            >
                                                <x-filament::icon icon="heroicon-o-x-mark" class="w-4 h-4" />
                                            </button>
                                        </div>

                                        <!-- Quantity Controls - Large Touch Targets -->
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                <button
                                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                                    class="w-8 h-8 rounded-full bg-white border border-gray-300 flex items-center justify-center hover:bg-gray-100 transition-colors touch-manipulation"
                                                >
                                                    <x-filament::icon icon="heroicon-o-minus" class="w-3 h-3" />
                                                </button>
                                                <span class="w-12 text-center font-medium text-sm">{{ $item['quantity'] }}</span>
                                                <button
                                                    wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                                    class="w-8 h-8 rounded-full bg-white border border-gray-300 flex items-center justify-center hover:bg-gray-100 transition-colors touch-manipulation"
                                                >
                                                    <x-filament::icon icon="heroicon-o-plus" class="w-3 h-3" />
                                                </button>
                                            </div>
                                            <span class="font-semibold text-sm text-gray-900">
                                                {{ $this->formatCurrency($item['subtotal']) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center h-32 text-gray-500">
                                <x-filament::icon icon="heroicon-o-shopping-cart" class="w-12 h-12 mb-3 text-gray-300" />
                                <h3 class="text-base font-medium text-gray-900 mb-1">Cart is empty</h3>
                                <p class="text-sm text-gray-500">Add products to get started</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Payment Section - Fixed at Bottom -->
                @if(!empty($this->cartItems))
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 shrink-0 mt-auto">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                            <x-filament::icon icon="heroicon-o-credit-card" class="w-5 h-5 mr-2" />
                            Payment
                        </h3>

                        <div class="space-y-3">
                            <!-- Payment Method -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        wire:click="$set('paymentMethod', 'cash')"
                                        class="px-3 py-2 text-sm rounded-lg border transition-all duration-200 flex items-center justify-center {{
                                            $paymentMethod === 'cash'
                                                ? 'border-primary-500 bg-primary-50 text-primary-700'
                                                : 'border-gray-200 hover:border-gray-300 text-gray-700'
                                        }}"
                                    >
                                        <x-filament::icon icon="heroicon-o-banknotes" class="w-4 h-4 mr-2" />
                                        Cash
                                    </button>
                                    <button
                                        wire:click="$set('paymentMethod', 'card')"
                                        class="px-3 py-2 text-sm rounded-lg border transition-all duration-200 flex items-center justify-center {{
                                            $paymentMethod === 'card'
                                                ? 'border-primary-500 bg-primary-50 text-primary-700'
                                                : 'border-gray-200 hover:border-gray-300 text-gray-700'
                                        }}"
                                    >
                                        <x-filament::icon icon="heroicon-o-credit-card" class="w-4 h-4 mr-2" />
                                        Card
                                    </button>
                                </div>
                            </div>

                            <!-- Summary -->
                            <div class="space-y-2 border-t border-gray-200 pt-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span class="font-medium">{{ $this->formatCurrency($totalAmount) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Tax (10%)</span>
                                    <span class="font-medium">{{ $this->formatCurrency($totalAmount * 0.10) }}</span>
                                </div>
                                <div class="flex justify-between text-lg font-semibold text-gray-900 border-t border-gray-200 pt-2">
                                    <span>Total</span>
                                    <span>{{ $this->formatCurrency($totalAmount * 1.10) }}</span>
                                </div>
                            </div>

                            <!-- Cash Payment Input -->
                            @if($paymentMethod === 'cash')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ $this->getCurrencySymbol() }} Received</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold">{{ $this->getCurrencySymbol() }}</span>
                                        <input
                                            type="number"
                                            wire:model.live="paidAmount"
                                            step="{{ '0.' . str_repeat('0', $this->getCurrencyDecimals() - 1) . '1' }}"
                                            placeholder="0.00"
                                            class="w-full pl-8 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                        >
                                    </div>
                                    @if($changeAmount > 0)
                                        <div class="mt-2 p-2 bg-green-50 border border-green-200 rounded-lg">
                                            <p class="text-green-700 font-medium">Change: {{ $this->formatCurrency($changeAmount) }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- Complete Order Button -->
                            <button
                                wire:click="completeOrder"
                                wire:loading.attr="disabled"
                                class="w-full bg-primary-600 hover:bg-primary-700 disabled:bg-gray-300 text-white font-medium py-2.5 rounded-lg transition-colors duration-200 flex items-center justify-center text-sm"
                            >
                                <span wire:loading.remove class="flex items-center">
                                    <x-filament::icon icon="heroicon-o-check-circle" class="w-4 h-4 mr-2" />
                                    Complete Order
                                </span>
                                <span wire:loading class="flex items-center">
                                    <x-filament::icon icon="heroicon-o-arrow-path" class="w-4 h-4 mr-2 animate-spin" />
                                    Processing...
                                </span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

</x-filament-panels::page>
