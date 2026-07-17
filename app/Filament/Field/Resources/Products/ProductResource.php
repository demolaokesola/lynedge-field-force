<?php

namespace App\Filament\Field\Resources\Products;

use App\Filament\Field\Resources\Products\Pages\ListProducts;
use App\Filament\Field\Resources\Products\Pages\ViewProduct;
use App\Filament\Field\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Field\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use App\Services\RepScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Field-panel facing: read-only "My Products" — reps see only active products in the
 * team scope of their active positions (RepScope::productsForUser). No create/edit;
 * write access to the Product master data stays on the admin-only Office resource.
 */
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationLabel = 'My Products';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $recordTitleAttribute = 'name';

    public static function infolist(Schema $schema): Schema
    {
        return ProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    /**
     * @return Builder<Product>
     */
    public static function getEloquentQuery(): Builder
    {
        return app(RepScope::class)->productsForUser(auth()->user());
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'view' => ViewProduct::route('/{record}'),
        ];
    }
}
