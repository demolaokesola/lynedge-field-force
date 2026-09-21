<?php

namespace App\Filament\Shared\Resources\StockLevels\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The current on-hand balance per (position, product), shared by the Field, Office
 * and Management resources. Read-only everywhere: balances only change through
 * {@see \App\Services\StockLedger}.
 */
class StockLevelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('position.code')
                    ->label('Position')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position.territory.name')
                    ->label('Territory')
                    ->sortable(),
                TextColumn::make('position.territory.region.name')
                    ->label('Region')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('On hand')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn (string $state): ?string => (float) $state < 0 ? 'danger' : null),
                TextColumn::make('updated_at')
                    ->label('Last movement')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('negative')
                    ->label('Negative balances only')
                    ->query(fn (Builder $query): Builder => $query->where('quantity', '<', 0)),
            ])
            ->defaultSort('quantity', 'asc');
    }
}
