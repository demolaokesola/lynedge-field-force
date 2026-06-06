<?php

use App\Filament\Shared\Resources\Calls\CallResource;
use Filament\Facades\Filament;

test('the Calls resource is registered in the field and management panels, not office', function (): void {
    $field = Filament::getPanel('field')->getResources();
    $management = Filament::getPanel('management')->getResources();
    $office = Filament::getPanel('office')->getResources();

    expect($field)->toContain(CallResource::class)
        ->and($management)->toContain(CallResource::class)
        ->and($office)->not->toContain(CallResource::class);
});
