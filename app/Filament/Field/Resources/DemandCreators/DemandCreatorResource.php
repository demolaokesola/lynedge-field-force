<?php

namespace App\Filament\Field\Resources\DemandCreators;

use App\Filament\Field\Clusters\CustomerDataCluster;
use App\Filament\Field\Resources\DemandCreators\Pages\CreateDemandCreator;
use App\Filament\Field\Resources\DemandCreators\Pages\EditDemandCreator;
use App\Filament\Field\Resources\DemandCreators\Pages\ListDemandCreators;
use App\Filament\Field\Resources\DemandCreators\Schemas\DemandCreatorForm;
use App\Filament\Field\Resources\DemandCreators\Tables\DemandCreatorsTable;
use App\Models\DemandCreator;
use App\Policies\DemandCreatorPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Field-panel facing: reps create/view/edit-own demand creators, scoped to their active
 * positions' territories (DemandCreator's ScopesToTerritory). The office panel's admin
 * DemandCreatorResource is a separate class with full unscoped CRUD; both share the
 * same model and {@see DemandCreatorPolicy}.
 */
class DemandCreatorResource extends Resource
{
    protected static ?string $model = DemandCreator::class;

    protected static ?string $cluster = CustomerDataCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return DemandCreatorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DemandCreatorsTable::configure($table);
    }

    /**
     * @return Builder<DemandCreator>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
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
