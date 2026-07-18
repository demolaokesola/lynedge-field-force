<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->to($this->panelUrlFor(Auth::user()) ?? '/');
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $panelUrl = $this->panelUrlFor(Auth::user());

        if ($panelUrl === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Your account is not assigned to any panel. Contact an administrator.',
            ]);
        }

        return redirect()->intended($panelUrl);
    }

    private function panelUrlFor(User $user): ?string
    {
        $panelId = $user->defaultPanelId();

        if ($panelId === null) {
            return null;
        }

        return Filament::getPanel($panelId)->getUrl();
    }
}
