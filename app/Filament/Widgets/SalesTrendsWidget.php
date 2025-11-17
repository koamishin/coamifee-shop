<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Date;

final class SalesTrendsWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public function getHeading(): string
    {
        return 'Sales Trends (Last 7 Days)';
    }

    protected function getData(): array
    {
        $data = Order::query()->selectRaw('date(created_at) as date, COUNT(*) as order_count, SUM(total) as sales')
            ->where('created_at', '>=', Date::now()->subDays(6)->startOfDay())
            ->where('created_at', '<=', Date::now()->endOfDay())
            ->groupByRaw('date(created_at)')
            ->orderBy('date', 'asc')
            ->get();

        $labels = [];
        $ordersData = [];
        $salesData = [];

        // Fill in missing days with zeros
        $startDate = Date::now()->subDays(6)->startOfDay();
        $endDate = Date::now()->endOfDay();
        $currentDate = $startDate->copy();

        $dataByDate = $data->keyBy('date');

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayData = $dataByDate->get($dateStr);

            $labels[] = $currentDate->format('M j');
            $ordersData[] = $dayData ? (int) $dayData->order_count : 0;
            $salesData[] = $dayData ? (float) $dayData->sales : 0;

            $currentDate->addDay();
        }

        $currency = app(\App\Services\GeneralSettingsService::class)->getCurrency();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $ordersData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => "Sales ({$currency})",
                    'data' => $salesData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
