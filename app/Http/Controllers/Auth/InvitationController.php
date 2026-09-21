<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Accepts an onboarding invitation: the invited user sets their first password and is
 * signed straight into their panel. Tokens come from the 'invitations' broker (24h).
 */
class InvitationController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $email = (string) $request->query('email');
        $user = User::query()->where('email', $email)->first();

        $isValid = $user !== null && Password::broker('invitations')->tokenExists($user, $token);

        return view('auth.set-password', [
            'token' => $token,
            'email' => $email,
            'isValid' => $isValid,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::broker('invitations')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        /** @var User $user */
        $user = User::query()->where('email', $request->string('email'))->firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to($user->defaultPanelUrl() ?? route('login'));
    }
}
