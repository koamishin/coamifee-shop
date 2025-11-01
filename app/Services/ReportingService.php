<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\TopProductDto;
use App\Models\Ingredient;
use App\Models\IngredientInventory;
use App\Models\IngredientUsage;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

final class ReportingService
{
    /**
     * @return array{
     *     with_inventory: Collection<int, array{
     *         name: string,
     *         current_stock: float,
     *         min_stock_level: float,
     *         max_stock_level: float,
     *         location: string,
     *         unit_type: string,
     *         status: string
     *     }>,
     *     without_inventory: Collection<int, array{
     *         name: string,
     *         unit_type: string
     *     }>,
     *     low_stock_alerts: Collection<int, array{
     *         ingredient_name: string,
     *         current_stock: float,
     *         min_stock_level: float,
     *         unit_type: string,
     *         shortage: float
     *     }>
     * }
     */
    public function getInventoryReport(): array
    {
        $ingredientsWithInventory = Ingredient::with('inventory')
            ->whereHas('inventory')
            ->get()
            ->map(function (Ingredient $ingredient): array {
                /** @var IngredientInventory|null $inventory */
                $inventory = $ingredient->inventory;

                return [
                    'name' => $ingredient->name,
                    'current_stock' => (float) ($inventory->current_stock ?? 0.0),
                    'min_stock_level' => (float) ($inventory->min_stock_level ?? 0.0),
                    'max_stock_level' => (float) ($inventory->max_stock_level ?? 0.0),
                    'location' => (string) ($inventory->location ?? 'N/A'),
                    'unit_type' => $ingredient->unit_type->getLabel(),
                    'status' => $this->getStockStatus($inventory ?: null),
                ];
            });

        $ingredientsWithoutInventory = Ingredient::query()->whereDoesntHave('inventory')
            ->get()
            ->map(function (Ingredient $ingredient): array {
                return [
                    'name' => $ingredient->name,
                    'unit_type' => $ingredient->unit_type->getLabel(),
                ];
            });

        return [
            'with_inventory' => $ingredientsWithInventory,
            'without_inventory' => $ingredientsWithoutInventory,
            'low_stock_alerts' => $this->getLowStockItems(),
        ];
    }

    /**
     * @return Collection<string, array{
     *     ingredient_name: string,
     *     total_quantity_used: float,
     *     unit_type: string,
     *     usage_count: int,
     *     total_cost: float
     * }>
     */
    public function getIngredientUsageReport(Carbon $startDate, Carbon $endDate): Collection
    {
        return IngredientUsage::with(['ingredient', 'orderItem.product', 'orderItem.order'])
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->get()
            ->groupBy(function (IngredientUsage $usage): string {
                return $usage->ingredient->name ?? 'Unknown';
            })
            ->map(function (Collection $usages): array {
                $firstUsage = $usages->first();
                if ($firstUsage === null) {
                    return [
                        'ingredient_name' => '',
                        'total_quantity_used' => 0.0,
                        'unit_type' => '',
                        'usage_count' => 0,
                        'total_cost' => 0.0,
                    ];
                }

                $ingredient = $firstUsage->ingredient;
                if (! $ingredient instanceof Ingredient) {
                    return [
                        'ingredient_name' => '',
                        'total_quantity_used' => 0.0,
                        'unit_type' => '',
                        'usage_count' => 0,
                        'total_cost' => 0.0,
                    ];
                }

                return [
                    'ingredient_name' => (string) ($ingredient->name ?? ''),
                    'total_quantity_used' => (float) $usages->sum('quantity_used'),
                    'unit_type' => $ingredient->unit_type->getLabel(),
                    'usage_count' => $usages->count(),
                    'total_cost' => (float) $usages->sum('quantity_used') * (float) ($ingredient->unit_cost ?? 0.0),
                ];
            });
    }

