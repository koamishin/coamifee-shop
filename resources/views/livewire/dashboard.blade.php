<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Welcome to your ordering system dashboard</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Last updated: {{ now()->format('M j, Y g:i A') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Products -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 transform transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:scale-105 cursor-pointer group">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center group-hover:bg-blue-600 transition-colors duration-300">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors duration-300">Total Products</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300">{{ $stats['total_products'] }}</p>
                        <p class="text-xs text-green-600 dark:text-green-400">{{ $stats['active_products'] }} active</p>
                    </div>
                </div>
            </div>

            <!-- Total Orders -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 transform transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:scale-105 cursor-pointer group">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center group-hover:bg-green-600 transition-colors duration-300">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors duration-300">Total Orders</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors duration-300">{{ $stats['total_orders'] }}</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400">{{ $stats['today_orders'] }} today</p>
                    </div>
                </div>
            </div>

            <!-- Today's Revenue -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 transform transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:scale-105 cursor-pointer group">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center group-hover:bg-yellow-600 transition-colors duration-300">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors duration-300">Today's Revenue</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white group-hover:text-yellow-600 dark:group-hover:text-yellow-400 transition-colors duration-300">${{ number_format($stats['today_revenue'], 2) }}</p>
                        <p class="text-xs text-green-600 dark:text-green-400">${{ number_format($stats['this_week_revenue'], 2) }} this week</p>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 transform transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:scale-105 cursor-pointer group">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center group-hover:bg-red-600 transition-colors duration-300">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors duration-300">Low Stock</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors duration-300">{{ $stats['low_stock_count'] }}</p>
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $stats['out_of_stock_count'] }} out of stock</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Status Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-8 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Order Status Overview</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-500 dark:text-gray-400">{{ $orderStatusCounts['pending'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Pending</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-500">{{ $orderStatusCounts['confirmed'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Confirmed</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-500">{{ $orderStatusCounts['preparing'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Preparing</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-500">{{ $orderStatusCounts['ready'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Ready</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-emerald-500">{{ $orderStatusCounts['completed'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Completed</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-500">{{ $orderStatusCounts['cancelled'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Cancelled</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Recent Orders -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Orders</h3>
                </div>
                <div class="p-6">
                    @if($recentOrders->count() > 0)
                        <div class="space-y-4">
                            @foreach($recentOrders as $order)
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg transform transition-all duration-300 hover:bg-gray-100 dark:hover:bg-gray-600 hover:shadow-md hover:-translate-y-1 cursor-pointer group">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-2 h-2 bg-{{ $order->status_color }}-500 rounded-full group-hover:scale-150 transition-transform duration-300"></div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300">Order #{{ $order->order_number }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $order->customer->name ?? 'Walk-in Customer' }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors duration-300">{{ $order->formatted_total_amount }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $order->order_date->format('M j, g:i A') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No recent orders</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Low Stock Products -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Low Stock Alert</h3>
                </div>
                <div class="p-6">
                    @if($lowStockProducts->count() > 0)
                        <div class="space-y-4">
                            @foreach($lowStockProducts as $product)
                                <div class="flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 transform transition-all duration-300 hover:bg-red-100 dark:hover:bg-red-900/30 hover:shadow-md hover:-translate-y-1 cursor-pointer group">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-2 h-2 bg-red-500 rounded-full group-hover:scale-150 group-hover:animate-pulse transition-all duration-300"></div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors duration-300">{{ $product->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $product->category->name ?? 'No Category' }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-red-600 dark:text-red-400 group-hover:text-red-700 dark:group-hover:text-red-300 transition-colors duration-300">{{ $product->stock_level }} left</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Min: {{ $product->inventory->minimum_stock ?? 0 }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">All products are well stocked!</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Featured Products -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 transform transition duration-300 hover:-translate-y-2 hover:shadow-lg hover:bg-gray-100 dark:hover:bg-gray-600">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Featured Products</h3>
            </div>
            <div class="p-6">
                @if($featuredProducts->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($featuredProducts as $product)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 transform transition-all duration-300 hover:-translate-y-2 hover:shadow-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer group">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-32 object-cover rounded-lg mb-3 group-hover:scale-105 transition-transform duration-300">
                                @endif
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300">{{ $product->name }}</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ $product->category->name ?? 'No Category' }}</p>
                                    <div class="flex items-center justify-between">
                                        <span class="text-lg font-bold text-green-600 dark:text-green-400 group-hover:text-green-700 dark:group-hover:text-green-300 transition-colors duration-300">{{ $product->formatted_price }}</span>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-800 transition-colors duration-300">
                                            Featured
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No featured products</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Top Selling Products -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-8 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Top Selling Products</h3>
            </div>
            <div class="p-6">
                @if($topSellingProducts->count() > 0)
                    <div class="space-y-4">
                        @foreach($topSellingProducts as $index => $product)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg transform transition-all duration-300 hover:bg-gray-100 dark:hover:bg-gray-600 hover:shadow-md hover:-translate-y-1 cursor-pointer group">
                                <div class="flex items-center space-x-4">
                                    <div class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold group-hover:bg-blue-600 group-hover:scale-110 transition-all duration-300">
                                        {{ $index + 1 }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300">{{ $product->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $product->category->name ?? 'No Category' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors duration-300">{{ $product->total_sold }} sold</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $product->formatted_price }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No sales data available</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Category Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Category Distribution</h3>
            </div>
            <div class="p-6">
                @if($categoryDistribution->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($categoryDistribution as $category)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg transform transition-all duration-300 hover:bg-gray-100 dark:hover:bg-gray-600 hover:shadow-md hover:-translate-y-1 cursor-pointer group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center group-hover:bg-blue-600 group-hover:scale-110 transition-all duration-300">
                                        <span class="text-white text-sm font-bold">{{ substr($category->name, 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300">{{ $category->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $category->product_count }} products</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="w-16 bg-gray-200 dark:bg-gray-600 rounded-full h-2 group-hover:bg-gray-300 dark:group-hover:bg-gray-500 transition-colors duration-300">
                                        <div class="bg-blue-500 h-2 rounded-full group-hover:bg-blue-600 transition-all duration-300" style="width: {{ $categoryDistribution->max('product_count') > 0 ? ($category->product_count / $categoryDistribution->max('product_count')) * 100 : 0 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No categories available</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>