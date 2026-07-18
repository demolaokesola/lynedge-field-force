<?php

namespace App\Filament\Office\Resources\Positions\Tables;

use App\Enums\PositionStatus;
use App\Models\Position;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PositionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('territory.name')
                    ->label('Territory')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('team.name')
                    ->label('Team')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Position')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('occupant')
                    ->label('Occupant')
                    ->badge()
                    ->state(fn (Position $record): string => $record->openAssignment?->user?->name ?? 'VACANT')
                    ->color(fn (Position $record): string => $record->openAssignment ? 'success' : 'danger'),
                TextColumn::make('supervisor.name')
                    ->label('Supervisor')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('territory')
                    ->relationship('territory', 'name'),
                SelectFilter::make('team')
                    ->relationship('team', 'name'),
                SelectFilter::make('status')
                    ->options(PositionStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
