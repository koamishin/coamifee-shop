<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\DrinkCostingService;
use App\Services\GeneralSettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Collection;
use UnitEnum;

final class DrinkCosting extends Page
{
    public Collection $costingsData;

    public Collection $categories;

    public ?int $selectedCategoryId = null;

    public float $laborPercentage = 30.0;

    public float $markupPercentage = 40.0;

    /** @var array<string, mixed> */
    public array $summaryStats = [];

    public string $currency = 'PHP';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Drink Costing';

    protected static ?string $title = 'Drink Costing Analysis';

    protected static UnitEnum|string|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.drink-costing';

    public function mount(DrinkCostingService $costingService, GeneralSettingsService $settingsService): void
    {
        $this->currency = $settingsService->getCurrency();
        $this->categories = $costingService->getCategories();
        $this->refreshData();
    }

    public function boot(DrinkCostingService $costingService): void
    {
        // Service is injected for each request
    }

    public function refreshData(): void
    {
        $costingService = resolve(DrinkCostingService::class);

        $this->costingsData = $costingService->getDrinkCostings(
            $this->selectedCategoryId,
            $this->laborPercentage,
            $this->markupPercentage
        );

        $this->summaryStats = $costingService->getSummaryStatistics($this->costingsData);
    }

    public function filterByCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
        $this->refreshData();
    }

    public function updatedLaborPercentage(): void
    {
        $this->refreshData();
    }

    public function updatedMarkupPercentage(): void
    {
        $this->refreshData();
    }

    public function formatCurrency(float $amount): string
    {
        return $this->currency.' '.number_format($amount, 2);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('settings')
                ->label('Costing Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('info')
                ->form([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('labor_percentage')
                                ->label('Labor Cost %')
                                ->numeric()
                                ->default($this->laborPercentage)
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(100)
                                ->required(),

                            TextInput::make('markup_percentage')
                                ->label('Target Markup %')
                                ->numeric()
                                ->default($this->markupPercentage)
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(500)
                                ->required(),

                            Select::make('category_id')
                                ->label('Filter by Category')
                                ->options(fn (): array => ['' => 'All Categories'] + $this->categories->pluck('name', 'id')->toArray())
                                ->default($this->selectedCategoryId),
                        ]),
                ])
                ->action(function (array $data): void {
                    $this->laborPercentage = (float) $data['labor_percentage'];
                    $this->markupPercentage = (float) $data['markup_percentage'];
                    $this->selectedCategoryId = $data['category_id'] !== '' ? (int) $data['category_id'] : null;
                    $this->refreshData();

                    Notification::make()
                        ->title('Settings updated')
                        ->body('Costing calculations have been refreshed.')
                        ->success()
                        ->send();
                }),

            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(fn () => $this->refreshData()),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'costingsData' => $this->costingsData,
            'summaryStats' => $this->summaryStats,
            'currency' => $this->currency,
            'laborPercentage' => $this->laborPercentage,
            'markupPercentage' => $this->markupPercentage,
            'categories' => $this->categories,
            'selectedCategoryId' => $this->selectedCategoryId,
        ];
    }
}
