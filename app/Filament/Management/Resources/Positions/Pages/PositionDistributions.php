<?php

namespace App\Filament\Management\Resources\Positions\Pages;

use App\Filament\Management\Resources\Positions\PositionResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The "Distribution" tab — every distribution ever raised under this position,
 * all-time, newest first.
 */
class PositionDistributions extends ManageRelatedRecords
{
    protected static string $resource = PositionResource::class;

    protected static string $relationship = 'distributions';

    public static function getNavigationLabel(): string
    {
        return 'Distribution';
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total (₦)')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Rep')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}
