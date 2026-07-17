<?php

namespace App\Filament\Field\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('territory.name')
                    ->label('Territory'),
                TextEntry::make('phone'),
                TextEntry::make('address'),
            ]);
    }
}
