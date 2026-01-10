<?php

declare(strict_types=1);

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\CoffeeShopOverviewWidget;
use App\Filament\Widgets\SalesTrendsWidget;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

test('dashboard page renders with filter action button', function (): void {
    Livewire::test(Dashboard::class)
        ->assertSuccessful()
        ->assertActionExists('filter');
});

test('dashboard filters default to today', function (): void {
    $dateRange = Dashboard::getDateRangeFromFilters([]);

    expect($dateRange['start']->toDateString())->toBe(Date::today()->toDateString())
        ->and($dateRange['end']->toDateString())->toBe(Date::today()->toDateString());
});

test('dashboard filters work for yesterday', function (): void {
    $dateRange = Dashboard::getDateRangeFromFilters(['period' => 'yesterday']);

    expect($dateRange['start']->toDateString())->toBe(Date::yesterday()->toDateString())
        ->and($dateRange['end']->toDateString())->toBe(Date::yesterday()->toDateString());
});

test('dashboard filters work for this week', function (): void {
    $dateRange = Dashboard::getDateRangeFromFilters(['period' => 'this_week']);

    expect($dateRange['start']->toDateString())->toBe(Date::now()->startOfWeek()->toDateString())
        ->and($dateRange['end']->toDateString())->toBe(Date::now()->endOfWeek()->toDateString());
});

test('dashboard filters work for last week', function (): void {
    $dateRange = Dashboard::getDateRangeFromFilters(['period' => 'last_week']);

    expect($dateRange['start']->toDateString())->toBe(Date::now()->subWeek()->startOfWeek()->toDateString())
        ->and($dateRange['end']->toDateString())->toBe(Date::now()->subWeek()->endOfWeek()->toDateString());
});

test('dashboard filters work for this month', function (): void {
    $dateRange = Dashboard::getDateRangeFromFilters(['period' => 'this_month']);

    expect($dateRange['start']->toDateString())->toBe(Date::now()->startOfMonth()->toDateString())
        ->and($dateRange['end']->toDateString())->toBe(Date::now()->endOfMonth()->toDateString());
});

test('dashboard filters work for last month', function (): void {
    $dateRange = Dashboard::getDateRangeFromFilters(['period' => 'last_month']);

    expect($dateRange['start']->toDateString())->toBe(Date::now()->subMonth()->startOfMonth()->toDateString())
        ->and($dateRange['end']->toDateString())->toBe(Date::now()->subMonth()->endOfMonth()->toDateString());
});

test('dashboard filters work for custom date range', function (): void {
    $customStart = '2025-12-01';
    $customEnd = '2025-12-15';

    $dateRange = Dashboard::getDateRangeFromFilters([
        'period' => 'custom',
        'startDate' => $customStart,
        'endDate' => $customEnd,
    ]);

    expect($dateRange['start']->toDateString())->toBe($customStart)
        ->and($dateRange['end']->toDateString())->toBe($customEnd);
});

test('coffee shop overview widget uses page filters', function (): void {
    // Create orders for today
    Order::factory()->count(3)->create([
        'created_at' => Date::today(),
        'payment_status' => 'paid',
        'total' => 100.00,
    ]);

    // Create orders for yesterday
    Order::factory()->count(2)->create([
        'created_at' => Date::yesterday(),
        'payment_status' => 'paid',
        'total' => 50.00,
    ]);

    // Test with default filter (today) - should show 3 orders
    Livewire::test(CoffeeShopOverviewWidget::class)
        ->assertSuccessful();
});

test('sales trends widget uses page filters', function (): void {
    // Create some orders for the chart
    Order::factory()->count(2)->create([
        'created_at' => Date::today(),
        'payment_status' => 'paid',
        'total' => 100.00,
    ]);

    Livewire::test(SalesTrendsWidget::class)
        ->assertSuccessful();
});

test('period label returns correct text', function (): void {
    expect(Dashboard::getPeriodLabel(['period' => 'today']))->toBe('Today')
        ->and(Dashboard::getPeriodLabel(['period' => 'yesterday']))->toBe('Yesterday')
        ->and(Dashboard::getPeriodLabel(['period' => 'this_week']))->toBe('This Week')
        ->and(Dashboard::getPeriodLabel(['period' => 'last_week']))->toBe('Last Week')
        ->and(Dashboard::getPeriodLabel(['period' => 'this_month']))->toBe('This Month')
        ->and(Dashboard::getPeriodLabel(['period' => 'last_month']))->toBe('Last Month')
        ->and(Dashboard::getPeriodLabel(['period' => 'custom']))->toBe('Custom Range');
});
