<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate;

class RedirectUnauthenticatedToLogin extends Authenticate
{
    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
