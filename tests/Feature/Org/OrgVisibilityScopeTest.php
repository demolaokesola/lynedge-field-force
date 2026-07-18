<?php

use App\Models\Position;
use App\Models\Region;
use App\Models\Territory;
use App\Models\User;

beforeEach(function (): void {
    $this->regionA = Region::factory()->create();
    $this->regionB = Region::factory()->create();

    Territory::factory()->count(2)->for($this->regionA)->create();
    Territory::factory()->count(2)->for($this->regionB)->create();
});

dataset('national roles', [
    'superuser' => ['superuser'],
    'platform_admin' => ['platform_admin'],
    'hq_lead' => ['hq_lead'],
    'accountant' => ['accountant'],
]);

test('national roles see every region and territory', function (string $role): void {
    $user = User::factory()->withRole($role)->create();

    expect(Region::visibleOrgTo($user)->count())->toBe(2)
        ->and(Territory::visibleOrgTo($user)->count())->toBe(4);
})->with('national roles');

test('a regional_head sees only their region and its territories', function (): void {
    $head = User::factory()->withRole('regional_head')->inRegion($this->regionA)->create();

    expect(Region::visibleOrgTo($head)->pluck('id')->all())->toEqual([$this->regionA->id])
        ->and(Territory::visibleOrgTo($head)->count())->toBe(2)
        ->and(Territory::visibleOrgTo($head)->pluck('region_id')->unique()->all())
        ->toEqual([$this->regionA->id]);
});

test('reps see no regions or territories', function (): void {
    $user = User::factory()->withRole('sales_rep')->create();

    expect(Region::visibleOrgTo($user)->count())->toBe(0)
        ->and(Territory::visibleOrgTo($user)->count())->toBe(0);
});

test('a rep who supervises a position still sees no regions or territories', function (): void {
    $supervisor = User::factory()->withRole('sales_rep')->create();
    Position::factory()->create([
        'territory_id' => Territory::factory()->for($this->regionA),
        'supervisor_id' => $supervisor->id,
    ]);

    expect(Region::visibleOrgTo($supervisor)->count())->toBe(0)
        ->and(Territory::visibleOrgTo($supervisor)->count())->toBe(0);
});
