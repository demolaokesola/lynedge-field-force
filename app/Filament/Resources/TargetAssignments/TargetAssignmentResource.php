<?php

namespace App\Filament\Resources\TargetAssignments;

use App\Filament\Resources\TargetAssignments\Pages\CreateTargetAssignment;
use App\Filament\Resources\TargetAssignments\Pages\EditTargetAssignment;
use App\Filament\Resources\TargetAssignments\Pages\ListTargetAssignments;
use App\Filament\Resources\TargetAssignments\Schemas\TargetAssignmentForm;
use App\Filament\Resources\TargetAssignments\Tables\TargetAssignmentsTable;
use App\Models\TargetAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TargetAssignmentResource extends Resource
{
    protected static ?string $model = TargetAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Targets';

    public static function form(Schema $schema): Schema
    {
        return TargetAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TargetAssignmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTargetAssignments::route('/'),
            'create' => CreateTargetAssignment::route('/create'),
            'edit' => EditTargetAssignment::route('/{record}/edit'),
        ];
    }
}
