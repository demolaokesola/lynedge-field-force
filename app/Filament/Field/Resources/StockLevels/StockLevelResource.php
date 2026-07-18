<?php

namespace App\Filament\Field\Resources\StockLevels;

use App\Filament\Field\Clusters\MyStockCluster;
use App\Filament\Field\Resources\StockLevels\Pages\ListStockLevels;
use App\Filament\Field\Resources\StockLevels\Tables\StockLevelsTable;
use App\Models\PositionProductStock;
use App\Services\StockLedger;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Field-facing: the current balance for each (position, product) the viewer holds or
 * supervises. Read-only; balances only ever change via {@see StockLedger}.
 */
class StockLevelResource extends Resource
{
    protected static ?string $model = PositionProductStock::class;

    protected static ?string $cluster = MyStockCluster::class;

    protected static ?string $navigationLabel = 'Stock Levels';

    protected static ?string $breadcrumb = 'Stock Levels'; 

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

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
