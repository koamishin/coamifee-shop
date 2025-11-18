<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Widgets;

use App\Filament\Concerns\CurrencyAware;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class DeliveryOverview extends StatsOverviewWidget
{
    use CurrencyAware;

    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $deliveryOrders = Order::where('order_type', 'delivery')->get();
        $dineInOrders = Order::whereIn('order_type', ['dine_in', 'dine-in'])->get();
        $takeawayOrders = Order::where('order_type', 'takeaway')->get();

        $totalOrders = Order::count();
        $deliveryPercentage = $totalOrders > 0 ? round(($deliveryOrders->count() / $totalOrders) * 100, 1) : 0;
        $dineInPercentage = $totalOrders > 0 ? round(($dineInOrders->count() / $totalOrders) * 100, 1) : 0;
        $takeawayPercentage = $totalOrders > 0 ? round(($takeawayOrders->count() / $totalOrders) * 100, 1) : 0;

        return [
            Stat::make('Delivery Orders', $this->formatMoney($deliveryOrders->sum('total')))
                ->description($deliveryOrders->count().' orders ('.$deliveryPercentage.'% of total)')
                ->descriptionIcon('heroicon-o-truck')
                ->color('warning')
                ->chart($this->getChartData('delivery')),

            Stat::make('Dine-In Orders', $this->formatMoney($dineInOrders->sum('total')))
                ->description($dineInOrders->count().' orders ('.$dineInPercentage.'% of total)')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('success')
                ->chart($this->getChartData('dine_in')),

            Stat::make('Takeaway Orders', $this->formatMoney($takeawayOrders->sum('total')))
                ->description($takeawayOrders->count().' orders ('.$takeawayPercentage.'% of total)')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('info')
                ->chart($this->getChartData('takeaway')),
        ];
    }

    private function formatMoney(float $amount): string
    {
        $currency = app(\App\Services\GeneralSettingsService::class)->getCurrency();

        return $currency.' '.number_format($amount, 2);
    }

    private function getChartData(string $type): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $query = Order::whereDate('created_at', $date);

            if ($type === 'dine_in') {
                $total = $query->whereIn('order_type', ['dine_in', 'dine-in'])->sum('total');
            } else {
                $total = $query->where('order_type', $type)->sum('total');
            }

            $data[] = (float) $total;
        }

        return $data;
    }
}
