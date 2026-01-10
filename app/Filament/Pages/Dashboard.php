<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Illuminate\Support\Facades\Date;

final class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';

    /**
     * Helper method to get the date range based on filters
     *
     * @return array{start: \Illuminate\Support\Carbon, end: \Illuminate\Support\Carbon}
     */
    public static function getDateRangeFromFilters(array $filters): array
    {
        $period = $filters['period'] ?? 'today';

        return match ($period) {
            'today' => [
                'start' => Date::today()->startOfDay(),
                'end' => Date::today()->endOfDay(),
            ],
            'yesterday' => [
                'start' => Date::yesterday()->startOfDay(),
                'end' => Date::yesterday()->endOfDay(),
            ],
            'this_week' => [
                'start' => Date::now()->startOfWeek(),
                'end' => Date::now()->endOfWeek(),
            ],
            'last_week' => [
                'start' => Date::now()->subWeek()->startOfWeek(),
                'end' => Date::now()->subWeek()->endOfWeek(),
            ],
            'this_month' => [
                'start' => Date::now()->startOfMonth(),
                'end' => Date::now()->endOfMonth(),
            ],
            'last_month' => [
                'start' => Date::now()->subMonth()->startOfMonth(),
                'end' => Date::now()->subMonth()->endOfMonth(),
            ],
            'custom' => [
                'start' => isset($filters['startDate'])
                    ? Date::parse($filters['startDate'])->startOfDay()
                    : Date::today()->subDays(7)->startOfDay(),
                'end' => isset($filters['endDate'])
                    ? Date::parse($filters['endDate'])->endOfDay()
                    : Date::today()->endOfDay(),
            ],
            default => [
                'start' => Date::today()->startOfDay(),
                'end' => Date::today()->endOfDay(),
            ],
        };
    }

    /**
     * Get human-readable period label
     */
    public static function getPeriodLabel(array $filters): string
    {
        $period = $filters['period'] ?? 'today';

        return match ($period) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'custom' => 'Custom Range',
            default => 'Today',
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->label('Filter Analytics')
                ->icon('heroicon-o-funnel')
                ->schema([
                    Select::make('period')
                        ->label('Filter Period')
                        ->options([
                            'today' => 'Today',
                            'yesterday' => 'Yesterday',
                            'this_week' => 'This Week',
                            'last_week' => 'Last Week',
                            'this_month' => 'This Month',
                            'last_month' => 'Last Month',
                            'custom' => 'Custom Date Range',
                        ])
                        ->default('today')
                        ->live()
                        ->afterStateUpdated(function (callable $set, ?string $state): void {
                            if ($state !== 'custom') {
                                $set('startDate', null);
                                $set('endDate', null);
                            }
                        }),

                    DatePicker::make('startDate')
                        ->label('Start Date')
                        ->visible(fn ($get): bool => $get('period') === 'custom')
                        ->default(Date::today()->subDays(7))
                        ->maxDate(Date::today())
                        ->native(false)
                        ->required(fn ($get): bool => $get('period') === 'custom'),

                    DatePicker::make('endDate')
                        ->label('End Date')
                        ->visible(fn ($get): bool => $get('period') === 'custom')
                        ->default(Date::today())
                        ->maxDate(Date::today())
                        ->native(false)
                        ->required(fn ($get): bool => $get('period') === 'custom')
                        ->afterOrEqual('startDate'),
                ]),
        ];
    }
}
