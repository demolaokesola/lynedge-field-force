<?php

use App\Enums\TeamKind;
use App\Enums\TeamPolicy;
use App\Models\Region;
use App\Models\Team;
use App\Models\Territory;
use App\Models\User;

test('a region has many territories', function (): void {
    $region = Region::factory()->create();
    $territories = Territory::factory()->count(2)->for($region)->create();

    expect($region->territories)->toHaveCount(2)
        ->and($region->territories->pluck('id')->sort()->values()->all())
        ->toEqual($territories->pluck('id')->sort()->values()->all());
});

test('a territory belongs to a region and casts its policy', function (): void {
    $territory = Territory::factory()->strict()->create();

    expect($territory->region)->toBeInstanceOf(Region::class)
        ->and($territory->team_policy)->toBe(TeamPolicy::Strict);
});

test('a team casts its kind and active flag', function (): void {
    $team = Team::factory()->liberal()->create();

    expect($team->kind)->toBe(TeamKind::Liberal)
        ->and($team->active)->toBeTrue();
});

test('a user may be anchored to a region', function (): void {
    $region = Region::factory()->create();
    $user = User::factory()->inRegion($region)->create();

    expect($user->region)->toBeInstanceOf(Region::class)
        ->and($user->region->is($region))->toBeTrue()
        ->and($user->is_active)->toBeTrue();
});

test('a rep created without a region has a null region anchor', function (): void {
    $rep = User::factory()->withRole('sales_rep')->create();

    expect($rep->region_id)->toBeNull()
        ->and($rep->region)->toBeNull();
});
