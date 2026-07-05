<?php

namespace Database\Seeders;

use App\Enums\TeamKind;
use App\Models\Product;
use App\Models\Relations\TeamMembership;
use App\Models\Team;
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
    }
}
