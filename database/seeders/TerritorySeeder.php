<?php

namespace Database\Seeders;

use App\Enums\TeamPolicy;
use App\Models\Region;
use App\Models\Territory;
use Illuminate\Database\Seeder;

/**
 * The Nigerian sales territories, grouped under {@see RegionSeeder::REGIONS}.
 * Every territory defaults to a strict team policy. Idempotent: keyed on code
 * so re-running is safe.
 */
class TerritorySeeder extends Seeder
{
    /**
     * code => [name, region code].
     *
     * @var array<string, array{string, string}>
     */
    public const TERRITORIES = [
        // NORTH
        'BENUE' => ['BENUE', 'NO'],
        'FCT-1' => ['FCT 1', 'NO'],
        'FCT-2' => ['FCT 2', 'NO'],
        'FCT-3' => ['FCT 3', 'NO'],
        'GOMBE' => ['GOMBE', 'NO'],
        'KADUNA' => ['KADUNA', 'NO'],
        'KANO' => ['KANO', 'NO'],
        'PLATEAU' => ['PLATEAU', 'NO'],
        'SOKOTO' => ['SOKOTO', 'NO'],

        // SOUTH EAST
        'ABA-1' => ['ABA 1', 'SE'],
        'ABA-2' => ['ABA 2', 'SE'],
        'ABIA' => ['ABIA', 'SE'],
        'AWKA' => ['AWKA', 'SE'],
        'EBONYI' => ['EBONYI', 'SE'],
        'ENUGU-1' => ['ENUGU 1', 'SE'],
        'ENUGU-2' => ['ENUGU 2', 'SE'],
        'IMO-1' => ['IMO 1', 'SE'],
        'IMO-2' => ['IMO 2', 'SE'],
        'IMO-3' => ['IMO 3', 'SE'],
        'ONITSHA-1' => ['ONITSHA 1', 'SE'],
        'ONITSHA-2' => ['ONITSHA 2', 'SE'],

        // SOUTH SOUTH
        'AKWA-IBOM-1' => ['AKWA IBOM 1', 'SS'],
        'AKWA-IBOM-2' => ['AKWA IBOM 2', 'SS'],
        'BAYELSA' => ['BAYELSA', 'SS'],
        'CROSS-RIVERS' => ['CROSS RIVERS', 'SS'],
        'DELTA-1' => ['DELTA 1', 'SS'],
        'DELTA-2' => ['DELTA 2', 'SS'],
        'EDO-1' => ['EDO 1', 'SS'],
        'EDO-2' => ['EDO 2', 'SS'],
        'RIVERS-1' => ['RIVERS 1', 'SS'],
        'RIVERS-2' => ['RIVERS 2', 'SS'],
        'RIVERS-3' => ['RIVERS 3', 'SS'],

        // SOUTH WEST
        'KWARA' => ['KWARA', 'SW'],
        'LAGOS-1' => ['LAGOS 1', 'SW'],
        'LAGOS-2' => ['LAGOS 2', 'SW'],
        'LAGOS-3' => ['LAGOS 3', 'SW'],
        'LAGOS-4' => ['LAGOS 4', 'SW'],
        'LAGOS-5' => ['LAGOS 5', 'SW'],
        'LAGOS-6' => ['LAGOS 6', 'SW'],
        'LAGOS-7' => ['LAGOS 7', 'SW'],
        'LAGOS-8' => ['LAGOS 8', 'SW'],
        'LAGOS-9' => ['LAGOS 9', 'SW'],
        'OGUN' => ['OGUN', 'SW'],
        'ONDO' => ['ONDO', 'SW'],
        'OSUN' => ['OSUN', 'SW'],
        'OYO-1' => ['OYO 1', 'SW'],
        'OYO-2' => ['OYO 2', 'SW'],
    ];

    public function run(): void
    {
        $regions = Region::whereIn('code', array_unique(array_column(self::TERRITORIES, 1)))
            ->get()
            ->keyBy('code');

        foreach (self::TERRITORIES as $code => [$name, $regionCode]) {
            Territory::firstOrCreate(
                ['code' => $code],
                [
                    'region_id' => $regions[$regionCode]->id,
                    'name' => $name,
                    'team_policy' => TeamPolicy::Strict,
                ],
            );
        }
    }
}
