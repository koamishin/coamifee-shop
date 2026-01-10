<x-filament-panels::page>
    {{-- Status & Time Header --}}
    <div class="absolute top-3 right-16 z-50 flex items-center gap-3 px-4 py-1.5">
        {{-- Status Indicator Dot --}}
        <div class="flex items-center gap-1.5">
            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Online</span>
        </div>
        {{-- Time Separator --}}
        <span class="text-gray-300 dark:text-gray-600">•</span>
        {{-- Manila Date & Time --}}
        <div class="text-xs font-medium text-gray-600 dark:text-gray-400 flex items-center gap-2">
            <span id="manila-date" x-data="{ date: '' }" x-init="
                const updateDateTime = () => {
                    const now = new Date();
                    const manilaTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
                    const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
                    const dateStr = manilaTime.toLocaleDateString('en-US', options);
                    const hours = String(manilaTime.getHours()).padStart(2, '0');
                    const minutes = String(manilaTime.getMinutes()).padStart(2, '0');
                    const seconds = String(manilaTime.getSeconds()).padStart(2, '0');
                    document.getElementById('manila-date').innerText = dateStr;
                    document.getElementById('manila-clock').innerText = hours + ':' + minutes + ':' + seconds;
                };
                updateDateTime();
                setInterval(updateDateTime, 1000);
            ">--</span>
            <span id="manila-clock">--:--:--</span>
        </div>
    </div>

    <div class="space-y-6">
        {{-- Summary Statistics Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            {{-- Total Products --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                        <x-heroicon-o-cube class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Products</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $summaryStats['total_products'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Average Profit Margin --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900 flex items-center justify-center">
                        <x-heroicon-o-chart-bar class="w-5 h-5 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Avg Profit Margin</p>
                        <p class="text-xl font-bold {{ ($summaryStats['avg_profit_margin'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ number_format($summaryStats['avg_profit_margin'] ?? 0, 1) }}%
                        </p>
                    </div>
                </div>
            </div>

            {{-- Average Food Cost --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900 flex items-center justify-center">
                        <x-heroicon-o-calculator class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Avg Food Cost %</p>
                        <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($summaryStats['avg_food_cost_percentage'] ?? 0, 1) }}%</p>
                    </div>
                </div>
            </div>

            {{-- Profitable Count --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900 flex items-center justify-center">
                        <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Profitable</p>
                        <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ $summaryStats['profitable_count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Unprofitable Count --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900 flex items-center justify-center">
                        <x-heroicon-o-arrow-trending-down class="w-5 h-5 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Unprofitable</p>
                        <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ $summaryStats['unprofitable_count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Below Target Count --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900 flex items-center justify-center">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Below Target</p>
                        <p class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ $summaryStats['below_target_count'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Category Filter Tabs --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-2 overflow-x-auto">
                <button
                    wire:click="filterByCategory(null)"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition-all whitespace-nowrap {{ $selectedCategoryId === null ? 'bg-amber-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                >
                    All Categories
                </button>
                @foreach($categories as $category)
                    <button
                        wire:click="filterByCategory({{ $category->id }})"
                        class="px-4 py-2 rounded-lg font-medium text-sm transition-all whitespace-nowrap {{ $selectedCategoryId === $category->id ? 'bg-amber-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                    >
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Settings Info Bar --}}
        <div class="bg-amber-50 dark:bg-amber-900/30 rounded-xl border border-amber-200 dark:border-amber-700 p-4">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-user class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                        <span class="text-sm text-amber-800 dark:text-amber-200">Labor Cost: <span class="font-bold">{{ number_format($laborPercentage, 1) }}%</span></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-banknotes class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                        <span class="text-sm text-amber-800 dark:text-amber-200">Target Markup: <span class="font-bold">{{ number_format($markupPercentage, 1) }}%</span></span>
                    </div>
                </div>
                <span class="text-xs text-amber-600 dark:text-amber-400">Click "Costing Settings" above to adjust</span>
            </div>
        </div>

        {{-- Costing Table --}}
        @if($costingsData->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gradient-to-r from-amber-50 to-amber-100 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Product</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Category</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Ingredient Cost</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Labor ({{ number_format($laborPercentage, 0) }}%)</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Total Cost</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Menu Price</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Gross Profit</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Margin %</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Food Cost %</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($costingsData as $costing)
                                <tr
                                    x-data="{ expanded: false }"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {{ !$costing['is_profitable'] ? 'bg-red-50/50 dark:bg-red-900/10' : '' }}"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            @if(count($costing['ingredients']) > 0)
                                                <button
                                                    @click="expanded = !expanded"
                                                    class="flex-shrink-0 w-6 h-6 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                                >
                                                    <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-500 transition-transform" x-bind:class="expanded ? 'rotate-90' : ''" />
                                                </button>
                                            @else
                                                <div class="w-6"></div>
                                            @endif
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $costing['product_name'] }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $costing['sku'] ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $costing['category_name'] }}</td>
                                    <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-medium">{{ $currency }} {{ number_format($costing['ingredient_cost'], 2) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ $currency }} {{ number_format($costing['labor_cost'], 2) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-bold">{{ $currency }} {{ number_format($costing['total_cost'], 2) }}</td>
                                    <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400 font-bold">{{ $currency }} {{ number_format($costing['menu_price'], 2) }}</td>
                                    <td class="px-4 py-3 text-right font-bold {{ $costing['gross_profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $currency }} {{ number_format($costing['gross_profit'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $costing['profit_margin'] >= 50 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($costing['profit_margin'] >= 30 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                            {{ number_format($costing['profit_margin'], 1) }}%
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">{{ number_format($costing['food_cost_percentage'], 1) }}%</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($costing['price_status'] === 'above_target')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                <x-heroicon-o-arrow-up class="w-3 h-3 mr-1" />
                                                Above
                                            </span>
                                        @elseif($costing['price_status'] === 'on_target')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                <x-heroicon-o-check class="w-3 h-3 mr-1" />
                                                On Target
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                <x-heroicon-o-arrow-down class="w-3 h-3 mr-1" />
                                                Below
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                {{-- Expandable Ingredients Row --}}
                                @if(count($costing['ingredients']) > 0)
                                    <tr x-show="expanded" x-collapse class="bg-gray-50 dark:bg-gray-900/50">
                                        <td colspan="10" class="px-4 py-3">
                                            <div class="ml-8 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                                    <x-heroicon-o-beaker class="w-4 h-4" />
                                                    Ingredient Breakdown for {{ $costing['product_name'] }}
                                                </h4>
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="text-xs text-gray-500 dark:text-gray-400">
                                                            <th class="text-left pb-2">Ingredient</th>
                                                            <th class="text-right pb-2">Quantity</th>
                                                            <th class="text-right pb-2">Unit Cost</th>
                                                            <th class="text-right pb-2">Item Cost</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                                        @foreach($costing['ingredients'] as $ingredient)
                                                            <tr>
                                                                <td class="py-2 text-gray-900 dark:text-white">{{ $ingredient['ingredient_name'] }}</td>
                                                                <td class="py-2 text-right text-gray-600 dark:text-gray-400">{{ number_format($ingredient['quantity_required'], 2) }} {{ $ingredient['unit_type'] }}</td>
                                                                <td class="py-2 text-right text-gray-600 dark:text-gray-400">{{ $currency }} {{ number_format($ingredient['unit_cost'], 4) }}</td>
                                                                <td class="py-2 text-right font-medium text-gray-900 dark:text-white">{{ $currency }} {{ number_format($ingredient['item_cost'], 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot class="border-t border-gray-200 dark:border-gray-600">
                                                        <tr>
                                                            <td colspan="3" class="py-2 text-right font-semibold text-gray-700 dark:text-gray-300">Subtotal (Ingredient Cost):</td>
                                                            <td class="py-2 text-right font-bold text-gray-900 dark:text-white">{{ $currency }} {{ number_format($costing['ingredient_cost'], 2) }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>

                                                {{-- Costing Summary --}}
                                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 grid grid-cols-2 md:grid-cols-4 gap-4">
                                                    <div>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">Suggested Price</p>
                                                        <p class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ $currency }} {{ number_format($costing['suggested_price'], 2) }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">Current Menu Price</p>
                                                        <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $currency }} {{ number_format($costing['menu_price'], 2) }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">Actual Markup</p>
                                                        <p class="text-lg font-bold {{ $costing['actual_markup'] >= $markupPercentage ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ number_format($costing['actual_markup'], 1) }}%</p>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">Price Difference</p>
                                                        @php $priceDiff = $costing['menu_price'] - $costing['suggested_price']; @endphp
                                                        <p class="text-lg font-bold {{ $priceDiff >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                            {{ $priceDiff >= 0 ? '+' : '' }}{{ $currency }} {{ number_format($priceDiff, 2) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            {{-- Empty State --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-amber-100 dark:bg-amber-900 mb-4">
                    <x-heroicon-o-calculator class="h-8 w-8 text-amber-600 dark:text-amber-400" />
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No products found</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                    @if($selectedCategoryId)
                        No active products found in this category. Try selecting a different category or "All Categories".
                    @else
                        No active products with ingredient data found. Make sure products have ingredients assigned with unit costs set.
                    @endif
                </p>
                <button
                    wire:click="filterByCategory(null)"
                    type="button"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 dark:bg-amber-500 dark:hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors"
                >
                    <x-heroicon-o-arrow-path class="w-4 h-4 mr-2" />
                    Show All Categories
                </button>
            </div>
        @endif

        {{-- Legend / Help Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Legend & Formulas</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs text-gray-600 dark:text-gray-400">
                <div>
                    <p class="font-medium text-gray-700 dark:text-gray-300">Ingredient Cost</p>
                    <p>Sum of (Quantity × Unit Cost) for all ingredients</p>
                </div>
                <div>
                    <p class="font-medium text-gray-700 dark:text-gray-300">Labor Cost</p>
                    <p>Ingredient Cost × {{ number_format($laborPercentage, 0) }}%</p>
                </div>
                <div>
                    <p class="font-medium text-gray-700 dark:text-gray-300">Total Cost</p>
                    <p>Ingredient Cost + Labor Cost</p>
                </div>
                <div>
                    <p class="font-medium text-gray-700 dark:text-gray-300">Gross Profit</p>
                    <p>Menu Price - Total Cost</p>
                </div>
                <div>
                    <p class="font-medium text-gray-700 dark:text-gray-300">Profit Margin</p>
                    <p>(Gross Profit / Menu Price) × 100</p>
                </div>
                <div>
                    <p class="font-medium text-gray-700 dark:text-gray-300">Food Cost %</p>
                    <p>(Total Cost / Menu Price) × 100</p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        <x-heroicon-o-arrow-up class="w-3 h-3 mr-1" /> Above
                    </span>
                    <span>Menu price is 10%+ above suggested</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        <x-heroicon-o-check class="w-3 h-3 mr-1" /> On Target
                    </span>
                    <span>Menu price is within ±10% of suggested</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        <x-heroicon-o-arrow-down class="w-3 h-3 mr-1" /> Below
                    </span>
                    <span>Menu price is 10%+ below suggested</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
