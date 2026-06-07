<?php

namespace App\Filament\Resources\TargetTiers;

use App\Filament\Resources\TargetTiers\Pages\CreateTargetTier;
use App\Filament\Resources\TargetTiers\Pages\EditTargetTier;
use App\Filament\Resources\TargetTiers\Pages\ListTargetTiers;
use App\Filament\Resources\TargetTiers\RelationManagers\TierLinesRelationManager;
use App\Filament\Resources\TargetTiers\Schemas\TargetTierForm;
use App\Filament\Resources\TargetTiers\Tables\TargetTiersTable;
use App\Models\TargetTier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TargetTierResource extends Resource
{
    protected static ?string $model = TargetTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|UnitEnum|null $navigationGroup = 'Targets';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TargetTierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TargetTiersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TierLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTargetTiers::route('/'),
            'create' => CreateTargetTier::route('/create'),
            'edit' => EditTargetTier::route('/{record}/edit'),
        ];
    }
}
