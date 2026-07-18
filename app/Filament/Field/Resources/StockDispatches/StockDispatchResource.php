<?php

namespace App\Filament\Field\Resources\StockDispatches;

use App\Filament\Field\Clusters\MyStockCluster;
use App\Filament\Field\Resources\StockDispatches\Pages\ListStockDispatches;
use App\Filament\Field\Resources\StockDispatches\Pages\ViewStockDispatch;
use App\Filament\Field\Resources\StockDispatches\Schemas\StockDispatchInfolist;
use App\Filament\Field\Resources\StockDispatches\Tables\StockDispatchesTable;
use App\Models\StockDispatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Field-facing: read-only stock sent to positions the viewer holds or supervises, with
 * a single Accept action. No create/edit — write access stays on the Office resource.
 */
class StockDispatchResource extends Resource
{
    protected static ?string $model = StockDispatch::class;

    protected static ?string $cluster = MyStockCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $navigationLabel = 'Stock Dispatches';

    protected static ?int $navigationSort = 2;

    public static function infolist(Schema $schema): Schema
    {
        return StockDispatchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockDispatchesTable::configure($table);
    }

    /**
     * @return Builder<StockDispatch>
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
            'index' => ListStockDispatches::route('/'),
            'view' => ViewStockDispatch::route('/{record}'),
        ];
    }
}
