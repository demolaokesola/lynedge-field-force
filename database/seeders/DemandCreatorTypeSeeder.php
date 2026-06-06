<?php

namespace Database\Seeders;

use App\Models\DemandCreatorType;
use Illuminate\Database\Seeder;

class DemandCreatorTypeSeeder extends Seeder
{
    /**
     * The fixed lookup of demand-creator categories (docs/playbook.md §1).
     *
     * @var list<string>
     */
    public const TYPES = [
        'Hospital Pharmacist',
        'Prescribing Community Pharmacist',
        'Doctor/CHEW',
        'Laboratory Scientist',
        'Public Mobilization Place',
        'Merchandizer',
    ];

    public function run(): void
    {
        foreach (self::TYPES as $name) {
            DemandCreatorType::firstOrCreate(['name' => $name]);
        }
    }
}
