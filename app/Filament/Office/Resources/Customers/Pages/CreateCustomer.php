<?php

namespace App\Filament\Office\Resources\Customers\Pages;

use App\Filament\Office\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
