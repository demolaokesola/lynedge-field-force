<?php

use App\Models\User;

dataset('panel paths', [
    'field' => ['sales_rep', '/field'],
    'office' => ['platform_admin', '/office'],
    'management' => ['hq_lead', '/management'],
]);

test('a permitted user loads the panel dashboard', function (string $role, string $path): void {
    $user = User::factory()->withRole($role)->create();

    $this->actingAs($user)->get($path)->assertOk();
})->with('panel paths');

test('a user without the right role is denied entry to the panel', function (): void {
    $salesRep = User::factory()->withRole('sales_rep')->create();

    $this->actingAs($salesRep)->get('/office')->assertForbidden();
});

test('a guest is redirected to the panel login', function (): void {
    $this->get('/office')->assertRedirect('/office/login');
});
