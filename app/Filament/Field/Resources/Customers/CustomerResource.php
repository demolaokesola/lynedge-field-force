<?php

namespace App\Filament\Field\Resources\Customers;

use App\Filament\Field\Clusters\CustomerDataCluster;
use App\Filament\Field\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Field\Resources\Customers\Pages\EditCustomer;
use App\Filament\Field\Resources\Customers\Pages\ListCustomers;
use App\Filament\Field\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Field\Resources\Customers\Tables\CustomersTable;
use App\Models\Customer;
use App\Policies\CustomerPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Field-panel facing: reps create/view/edit-own customers, scoped to their active
 * positions' territories (Customer's ScopesToTerritory). The office panel's admin
 * CustomerResource is a separate class with full unscoped CRUD; both share the same
 * model and {@see CustomerPolicy}.
 */
class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $cluster = CustomerDataCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    /**
     * @return Builder<Customer>
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
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
