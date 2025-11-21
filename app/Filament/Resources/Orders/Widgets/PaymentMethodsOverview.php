<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Widgets;

use App\Filament\Concerns\CurrencyAware;
use App\Models\Order;
use App\Services\GeneralSettingsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class PaymentMethodsOverview extends StatsOverviewWidget
{
    use CurrencyAware;

    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $settingsService = app(GeneralSettingsService::class);
        $enabledPaymentMethods = $settingsService->getEnabledPaymentMethods();
        $stats = [];

        foreach ($enabledPaymentMethods as $method => $config) {
            $orders = Order::where('payment_method', $method)->get();

            $stats[] = Stat::make($config['name'], $this->formatMoney($orders->sum('total')))
                ->description($orders->count().' orders paid with '.$config['name'])
                ->descriptionIcon($config['icon'])
                ->color($config['color'])
                ->chart($this->getChartData($method));
        }

        return $stats;
    }

    private function formatMoney(float $amount): string
    {
        $currency = app(GeneralSettingsService::class)->getCurrency();

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
