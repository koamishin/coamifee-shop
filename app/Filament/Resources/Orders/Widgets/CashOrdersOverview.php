<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Widgets;

use App\Filament\Concerns\CurrencyAware;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class CashOrdersOverview extends StatsOverviewWidget
{
    use CurrencyAware;

    protected static ?int $sort = 6;

    protected function getStats(): array
    {
        return [];
    }
}
