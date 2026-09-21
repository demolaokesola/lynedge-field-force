<?php

namespace App\Filament\Field\Resources\StockMovements;

use App\Filament\Field\Clusters\MyStockCluster;
use App\Filament\Field\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Shared\Resources\StockMovements\Tables\StockMovementsTable;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Field-facing: every stock change for positions the viewer holds or supervises —
 * the audit trail behind the Stock Levels figures. Read-only.
 */
class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $cluster = MyStockCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Stock History';

    protected static ?string $modelLabel = 'stock movement';

    protected static ?int $navigationSort = 5;

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
