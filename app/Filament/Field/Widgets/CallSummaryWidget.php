<?php

namespace App\Filament\Field\Widgets;

use App\Enums\CallType;
use App\Models\Call;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CallSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    public function getHeading(): ?string
    {
        return auth()->user()?->isSupervisor()
            ? 'Supervised Calls — This Month'
            : 'My Calls — This Month';
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $base = Call::visibleTo($user)
            ->whereYear('called_at', now()->year)
            ->whereMonth('called_at', now()->month);

        $total = (clone $base)->count();
        $physical = (clone $base)->where('call_type', CallType::PhysicalVisit->value)->count();
        $phone = (clone $base)->where('call_type', CallType::PhoneCall->value)->count();

        return [
            Stat::make('Total Calls', $total)
                ->description('All types, this month')
                ->descriptionIcon('heroicon-m-phone')
                ->color('primary'),
            Stat::make('Physical Visits', $physical)
                ->description('Face-to-face calls')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('info'),
            Stat::make('Phone Calls', $phone)
                ->description('Telephone contacts')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('success'),
        ];
    }
}
