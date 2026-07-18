<?php

namespace App\Filament\Field\Resources\StockDispatches\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StockDispatchInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('position.code')
                    ->label('Position'),
                TextEntry::make('dispatch_date')
                    ->date(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('dispatchedBy.name')
                    ->label('Dispatched by'),
                TextEntry::make('accepted_at')
                    ->dateTime()
                    ->placeholder('— pending'),
                TextEntry::make('notes')
                    ->placeholder('—')
                    ->columnSpanFull(),
                RepeatableEntry::make('lines')
                    ->label('Products')
                    ->table([
                        TableColumn::make('Product'),
                        TableColumn::make('Quantity'),
                    ])
                    ->schema([
                        TextEntry::make('product.name'),
                        TextEntry::make('quantity'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
