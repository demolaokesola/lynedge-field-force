<?php

namespace App\Filament\Field\Resources\StockLevels\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockLevelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('position.code')
                    ->label('Position')
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->sortable()
                    ->color(fn (string $state): ?string => (float) $state < 0 ? 'danger' : null),
            ])
            ->defaultSort('quantity', 'asc');
    }
}
