<?php

namespace App\Filament\Management\Resources\Positions\Schemas;

use App\Models\Position;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PositionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code')
                    ->label('Position'),
                TextEntry::make('territory.name')
                    ->label('Territory'),
                TextEntry::make('team.name')
                    ->label('Team'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('occupant')
                    ->label('Occupant')
                    ->badge()
                    ->state(fn (Position $record): string => $record->openAssignment?->user?->name ?? 'VACANT')
                    ->color(fn (Position $record): string => $record->openAssignment ? 'success' : 'danger'),
                TextEntry::make('supervisor.name')
                    ->label('Supervisor')
                    ->placeholder('—'),
            ]);
    }
}
