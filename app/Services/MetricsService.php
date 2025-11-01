<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductMetric;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class MetricsService
{
    public function recordProductMetrics(int $productId, ?Carbon $date = null): void
    {
        $date ??= now();

        $product = Product::query()->find($productId);
        if (! $product) {
            return;
        }

        $orders = Order::query()->whereDate('created_at', $date)
            ->whereHas('items', function ($query) use ($productId): void {
                $query->where('product_id', $productId);
            })
            ->get();

        $totalOrders = 0;
        $totalRevenue = 0;

        foreach ($orders as $order) {
            $orderItems = $order->items()->where('product_id', $productId)->get();

            foreach ($orderItems as $item) {
                assert($item instanceof \App\Models\OrderItem);
                $totalOrders += $item->quantity;
                $totalRevenue += $item->price * $item->quantity;
            }
        }

        ProductMetric::query()->updateOrCreate([
            'product_id' => $productId,
            'metric_date' => $date->toDateString(),
            'period_type' => 'daily',
        ], [
            'orders_count' => $totalOrders,
            'total_revenue' => $totalRevenue,
        ]);

        $this->updateWeeklyMetrics($productId, $date);
        $this->updateMonthlyMetrics($productId, $date);
    }

    public function getProductMetrics(int $productId, string $period = 'daily', int $days = 30): Collection
    {
        $query = ProductMetric::query()->where('product_id', $productId)
            ->where('period_type', $period);

        if ($period === 'daily') {
            $query->where('metric_date', '>=', now()->subDays($days))
                ->latest('metric_date');
        } elseif ($period === 'weekly') {
            $query->where('metric_date', '>=', now()->subWeeks($days))
                ->latest('metric_date');
        } elseif ($period === 'monthly') {
            $query->where('metric_date', '>=', now()->subMonths($days))
                ->latest('metric_date');
        }

        return $query->get();
    }

    public function getTopProducts(int $limit = 10, string $period = 'daily', int $days = 7): Collection
    {
        $startDate = match ($period) {
            'daily' => now()->subDays($days),
            'weekly' => now()->subWeeks($days),
            'monthly' => now()->subMonths($days),
            default => now()->subDays(7),
        };

        return ProductMetric::with('product')
            ->where('period_type', $period)
            ->where('metric_date', '>=', $startDate)
            ->selectRaw('product_id, SUM(orders_count) as total_orders, SUM(total_revenue) as total_revenue')
            ->groupBy('product_id')
            ->orderBy('total_orders', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getRevenueReport(Carbon $startDate, Carbon $endDate): array
    {
        $metrics = ProductMetric::with('product')
            ->where('period_type', 'daily')
            ->whereBetween('metric_date', [$startDate, $endDate])
            ->get();

        $totalRevenue = $metrics->sum('total_revenue');
        $totalOrders = $metrics->sum('orders_count');
        $productBreakdown = $metrics->groupBy('product.name')
            ->map(fn ($productMetrics): array => [
                'orders' => $productMetrics->sum('orders_count'),
                'revenue' => $productMetrics->sum('total_revenue'),
            ]);

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'product_breakdown' => $productBreakdown,
            'period' => $startDate->format('M j, Y').' - '.$endDate->format('M j, Y'),
        ];
    }

    private function updateWeeklyMetrics(int $productId, Carbon $date): void
    {
        $weekStart = $date->startOfWeek()->toDateString();
        $weekEnd = $date->endOfWeek()->toDateString();

        $weeklyMetrics = ProductMetric::query()->where('product_id', $productId)
            ->where('period_type', 'daily')
            ->whereBetween('metric_date', [$weekStart, $weekEnd])
            ->get();

        $totalOrders = $weeklyMetrics->sum('orders_count');
        $totalRevenue = $weeklyMetrics->sum('total_revenue');

        ProductMetric::query()->updateOrCreate([
            'product_id' => $productId,
            'metric_date' => $weekStart,
            'period_type' => 'weekly',
        ], [
            'orders_count' => $totalOrders,
            'total_revenue' => $totalRevenue,
        ]);
    }

    private function updateMonthlyMetrics(int $productId, Carbon $date): void
    {
        $monthStart = $date->startOfMonth()->toDateString();
        $monthEnd = $date->endOfMonth()->toDateString();

        $monthlyMetrics = ProductMetric::query()->where('product_id', $productId)
            ->where('period_type', 'daily')
            ->whereBetween('metric_date', [$monthStart, $monthEnd])
            ->get();

        $totalOrders = $monthlyMetrics->sum('orders_count');
        $totalRevenue = $monthlyMetrics->sum('total_revenue');

        ProductMetric::query()->updateOrCreate([
            'product_id' => $productId,
            'metric_date' => $monthStart,
            'period_type' => 'monthly',
        ], [
            'orders_count' => $totalOrders,
            'total_revenue' => $totalRevenue,
        ]);
    }
}
