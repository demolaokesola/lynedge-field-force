<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\FieldPanelProvider;
use App\Providers\Filament\ManagementPanelProvider;
use App\Providers\Filament\OfficePanelProvider;
use App\Providers\ObserverServiceProvider;

return [
    AppServiceProvider::class,
    FieldPanelProvider::class,
    ManagementPanelProvider::class,
    OfficePanelProvider::class,
    ObserverServiceProvider::class,
];
