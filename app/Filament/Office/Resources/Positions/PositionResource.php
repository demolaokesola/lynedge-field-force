<?php

namespace App\Filament\Office\Resources\Positions;

use App\Filament\Office\Resources\Positions\Pages\CreatePosition;
use App\Filament\Office\Resources\Positions\Pages\EditPosition;
use App\Filament\Office\Resources\Positions\Pages\ListPositions;
use App\Filament\Office\Resources\Positions\RelationManagers\AssignmentsRelationManager;
use App\Filament\Office\Resources\Positions\Schemas\PositionForm;
use App\Filament\Office\Resources\Positions\Tables\PositionsTable;
use App\Models\Position;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function form(Schema $schema): Schema
    {
        return PositionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PositionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPositions::route('/'),
            'create' => CreatePosition::route('/create'),
            'edit' => EditPosition::route('/{record}/edit'),
        ];
    }
}
