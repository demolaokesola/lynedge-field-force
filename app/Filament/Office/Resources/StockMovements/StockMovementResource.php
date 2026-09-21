<?php

namespace App\Filament\Office\Resources\StockMovements;

use App\Filament\Office\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Shared\Resources\StockMovements\Tables\StockMovementsTable;
use App\Models\StockMovement;
use App\Services\StockLedger;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Office-facing: the full stock ledger. Read-only; rows are only ever written by
 * {@see StockLedger}.
 */
class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?string $navigationLabel = 'Stock Ledger';

    protected static ?string $modelLabel = 'stock movement';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return StockMovementsTable::configure($table);
    }

    /**
     * @return Builder<StockMovement>
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
            'index' => ListStockMovements::route('/'),
        ];
    }
}
