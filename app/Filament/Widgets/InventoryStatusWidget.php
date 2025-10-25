<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Ingredient;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

final class InventoryStatusWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public function getHeading(): string
    {
        return 'Ingredient Stock Status';
    }

    protected function getData(): array
    {
        // Get trackable ingredients with inventory data
        $ingredients = Ingredient::with('inventory')
            ->where('is_trackable', true)
            ->get();

        $labels = [];
        $stockData = [];
        $minStockData = [];
        $colors = [];

        foreach ($ingredients as $ingredient) {
            if (! $ingredient->inventory) {
                continue;
            }

            $labels[] = Str::limit($ingredient->name, 20);
            $stockData[] = (float) $ingredient->inventory->current_stock;
            $minStockData[] = (float) ($ingredient->inventory->min_stock_level ?? 0);

            // Determine color based on stock level
            $currentStock = $ingredient->inventory->current_stock;
            $minStock = $ingredient->inventory->min_stock_level ?? 0;
            $maxStock = $ingredient->inventory->max_stock_level ?? PHP_FLOAT_MAX;

            if ($currentStock <= $minStock) {
                $colors[] = 'rgba(239, 68, 68, 0.8)'; // red
            } elseif ($currentStock >= $maxStock) {
                $colors[] = 'rgba(245, 158, 11, 0.8)'; // orange
            } else {
                $colors[] = 'rgba(34, 197, 94, 0.8)'; // green
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Current Stock',
                    'data' => $stockData,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Min Stock Level',
                    'data' => $minStockData,
                    'type' => 'line',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderWidth' => 2,
                    'borderDash' => [5, 5],
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
                        'text' => 'Stock Level',
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
