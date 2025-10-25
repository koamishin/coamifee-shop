<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\IngredientInventory;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

final class CoffeeShopOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            Stat::make('Today\'s Orders', Order::whereDate('created_at', $today)->count())
                ->description('Orders placed today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([0, 2, 5, 3, 8, 12, 15]),

            Stat::make('Today\'s Revenue', Order::whereDate('created_at', $today)->sum('total'))
                ->description('$'.number_format(Order::whereDate('created_at', $today)->sum('total'), 2))
                ->description('Revenue from today')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),

            Stat::make('Active Products', Product::count())
                ->description('Total products available')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),

            Stat::make('Low Stock Alerts', $this->getLowStockCount())
                ->description('Ingredients needing restock')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($this->getLowStockCount() > 0 ? 'danger' : 'success'),
        ];
    }

    private function getLowStockCount(): int
    {
        return IngredientInventory::with('ingredient')
            ->whereHas('ingredient', fn ($query) => $query->where('is_trackable', true))
            ->whereColumn('current_stock', '<=', 'min_stock_level')
            ->count();
    }
}
