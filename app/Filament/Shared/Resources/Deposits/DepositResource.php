<?php

namespace App\Filament\Shared\Resources\Deposits;

use App\Filament\Shared\Resources\Deposits\Pages\CreateDeposit;
use App\Filament\Shared\Resources\Deposits\Pages\EditDeposit;
use App\Filament\Shared\Resources\Deposits\Pages\ListDeposits;
use App\Filament\Shared\Resources\Deposits\RelationManagers\AllocationsRelationManager;
use App\Filament\Shared\Resources\Deposits\Schemas\DepositForm;
use App\Filament\Shared\Resources\Deposits\Tables\DepositsTable;
use App\Models\Deposit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared by the field (create/view own) and office (full manage + reconciliation) panels.
 * Row visibility is controlled by the transaction scope; write actions are gated by DepositPolicy.
 */
class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return DepositForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DepositsTable::configure($table);
    }

    /**
     * Transaction visibility scope (Scope A) — single choke-point for every list and
     * record lookup in this resource.
     *
     * @return Builder<Deposit>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function getRelations(): array
    {
        return [
            AllocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeposits::route('/'),
            'create' => CreateDeposit::route('/create'),
            'edit' => EditDeposit::route('/{record}/edit'),
        ];
    }
}
