<?php

namespace App\Filament\Resources\DemandCreators;

use App\Filament\Resources\DemandCreators\Pages\CreateDemandCreator;
use App\Filament\Resources\DemandCreators\Pages\EditDemandCreator;
use App\Filament\Resources\DemandCreators\Pages\ListDemandCreators;
use App\Filament\Resources\DemandCreators\Schemas\DemandCreatorForm;
use App\Filament\Resources\DemandCreators\Tables\DemandCreatorsTable;
use App\Models\DemandCreator;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DemandCreatorResource extends Resource
{
    protected static ?string $model = DemandCreator::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return DemandCreatorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DemandCreatorsTable::configure($table);
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
            'index' => ListDemandCreators::route('/'),
            'create' => CreateDemandCreator::route('/create'),
            'edit' => EditDemandCreator::route('/{record}/edit'),
        ];
    }
}
