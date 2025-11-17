<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Widgets;

use App\Filament\Concerns\CurrencyAware;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class PaymentMethodsOverview extends StatsOverviewWidget
{
    use CurrencyAware;

    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $cashOrders = Order::where('payment_method', 'cash')->get();
        $cardOrders = Order::where('payment_method', 'card')->get();
        $digitalOrders = Order::where('payment_method', 'digital')->get();
        $bankTransferOrders = Order::where('payment_method', 'bank_transfer')->get();

        return [
            Stat::make('Cash Payments', $this->formatMoney($cashOrders->sum('total')))
                ->description($cashOrders->count().' orders paid with cash')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('warning')
                ->chart($this->getChartData('cash')),

            Stat::make('Card Payments', $this->formatMoney($cardOrders->sum('total')))
                ->description($cardOrders->count().' orders paid with card')
                ->descriptionIcon('heroicon-o-credit-card')
                ->color('success')
                ->chart($this->getChartData('card')),

            Stat::make('Digital Wallet', $this->formatMoney($digitalOrders->sum('total')))
                ->description($digitalOrders->count().' orders paid digitally')
                ->descriptionIcon('heroicon-o-device-phone-mobile')
                ->color('primary')
                ->chart($this->getChartData('digital')),

            Stat::make('Bank Transfer', $this->formatMoney($bankTransferOrders->sum('total')))
                ->description($bankTransferOrders->count().' orders via bank transfer')
                ->descriptionIcon('heroicon-o-building-library')
                ->color('info')
                ->chart($this->getChartData('bank_transfer')),
        ];
    }

    private function formatMoney(float $amount): string
    {
        $currency = app(\App\Services\GeneralSettingsService::class)->getCurrency();

        return $currency.' '.number_format($amount, 2);
    }

    private function getChartData(string $method): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $total = Order::where('payment_method', $method)
                ->whereDate('created_at', $date)
                ->sum('total');
            $data[] = (float) $total;
        }

        return $data;
    }
}
