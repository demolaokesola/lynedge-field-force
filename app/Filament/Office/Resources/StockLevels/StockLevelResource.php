<?php

namespace App\Filament\Office\Resources\StockLevels;

use App\Filament\Office\Resources\StockLevels\Pages\ListStockLevels;
use App\Filament\Shared\Resources\StockLevels\Tables\StockLevelsTable;
use App\Models\PositionProductStock;
use App\Services\StockLedger;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Office-facing: the current on-hand balance for every (position, product) the viewer
 * may see. Read-only; balances only ever change via {@see StockLedger}.
 */
class StockLevelResource extends Resource
{
    protected static ?string $model = PositionProductStock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?string $navigationLabel = 'Stock Levels';

    protected static ?string $modelLabel = 'stock level';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return StockLevelsTable::configure($table);
    }

    /**
     * @return Builder<PositionProductStock>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockLevels::route('/'),
        ];
    }
}
