<?php

namespace App\Filament\Office\Resources\StockAdjustments;

use App\Filament\Office\Resources\StockAdjustments\Pages\CreateStockAdjustment;
use App\Filament\Office\Resources\StockAdjustments\Pages\EditStockAdjustment;
use App\Filament\Office\Resources\StockAdjustments\Pages\ListStockAdjustments;
use App\Filament\Office\Resources\StockAdjustments\Schemas\StockAdjustmentForm;
use App\Filament\Office\Resources\StockAdjustments\Tables\StockAdjustmentsTable;
use App\Models\StockAdjustment;
use App\Policies\StockAdjustmentPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Office-facing: Operations (platform_admin) corrects a position's stock (damage,
 * loss, correction, return, recall). Rows are narrowed by the position-anchored
 * transaction scope; write is gated by {@see StockAdjustmentPolicy}.
 */
class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Stock';

    protected static ?string $navigationLabel = 'Stock Adjustments';

    public static function form(Schema $schema): Schema
    {
        return StockAdjustmentForm::configure($schema);
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
            'create' => CreateStockAdjustment::route('/create'),
            'edit' => EditStockAdjustment::route('/{record}/edit'),
        ];
    }
}
