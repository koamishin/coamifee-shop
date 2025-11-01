<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\IngredientInventory;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

final class LowStockAlertWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Low Stock Alerts';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                IngredientInventory::with(['ingredient'])
                    ->whereHas('ingredient', fn ($query) => $query->whereNotNull('id'))
                    ->whereColumn('current_stock', '<=', 'min_stock_level')
                    ->orderByRaw('(current_stock / min_stock_level) ASC')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('ingredient.name')
                    ->label('Ingredient')
                    ->description('Name of ingredient')
                    ->weight('medium')
                    ->limit(30),
                TextColumn::make('current_stock')
                    ->label('Current Stock')
                    ->description('Available quantity')
                    ->numeric(decimalPlaces: 2)
                    ->alignRight()
                    ->formatStateUsing(fn ($record): HtmlString => new HtmlString("
                        <span style='color: #dc2626; font-weight: bold;'>".
                        number_format($record->current_stock, 2).
                        "</span>
                        <span style='color: #6b7280; font-size: 0.85em; margin-left: 4px;'>".
                        $record->ingredient->unit_type.
                        '</span>
                    ')),
                TextColumn::make('min_stock_level')
                    ->label('Min Level')
                    ->description('Minimum stock level')
                    ->numeric(decimalPlaces: 2)
                    ->alignRight()
                    ->formatStateUsing(fn ($record): HtmlString => new HtmlString("
                        <span style='color: #f59e0b; font-weight: bold;'>".
                        number_format($record->min_stock_level, 2).
                        "</span>
                        <span style='color: #6b7280; font-size: 0.85em; margin-left: 4px;'>".
                        $record->ingredient->unit_type.
                        '</span>
                    ')),
                TextColumn::make('shortage')
                    ->label('Shortage')
                    ->description('Amount below minimum')
                    ->numeric(decimalPlaces: 2)
                    ->alignRight()
                    ->formatStateUsing(fn ($record): HtmlString => new HtmlString("
                        <span style='color: #dc2626; font-weight: bold;'>".
                        number_format(max(0, $record->min_stock_level - $record->current_stock), 2).
                        "</span>
                        <span style='color: #6b7280; font-size: 0.85em; margin-left: 4px;'>".
                        $record->ingredient->unit_type.
                        '</span>
                    ')),
                TextColumn::make('urgency')
                    ->label('Urgency')
                    ->description('Restock urgency level')
                    ->alignCenter()
                    ->formatStateUsing($this->getUrgencyLevel(...)),
            ])
            ->emptyStateHeading('No Low Stock Items')
            ->emptyStateDescription('All trackable ingredients are above minimum stock levels')
            ->paginated(false);
    }

    private function getUrgencyLevel($record): HtmlString
    {
        $shortage = max(0, $record->min_stock_level - $record->current_stock);
        $percentage = ($shortage / $record->min_stock_level) * 100;

        if ($percentage >= 100) {
            return new HtmlString('<span style="background: #dc2626; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em;">CRITICAL</span>');
        }
        if ($percentage >= 50) {
            return new HtmlString('<span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em;">HIGH</span>');
        }
        if ($percentage >= 20) {
            return new HtmlString('<span style="background: #fb923c; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em;">MEDIUM</span>');
        }

        return new HtmlString('<span style="background: #fbbf24; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85em;">LOW</span>');
    }
}
