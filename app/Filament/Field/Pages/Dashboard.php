<?php

namespace App\Filament\Field\Pages;

use App\Filament\Shared\Resources\Calls\CallResource;
use App\Filament\Shared\Resources\Distributions\DistributionResource;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected const SESSION_STARTED_AT_KEY = 'field_dashboard_session_started_at';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('logCall')
                ->label('Log a Call')
                ->icon(Heroicon::OutlinedPhone)
                ->url(fn (): string => CallResource::getUrl('create')),

            Action::make('newDistribution')
                ->label('New Distribution')
                ->icon(Heroicon::OutlinedDocumentText)
                ->url(fn (): string => DistributionResource::getUrl('create')),
        ];
    }

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
