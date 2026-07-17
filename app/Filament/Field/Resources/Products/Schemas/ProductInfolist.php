<?php

namespace App\Filament\Field\Resources\Products\Schemas;

use App\Support\Money;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('sku')
                    ->label('SKU'),
                TextEntry::make('pack_size')
                    ->label('Pack Size'),
                TextEntry::make('unit_price')
                    ->formatStateUsing(fn (mixed $state): ?string => match (true) {
                        $state === null => null,
                        $state instanceof Money => $state->format(),
                        default => Money::of($state)->format(),
                    }),
                TextEntry::make('teams.name')
                    ->label('Teams')
                    ->badge(),
            ]);
    }
}
