<?php

namespace App\Filament\Field\Resources\Customers\Pages;

use App\Filament\Field\Resources\Customers\CustomerResource;
use App\Filament\Field\Resources\Customers\Widgets\CustomerActivityWidget;
use App\Filament\Field\Resources\Customers\Widgets\CustomerDepositsWidget;
use App\Filament\Field\Resources\Customers\Widgets\CustomerDistributionsWidget;
use App\Filament\Field\Resources\Customers\Widgets\CustomerReconciliationWidget;
use App\Filament\Field\Resources\Customers\Widgets\CustomerTopProductsWidget;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            CustomerReconciliationWidget::class,
            CustomerActivityWidget::class,
            CustomerTopProductsWidget::class,
            CustomerDistributionsWidget::class,
            CustomerDepositsWidget::class,
        ];
    }
}
