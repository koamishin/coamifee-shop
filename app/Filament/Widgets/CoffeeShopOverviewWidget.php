<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\IngredientInventory;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

final class CoffeeShopOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $today = Date::today();
        Date::now()->startOfWeek();
        Date::now()->startOfMonth();

        $currency = app(\App\Services\GeneralSettingsService::class)->getCurrency();
        $todaysSales = Order::query()->whereDate('created_at', $today)->sum('total');

        return [
            Stat::make('Today\'s Orders', Order::query()->whereDate('created_at', $today)->count())
                ->description('Orders placed today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([0, 2, 5, 3, 8, 12, 15]),

            Stat::make('Today\'s Sales', number_format($todaysSales, 2))
                ->description("{$currency} ".number_format($todaysSales, 2).' from today')
                ->descriptionIcon('mdi-currency-php')
                ->color('primary'),

            Stat::make('Active Products', Product::query()->count())
                ->description('Total products available')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),
            // Auth::attempt()

            Stat::make('Low Stock Alerts', $this->getLowStockCount())
                ->description('Ingredients needing restock')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($this->getLowStockCount() > 0 ? 'danger' : 'success'),
        ];
    }

    private function getLowStockCount(): int
    {
        return IngredientInventory::whereHas('ingredient', fn ($query) => $query->whereNotNull('id'))
            ->whereColumn('current_stock', '<=', 'min_stock_level')
            ->count();
    }
}
