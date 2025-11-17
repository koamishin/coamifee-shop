<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Widgets;

use App\Filament\Concerns\CurrencyAware;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class CashOrdersOverview extends StatsOverviewWidget
{
    use CurrencyAware;

    protected static ?int $sort = 6;

    protected function getStats(): array
    {
        // Today's cash orders
        $todayCash = Order::where('payment_method', 'cash')
            ->whereDate('created_at', today())
            ->get();

        // This week's cash orders
        $weekCash = Order::where('payment_method', 'cash')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->get();

        // This month's cash orders
        $monthCash = Order::where('payment_method', 'cash')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->get();

        // All time cash orders
        $totalCash = Order::where('payment_method', 'cash')->get();
        $totalOrders = Order::count();
        $cashPercentage = $totalOrders > 0 ? round(($totalCash->count() / $totalOrders) * 100, 1) : 0;

        return [
            Stat::make('Today\'s Cash', $this->formatMoney($todayCash->sum('total')))
                ->description($todayCash->count().' cash orders today')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('warning')
                ->chart($this->getTodayChart()),

            Stat::make('This Week\'s Cash', $this->formatMoney($weekCash->sum('total')))
                ->description($weekCash->count().' cash orders this week')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info')
                ->chart($this->getWeekChart()),

            Stat::make('This Month\'s Cash', $this->formatMoney($monthCash->sum('total')))
                ->description($monthCash->count().' cash orders this month')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('primary')
                ->chart($this->getMonthChart()),

            Stat::make('Total Cash Revenue', $this->formatMoney($totalCash->sum('total')))
                ->description($totalCash->count().' total cash orders ('.$cashPercentage.'%)')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success')
                ->chart($this->getChartData()),
        ];
    }

    private function formatMoney(float $amount): string
    {
        $currency = app(\App\Services\GeneralSettingsService::class)->getCurrency();

        return $currency.' '.number_format($amount, 2);
    }

    private function getTodayChart(): array
    {
        $data = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $total = Order::where('payment_method', 'cash')
                ->whereDate('created_at', today())
                ->whereRaw('CAST(strftime("%H", created_at) AS INTEGER) = ?', [$hour])
                ->sum('total');
            $data[] = (float) $total;
        }

        return array_slice($data, 0, 12); // Show last 12 hours for better visualization
    }

    private function getWeekChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $total = Order::where('payment_method', 'cash')
                ->whereDate('created_at', $date)
                ->sum('total');
            $data[] = (float) $total;
        }

        return $data;
    }

    private function getMonthChart(): array
    {
        $data = [];
        $daysInMonth = now()->daysInMonth;
        $today = now()->day;

        // Show last 7 days of the month for visualization
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $total = Order::where('payment_method', 'cash')
                ->whereDate('created_at', $date)
                ->sum('total');
            $data[] = (float) $total;
        }

        return $data;
    }

    private function getChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $total = Order::where('payment_method', 'cash')
                ->whereDate('created_at', $date)
                ->sum('total');
            $data[] = (float) $total;
        }

        return $data;
    }
}
