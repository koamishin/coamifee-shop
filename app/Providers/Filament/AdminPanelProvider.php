<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Widgets\CoffeeShopOverviewWidget;
use App\Filament\Widgets\FinancialSummaryWidget;
use App\Filament\Widgets\LowStockAlertWidget;
use App\Filament\Widgets\OrderStatusWidget;
use App\Filament\Widgets\SalesTrendsWidget;
use App\Filament\Widgets\TopProductsWidget;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
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
use Joaopaulolndev\FilamentGeneralSettings\FilamentGeneralSettingsPlugin;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->topbar(false)
            ->spa(true)
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: "App\Filament\Resources",
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: "App\Filament\Pages",
            )
            ->pages([Dashboard::class])
            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: "App\Filament\Widgets",
            )
            ->widgets([
                CoffeeShopOverviewWidget::class,
                SalesTrendsWidget::class,
                TopProductsWidget::class,
                // idget::class,
                // FinancialSummaryWidget::class,
                OrderStatusWidget::class,
                LowStockAlertWidget::class,
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
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentGeneralSettingsPlugin::make()
                    ->setSort(3)
                    ->setIcon('heroicon-o-cog')
                    ->setNavigationGroup('Settings')
                    ->setTitle('General Settings')
                    ->setNavigationLabel('General Settings'),
            ])
            ->authMiddleware([Authenticate::class])
            // Configure demo mode restrictions
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
            });
    }
}
