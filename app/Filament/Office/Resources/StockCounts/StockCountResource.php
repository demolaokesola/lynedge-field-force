<?php

namespace App\Filament\Office\Resources\StockCounts;

use App\Filament\Office\Resources\StockCounts\Pages\CreateStockCount;
use App\Filament\Office\Resources\StockCounts\Pages\EditStockCount;
use App\Filament\Office\Resources\StockCounts\Pages\ListStockCounts;
use App\Filament\Office\Resources\StockCounts\Pages\ViewStockCount;
use App\Filament\Office\Resources\StockCounts\Tables\StockCountsTable;
use App\Filament\Shared\Resources\StockCounts\Schemas\StockCountForm;
use App\Filament\Shared\Resources\StockCounts\Schemas\StockCountInfolist;
use App\Models\StockCount;
use App\Policies\StockCountPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Office-facing: Operations (platform_admin) enters opening balances and reviews /
 * posts the counts reps submit. Rows are narrowed by the position-anchored transaction
 * scope; write is gated by {@see StockCountPolicy}.
 */
class StockCountResource extends Resource
{
    protected static ?string $model = StockCount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?string $navigationLabel = 'Stock Counts';

    public static function form(Schema $schema): Schema
    {
        return StockCountForm::configure($schema, forOperations: true);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StockCountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockCountsTable::configure($table);
    }

    /**
     * @return Builder<StockCount>
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
            'index' => ListStockCounts::route('/'),
            'create' => CreateStockCount::route('/create'),
            'view' => ViewStockCount::route('/{record}'),
            'edit' => EditStockCount::route('/{record}/edit'),
        ];
    }
}
