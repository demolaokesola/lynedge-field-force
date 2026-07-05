<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

/**
 * The four Nigerian sales regions. Idempotent: keyed on code so re-running is safe.
 */
class RegionSeeder extends Seeder
{
    /**
     * code => name.
     *
     * @var array<string, string>
     */
    public const REGIONS = [
        'NO' => 'NORTH',
        'SE' => 'SOUTH EAST',
        'SS' => 'SOUTH SOUTH',
        'SW' => 'SOUTH WEST',
    ];

    public function run(): void
    {
        foreach (self::REGIONS as $code => $name) {
            Region::firstOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
