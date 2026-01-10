<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Pages\Dashboard;
use App\Models\IngredientInventory;
use App\Models\Order;
use App\Models\Product;
use App\Services\GeneralSettingsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class CoffeeShopOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $filters = $this->pageFilters ?? [];
        $dateRange = Dashboard::getDateRangeFromFilters($filters);
        $periodLabel = Dashboard::getPeriodLabel($filters);

        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $currency = resolve(GeneralSettingsService::class)->getCurrency();

        // Filtered sales (based on selected period)
        $filteredSales = Order::query()
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->where('payment_status', 'paid')
            ->sum('total');

        // Filtered orders count
        $filteredOrders = Order::query()
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->count();

        // Filtered units sold
        $filteredUnitsSold = $this->getFilteredUnitsSold($startDate, $endDate);

        // All-time stats (not filtered)
        $totalRevenue = $this->getTotalRevenue();

        return [
            Stat::make("{$periodLabel} Orders", number_format($filteredOrders))
                ->description("Orders for {$periodLabel}")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($this->getOrdersChart($startDate, $endDate)),

            Stat::make("{$periodLabel} Sales", number_format((float) $filteredSales, 2))
                ->description("{$currency} ".number_format((float) $filteredSales, 2)." for {$periodLabel}")
                ->descriptionIcon('mdi-currency-php')
                ->color('primary')
                ->chart($this->getSalesChart($startDate, $endDate)),

            Stat::make("{$periodLabel} Units Sold", number_format($filteredUnitsSold))
                ->description("Units sold for {$periodLabel}")
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),

            Stat::make('Total Revenue', "{$currency} ".number_format($totalRevenue, 2))
                ->description('All-time total revenue')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Active Products', Product::query()->count())
                ->description('Total products available')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),

            Stat::make('Low Stock Alerts', $this->getLowStockCount())
                ->description('Ingredients needing restock')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($this->getLowStockCount() > 0 ? 'danger' : 'success'),
        ];
    }

    private function getLowStockCount(): int
    {
        return IngredientInventory::query()->whereHas('ingredient', fn ($query) => $query->whereNotNull('id'))
            ->whereColumn('current_stock', '<=', 'min_stock_level')
            ->count();
    }

    private function getTotalRevenue(): float
    {
        return (float) Order::query()
            ->where('payment_status', 'paid')
            ->where('status', 'completed')
            ->sum('total');
    }

    /**
     * @param  \Illuminate\Support\Carbon  $startDate
     * @param  \Illuminate\Support\Carbon  $endDate
     */
    private function getFilteredUnitsSold($startDate, $endDate): int
    {
        return (int) Order::query()
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.created_at', '<=', $endDate)
            ->where('payment_status', 'paid')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->sum('order_items.quantity');
    }

    /**
     * Get chart data for orders within the date range
     *
     * @param  \Illuminate\Support\Carbon  $startDate
     * @param  \Illuminate\Support\Carbon  $endDate
     */
    private function getOrdersChart($startDate, $endDate): array
    {
        $days = $startDate->diffInDays($endDate);
        $dataPoints = min($days + 1, 7); // Max 7 data points

        $chart = [];
        $interval = max(1, (int) ceil($days / $dataPoints));

        for ($i = 0; $i < $dataPoints; $i++) {
            $dayStart = $startDate->copy()->addDays($i * $interval)->startOfDay();
            $dayEnd = $dayStart->copy()->endOfDay();

            if ($dayStart->gt($endDate)) {
                break;
            }

            $count = Order::query()
                ->where('created_at', '>=', $dayStart)
                ->where('created_at', '<=', min($dayEnd, $endDate))
                ->count();

            $chart[] = $count;
        }

        return $chart === [] ? [0] : $chart;
    }

    /**
     * Get chart data for sales within the date range
     *
     * @param  \Illuminate\Support\Carbon  $startDate
     * @param  \Illuminate\Support\Carbon  $endDate
     */
    private function getSalesChart($startDate, $endDate): array
    {
        $days = $startDate->diffInDays($endDate);
        $dataPoints = min($days + 1, 7); // Max 7 data points

        $chart = [];
        $interval = max(1, (int) ceil($days / $dataPoints));

        for ($i = 0; $i < $dataPoints; $i++) {
            $dayStart = $startDate->copy()->addDays($i * $interval)->startOfDay();
            $dayEnd = $dayStart->copy()->endOfDay();

            if ($dayStart->gt($endDate)) {
                break;
            }

            $sales = Order::query()
                ->where('created_at', '>=', $dayStart)
                ->where('created_at', '<=', min($dayEnd, $endDate))
                ->where('payment_status', 'paid')
                ->sum('total');

            $chart[] = (float) $sales;
        }

        return $chart === [] ? [0] : $chart;
    }
}
