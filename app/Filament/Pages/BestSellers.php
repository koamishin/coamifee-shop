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
use UnitEnum;

final class BestSellers extends Page
{
    public Collection $bestSellersData;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public string $periodFilter = 'all';

    // protected static bool $shouldRegisterNavigation = true;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    // protected static UnitEnum|string|null $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Best Sellers';

    protected static ?string $title = 'Best Sellers Report';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.best-sellers';

    public function mount(): void
    {
        $this->startDate = now()->subMonth()->toDateString();
        $this->endDate = now()->toDateString();
        $this->bestSellersData = $this->getBestSellersData();
    }

    public function refreshData(): void
    {
        $this->bestSellersData = $this->getBestSellersData();
    }

    public function filterByPeriod(string $period): void
    {
        $this->periodFilter = $period;

        match ($period) {
            'today' => [
                $this->startDate = now()->toDateString(),
                $this->endDate = now()->toDateString(),
            ],
            'week' => [
                $this->startDate = now()->startOfWeek()->toDateString(),
                $this->endDate = now()->endOfWeek()->toDateString(),
            ],
            'month' => [
                $this->startDate = now()->startOfMonth()->toDateString(),
                $this->endDate = now()->endOfMonth()->toDateString(),
            ],
            'year' => [
                $this->startDate = now()->startOfYear()->toDateString(),
                $this->endDate = now()->endOfYear()->toDateString(),
            ],
            default => [
                $this->startDate = now()->subMonth()->toDateString(),
                $this->endDate = now()->toDateString(),
            ]
        };

        $this->refreshData();
    }

    protected function getBestSellersData(): Collection
    {
        $startDate = $this->startDate ? now()->parse($this->startDate)->startOfDay() : now()->subMonth();
        $endDate = $this->endDate ? now()->parse($this->endDate)->endOfDay() : now();

        // Get all order items from completed orders within the date range
        $orderItems = OrderItem::query()
            ->whereHas('order', function (Builder $query) use ($startDate, $endDate) {
                $query->where('created_at', '>=', $startDate)
                    ->where('created_at', '<=', $endDate)
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
                                ->default($this->startDate)
                                ->required(),

                            Forms\Components\DatePicker::make('end_date')
                                ->label('End Date')
                                ->default($this->endDate)
                                ->required()
                                ->after('start_date'),
                        ]),
                ])
                ->action(function (array $data) {
                    // Update best sellers data based on date range
                    $this->startDate = $data['start_date'];
                    $this->endDate = $data['end_date'];
                    $this->refreshData();

                    \Filament\Notifications\Notification::make()
                        ->title('Date range updated')
                        ->body('Showing data from '.$data['start_date'].' to '.$data['end_date'])
                        ->success()
                        ->send();
                }),
        ];
    }
}
