<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Widgets\ChartWidget;

final class TopProductsWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $topProducts = OrderItem::query()->selectRaw('
                product_id,
                SUM(quantity) as total_quantity,
                SUM(quantity * price) as total_revenue,
                COUNT(DISTINCT order_id) as orders_count
            ')
            ->whereHas('order', fn ($query) => $query->where('created_at', '>=', now()->subDays(30)))
            ->groupBy('product_id')
            ->orderBy('total_quantity', 'desc')
            ->limit(10)
            ->with('product')
            ->get();

        $labels = [];
        $quantities = [];
        $revenues = [];

        foreach ($topProducts as $item) {
            $labels[] = $item->product->getAttribute('name');
            $quantities[] = $item->total_quantity;
            $revenues[] = (float) $item->total_revenue;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Units Sold',
                    'data' => $quantities,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Units Sold',
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 45,
                        'autoSkip' => true,
                        'maxTicksLimit' => 10,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
        ];
    }
}
