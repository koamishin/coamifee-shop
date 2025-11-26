<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Navigation Tabs --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center gap-2 overflow-x-auto">
                <button
                    wire:click="filterByPeriod('all')"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition-all whitespace-nowrap {{ $periodFilter === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    All Time
                </button>
                <button
                    wire:click="filterByPeriod('today')"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition-all whitespace-nowrap {{ $periodFilter === 'today' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    Today
                </button>
                <button
                    wire:click="filterByPeriod('week')"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition-all whitespace-nowrap {{ $periodFilter === 'week' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2 h-2 bg-blue-400 rounded-full"></span>
                        This Week
                    </span>
                </button>
                <button
                    wire:click="filterByPeriod('month')"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition-all whitespace-nowrap {{ $periodFilter === 'month' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                        This Month
                    </span>
                </button>
                <button
                    wire:click="filterByPeriod('year')"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition-all whitespace-nowrap {{ $periodFilter === 'year' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2 h-2 bg-amber-400 rounded-full"></span>
                        This Year
                    </span>
                </button>
            </div>
        </div>

        {{-- Best Sellers Grid --}}
        @if($this->bestSellersData->isNotEmpty())
        <!-- Best Sellers Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($this->bestSellersData as $categoryName => $products)
                <!-- Category Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 border-amber-300 dark:border-amber-700 overflow-hidden hover:shadow-md transition-shadow">
                    <!-- Category Header -->
                    <div class="px-4 py-3 bg-gradient-to-r from-amber-50 to-amber-100 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $categoryName }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $products->count() }} {{ Str::plural('product', $products->count()) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-amber-700 dark:text-amber-400">
                                    ₱{{ number_format($products->sum('total_revenue'), 0) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $products->sum('total_quantity') }} units
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products List -->
                    <div class="p-4 space-y-2 max-h-64 overflow-y-auto">
                        @foreach($products as $index => $productData)
                            @php
                                $topProductQuantity = $products->first()->total_quantity ?? 1;
                                $percentage = $topProductQuantity > 0 ? round(($productData->total_quantity / $topProductQuantity) * 100, 0) : 0;
                            @endphp

                            <div class="flex items-center justify-between text-sm group hover:bg-gray-50 dark:hover:bg-gray-700 p-3 rounded transition-colors relative {{ $index === 0 ? 'bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-950 dark:to-amber-950 border-l-4 border-yellow-400' : '' }}">
                                <!-- Rank Badge -->
                                <div class="flex-shrink-0 mr-2">
                                    @if($index === 0)
                                        <div class="flex items-center justify-center w-7 h-7 rounded-full bg-gradient-to-br from-yellow-400 to-amber-500 text-white font-bold text-xs shadow-md">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </div>
                                    @elseif($index === 1)
                                        <div class="flex items-center justify-center w-7 h-7 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 text-white font-bold text-xs shadow-md">
                                            2
                                        </div>
                                    @elseif($index === 2)
                                        <div class="flex items-center justify-center w-7 h-7 rounded-full bg-gradient-to-br from-orange-300 to-orange-400 text-white font-bold text-xs shadow-md">
                                            3
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center w-7 h-7 rounded-full bg-blue-500 dark:bg-blue-600 text-white font-bold text-xs shadow-md">
                                            {{ $index + 1 }}
                                        </div>
                                    @endif
                                </div>

                                <!-- Product Info -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm {{ $index === 0 ? 'font-bold text-amber-900 dark:text-amber-100' : 'font-semibold text-gray-900 dark:text-white' }} truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                        {{ $productData->product->name }}
                                    </p>
                                    <p class="text-xs {{ $index === 0 ? 'text-amber-700 dark:text-amber-300' : 'text-gray-500 dark:text-gray-400' }} truncate">
                                        SKU: {{ $productData->product->sku }}
                                    </p>
                                </div>

                                <!-- Metrics -->
                                <div class="text-right ml-2 flex-shrink-0">
                                    <div class="text-sm {{ $index === 0 ? 'font-bold text-amber-900 dark:text-amber-100' : 'font-bold text-gray-900 dark:text-white' }}">
                                        {{ number_format($productData->total_quantity) }}
                                    </div>
                                    <div class="text-xs {{ $index === 0 ? 'text-amber-700 dark:text-amber-300' : 'text-green-600 dark:text-green-400' }} font-semibold">
                                        ₱{{ number_format($productData->total_revenue, 0) }}
                                    </div>
                                    <div class="text-xs {{ $index === 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400' }} mt-0.5">
                                        {{ $percentage }}%
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>


    @else
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                <svg class="h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No sales data available</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                @if(\App\Models\OrderItem::count() === 0)
                    No orders have been placed yet. Once you start completing orders, your best-selling products will appear here.
                @else
                    No products found with completed orders between {{ $this->startDate }} and {{ $this->endDate }}. Try adjusting the date range or complete some orders to see your best sellers.
                @endif
            </p>
            <button
                wire:click="refreshData"
                type="button"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh Data
            </button>
        </div>
        @endif
    </div>
</x-filament-panels::page>
