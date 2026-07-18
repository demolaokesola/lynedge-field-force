<?php

namespace App\Filament\Management\Resources\Positions\Pages;

use App\Enums\CallType;
use App\Filament\Management\Resources\Positions\PositionResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * The "Call Activity" tab — every call/visit record ever logged under this position,
 * all-time, newest first.
 */
class PositionCalls extends ManageRelatedRecords
{
    protected static string $resource = PositionResource::class;

    protected static string $relationship = 'calls';

    public static function getNavigationLabel(): string
    {
        return 'Call Activity';
    }

    public function table(Table $table): Table
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
                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products'),
            ])
            ->filters([
                SelectFilter::make('call_type')
                    ->options(CallType::class),
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}