    /**
     * @return array{
     *     period: string,
     *     total_revenue: float,
     *     total_orders: int,
     *     average_order_value: float,
     *     product_sales: Collection<string, array{
     *         product_name: string,
     *         quantity_sold: float,
     *         revenue: float
     *     }>
     * }
     */
    public function getSalesReport(Carbon $startDate, Carbon $endDate): array
    {
        $orders = Order::query()->whereBetween('created_at', [$startDate, $endDate])
            ->with(['items.product', 'customer'])
            ->get();

        $totalRevenue = $orders->sum('total');
        $totalOrders = $orders->count();
        $averageOrderValue = $totalOrders > 0 ? (float) $totalRevenue / $totalOrders : 0.0;

        $productSales = $orders->flatMap->items
            ->groupBy(function ($item): string {
                return $item->product->name ?? 'Unknown';
            })
            ->map(function (Collection $items): array {
                $firstItem = $items->first();
                if ($firstItem === null) {
                    return [
                        'product_name' => '',
                        'quantity_sold' => 0.0,
                        'revenue' => 0.0,
                    ];
                }

                $product = $firstItem->product;
                if ($product === null) {
                    return [
                        'product_name' => '',
                        'quantity_sold' => 0.0,
                        'revenue' => 0.0,
                    ];
                }

                return [
                    'product_name' => (string) $product->name,
                    'quantity_sold' => (float) $items->sum('quantity'),
                    'revenue' => (float) $items->sum(function ($item): float {
                        return (float) $item->price * (float) $item->quantity;
                    }),
                ];
            })
            ->sortByDesc('revenue');

        return [
            'period' => $startDate->format('M j, Y').' - '.$endDate->format('M j, Y'),
            'total_revenue' => (float) $totalRevenue,
            'total_orders' => $totalOrders,
            'average_order_value' => $averageOrderValue,
            'product_sales' => $productSales,
        ];
    }

    /**
     * @return Collection<int, TopProductDto>
     */
    public function getTopProducts(int $limit = 5, string $period = 'daily', int $days = 7): Collection
    {
        $startDate = match ($period) {
            'daily' => Date::now()->subDays($days),
            'weekly' => Date::now()->subWeeks($days),
            'monthly' => Date::now()->subMonths($days),
            'yearly' => Date::now()->subYears($days),
            default => Date::now()->subDays($days),
        };

        return Order::with(['items.product'])
            ->whereBetween('created_at', [$startDate, Date::now()])
            ->get()
            ->flatMap->items
            ->groupBy('product_id')
            ->map(function (Collection $items, mixed $productId) {
                $firstItem = $items->first();
                /** @var OrderItem $firstItem */
                $product = $firstItem->product;
                /** @var Product|null $product */

                return new TopProductDto(
                    product: $product,
                    quantity_sold: (float) $items->sum('quantity'),
                    revenue: (float) $items->sum(function ($item) {
                        /** @var OrderItem $item */
                        return (float) $item->price * (float) $item->quantity;
                    })
                );
            })
            ->sortByDesc('quantity_sold')
            ->take($limit)
            ->values();
    }

    /**
     * @return array{
     *     period: string,
     *     total_ingredient_cost: float,
     *     ingredient_breakdown: Collection<string, array{
     *         ingredient_name: string,
     *         quantity_used: float,
     *         unit_type: string,
     *         unit_cost: float,
     *         total_cost: float
     *     }>
     * }
     */
    public function getCostAnalysisReport(Carbon $startDate, Carbon $endDate): array
    {
        $ingredientCosts = IngredientUsage::with('ingredient')
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->get()
            ->groupBy(function (IngredientUsage $usage): string {
                return $usage->ingredient->name ?? 'Unknown';
            })
            ->map(function (Collection $usages): array {
                $firstUsage = $usages->first();
                if ($firstUsage === null) {
                    return [
                        'ingredient_name' => '',
                        'quantity_used' => 0.0,
                        'unit_type' => '',
                        'unit_cost' => 0.0,
                        'total_cost' => 0.0,
                    ];
                }

                $ingredient = $firstUsage->ingredient;
                if (! $ingredient instanceof Ingredient) {
                    return [
                        'ingredient_name' => '',
                        'quantity_used' => 0.0,
                        'unit_type' => '',
                        'unit_cost' => 0.0,
                        'total_cost' => 0.0,
                    ];
                }

                $totalQuantity = (float) $usages->sum('quantity_used');
                $unitCost = (float) ($ingredient->unit_cost ?? 0.0);
                $totalCost = $totalQuantity * $unitCost;

                return [
                    'ingredient_name' => (string) ($ingredient->name ?? ''),
                    'quantity_used' => $totalQuantity,
                    'unit_type' => $ingredient->unit_type->getLabel(),
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                ];
            })
            ->sortByDesc('total_cost');

        $totalIngredientCost = $ingredientCosts->sum('total_cost');

        return [
            'period' => $startDate->format('M j, Y').' - '.$endDate->format('M j, Y'),
            'total_ingredient_cost' => (float) $totalIngredientCost,
            'ingredient_breakdown' => $ingredientCosts,
        ];
    }

