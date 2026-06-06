<?php

namespace App\Filament\Shared\Resources\Calls;

use App\Filament\Shared\Resources\Calls\Pages\CreateCall;
use App\Filament\Shared\Resources\Calls\Pages\EditCall;
use App\Filament\Shared\Resources\Calls\Pages\ListCalls;
use App\Filament\Shared\Resources\Calls\Schemas\CallForm;
use App\Filament\Shared\Resources\Calls\Tables\CallsTable;
use App\Models\Call;
use App\Policies\CallPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared by the field and management panels (registered explicitly in each provider, never
 * auto-discovered). Behaviour is varied by the viewer, not the panel: rows are narrowed by
 * the transaction scope below; create/update/delete are gated by {@see CallPolicy}.
 */
class CallResource extends Resource
{
    protected static ?string $model = Call::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $recordTitleAttribute = 'demandCreator.name';

    public static function form(Schema $schema): Schema
    {
        return CallForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CallsTable::configure($table);
    }

    /**
     * The single choke-point for transaction visibility (Scope A). Every list, edit and
     * record lookup flows through here, so a viewer can only ever reach rows the scope
     * permits — own activity for a rep, region for supervisor/regional_head, all for HQ.
     *
     * @return Builder<Call>
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
            'index' => ListCalls::route('/'),
            'create' => CreateCall::route('/create'),
            'edit' => EditCall::route('/{record}/edit'),
        ];
    }
}
