<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

final class BestSellersStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $oneMonthAgo = now()->subMonth();

        // Get product sales data from completed orders in the last month
        $productSales = OrderItem::query()
            ->whereHas('order', function (Builder $query) use ($oneMonthAgo) {
                $query->where('created_at', '>=', $oneMonthAgo)
                    ->where('status', 'completed'); // Only count completed orders
            })
            ->with(['product.category'])
            ->selectRaw('
                product_id,
                SUM(quantity) as total_quantity,
                SUM(quantity * price) as total_revenue
            ')
            ->groupBy('product_id')
            ->get();

        // Group by category and filter categories with 1+ products
        $categoryProducts = $productSales
            ->groupBy(fn ($item) => $item->product->category->name ?? 'Uncategorized')
            ->filter(function ($products) {
                return $products->count() >= 1;
            });

        $totalCategories = $categoryProducts->count();
        $totalUnitsSold = $productSales->sum('total_quantity');
        $totalRevenue = $productSales->sum('total_revenue');

        return [
            // Stat::make('Categories Featured', number_format($totalCategories))
            //     ->description('Categories with products')
            //     ->descriptionIcon('heroicon-m-tag')
            //     ->color('primary')
            //     ->chart([7, 2, 10, 3, 15, 4, 17]),

            // Stat::make('Total Units Sold', number_format($totalUnitsSold))
            //     ->description('Products sold in last 30 days')
            //     ->descriptionIcon('heroicon-m-shopping-cart')
            //     ->color('success')
            //     ->chart([65, 59, 80, 81, 56, 55, 72]),

            // Stat::make('Total Revenue', '₱'.number_format($totalRevenue, 0))
            //     ->description('Revenue from best sellers')
            //     ->descriptionIcon('heroicon-m-banknotes')
            //     ->color('warning')
            //     ->chart([12, 11, 14, 13, 15, 14, 16]),
        ];
    }
}
