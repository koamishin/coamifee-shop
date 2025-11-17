<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductMetric;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class GenerateProductMetrics extends Command
{
    protected $signature = 'metrics:generate-product
                            {--period=daily : Period type (daily, weekly, monthly)}
                            {--days=30 : Number of days to generate metrics for}
                            {--fresh : Delete existing metrics before generating}';

    protected $description = 'Generate product metrics from order data';

    public function handle(): int
    {
        $period = $this->option('period');
        $days = (int) $this->option('days');
        $fresh = $this->option('fresh');

        if ($fresh) {
            $this->info('Deleting existing product metrics...');
            ProductMetric::query()->delete();
        }

        $this->info("Generating {$period} product metrics for the last {$days} days...");

        $products = Product::all();
        $generatedCount = 0;

        foreach ($products as $product) {
            $this->info("Processing product: {$product->name}");

            $metrics = $this->generateMetricsForProduct($product->id, $period, $days);

            foreach ($metrics as $metric) {
                ProductMetric::updateOrCreate(
                    [
                        'product_id' => $metric['product_id'],
                        'metric_date' => $metric['metric_date'],
                        'period_type' => $metric['period_type'],
                    ],
                    [
                        'orders_count' => $metric['orders_count'],
                        'total_revenue' => $metric['total_revenue'],
                    ]
                );

                $generatedCount++;
            }
        }

        $this->info("✅ Successfully generated {$generatedCount} product metrics!");

        return self::SUCCESS;
    }

    private function generateMetricsForProduct(int $productId, string $period, int $days): array
    {
        $metrics = [];
        $now = now();

        for ($i = 0; $i < $days; $i++) {
            $date = $now->copy()->subDays($i);

            [$startDate, $endDate] = match ($period) {
                'weekly' => [
                    $date->copy()->startOfWeek(),
                    $date->copy()->endOfWeek(),
                ],
                'monthly' => [
                    $date->copy()->startOfMonth(),
                    $date->copy()->endOfMonth(),
                ],
                default => [
                    $date->copy()->startOfDay(),
                    $date->copy()->endOfDay(),
                ],
            };

            // Skip if we already processed this period
            $metricDate = $startDate->toDateString();
            if (isset($metrics[$metricDate])) {
                continue;
            }

            // Get metrics from order_items using raw query for SQLite compatibility
            $result = DB::table('order_items')
                ->select([
                    DB::raw('COUNT(DISTINCT order_items.order_id) as orders_count'),
                    DB::raw('SUM(CAST(order_items.quantity AS REAL) * CAST(order_items.price AS REAL)) as total_revenue'),
                ])
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $productId)
                ->whereBetween('orders.created_at', [$startDate, $endDate])
                ->first();

            $ordersCount = (int) ($result->orders_count ?? 0);
            $totalRevenue = (float) ($result->total_revenue ?? 0);

            // Only create metrics if there's activity
            if ($ordersCount > 0 || $totalRevenue > 0) {
                $metrics[$metricDate] = [
                    'product_id' => $productId,
                    'metric_date' => $metricDate,
                    'orders_count' => $ordersCount,
                    'total_revenue' => $totalRevenue,
                    'period_type' => $period,
                ];
            }
        }

        return array_values($metrics);
    }
}
