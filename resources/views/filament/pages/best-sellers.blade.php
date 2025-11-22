<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Best Sellers by Category</h2>
                    <p class="mt-2 text-gray-600">
                        Top 3 best-selling products per category based on <strong>completed orders</strong> from the last month.
                        Shows categories with 1 or more products.
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-500">Last 30 Days</span>
                </div>
            </div>
        </div>

        <!-- Best Sellers Content -->
        @if($this->bestSellersData->isNotEmpty())
            <div class="space-y-6">
                @foreach($this->bestSellersData as $categoryName => $products)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <!-- Category Header -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    {{ $categoryName }}
                                </h3>
                                <span class="text-sm text-gray-500">
                                    {{ $products->count() }} top products
                                </span>
                            </div>
                        </div>

                        <!-- Products Grid -->
                        <div class="p-6">
                            <div class="grid gap-4 md:grid-cols-1 lg:grid-cols-3">
                                @foreach($products as $index => $productData)
                                    <div class="relative">
                                        <!-- Ranking Badge -->
                                        <div class="absolute -top-2 -left-2 z-10">
                                            @if($index === 0)
                                                <div class="bg-yellow-400 text-yellow-900 rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm shadow-lg">
                                                    1
                                                </div>
                                            @elseif($index === 1)
                                                <div class="bg-gray-400 text-gray-900 rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm shadow-lg">
                                                    2
                                                </div>
                                            @elseif($index === 2)
                                                <div class="bg-orange-400 text-orange-900 rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm shadow-lg">
                                                    3
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Product Card -->
                                        <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200 {{ $index === 0 ? 'ring-2 ring-yellow-400 ring-opacity-50' : '' }}">
                                            <div class="space-y-3">
                                                <!-- Product Name -->
                                                <div class="flex items-start justify-between">
                                                    <h4 class="font-semibold text-gray-900 flex-1 mr-2">
                                                        {{ $productData->product->name }}
                                                    </h4>
                                                    @if($index === 0)
                                                        <svg class="w-5 h-5 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                        </svg>
                                                    @endif
                                                </div>

                                                <!-- Product Details -->
                                                <div class="space-y-2">
                                                    <!-- Sales Info -->
                                                    <div class="flex items-center justify-between text-sm">
                                                        <span class="text-gray-500">Units Sold:</span>
                                                        <span class="font-semibold text-blue-600">
                                                            {{ number_format($productData->total_quantity) }}
                                                        </span>
                                                    </div>

                                                    <div class="flex items-center justify-between text-sm">
                                                        <span class="text-gray-500">Revenue:</span>
                                                        <span class="font-semibold text-green-600">
                                                            ₱{{ number_format($productData->total_revenue, 2) }}
                                                        </span>
                                                    </div>

                                                    <!-- Progress Bar -->
                                                    <div class="mt-2">
                                                        @php
                                                            $topProductQuantity = $products->first()->total_quantity ?? 1;
                                                            $percentage = $topProductQuantity > 0 ? round(($productData->total_quantity / $topProductQuantity) * 100, 0) : 0;
                                                        @endphp
                                                        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                                            <span>Sales Performance</span>
                                                            <span>{{ $percentage }}%</span>
                                                        </div>
                                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                                            <div
                                                                class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-300"
                                                                style="width: {{ min($percentage, 100) }}%"
                                                            ></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Best Sellers Data Available</h3>
                <p class="text-gray-500 mb-6">
                    @if(\App\Models\OrderItem::count() === 0)
                        No sales data available yet. Complete some orders to see best sellers here.
                    @else
                        No products found with <strong>completed orders</strong> in the last month.
                        Try completing more orders or check back later.
                    @endif
                </p>
                <button
                    wire:click="refreshData"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh Data
                </button>
            </div>
        @endif

        <!-- Summary Stats -->
        @if($this->bestSellersData->isNotEmpty())
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 border border-blue-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Summary Statistics</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">
                            {{ $this->bestSellersData->count() }}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Categories Featured</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600">
                            {{ $this->bestSellersData->sum(function($products) { return $products->sum('total_quantity'); }) }}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Total Units Sold</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-purple-600">
                            ₱{{ number_format($this->bestSellersData->sum(function($products) { return $products->sum('total_revenue'); }), 0) }}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Total Revenue</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Info Section -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold">About Best Sellers</p>
                    <p class="mt-1">
                        This data shows actual product sales from <strong>completed orders</strong> in the last 30 days, excluding add-ons.
                        Shows categories with 1 or more products. Use the actions above to refresh data or export reports.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>