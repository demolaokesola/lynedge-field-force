<?php

namespace App\Filament\Shared\Resources\StockCounts\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StockCountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('position.code')
                    ->label('Position'),
                TextEntry::make('kind')
                    ->badge(),
                TextEntry::make('count_date')
                    ->date(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('countedBy.name')
                    ->label('Counted by'),
                TextEntry::make('submitted_at')
                    ->dateTime()
                    ->placeholder('—'),
                TextEntry::make('postedBy.name')
                    ->label('Posted by')
                    ->placeholder('—'),
                TextEntry::make('posted_at')
                    ->dateTime()
                    ->placeholder('—'),
                TextEntry::make('notes')
                    ->placeholder('—')
                    ->columnSpanFull(),
                RepeatableEntry::make('lines')
                    ->label('Products')
                    ->table([
                        TableColumn::make('Product'),
                        TableColumn::make('Counted'),
                        TableColumn::make('System'),
                        TableColumn::make('Variance'),
                    ])
                    ->schema([
                        TextEntry::make('product.name'),
                        TextEntry::make('counted_quantity'),
                        TextEntry::make('system_quantity')
                            ->placeholder('— not posted'),
                        TextEntry::make('variance_quantity')
                            ->placeholder('— not posted')
                            ->color(fn (?string $state): ?string => match (true) {
                                $state === null => null,
                                (float) $state < 0 => 'danger',
                                (float) $state > 0 => 'success',
                                default => null,
                            }),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
