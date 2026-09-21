<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('the login page links to forgot password', function (): void {
    $this->get('/')->assertOk()->assertSee(route('password.request'));
});

test('the forgot-password page renders', function (): void {
    $this->get('/forgot-password')->assertOk()->assertSee('Send reset link');
});

test('a known email receives a reset link pointing at the reset page', function (): void {
    Notification::fake();
    $user = User::factory()->withRole('sales_rep')->create();

    $this->from('/forgot-password')
        ->post('/forgot-password', ['email' => $user->email])
        ->assertRedirect('/forgot-password')
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $url = $notification->toMail($user)->actionUrl;

        return str_starts_with($url, url('/reset-password/'.$notification->token))
            && Password::broker()->tokenExists($user, $notification->token);
    });
});

test('an unknown email gets the same response and no email', function (): void {
    Notification::fake();

    $this->from('/forgot-password')
        ->post('/forgot-password', ['email' => 'nobody@example.com'])
        ->assertRedirect('/forgot-password')
        ->assertSessionHas('status')
        ->assertSessionHasNoErrors();

    Notification::assertNothingSent();
});

test('the reset page renders with the email pre-filled', function (): void {
    $user = User::factory()->withRole('sales_rep')->create();
    $token = Password::broker()->createToken($user);

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('Reset password')
        ->assertSee($user->email);
});

test('a valid token updates the password and returns the user to the login page', function (): void {
    $user = User::factory()->withRole('sales_rep')->create();
    $token = Password::broker()->createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'fresh-secret-123',
        'password_confirmation' => 'fresh-secret-123',
    ])->assertRedirect('/')->assertSessionHas('status');

    $this->assertGuest();
    expect(Hash::check('fresh-secret-123', $user->refresh()->password))->toBeTrue()
        ->and(Password::broker()->tokenExists($user, $token))->toBeFalse();

    $this->post('/login', ['email' => $user->email, 'password' => 'fresh-secret-123'])->assertRedirect('/field');
});

test('an invalid token is rejected', function (): void {
    $user = User::factory()->withRole('sales_rep')->create();
    Password::broker()->createToken($user);

    $this->from('/reset-password/bad')->post('/reset-password', [
        'token' => 'bad',
        'email' => $user->email,
        'password' => 'fresh-secret-123',
        'password_confirmation' => 'fresh-secret-123',
    ])->assertRedirect('/reset-password/bad')->assertSessionHasErrors('email');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('an expired reset token is rejected', function (): void {
    $user = User::factory()->withRole('sales_rep')->create();
    $token = Password::broker()->createToken($user);

    $this->travel(61)->minutes();

    $this->from('/reset-password/'.$token)->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'fresh-secret-123',
        'password_confirmation' => 'fresh-secret-123',
    ])->assertSessionHasErrors('email');
});

test('a signed-in user is sent to their panel instead of the guest pages', function (): void {
    $user = User::factory()->withRole('sales_rep')->create();

    $this->actingAs($user)->get('/forgot-password')->assertRedirect();
});
