<?php

use App\Filament\Field\Clusters\CustomerDataCluster;
use App\Filament\Field\Resources\Customers\CustomerResource as FieldCustomerResource;
use App\Filament\Field\Resources\DemandCreators\DemandCreatorResource as FieldDemandCreatorResource;
use App\Filament\Office\Resources\Customers\CustomerResource as OfficeCustomerResource;
use App\Filament\Office\Resources\DemandCreators\DemandCreatorResource as OfficeDemandCreatorResource;
use Filament\Facades\Filament;

test('the My Contacts cluster and its resources are registered in the field panel only', function (): void {
    $field = Filament::getPanel('field');
    $office = Filament::getPanel('office');
    $management = Filament::getPanel('management');

    expect($field->getClusters())->toContain(CustomerDataCluster::class);

    expect($field->getResources())->toContain(FieldCustomerResource::class)
        ->toContain(FieldDemandCreatorResource::class);

    expect($office->getResources())->not->toContain(FieldCustomerResource::class)
        ->not->toContain(FieldDemandCreatorResource::class);

    expect($management->getResources())->not->toContain(FieldCustomerResource::class)
        ->not->toContain(FieldDemandCreatorResource::class);
});

test('the office admin Customer/DemandCreator resources remain office-only', function (): void {
    $office = Filament::getPanel('office');
    $field = Filament::getPanel('field');
    $management = Filament::getPanel('management');

    expect($office->getResources())->toContain(OfficeCustomerResource::class)
        ->toContain(OfficeDemandCreatorResource::class);

    expect($field->getResources())->not->toContain(OfficeCustomerResource::class)
        ->not->toContain(OfficeDemandCreatorResource::class);

    expect($management->getResources())->not->toContain(OfficeCustomerResource::class)
        ->not->toContain(OfficeDemandCreatorResource::class);
});
