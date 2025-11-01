<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductMetrics\Tables;

use App\Filament\Concerns\CurrencyAware;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

final class ProductMetricsTable
{
    use CurrencyAware;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->description('Product being tracked')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(30)
                    ->icon('heroicon-o-shopping-bag'),

                TextColumn::make('metric_date')
                    ->label('Date')
                    ->description('Metric collection date')
                    ->date('M j, Y')
                    ->sortable()
                    ->alignCenter()
                    ->icon('heroicon-o-calendar'),

                TextColumn::make('period_type')
                    ->label('Period')
                    ->description('Time period type')
                    ->badge()
                    ->icon(
                        fn ($state): string => match ($state) {
                            'daily' => 'heroicon-o-calendar-days',
                            'weekly' => 'heroicon-o-calendar',
                            'monthly' => 'heroicon-o-chart-bar',
                            default => 'heroicon-o-question-mark-circle',
                        },
                    )
                    ->color(
                        fn ($state): string => match ($state) {
                            'daily' => 'info',
                            'weekly' => 'warning',
                            'monthly' => 'success',
                            default => 'gray',
                        },
                    )
                    ->formatStateUsing(
                        fn ($state): string => match ($state) {
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                            default => ucfirst((string) $state),
                        },
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->description('Number of orders')
                    ->numeric()
                    ->sortable()
                    ->alignRight()
                    ->weight('medium')
                    ->icon('heroicon-o-shopping-cart')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(
                        fn ($state): string => number_format((int) $state),
                    ),

                TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->description('Total revenue generated')
                    ->money(self::getMoneyConfig())
                    ->sortable()
                    ->alignRight()
                    ->weight('bold')
                    ->size('lg')
                    ->color('success'),

                TextColumn::make('average_order_value')
                    ->label('AOV')
                    ->description('Average order value')
                    ->money(self::getMoneyConfig())
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(function ($record): string|HtmlString {
                        $orders = (int) $record->orders_count;
                        $revenue = (float) $record->total_revenue;

                        if ($orders === 0) {
                            return self::formatCurrency(0);
                        }

                        $aov = $revenue / $orders;
                        $color =
                            $aov >= 50
                                ? '#10b981'
                                : ($aov >= 25
                                    ? '#f59e0b'
                                    : '#ef4444');

                        return new HtmlString(
                            "<span style='color: {$color}; font-weight: 600;'>".
                                self::formatCurrency($aov).
                                '</span>',
                        );
                    }),

                TextColumn::make('revenue_per_day')
                    ->label('Revenue/Day')
                    ->description('Revenue per day in period')
                    ->money(self::getMoneyConfig())
                    ->sortable()
                    ->alignRight()
                    ->formatStateUsing(function ($record): float {
                        $revenue = (float) $record->total_revenue;
                        $period = $record->period_type ?? 'daily';

                        $days = match ($period) {
                            'daily' => 1,
                            'weekly' => 7,
                            'monthly' => 30,
                            default => 1,
                        };

                        return $revenue / $days;
                    })
                    ->color('info'),

                TextColumn::make('performance_indicator')
                    ->label('Performance')
                    ->description('Overall performance rating')
                    ->badge()
                    ->formatStateUsing(function ($record): string {
                        $orders = (int) $record->orders_count;
                        $revenue = (float) $record->total_revenue;

                        if ($orders === 0 && $revenue === 0.0) {
                            return '⚪ No Activity';
                        }

                        $aov = $orders > 0 ? $revenue / $orders : 0;

                        if ($aov >= 50 && $orders >= 10) {
                            return '🟢 Excellent';
                        }

                        if ($aov >= 25 && $orders >= 5) {
                            return '🟠 Good';
                        }

                        return '🔴 Needs Attention';
                    })
                    ->color(
                        fn ($state): string => match (true) {
                            str_contains((string) $state, 'Excellent') => 'success',
                            str_contains((string) $state, 'Good') => 'warning',
                            str_contains((string) $state, 'No Activity') => 'gray',
                            default => 'danger',
                        },
                    ),

                TextColumn::make('created_at')
                    ->label('Recorded')
                    ->description('When metric was recorded')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('metric_date', 'desc')
            ->striped()
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Filter by product'),

                SelectFilter::make('period_type')
                    ->label('Period Type')
                    ->options([
                        'daily' => '📅 Daily',
                        'weekly' => '📊 Weekly',
                        'monthly' => '📈 Monthly',
                    ])
                    ->placeholder('Filter by period'),

                SelectFilter::make('performance_range')
                    ->label('Performance Range')
                    ->options([
                        'excellent' => '🟢 Excellent',
                        'good' => '🟠 Good',
                        'needs_attention' => '🔴 Needs Attention',
                        'no_activity' => '⚪ No Activity',
                    ])
                    ->placeholder('Filter by performance')
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        match ($data['value']) {
                            'excellent' => $query->whereRaw(
                                '(total_revenue / GREATEST(orders_count, 1)) >= 50 AND orders_count >= 10',
                            ),
                            'good' => $query->whereRaw(
                                '(total_revenue / GREATEST(orders_count, 1)) >= 25 AND orders_count >= 5',
                            ),
                            'needs_attention' => $query->whereRaw(
                                '(total_revenue / GREATEST(orders_count, 1)) < 25 OR orders_count < 5',
                            ),
                            'no_activity' => $query
                                ->where('orders_count', 0)
                                ->where('total_revenue', 0),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View Details')
                        ->icon('heroicon-o-eye'),

                    EditAction::make()
                        ->label('Edit Metric')
                        ->icon('heroicon-o-pencil'),

                    Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->action(function ($record): void {
                            $newRecord = $record->replicate();
                            $newRecord->metric_date = now();
                            $newRecord->save();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Duplicate Metric')
                        ->modalDescription(
                            "Create a copy of this product metric for today's date.",
                        )
                        ->modalSubmitActionLabel('Yes, duplicate it'),

                    Action::make('export_data')
                        ->label('Export Data')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn ($record) =>
                            // Implementation for exporting single metric data
                            response()->streamDownload(function () use (
                                $record,
                            ): void {
                                echo "Product,Date,Period,Orders,Revenue,AOV\n";
                                echo "{$record->product->name},{$record->metric_date},{$record->period_type},{$record->orders_count},{$record->total_revenue},".
                                    ($record->orders_count > 0
                                        ? $record->total_revenue /
                                            $record->orders_count
                                        : 0).
                                    "\n";
                            }, "product-metric-{$record->id}.csv"))
                        ->openUrlInNewTab(),
                ])
                    ->label('Actions')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->color('primary'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Delete Selected')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Selected Metrics')
                    ->modalDescription(
                        'Are you sure you want to delete the selected product metrics? This action cannot be undone.',
                    )
                    ->modalSubmitActionLabel('Yes, delete them'),

                Action::make('export_bulk')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn ($records) => response()->streamDownload(function () use (
                        $records,
                    ): void {
                        echo "Product,Date,Period,Orders,Revenue,AOV\n";
                        foreach ($records as $record) {
                            echo "{$record->product->name},{$record->metric_date},{$record->period_type},{$record->orders_count},{$record->total_revenue},".
                                ($record->orders_count > 0
                                    ? $record->total_revenue /
                                        $record->orders_count
                                    : 0).
                                "\n";
                        }
                    }, 'product-metrics-bulk-'.now()->format('Y-m-d').'.csv'))
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('No product metrics found')
            ->emptyStateDescription(
                'No metrics have been recorded for products yet. Start tracking product performance.',
            )
            ->emptyStateActions([
                Action::make('create_first')
                    ->label('Create Product Metric')
                    ->icon('heroicon-o-plus')
                    ->url(
                        route(
                            'filament.admin.resources.product-metrics.create',
                        ),
                    ),
            ])
            ->poll('60s'); // Refresh every minute for real-time updates
    }
}
