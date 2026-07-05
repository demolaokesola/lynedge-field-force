<?php

use App\Enums\TeamPolicy;
use App\Models\Region;
use App\Models\Territory;
use Database\Seeders\RegionSeeder;
use Database\Seeders\TerritorySeeder;

test('the region seeder is idempotent and seeds all regions', function (): void {
    (new RegionSeeder)->run();
    (new RegionSeeder)->run();

    expect(Region::count())->toBe(count(RegionSeeder::REGIONS));
});

test('the territory seeder is idempotent, links the right region, and defaults to strict policy', function (): void {
    (new RegionSeeder)->run();
    (new TerritorySeeder)->run();
    (new TerritorySeeder)->run();

    expect(Territory::count())->toBe(count(TerritorySeeder::TERRITORIES));

    $benue = Territory::where('code', 'BENUE')->first();

    expect($benue->region->code)->toBe('NO')
        ->and($benue->team_policy)->toBe(TeamPolicy::Strict);
});
