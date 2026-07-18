<?php

namespace App\Filament\Management\Resources\Positions;

use App\Filament\Management\Resources\Positions\Pages\ListPositions;
use App\Filament\Management\Resources\Positions\Pages\PositionCalls;
use App\Filament\Management\Resources\Positions\Pages\PositionDistributions;
use App\Filament\Management\Resources\Positions\Pages\ViewPosition;
use App\Filament\Management\Resources\Positions\Schemas\PositionInfolist;
use App\Filament\Management\Resources\Positions\Tables\PositionsTable;
use App\Models\Position;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only in the management panel — hq_lead/regional_head drill-down into org
 * structure and per-position performance. Rows are narrowed by Position::visibleOrgTo();
 * no create/edit/delete pages exist here (write access stays on the Office resource).
 */
class PositionResource extends Resource
{
    protected static ?string $model = Position::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'code';

    public static function infolist(Schema $schema): Schema
    {
        return PositionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PositionsTable::configure($table);
    }

    /**
     * The single choke-point for org visibility (Scope B). Every list, view and
     * record lookup — including the sub-navigation pages — flows through here.
     *
     * @return Builder<Position>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleOrgTo(auth()->user());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPositions::route('/'),
            'view' => ViewPosition::route('/{record}'),
            'distributions' => PositionDistributions::route('/{record}/distributions'),
            'calls' => PositionCalls::route('/{record}/calls'),
        ];
    }

    /**
     * @return array<NavigationItem>
     */
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewPosition::class,
            PositionDistributions::class,
            PositionCalls::class,
        ]);
    }
}
