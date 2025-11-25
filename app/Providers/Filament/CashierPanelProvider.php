<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\OrdersProcessing;
use App\Filament\Pages\PosPage;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class CashierPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('cashier')
            ->path('cashier')
            ->login()
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->topNavigation()
            ->discoverResources(in: app_path('Filament/Cashier/Resources'), for: 'App\Filament\Cashier\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                PosPage::class,
                OrdersProcessing::class,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')

            // ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->when(config('app.env') === 'demo', function (Panel $panel) {
                // Apply production-like restrictions for demo mode
                $panel
                    ->renderHook(
                        'panels::head.end',
                        fn (): string => '<style>
                            .demo-banner {
                                background: #f59e0b;
                                color: white;
                                padding: 8px 16px;
                                text-align: center;
                                font-weight: 600;
                         
                            }
                        </style>
                        <div class="demo-banner">
                            DEMO MODE - This is a demonstration environment
                        </div>'
                    );
            });;
    }
}
