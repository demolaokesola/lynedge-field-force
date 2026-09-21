<?php

use App\Filament\Office\Resources\Users\Pages\CreateUser;
use App\Filament\Office\Resources\Users\Pages\EditUser;
use App\Filament\Office\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Notifications\UserInvitation;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

dataset('role panel redirects', [
    'sales_rep' => ['sales_rep', '/field'],
    'platform_admin' => ['platform_admin', '/office'],
    'accountant' => ['accountant', '/office'],
    'hq_lead' => ['hq_lead', '/management'],
    'regional_head' => ['regional_head', '/management'],
]);

/**
 * Issues a fresh invitation token for the user and returns the link the email carries.
 */
function invitationUrlFor(User $user): string
{
    $token = Password::broker('invitations')->createToken($user);

    return (new UserInvitation($token))->url($user);
}

describe('creating a user from the Office panel', function (): void {
    beforeEach(function (): void {
        Notification::fake();
        Filament::setCurrentPanel(Filament::getPanel('office'));
        $this->actingAs(User::factory()->withRole('platform_admin')->create());
    });

    test('stores the user without a password and emails an invitation', function (): void {
        $role = Role::findOrCreate('sales_rep', 'web');

        livewire(CreateUser::class)
            ->fillForm([
                'name' => 'Ada Rep',
                'email' => 'ada@example.com',
                'roles' => [$role->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Invitation sent to ada@example.com');

        $user = User::query()->where('email', 'ada@example.com')->firstOrFail();

        expect($user->password)->toBeNull()
            ->and($user->invited_at)->not->toBeNull()
            ->and($user->hasSetPassword())->toBeFalse();

        Notification::assertSentTo($user, UserInvitation::class, function (UserInvitation $notification) use ($user): bool {
            return Password::broker('invitations')->tokenExists($user, $notification->token)
                && str_contains($notification->url($user), '/invitation/'.$notification->token);
        });
    });

    test('the form no longer exposes a password field', function (): void {
        livewire(CreateUser::class)->assertFormFieldDoesNotExist('password');
    });
});

describe('resending an invitation', function (): void {
    beforeEach(function (): void {
        Notification::fake();
        Filament::setCurrentPanel(Filament::getPanel('office'));
    });

    test('a platform admin can resend from the users table and the earlier token stops working', function (): void {
        $this->actingAs(User::factory()->withRole('platform_admin')->create());
        $invited = User::factory()->invited()->withRole('sales_rep')->create();
        $oldToken = Password::broker('invitations')->createToken($invited);

        livewire(ListUsers::class)
            ->callAction(TestAction::make('resendInvitation')->table($invited))
            ->assertNotified("Invitation sent to {$invited->email}");

        Notification::assertSentTo($invited, UserInvitation::class);
        expect(Password::broker('invitations')->tokenExists($invited, $oldToken))->toBeFalse()
            ->and(DB::table('password_reset_tokens')->where('email', $invited->email)->count())->toBe(1);
    });

    test('a platform admin can resend from the edit page', function (): void {
        $this->actingAs(User::factory()->withRole('platform_admin')->create());
        $invited = User::factory()->invited()->withRole('sales_rep')->create();

        livewire(EditUser::class, ['record' => $invited->id])
            ->callAction('resendInvitation')
            ->assertNotified();

        Notification::assertSentTo($invited, UserInvitation::class);
    });

    test('the action is hidden once the user has set a password', function (): void {
        $this->actingAs(User::factory()->withRole('platform_admin')->create());
        $active = User::factory()->withRole('sales_rep')->create();

        livewire(ListUsers::class)
            ->assertActionHidden(TestAction::make('resendInvitation')->table($active));
    });

    test('an accountant cannot resend invitations', function (): void {
        $this->actingAs(User::factory()->withRole('accountant')->create());
        $invited = User::factory()->invited()->withRole('sales_rep')->create();

        expect(auth()->user()->can('invite', $invited))->toBeFalse();
    });
});

describe('accepting an invitation', function (): void {
    test('the link shows the set-password form', function (): void {
        $invited = User::factory()->invited()->withRole('sales_rep')->create();

        $this->get(invitationUrlFor($invited))
            ->assertOk()
            ->assertSee('Set password and sign in')
            ->assertSee($invited->email);
    });

    test('setting a password signs the user in and opens their panel', function (string $role, string $panelPath): void {
        $invited = User::factory()->invited()->withRole($role)->create();
        $token = Password::broker('invitations')->createToken($invited);

        $this->post('/invitation', [
            'token' => $token,
            'email' => $invited->email,
            'password' => 'brand-new-secret',
            'password_confirmation' => 'brand-new-secret',
        ])->assertRedirect($panelPath);

        $this->assertAuthenticatedAs($invited);

        $invited->refresh();
        expect($invited->hasSetPassword())->toBeTrue()
            ->and(Hash::check('brand-new-secret', $invited->password))->toBeTrue()
            ->and($invited->email_verified_at)->not->toBeNull()
            ->and(Password::broker('invitations')->tokenExists($invited, $token))->toBeFalse();
    })->with('role panel redirects');

    test('a mismatched confirmation is rejected', function (): void {
        $invited = User::factory()->invited()->withRole('sales_rep')->create();
        $token = Password::broker('invitations')->createToken($invited);

        $this->from('/invitation/'.$token)->post('/invitation', [
            'token' => $token,
            'email' => $invited->email,
            'password' => 'brand-new-secret',
            'password_confirmation' => 'something-else',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        expect($invited->refresh()->password)->toBeNull();
    });

    test('an expired link is rejected on both the page and the form', function (): void {
        $invited = User::factory()->invited()->withRole('sales_rep')->create();
        $token = Password::broker('invitations')->createToken($invited);
        $url = (new UserInvitation($token))->url($invited);

        $this->travel(25)->hours();

        $this->get($url)
            ->assertOk()
            ->assertSee('invalid or has expired')
            ->assertDontSee('Set password and sign in');

        $this->from($url)->post('/invitation', [
            'token' => $token,
            'email' => $invited->email,
            'password' => 'brand-new-secret',
            'password_confirmation' => 'brand-new-secret',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    test('a link is still valid just before the 24-hour mark', function (): void {
        $invited = User::factory()->invited()->withRole('sales_rep')->create();
        $url = invitationUrlFor($invited);

        $this->travel(23)->hours();

        $this->get($url)->assertOk()->assertSee('Set password and sign in');
    });

    test('a used token cannot be used again', function (): void {
        $invited = User::factory()->invited()->withRole('sales_rep')->create();
        $token = Password::broker('invitations')->createToken($invited);
        $payload = [
            'token' => $token,
            'email' => $invited->email,
            'password' => 'brand-new-secret',
            'password_confirmation' => 'brand-new-secret',
        ];

        $this->post('/invitation', $payload)->assertRedirect('/field');
        auth()->logout();

        $this->from('/invitation/'.$token)->post('/invitation', $payload)->assertSessionHasErrors('email');
    });

    test('a wrong token for a real email is rejected', function (): void {
        $invited = User::factory()->invited()->withRole('sales_rep')->create();
        Password::broker('invitations')->createToken($invited);

        $this->get(route('invitation.accept', ['token' => 'not-the-token', 'email' => $invited->email]))
            ->assertOk()
            ->assertSee('invalid or has expired');
    });
});

test('an invited user cannot sign in before setting a password', function (): void {
    $invited = User::factory()->invited()->withRole('sales_rep')->create();

    $this->from('/')->post('/login', [
        'email' => $invited->email,
        'password' => '',
    ])->assertSessionHasErrors('password');

    $this->from('/')->post('/login', [
        'email' => $invited->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
