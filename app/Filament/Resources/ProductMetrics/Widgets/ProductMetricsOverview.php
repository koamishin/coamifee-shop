<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductMetrics\Widgets;

use App\Filament\Concerns\CurrencyAware;
use App\Models\ProductMetric;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class ProductMetricsOverview extends StatsOverviewWidget
{
    use CurrencyAware;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $dailyMetrics = ProductMetric::where('period_type', 'daily')->get();
        $weeklyMetrics = ProductMetric::where('period_type', 'weekly')->get();
        $monthlyMetrics = ProductMetric::where('period_type', 'monthly')->get();

        $totalSales = ProductMetric::sum('total_revenue');
        $totalOrders = ProductMetric::sum('orders_count');
        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        return [
            Stat::make('Total Sales Tracked', $this->formatMoney((float) $totalSales))
                ->description('All product metrics combined')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success')
                ->chart($this->getSalesChart()),

            Stat::make('Total Orders Tracked', number_format((int) $totalOrders))
                ->description('Across all products and periods')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('primary')
                ->chart($this->getOrdersChart()),

            Stat::make('Average Order Value', $this->formatMoney($avgOrderValue))
                ->description('Based on all tracked metrics')
                ->descriptionIcon('heroicon-o-calculator')
                ->color('info'),

            Stat::make('Daily Metrics', number_format($dailyMetrics->count()))
                ->description($this->formatMoney($dailyMetrics->sum('total_revenue')).' in sales')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('warning'),

            Stat::make('Weekly Metrics', number_format($weeklyMetrics->count()))
                ->description($this->formatMoney($weeklyMetrics->sum('total_revenue')).' in sales')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Monthly Metrics', number_format($monthlyMetrics->count()))
                ->description($this->formatMoney($monthlyMetrics->sum('total_revenue')).' in sales')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('success'),
        ];
    }

    private function formatMoney(float $amount): string
    {
        $currency = app(\App\Services\GeneralSettingsService::class)->getCurrency();

        return $currency.' '.number_format($amount, 2);
    }

    private function getSalesChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $total = ProductMetric::where('period_type', 'daily')
                ->whereDate('metric_date', $date)
                ->sum('total_revenue');
            $data[] = (float) $total;
        }

        return $data;
    }

    private function getOrdersChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $total = ProductMetric::where('period_type', 'daily')
                ->whereDate('metric_date', $date)
                ->sum('orders_count');
            $data[] = (int) $total;
        }

        return $data;
    }
}
