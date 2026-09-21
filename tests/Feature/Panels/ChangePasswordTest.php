<?php

use App\Filament\Pages\Auth\ChangePassword;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;

use function Pest\Livewire\livewire;

dataset('panel roles', [
    'field / sales_rep' => ['field', 'sales_rep'],
    'office / platform_admin' => ['office', 'platform_admin'],
    'office / accountant' => ['office', 'accountant'],
    'management / hq_lead' => ['management', 'hq_lead'],
    'management / regional_head' => ['management', 'regional_head'],
]);

test('every panel exposes a change-password page to its users', function (string $panel, string $role): void {
    $user = User::factory()->withRole($role)->create();

    $this->actingAs($user)->get("/{$panel}/change-password")
        ->assertOk()
        ->assertSee('Change password');
})->with('panel roles');

test('a user can change their password after confirming the current one', function (string $panel, string $role): void {
    Filament::setCurrentPanel(Filament::getPanel($panel));
    $user = User::factory()->withRole($role)->create();
    $this->actingAs($user);

    livewire(ChangePassword::class)
        ->fillForm([
            'currentPassword' => 'password',
            'password' => 'new-secret-456',
            'passwordConfirmation' => 'new-secret-456',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(Hash::check('new-secret-456', $user->refresh()->password))->toBeTrue();
})->with('panel roles');

test('the wrong current password is rejected', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));
    $user = User::factory()->withRole('sales_rep')->create();
    $this->actingAs($user);

    livewire(ChangePassword::class)
        ->fillForm([
            'currentPassword' => 'not-my-password',
            'password' => 'new-secret-456',
            'passwordConfirmation' => 'new-secret-456',
        ])
        ->call('save')
        ->assertHasFormErrors(['currentPassword']);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('a mismatched confirmation is rejected', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));
    $user = User::factory()->withRole('sales_rep')->create();
    $this->actingAs($user);

    livewire(ChangePassword::class)
        ->fillForm([
            'currentPassword' => 'password',
            'password' => 'new-secret-456',
            'passwordConfirmation' => 'different',
        ])
        ->call('save')
        ->assertHasFormErrors(['password']);
});

test('the new password is required', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));
    $this->actingAs(User::factory()->withRole('sales_rep')->create());

    livewire(ChangePassword::class)
        ->fillForm(['currentPassword' => 'password', 'password' => '', 'passwordConfirmation' => ''])
        ->call('save')
        ->assertHasFormErrors(['password' => 'required']);
});

test('the page does not let users edit their name or email', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('field'));
    $this->actingAs(User::factory()->withRole('sales_rep')->create());

    livewire(ChangePassword::class)
        ->assertFormFieldDoesNotExist('name')
        ->assertFormFieldDoesNotExist('email');
});

test('the user menu links to the change-password page', function (): void {
    $user = User::factory()->withRole('sales_rep')->create();

    $this->actingAs($user)->get('/field')->assertOk()->assertSee('/field/change-password');
});
