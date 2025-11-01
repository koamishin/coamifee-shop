<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductIngredients\Schemas;

use App\Filament\Concerns\CurrencyAware;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

final class ProductIngredientInfolist
{
    use CurrencyAware;

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Recipe Details')
                ->description('Overview of this product-ingredient relationship')
                ->icon('heroicon-o-link')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('product.name')
                            ->label('Product')
                            ->weight('medium')
                            ->size('text-lg')
                            ->icon('heroicon-o-shopping-bag')
                            ->iconPosition('before')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('ingredient.name')
                            ->label('Ingredient')
                            ->weight('medium')
                            ->size('text-lg')
                            ->icon('heroicon-o-cube')
                            ->iconPosition('before')
                            ->badge()
                            ->color('primary'),
                    ]),

                    Grid::make(3)->schema([
                        TextEntry::make('quantity_required')
                            ->label('Required Quantity')
                            ->numeric(decimalPlaces: 3)
                            ->alignCenter()
                            ->formatStateUsing(function ($state, $record) {
                                $unit = $record->ingredient->unit_type?->getLabel() ?? 'unit';

                                return "{$state} {$unit}";
                            })
                            ->weight('medium')
                            ->badge()
                            ->color('success'),

                        TextEntry::make('cost_per_product')
                            ->label('Cost per Product')
                            ->money(self::getMoneyConfig())
                            ->alignCenter()
                            ->formatStateUsing(function ($record) {
                                return $record->quantity_required * ($record->ingredient->unit_cost ?? 0);
                            })
                            ->weight('medium')
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('products_possible')
                            ->label('Can Make')
                            ->alignCenter()
                            ->formatStateUsing(function ($record) {
                                if (! $record->ingredient->inventory) {
                                    return 'N/A';
                                }

                                $currentStock = (float) $record->ingredient->inventory->current_stock;
                                $productsPossible = floor($currentStock / $record->quantity_required);

                                return (string) $productsPossible;
                            })
                            ->weight('medium')
                            ->badge()
                            ->color(fn ($record): string => ! $record->ingredient->inventory ? 'gray' :
                                (floor($record->ingredient->inventory->current_stock / $record->quantity_required) <= 10 ? 'danger' :
                                (floor($record->ingredient->inventory->current_stock / $record->quantity_required) <= 50 ? 'warning' : 'success'))
                            ),
                    ]),
                ]),

            Section::make('Ingredient Information')
                ->description('Detailed information about the selected ingredient')
                ->icon('heroicon-o-cube')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('ingredient.unit_type')
                            ->label('Measurement Unit')
                            ->formatStateUsing(function ($state) {
                                $unitType = $state;

                                return $unitType?->getLabel() ?? 'Unknown';
                            })
                            ->badge()
                            ->color(fn ($state): string => $state?->getColor() ?? 'gray')
                            ->icon(fn ($state): string => $state?->getIcon() ?? 'heroicon-o-cube'),

                        TextEntry::make('ingredient.unit_cost')
                            ->label('Unit Cost')
                            ->money(self::getMoneyConfig())
                            ->alignCenter()
                            ->placeholder('Not set')
                            ->formatStateUsing(fn ($state): float|int => $state ?? 0),

                        TextEntry::make('ingredient.inventory.current_stock')
                            ->label('Current Stock')
                            ->numeric(decimalPlaces: 2)
                            ->alignCenter()
                            ->formatStateUsing(function ($record) {
                                if (! $record->ingredient->inventory) {
                                    return 'No Inventory';
                                }

                                $stock = $record->ingredient->inventory->current_stock;
                                $minStock = $record->ingredient->inventory->min_stock_level ?? 0;
                                $unit = $record->ingredient->unit_type?->getLabel() ?? 'unit';

                                $status = $stock <= $minStock ? 'Low' : 'Good';
                                $color = $stock <= $minStock ? 'danger' : 'success';

                                return new HtmlString("
                                    <div class='flex flex-col items-center'>
                                        <span class='text-{$color}-600 font-bold text-lg'>{$stock} {$unit}</span>
                                        <span class='text-xs text-gray-500'>{$status} Stock</span>
                                    </div>
                                ");
                            }),
                    ]),
                ])
                ->collapsible(),

            Section::make('Inventory Analysis')
                ->description('Stock status and production capacity analysis')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Grid::make(2)->schema([
                        IconEntry::make('stock_status')
                            ->label('Stock Status')
                            ->icon(function ($record): string {
                                if (! $record->ingredient->inventory) {
                                    return 'heroicon-o-x-circle';
                                }

                                $currentStock = (float) $record->ingredient->inventory->current_stock;
                                $minStock = $record->ingredient->inventory->min_stock_level ?? 0;
                                $productsPossible = floor($currentStock / $record->quantity_required);

                                if ($currentStock <= $minStock) {
                                    return 'heroicon-o-exclamation-triangle';
                                }
                                if ($productsPossible <= 10) {
                                    return 'heroicon-o-x-circle';
                                }
                                if ($productsPossible <= 50) {
                                    return 'heroicon-o-shield-check';
                                }

                                return 'heroicon-o-check-circle';
                            })
                            ->color(function ($record): string {
                                if (! $record->ingredient->inventory) {
                                    return 'gray';
                                }

                                $currentStock = (float) $record->ingredient->inventory->current_stock;
                                $minStock = $record->ingredient->inventory->min_stock_level ?? 0;
                                $productsPossible = floor($currentStock / $record->quantity_required);

                                if ($currentStock <= $minStock) {
                                    return 'danger';
                                }
                                if ($productsPossible <= 10) {
                                    return 'danger';
                                }
                                if ($productsPossible <= 50) {
                                    return 'warning';
                                }

                                return 'success';
                            })
                            ->formatStateUsing(function ($record): string {
                                if (! $record->ingredient->inventory) {
                                    return 'No inventory data available';
                                }

                                $currentStock = (float) $record->ingredient->inventory->current_stock;
                                $minStock = $record->ingredient->inventory->min_stock_level ?? 0;
                                $productsPossible = floor($currentStock / $record->quantity_required);
                                $unit = $record->ingredient->unit_type?->getLabel() ?? 'unit';

                                if ($currentStock <= $minStock) {
                                    return "Low Stock Alert: Only {$currentStock} {$unit} available (below minimum of {$minStock} {$unit})";
                                }
                                if ($productsPossible <= 10) {
                                    return "Critical: Only {$productsPossible} products can be made with current stock";
                                }
                                if ($productsPossible <= 50) {
                                    return "Warning: {$productsPossible} products possible with current stock";
                                }

                                return "Good Stock: {$productsPossible} products can be made with {$currentStock} {$unit}";
                            }),

                        TextEntry::make('reorder_suggestion')
                            ->label('Reorder Suggestion')
                            ->formatStateUsing(function ($record): string {
                                if (! $record->ingredient->inventory) {
                                    return 'Set up inventory to see reorder suggestions';
                                }

                                $currentStock = (float) $record->ingredient->inventory->current_stock;
                                $maxStock = $record->ingredient->inventory->max_stock_level ?? 0;
                                $minStock = $record->ingredient->inventory->min_stock_level ?? 0;
                                $productsPossible = floor($currentStock / $record->quantity_required);

                                $unitLabel = $record->ingredient->unit_type?->getLabel() ?? 'units';

                                if ($currentStock <= $minStock) {
                                    $suggested = $maxStock - $currentStock;

                                    return "Urgent: Order {$suggested} {$unitLabel} to reach maximum stock";
                                }
                                if ($productsPossible <= 20) {
                                    $suggested = ceil($maxStock * 0.6) - $currentStock;

                                    return "Consider ordering {$suggested} {$unitLabel} soon";
                                }

                                return 'Stock level is adequate';
                            })
                            ->badge()
                            ->color(function ($record): string {
                                if (! $record->ingredient->inventory) {
                                    return 'gray';
                                }

                                $currentStock = (float) $record->ingredient->inventory->current_stock;
                                $minStock = $record->ingredient->inventory->min_stock_level ?? 0;
                                $productsPossible = floor($currentStock / $record->quantity_required);

                                if ($currentStock <= $minStock) {
                                    return 'danger';
                                }
                                if ($productsPossible <= 20) {
                                    return 'warning';
                                }

                                return 'success';
                            }),
                    ]),
                ])
                ->collapsible()
                ->collapsed(),

            Section::make('System Information')
                ->description('Timestamps and system data')
                ->icon(Heroicon::Cog6Tooth)
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('M j, Y g:i A')
                            ->placeholder('-')
                            ->icon('heroicon-o-calendar'),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('M j, Y g:i A')
                            ->placeholder('-')
                            ->icon('heroicon-o-clock'),
                    ]),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }
}
