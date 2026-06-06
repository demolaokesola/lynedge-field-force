<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
use App\Enums\TeamKind;
use App\Enums\TeamPolicy;
use App\Models\Customer;
use App\Models\DemandCreator;
use App\Models\DemandCreatorType;
use App\Models\Product;
use App\Models\Region;
use App\Models\Relations\TeamMembership;
use App\Models\Team;
use App\Models\Territory;
use Illuminate\Database\Seeder;

/**
 * The §2.0 worked example plus realistic Nigerian demo data.
 *
 * Products AA–FF; strict Teams A & B; liberal Teams C & D. Every product sits in
 * exactly one strict team, so the catalogue is a valid disjoint strict partition and
 * the attach guard ({@see TeamMembership}) passes throughout.
 * Idempotent: keyed on sku / code / name so re-running is safe.
 */
class MasterDataSeeder extends Seeder
{
    /**
     * sku => [name, list of team codes it belongs to].
     *
     * @var array<string, array{string, list<string>}>
     */
    private const PRODUCTS = [
        'AA' => ['Amoxil 500 Capsules', ['A', 'C', 'D']],
        'BB' => ['Brufen 400 Tablets', ['A', 'C', 'D']],
        'CC' => ['Ciproxin 500 Tablets', ['A', 'C', 'D']],
        'DD' => ['Daktarin Cream', ['B', 'C', 'D']],
        'EE' => ['Emzor Paracetamol Syrup', ['B', 'C']],
        'FF' => ['Flagyl 200 Suspension', ['B', 'D']],
    ];

    /**
     * code => [name, kind].
     *
     * @var array<string, array{string, TeamKind}>
     */
    private const TEAMS = [
        'A' => ['Team A', TeamKind::Strict],
        'B' => ['Team B', TeamKind::Strict],
        'C' => ['Team C', TeamKind::Liberal],
        'D' => ['Team D', TeamKind::Liberal],
    ];

    public function run(): void
    {
        $teams = collect(self::TEAMS)->mapWithKeys(fn (array $spec, string $code): array => [
            $code => Team::firstOrCreate(
                ['code' => $code],
                ['name' => $spec[0], 'kind' => $spec[1], 'active' => true],
            ),
        ]);

        foreach (self::PRODUCTS as $sku => [$name, $teamCodes]) {
            $product = Product::firstOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'pack_size' => '10 x 10 tablets',
                    'unit_price' => fake()->randomFloat(2, 800, 12000),
                    'active' => true,
                ],
            );

            $product->teams()->sync($teamCodes === [] ? [] : $teams->only($teamCodes)->pluck('id')->all());
        }

        $this->seedTerritoryData();
    }

    private function seedTerritoryData(): void
    {
        $region = Region::firstOrCreate(['code' => 'SW'], ['name' => 'South West']);

        $lagos = Territory::firstOrCreate(
            ['code' => 'LAG-101'],
            ['region_id' => $region->id, 'name' => 'Lagos Mainland', 'team_policy' => TeamPolicy::Strict],
        );
        $ibadan = Territory::firstOrCreate(
            ['code' => 'IBA-102'],
            ['region_id' => $region->id, 'name' => 'Ibadan', 'team_policy' => TeamPolicy::Liberal],
        );

        foreach ([$lagos, $ibadan] as $territory) {
            Customer::firstOrCreate(
                ['territory_id' => $territory->id, 'name' => "HealthPlus {$territory->name}"],
                ['type' => CustomerType::Pharmacy, 'phone' => '08030000000', 'address' => "1 Market Rd, {$territory->name}"],
            );
            Customer::firstOrCreate(
                ['territory_id' => $territory->id, 'name' => "{$territory->name} General Hospital"],
                ['type' => CustomerType::Hospital, 'phone' => '08030000001', 'address' => "Hospital Rd, {$territory->name}"],
            );

            $type = DemandCreatorType::firstOrCreate(['name' => 'Hospital Pharmacist']);
            DemandCreator::firstOrCreate(
                ['territory_id' => $territory->id, 'name' => "Pharm. {$territory->name} Lead"],
                [
                    'demand_creator_type_id' => $type->id,
                    'affiliation' => "{$territory->name} General Hospital",
                    'phone' => '08030000002',
                    'address' => "Hospital Rd, {$territory->name}",
                ],
            );
        }
    }
}
