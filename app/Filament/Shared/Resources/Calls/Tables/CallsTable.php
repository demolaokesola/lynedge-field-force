<?php

namespace App\Filament\Shared\Resources\Calls\Tables;

use App\Enums\CallType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('called_at', 'desc')
            ->columns([
                TextColumn::make('called_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('call_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Rep')
                    ->sortable(),
                TextColumn::make('demandCreator.name')
                    ->label('Demand creator')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('territory.name')
                    ->sortable(),
                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products'),
            ])
            ->filters([
                SelectFilter::make('call_type')
                    ->options(CallType::class),
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
