<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosApiController;
use App\Livewire\Pos;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', fn (): Factory|View => view('welcome'))->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('pos', Pos::class)
    ->middleware(['auth'])
    ->name('pos');

// POS API Routes
Route::prefix('pos/api')->middleware(['auth'])->group(function () {
    Route::get('products', [PosApiController::class, 'getProducts']);
    Route::get('categories', [PosApiController::class, 'getCategories']);
    Route::post('add-to-cart', [PosApiController::class, 'addToCart']);
    Route::post('calculate-totals', [PosApiController::class, 'calculateTotals']);
    Route::post('checkout', [PosApiController::class, 'checkout']);
    Route::get('quick-items', [PosApiController::class, 'getQuickItems']);
    Route::get('best-sellers', [PosApiController::class, 'getBestSellers']);
    Route::get('recent-orders', [PosApiController::class, 'getRecentOrders']);
    Route::get('stats', [PosApiController::class, 'getStats']);
});

Route::middleware(['auth'])->group(function (): void {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
