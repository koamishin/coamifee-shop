<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

final class OrderStatusWidget extends ChartWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $orderStats = Order::query()->selectRaw('
                status,
                COUNT(*) as count
            ')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('status')
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($orderStats as $stat) {
            $labels[] = ucfirst(str_replace('_', ' ', $stat->status));
            $data[] = $stat->getAttribute('count');

            // Assign colors based on status
            $colors[] = match ($stat->status) {
                'completed' => 'rgba(34, 197, 94, 0.8)',
                'pending' => 'rgba(245, 158, 11, 0.8)',
                'confirmed' => 'rgba(59, 130, 246, 0.8)',
                'preparing' => 'rgba(168, 85, 247, 0.8)',
                'ready' => 'rgba(236, 72, 153, 0.8)',
                default => 'rgba(239, 68, 68, 0.8)',
            };
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => [
                        'padding' => 20,
                        'usePointStyle' => true,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'enabled' => true,
                    'callbacks' => [
                        'label' => 'function(context) {
                            let label = context.label || "";
                            let value = context.raw || 0;
                            return label + ": " + value + " orders";
                        }',
                    ],
                ],
            ],
        ];
    }
}
