<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductMetrics\Widgets;

use App\Filament\Concerns\CurrencyAware;
use App\Models\ProductMetric;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

final class TopPerformingProducts extends StatsOverviewWidget
{
    use CurrencyAware;

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Get top 3 products by sales
        $topProducts = ProductMetric::select([
            'product_id',
            DB::raw('SUM(total_revenue) as total_sales'),
            DB::raw('SUM(orders_count) as total_ord'),
        ])
            ->groupBy('product_id')
            ->orderBy('total_sales', 'desc')
            ->limit(3)
            ->with('product')
            ->get();

        $stats = [];

        foreach ($topProducts as $index => $metric) {
            $position = $index + 1;
            $medal = match ($position) {
                1 => '🥇',
                2 => '🥈',
                3 => '🥉',
                default => '🏆',
            };

            $color = match ($position) {
                1 => 'success',
                2 => 'warning',
                3 => 'info',
                default => 'gray',
            };

            $productName = $metric->product?->name ?? 'Unknown Product';
            $sales = (float) $metric->total_sales;
            $orders = (int) $metric->total_ord;
            $aov = $orders > 0 ? $sales / $orders : 0;

            $stats[] = Stat::make("{$medal} #{$position} - {$productName}", $this->formatMoney($sales))
                ->description("{$orders} orders • AOV: ".$this->formatMoney($aov))
                ->descriptionIcon('heroicon-o-trophy')
                ->color($color)
                ->chart($this->getProductChart((int) $metric->product_id));
        }

        // Fill remaining slots if less than 3 products
        while (count($stats) < 3) {
            $position = count($stats) + 1;
            $medal = match ($position) {
                1 => '🥇',
                2 => '🥈',
                3 => '🥉',
                default => '🏆',
            };

            $stats[] = Stat::make("{$medal} #{$position} - No Data", $this->formatMoney(0))
                ->description('No metrics available')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('gray');
        }

        return $stats;
    }

    private function formatMoney(float $amount): string
    {
        $currency = app(\App\Services\GeneralSettingsService::class)->getCurrency();

        return $currency.' '.number_format($amount, 2);
    }

    private function getProductChart(int $productId): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $total = ProductMetric::where('product_id', $productId)
                ->where('period_type', 'daily')
                ->whereDate('metric_date', $date)
                ->sum('total_revenue');
            $data[] = (float) $total;
        }

        return $data;
    }
}
