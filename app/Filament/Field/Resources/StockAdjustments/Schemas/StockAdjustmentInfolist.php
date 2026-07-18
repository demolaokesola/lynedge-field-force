<?php

namespace App\Filament\Field\Resources\StockAdjustments\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StockAdjustmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('position.code')
                    ->label('Position'),
                TextEntry::make('adjustment_date')
                    ->date(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('adjustedBy.name')
                    ->label('Adjusted by'),
                TextEntry::make('notes')
                    ->placeholder('—')
                    ->columnSpanFull(),
                RepeatableEntry::make('lines')
                    ->label('Products')
                    ->table([
                        TableColumn::make('Product'),
                        TableColumn::make('Quantity'),
                        TableColumn::make('Reason'),
                        TableColumn::make('Note'),
                    ])
                    ->schema([
                        TextEntry::make('product.name'),
                        TextEntry::make('quantity_delta'),
                        TextEntry::make('reason')
                            ->badge(),
                        TextEntry::make('note')
                            ->placeholder('—'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
