<?php

use App\Models\User;

dataset('role panel redirects', [
    'sales_rep' => ['sales_rep', '/field'],
    'platform_admin' => ['platform_admin', '/office'],
    'accountant' => ['accountant', '/office'],
    'hq_lead' => ['hq_lead', '/management'],
    'regional_head' => ['regional_head', '/management'],
    'superuser' => ['superuser', '/office'],
]);

test('a guest sees the centralized login form at the root', function (): void {
    $this->get('/')->assertOk()->assertSee('Sign in');
});

test('logging in redirects each role to its own panel', function (string $role, string $panelPath): void {
    $user = User::factory()->withRole($role)->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect($panelPath);

    $this->assertAuthenticatedAs($user);
})->with('role panel redirects');

test('an already authenticated user is redirected away from the login form', function (): void {
    $user = User::factory()->withRole('sales_rep')->create();

    $this->actingAs($user)->get('/')->assertRedirect('/field');
});

test('invalid credentials are rejected with a validation error', function (): void {
    $user = User::factory()->withRole('sales_rep')->create();

    $this->from('/')->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertRedirect('/')->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a user with no panel-mapped role cannot log in', function (): void {
    $user = User::factory()->create();

    $this->from('/')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/')->assertSessionHasErrors('email');

    $this->assertGuest();
});
