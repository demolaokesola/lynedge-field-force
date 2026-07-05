<?php

namespace App\Filament\Office\Resources\DemandCreatorTypes;

use App\Filament\Office\Resources\DemandCreatorTypes\Pages\CreateDemandCreatorType;
use App\Filament\Office\Resources\DemandCreatorTypes\Pages\EditDemandCreatorType;
use App\Filament\Office\Resources\DemandCreatorTypes\Pages\ListDemandCreatorTypes;
use App\Filament\Office\Resources\DemandCreatorTypes\Schemas\DemandCreatorTypeForm;
use App\Filament\Office\Resources\DemandCreatorTypes\Tables\DemandCreatorTypesTable;
use App\Models\DemandCreatorType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DemandCreatorTypeResource extends Resource
{
    protected static ?string $model = DemandCreatorType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return DemandCreatorTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DemandCreatorTypesTable::configure($table);
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
            'index' => ListDemandCreatorTypes::route('/'),
            'create' => CreateDemandCreatorType::route('/create'),
            'edit' => EditDemandCreatorType::route('/{record}/edit'),
        ];
    }
}
