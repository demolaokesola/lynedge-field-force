<?php

namespace App\Providers\Filament;

use App\Filament\Shared\Resources\Calls\CallResource;
use App\Filament\Shared\Resources\Distributions\DistributionResource;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class FieldPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('field')
            ->path('field')
            ->login()
            ->colors([
                'primary' => Color::Emerald,
            ])
            // Mobile-first: top navigation collapses cleanly on small screens and the
            // content spans the full width for reps working on phones in the field.
            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Field/Resources'), for: 'App\Filament\Field\Resources')
            // Shared with the management panel — one class, behaviour varies by viewer.
            ->resources([
                CallResource::class,
                DistributionResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Field/Pages'), for: 'App\Filament\Field\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Field/Widgets'), for: 'App\Filament\Field\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->plugin(FilamentShieldPlugin::make())
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
