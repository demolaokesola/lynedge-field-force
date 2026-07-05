<?php

use App\Enums\PositionStatus;
use App\Models\Position;
use App\Models\Team;
use App\Models\Territory;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\PositionSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\TerritorySeeder;

test('the position seeder is idempotent and creates an active position per territory per strict team', function (): void {
    (new RegionSeeder)->run();
    (new TerritorySeeder)->run();
    (new MasterDataSeeder)->run();
    (new PositionSeeder)->run();
    (new PositionSeeder)->run();

    $territoryCount = Territory::count();
    $strictTeamCount = Team::whereIn('code', ['A', 'B'])->count();

    expect(Position::count())->toBe($territoryCount * $strictTeamCount)
        ->and(Position::where('status', PositionStatus::Active)->count())->toBe($territoryCount * $strictTeamCount);

    $benue = Territory::where('code', 'BENUE')->first();
    $teamA = Team::where('code', 'A')->first();

    $position = Position::where('territory_id', $benue->id)->where('team_id', $teamA->id)->first();

    expect($position)->not->toBeNull()
        ->and($position->code)->toBe('BENUE-A')
        ->and($position->enforce_team_uniqueness)->toBeTrue();
});
