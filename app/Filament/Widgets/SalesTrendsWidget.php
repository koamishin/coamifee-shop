<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Pages\Dashboard;
use App\Models\Order;
use App\Services\GeneralSettingsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

final class SalesTrendsWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public function getHeading(): string
    {
        $filters = $this->pageFilters ?? [];
        $periodLabel = Dashboard::getPeriodLabel($filters);

        return "Sales Trends ({$periodLabel})";
    }

    protected function getData(): array
    {
        $filters = $this->pageFilters ?? [];
        $dateRange = Dashboard::getDateRangeFromFilters($filters);

        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $data = Order::query()->selectRaw('date(created_at) as date, COUNT(*) as order_count, SUM(total) as sales')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->groupByRaw('date(created_at)')
            ->orderBy('date', 'asc')
            ->get();

        $labels = [];
        $ordersData = [];
        $salesData = [];

        // Fill in all days with data (including zeros for days without orders)
        $currentDate = $startDate->copy();
        $dataByDate = $data->keyBy('date');

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            /** @var (Order&object{order_count: int, sales: float, date: string})|null $dayData */
            $dayData = $dataByDate->get($dateStr);

            $labels[] = $currentDate->format('M j');
            $ordersData[] = $dayData ? (int) $dayData->order_count : 0;
            $salesData[] = $dayData ? (float) $dayData->sales : 0;

            $currentDate->addDay();
        }

        $currency = resolve(GeneralSettingsService::class)->getCurrency();

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
