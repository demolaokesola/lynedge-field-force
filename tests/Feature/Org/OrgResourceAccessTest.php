<?php

use App\Models\User;

dataset('org resource paths', [
    'regions' => ['/office/regions'],
    'territories' => ['/office/territories'],
    'teams' => ['/office/teams'],
    'users' => ['/office/users'],
]);

test('platform_admin may view every org resource list', function (string $path): void {
    $admin = User::factory()->withRole('platform_admin')->create();

    $this->actingAs($admin)->get($path)->assertOk();
})->with('org resource paths');

test('superuser may view every org resource list via Gate::before', function (string $path): void {
    $superuser = User::factory()->withRole('superuser')->create();

    $this->actingAs($superuser)->get($path)->assertOk();
})->with('org resource paths');

test('accountant is denied every org resource list by the viewAny policy', function (string $path): void {
    // Accountant CAN enter the office panel, so this proves resource-level (canViewAny)
    // gating, independent of the panel entry gate.
    $accountant = User::factory()->withRole('accountant')->create();

    $this->actingAs($accountant)->get($path)->assertForbidden();
})->with('org resource paths');

test('a sales_rep is denied every org resource list', function (string $path): void {
    $rep = User::factory()->withRole('sales_rep')->create();

    $this->actingAs($rep)->get($path)->assertForbidden();
})->with('org resource paths');
