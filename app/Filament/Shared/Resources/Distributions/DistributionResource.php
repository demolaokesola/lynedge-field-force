<?php

namespace App\Filament\Shared\Resources\Distributions;

use App\Filament\Shared\Resources\Distributions\Pages\EditDistribution;
use App\Filament\Shared\Resources\Distributions\Pages\ListDistributions;
use App\Filament\Shared\Resources\Distributions\Schemas\DistributionForm;
use App\Filament\Shared\Resources\Distributions\Tables\DistributionsTable;
use App\Models\Distribution;
use App\Policies\DistributionPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared by the field and management panels (registered explicitly in each provider).
 * Rows are narrowed by the transaction scope; create/update/delete are gated by
 * {@see DistributionPolicy}.
 */
class DistributionResource extends Resource
{
    protected static ?string $model = Distribution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function form(Schema $schema): Schema
    {
        return DistributionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DistributionsTable::configure($table);
    }

    /**
     * The single choke-point for transaction visibility (Scope A). Every list, edit and
     * record lookup flows through here.
     *
     * @return Builder<Distribution>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDistributions::route('/'),
            'edit' => EditDistribution::route('/{record}/edit'),
        ];
    }
}