    /**
     * Get inventory transactions for a specified date range.
     */
    public function getInventoryTransactions(Carbon $startDate, Carbon $endDate): Collection
    {
        return InventoryTransaction::with(['ingredient', 'orderItem.product'])
            ->whereBetween('created_at', [$startDate, $endDate])->latest()
            ->get()
            ->map(function (InventoryTransaction $transaction): array {
                /** @var Ingredient|null $ingredient */
                $ingredient = $transaction->ingredient;
                /** @var OrderItem|null $orderItem */
                $orderItem = $transaction->orderItem;

                /** @var Product|null $product */
                $product = $orderItem?->product;

                return [
                    'id' => $transaction->id,
                    'ingredient_name' => $ingredient instanceof Ingredient ? (string) $ingredient->name : 'Unknown',
                    'transaction_type' => $transaction->transaction_type,
                    'quantity_change' => (float) $transaction->quantity_change,
                    'previous_stock' => (float) $transaction->previous_stock,
                    'new_stock' => (float) $transaction->new_stock,
                    'reason' => $transaction->reason,
                    'product_name' => $product?->name,
                    'created_at' => $transaction->created_at?->format('M j, Y H:i') ?? '',
                ];
            });
    }

    private function getStockStatus(?IngredientInventory $inventory): string
    {
        if ($inventory === null) {
            return 'No Inventory';
        }

        if ((float) $inventory->current_stock <= (float) $inventory->min_stock_level) {
            return 'Low Stock';
        }

        if ($inventory->max_stock_level !== null && (float) $inventory->current_stock >= (float) $inventory->max_stock_level) {
            return 'Overstocked';
        }

        return 'Normal';
    }

    /**
     * @return Collection<int, array{
     *     ingredient_name: string,
     *     current_stock: float,
     *     min_stock_level: float,
     *     unit_type: string,
     *     shortage: float
     * }>
     */
    private function getLowStockItems(): Collection
    {
        return Ingredient::with('inventory')
            ->whereHas('inventory')
            ->whereHas('inventory', function ($query): void {
                $query->whereColumn('current_stock', '<=', 'min_stock_level');
            })
            ->get()
            ->map(function (Ingredient $ingredient): array {
                $inventory = $ingredient->inventory;
                if ($inventory === null) {
                    return [
                        'ingredient_name' => $ingredient->name,
                        'current_stock' => 0.0,
                        'min_stock_level' => 0.0,
                        'unit_type' => $ingredient->unit_type->getLabel(),
                        'shortage' => 0.0,
                    ];
                }

                return [
                    'ingredient_name' => $ingredient->name,
                    'current_stock' => (float) ($inventory->current_stock ?? 0.0),
                    'min_stock_level' => (float) ($inventory->min_stock_level ?? 0.0),
                    'unit_type' => $ingredient->unit_type->getLabel(),
                    'shortage' => max(0.0, (float) ($inventory->min_stock_level ?? 0.0) - (float) ($inventory->current_stock ?? 0.0)),
                ];
            });
    }
}
