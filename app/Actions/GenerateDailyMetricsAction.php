<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Product;
use App\Services\MetricsService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Date;

final readonly class GenerateDailyMetricsAction
{
    public function __construct(
        private MetricsService $metricsService,
    ) {}

    /**
     * @return array{
     *     success: bool,
     *     date: string,
     *     processed_products: array<string>,
     *     failed_products: array<array{product_name: string, error: string}>,
     *     total_products: int,
     *     successful_count: int,
     *     failed_count: int
     * }
     */
    public function execute(?Carbon $date = null): array
    {
        $date ??= Date::yesterday();

        $products = Product::all();
        $processedProducts = [];
        $failedProducts = [];

        foreach ($products as $product) {
            try {
                $this->metricsService->recordProductMetrics($product->id, $date);
                $processedProducts[] = $product->name;
            } catch (Exception $e) {
                $failedProducts[] = [
                    'product_name' => $product->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => count($failedProducts) === 0,
            'date' => $date->format('Y-m-d'),
            'processed_products' => $processedProducts,
            'failed_products' => $failedProducts,
            'total_products' => $products->count(),
            'successful_count' => count($processedProducts),
            'failed_count' => count($failedProducts),
        ];
    }

    /**
     * @return array{
     *     success: bool,
     *     period: string,
     *     total_products_processed: int,
     *     total_successful: int,
     *     total_failed: int,
     *     daily_results: array{
     *         success: bool,
     *         date: string,
     *         processed_products: array<string>,
     *         failed_products: array<array{product_name: string, error: string}>,
     *         total_products: int,
     *         successful_count: int,
     *         failed_count: int
     *     }[]
     * }
     */
    public function executeForDateRange(Carbon $startDate, Carbon $endDate): array
    {
        $allResults = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $result = $this->execute($currentDate->copy());
            $allResults[] = $result;
            $currentDate->addDay();
        }

        $totalProducts = array_sum(array_column($allResults, 'total_products'));
        $totalSuccessful = array_sum(array_column($allResults, 'successful_count'));
        $totalFailed = array_sum(array_column($allResults, 'failed_count'));

        return [
            'success' => $totalFailed === 0,
            'period' => $startDate->format('Y-m-d').' to '.$endDate->format('Y-m-d'),
            'total_products_processed' => $totalProducts,
            'total_successful' => $totalSuccessful,
            'total_failed' => $totalFailed,
            'daily_results' => $allResults,
        ];
    }
}
