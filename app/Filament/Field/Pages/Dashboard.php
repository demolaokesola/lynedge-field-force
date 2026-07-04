<?php

namespace App\Filament\Field\Pages;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected const SESSION_STARTED_AT_KEY = 'field_dashboard_session_started_at';

    public function getTitle(): string|Htmlable
    {
        if ($this->sessionStartedAt()->diffInMinutes(now()) >= 5) {
            return parent::getTitle();
        }

        $user = Filament::auth()->user();

        return $user ? "Welcome {$user->name}" : parent::getTitle();
    }

    protected function sessionStartedAt(): Carbon
    {
        $startedAt = session(self::SESSION_STARTED_AT_KEY);

        if ($startedAt === null) {
            $startedAt = now();
            session([self::SESSION_STARTED_AT_KEY => $startedAt]);
        }

        return Carbon::parse($startedAt);
    }
}
