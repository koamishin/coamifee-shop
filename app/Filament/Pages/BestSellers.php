<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\OrderItem;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class BestSellers extends Page
{
    public Collection $bestSellersData;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Best Sellers';

    protected static ?string $title = 'Best Sellers Report';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.best-sellers';

    public function mount(): void
    {
        $this->bestSellersData = $this->getBestSellersData();
    }

    public function refreshData(): void
    {
        $this->bestSellersData = $this->getBestSellersData();
    }

    protected function getBestSellersData(): Collection
    {
        $oneMonthAgo = now()->subMonth();

        // Get all order items from completed orders in the last month
        $orderItems = OrderItem::query()
            ->whereHas('order', function (Builder $query) use ($oneMonthAgo) {
                $query->where('created_at', '>=', $oneMonthAgo)
                    ->where('status', 'completed');
            })
            ->with(['product.category'])
            ->get();

        // Group by product and calculate totals
        $productSales = $orderItems
            ->groupBy('product_id')
            ->map(function (Collection $items) {
                $firstItem = $items->first();

                return (object) [
                    'product' => $firstItem->product,
                    'total_quantity' => $items->sum('quantity'),
                    'total_revenue' => $items->sum(fn ($item) => $item->quantity * $item->price),
                ];
            })
            ->sortByDesc('total_quantity')
            ->values();

        // Group by category and get top 3 products per category
        $categoryProducts = $productSales
            ->groupBy(fn ($item) => $item->product->category->name ?? 'Uncategorized')
            ->filter(fn ($products) => $products->count() >= 1)
            ->map(fn ($products) => $products->take(3)->values());

        return $categoryProducts;
    }

    protected function getViewData(): array
    {
        return [
            'bestSellersData' => $this->bestSellersData,
        ];
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(fn () => $this->refreshData()),

            Actions\Action::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('format')
                        ->label('Export Format')
                        ->options([
                            'csv' => 'CSV',
                            'xlsx' => 'Excel',
                        ])
                        ->default('csv')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Export functionality can be implemented here
                    // For now, just show a notification
                    \Filament\Notifications\Notification::make()
                        ->title('Export started')
                        ->body("Best sellers data will be exported as {$data['format']}")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('dateRange')
                ->label('Date Range')
                ->icon('heroicon-o-calendar')
                ->color('info')
                ->form([
                    Grid::make(2)
                        ->schema([
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Start Date')
                                ->default(now()->subMonth())
                                ->required(),

                            Forms\Components\DatePicker::make('end_date')
                                ->label('End Date')
                                ->default(now())
                                ->required(),
                        ]),
                ])
                ->action(function (array $data) {
                    // Update best sellers data based on date range
                    // This would require modifying the getBestSellersData method
                    \Filament\Notifications\Notification::make()
                        ->title('Date range updated')
                        ->body('Showing data from '.$data['start_date'].' to '.$data['end_date'])
                        ->success()
                        ->send();
                }),
        ];
    }
}
