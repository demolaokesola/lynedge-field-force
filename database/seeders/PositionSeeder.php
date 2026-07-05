<?php

namespace Database\Seeders;

use App\Enums\PositionStatus;
use App\Enums\TeamPolicy;
use App\Models\Position;
use App\Models\Team;
use App\Models\Territory;
use Illuminate\Database\Seeder;

/**
 * One active Position per (territory, strict team) pair, for Team A and Team B.
 *
 * Every seeded territory is strict ({@see TerritorySeeder}), and A/B are the strict
 * teams ({@see MasterDataSeeder}), so this is a valid full manning of the strict
 * partition. Idempotent: keyed on (territory_id, team_id) so re-running is safe.
 *
 * `code` and `enforce_team_uniqueness` are normally derived by PositionObserver, but
 * DatabaseSeeder runs under WithoutModelEvents (disabling events for every nested
 * seeder), so they're set explicitly here to match what the observer would compute.
 */
class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $teams = Team::whereIn('code', ['A', 'B'])->get();

        Territory::all()->each(
            fn (Territory $territory) => $teams->each(
                fn (Team $team) => Position::firstOrCreate(
                    ['territory_id' => $territory->id, 'team_id' => $team->id],
                    [
                        'code' => "{$territory->code}-{$team->code}",
                        'enforce_team_uniqueness' => $territory->team_policy === TeamPolicy::Strict,
                        'status' => PositionStatus::Active,
                    ],
                )
            )
        );
    }
}
