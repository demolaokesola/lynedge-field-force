<?php

namespace App\Filament\Field\Resources\StockAdjustments;

use App\Filament\Field\Clusters\MyStockCluster;
use App\Filament\Field\Resources\StockAdjustments\Pages\ListStockAdjustments;
use App\Filament\Field\Resources\StockAdjustments\Pages\ViewStockAdjustment;
use App\Filament\Field\Resources\StockAdjustments\Schemas\StockAdjustmentInfolist;
use App\Filament\Field\Resources\StockAdjustments\Tables\StockAdjustmentsTable;
use App\Models\StockAdjustment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Field-facing: read-only transparency into stock corrections Operations has made for
 * positions the viewer holds or supervises. No actions — write stays on the Office
 * resource.
 */
class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static ?string $cluster = MyStockCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Stock Adjustments';

    public static function infolist(Schema $schema): Schema
    {
        return StockAdjustmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockAdjustmentsTable::configure($table);
    }

    /**
     * @return Builder<StockAdjustment>
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
            'index' => ListStockAdjustments::route('/'),
            'view' => ViewStockAdjustment::route('/{record}'),
        ];
    }
}
