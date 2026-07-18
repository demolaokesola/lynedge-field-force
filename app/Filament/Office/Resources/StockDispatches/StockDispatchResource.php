<?php

namespace App\Filament\Office\Resources\StockDispatches;

use App\Filament\Office\Resources\StockDispatches\Pages\CreateStockDispatch;
use App\Filament\Office\Resources\StockDispatches\Pages\EditStockDispatch;
use App\Filament\Office\Resources\StockDispatches\Pages\ListStockDispatches;
use App\Filament\Office\Resources\StockDispatches\Schemas\StockDispatchForm;
use App\Filament\Office\Resources\StockDispatches\Tables\StockDispatchesTable;
use App\Models\StockDispatch;
use App\Policies\StockDispatchPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Office-facing: Operations (platform_admin) sends stock to a position. Rows are
 * narrowed by {@see StockDispatchPolicy} and the position-anchored transaction scope;
 * write is gated by {@see StockDispatchPolicy}.
 */
class StockDispatchResource extends Resource
{
    protected static ?string $model = StockDispatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?string $navigationLabel = 'Stock Dispatches';

    public static function form(Schema $schema): Schema
    {
        return StockDispatchForm::configure($schema);
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
            'create' => CreateStockDispatch::route('/create'),
            'edit' => EditStockDispatch::route('/{record}/edit'),
        ];
    }
}
