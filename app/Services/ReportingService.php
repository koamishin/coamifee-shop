<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientUsage;
use App\Models\InventoryTransaction;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class ReportingService
{
    public function getInventoryReport(): array
    {
        $trackableIngredients = Ingredient::with('inventory')
            ->where('is_trackable', true)
            ->get()
            ->map(function ($ingredient) {
                $inventory = $ingredient->inventory()->first();

                return [
                    'name' => $ingredient->name,
                    'current_stock' => $inventory?->current_stock ?? 0,
                    'min_stock_level' => $inventory?->min_stock_level ?? 0,
                    'max_stock_level' => $inventory?->max_stock_level ?? 0,
                    'location' => $inventory?->location ?? 'N/A',
                    'unit_type' => $ingredient->unit_type,
                    'status' => $this->getStockStatus($inventory),
                ];
            });

        $untrackableIngredients = Ingredient::where('is_trackable', false)
            ->get()
            ->map(function ($ingredient) {
                return [
                    'name' => $ingredient->name,
                    'unit_type' => $ingredient->unit_type,
                    'unit_cost' => $ingredient->unit_cost,
                ];
            });

        return [
            'trackable' => $trackableIngredients,
            'untrackable' => $untrackableIngredients,
            'low_stock_alerts' => $this->getLowStockItems(),
        ];
    }

    public function getIngredientUsageReport(Carbon $startDate, Carbon $endDate): Collection
    {
        return IngredientUsage::with(['ingredient', 'orderItem.product', 'orderItem.order'])
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->get()
            ->groupBy('ingredient.name')
            ->map(function ($usages) {
                return [
                    'ingredient_name' => $usages->first()->ingredient->name,
                    'total_quantity_used' => $usages->sum('quantity_used'),
                    'unit_type' => $usages->first()->ingredient->unit_type,
                    'usage_count' => $usages->count(),
                    'total_cost' => $usages->sum('quantity_used') * $usages->first()->ingredient->unit_cost,
                ];
            });
    }

    public function getSalesReport(Carbon $startDate, Carbon $endDate): array
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->with(['items.product', 'customer'])
            ->get();

        $totalRevenue = $orders->sum('total');
        $totalOrders = $orders->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $productSales = $orders->flatMap->items
            ->groupBy('product.name')
            ->map(function ($items) {
                return [
                    'product_name' => $items->first()->product->name,
                    'quantity_sold' => $items->sum('quantity'),
                    'revenue' => $items->sum(function ($item) {
                        return $item->price * $item->quantity;
                    }),
                ];
            })
            ->sortByDesc('revenue');

        return [
            'period' => $startDate->format('M j, Y').' - '.$endDate->format('M j, Y'),
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'average_order_value' => $averageOrderValue,
            'product_sales' => $productSales,
        ];
    }

    public function getCostAnalysisReport(Carbon $startDate, Carbon $endDate): array
    {
        $ingredientCosts = IngredientUsage::with('ingredient')
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->get()
            ->groupBy('ingredient.name')
            ->map(function ($usages) {
                $ingredient = $usages->first()->ingredient;
                $totalQuantity = $usages->sum('quantity_used');
                $totalCost = $totalQuantity * $ingredient->unit_cost;

                return [
                    'ingredient_name' => $ingredient->name,
                    'quantity_used' => $totalQuantity,
                    'unit_type' => $ingredient->unit_type,
                    'unit_cost' => $ingredient->unit_cost,
                    'total_cost' => $totalCost,
                ];
            })
            ->sortByDesc('total_cost');

        $totalIngredientCost = $ingredientCosts->sum('total_cost');

        return [
            'period' => $startDate->format('M j, Y').' - '.$endDate->format('M j, Y'),
            'total_ingredient_cost' => $totalIngredientCost,
            'ingredient_breakdown' => $ingredientCosts,
        ];
    }

    public function getInventoryTransactions(Carbon $startDate, Carbon $endDate): Collection
    {
        return InventoryTransaction::with(['ingredient', 'orderItem.product'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'ingredient_name' => $transaction->ingredient->name,
                    'transaction_type' => $transaction->transaction_type,
                    'quantity_change' => $transaction->quantity_change,
                    'previous_stock' => $transaction->previous_stock,
                    'new_stock' => $transaction->new_stock,
                    'reason' => $transaction->reason,
                    'product_name' => $transaction->orderItem?->product->name,
                    'created_at' => $transaction->created_at->format('M j, Y H:i'),
                ];
            });
    }

    private function getStockStatus($inventory): string
    {
        if (! $inventory) {
            return 'No Inventory';
        }

        if ($inventory->current_stock <= $inventory->min_stock_level) {
            return 'Low Stock';
        }

        if ($inventory->max_stock_level && $inventory->current_stock >= $inventory->max_stock_level) {
            return 'Overstocked';
        }

        return 'Normal';
    }

    private function getLowStockItems(): Collection
    {
        return Ingredient::with('inventory')
            ->where('is_trackable', true)
            ->whereHas('inventory', function ($query) {
                $query->whereColumn('current_stock', '<=', 'min_stock_level');
            })
            ->get()
            ->map(function ($ingredient) {
                $inventory = $ingredient->inventory;

                return [
                    'ingredient_name' => $ingredient->name,
                    'current_stock' => $inventory->current_stock,
                    'min_stock_level' => $inventory->min_stock_level,
                    'unit_type' => $ingredient->unit_type,
                    'shortage' => max(0, $inventory->min_stock_level - $inventory->current_stock),
                ];
            });
    }
}
