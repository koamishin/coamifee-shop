<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Date;

final class FinancialSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        Date::today();
        Date::now()->startOfWeek();
        $thisMonth = Date::now()->startOfMonth();
        $lastMonth = Date::now()->subMonth()->startOfMonth();

        return [
            Stat::make('This Month Revenue',
                Order::query()->whereDate('created_at', '>=', $thisMonth)->sum('total'))
                ->description('$'.number_format(
                    Order::query()->whereDate('created_at', '>=', $thisMonth)->sum('total'), 2))
                ->description('Revenue from '.$thisMonth->format('F j'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($this->getMonthlyChart()),

            Stat::make('Last Month Revenue',
                Order::query()->whereDate('created_at', '>=', $lastMonth)
                    ->whereDate('created_at', '<', $thisMonth)
                    ->sum('total'))
                ->description('$'.number_format(
                    Order::query()->whereDate('created_at', '>=', $lastMonth)
                        ->whereDate('created_at', '<', $thisMonth)
                        ->sum('total'), 2))
                ->description($lastMonth->format('F'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('Average Order Value', $this->getAverageOrderValue())
                ->description('$'.number_format($this->getAverageOrderValue(), 2))
                ->description('This month average')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('primary'),

            Stat::make('Total Orders (Month)', Order::query()->whereDate('created_at', '>=', $thisMonth)->count())
                ->description('Orders this month')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning')
                ->chart($this->getOrdersChart()),
        ];
    }

    private function getAverageOrderValue(): float
    {
        $thisMonth = Date::now()->startOfMonth();
        $orders = Order::query()->whereDate('created_at', '>=', $thisMonth)
            ->where('total', '>', 0)
            ->get();

        if ($orders->isEmpty()) {
            return 0;
        }

        return $orders->sum('total') / $orders->count();
    }

    private function getMonthlyChart(): array
    {
        // Get last 30 days of revenue
        $data = Order::query()->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->where('created_at', '>=', now()->subDays(29))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $chart = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayData = $data->firstWhere('date', $date);
            $chart[] = $dayData ? (float) $dayData->revenue : 0;
        }

        return $chart;
    }

    private function getOrdersChart(): array
    {
        // Get last 30 days of orders
        $data = Order::query()->selectRaw('DATE(created_at) as date, COUNT(*) as orders')
            ->where('created_at', '>=', now()->subDays(29))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $chart = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayData = $data->firstWhere('date', $date);
            $chart[] = $dayData ? (int) $dayData->orders : 0;
        }

        return $chart;
    }
}
