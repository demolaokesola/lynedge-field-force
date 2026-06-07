<?php

namespace App\Filament\Resources\TargetAssignments\Tables;

use App\Enums\TargetBasis;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TargetAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Rep')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cycle.name')
                    ->label('Cycle')
                    ->sortable(),
                TextColumn::make('basis')
                    ->badge()
                    ->sortable(),
                TextColumn::make('tier.name')
                    ->label('Tier')
                    ->placeholder('Custom')
                    ->sortable(),
                TextColumn::make('reason')
                    ->badge()
                    ->sortable(),
                TextColumn::make('effective_from')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('effective_to')
                    ->date('d M Y')
                    ->placeholder('— open')
                    ->sortable(),
            ])
            ->defaultSort('effective_from', 'desc')
            ->filters([
                SelectFilter::make('cycle')
                    ->relationship('cycle', 'name'),
                SelectFilter::make('basis')
                    ->options(TargetBasis::class),
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
