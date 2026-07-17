<?php

namespace App\Filament\Field\Resources\Products\Tables;

use App\Filament\Field\Resources\Products\ProductResource;
use App\Models\Product;
use App\Support\Money;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pack_size')
                    ->toggleable(),
                TextColumn::make('unit_price')
                    ->formatStateUsing(fn (mixed $state): ?string => match (true) {
                        $state === null => null,
                        $state instanceof Money => $state->format(),
                        default => Money::of($state)->format(),
                    })
                    ->sortable(),
                TextColumn::make('teams.name')
                    ->badge(),
            ])
            ->recordUrl(fn (Product $record): string => ProductResource::getUrl('view', ['record' => $record]));
    }
}
