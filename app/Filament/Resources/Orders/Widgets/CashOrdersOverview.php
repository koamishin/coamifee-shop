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
        $orders = Order::where('payment_method', 'cash')
            ->where('status', 'completed')
            ->where('payment_status', '!=', 'refunded')
            ->where('payment_status', '!=', 'refund_partial')
            ->get();

        return [
            Stat::make('Cash (Completed)', $this->formatMoney($orders->sum('total')))
                ->description($orders->count().' completed orders paid in cash')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success')
                ->chart($this->getChartData()),
        ];
    }

    private function formatMoney(float $amount): string
    {
        $currency = app(\App\Services\GeneralSettingsService::class)->getCurrency();

        return $currency.' '.number_format($amount, 2);
    }

    private function getChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $total = Order::where('payment_method', 'cash')
                ->where('status', 'completed')
                ->where('payment_status', '!=', 'refunded')
                ->where('payment_status', '!=', 'refund_partial')
                ->whereDate('created_at', $date)
                ->sum('total');
            $data[] = (float) $total;
        }

        return $data;
    }
}
