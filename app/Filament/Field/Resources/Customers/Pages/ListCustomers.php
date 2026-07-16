<?php

namespace App\Filament\Field\Resources\Customers\Pages;

use App\Filament\Field\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Navigate to the dedicated create page instead of opening the default
            // modal — position_id -> territory_id derivation only happens there
            // (see CreateCustomer::mutateFormDataBeforeCreate).
            CreateAction::make()
                ->url(fn (): string => CustomerResource::getUrl('create')),
        ];
    }
}
