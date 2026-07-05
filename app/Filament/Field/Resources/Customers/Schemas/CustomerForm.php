<?php

namespace App\Filament\Field\Resources\Customers\Schemas;

use App\Enums\CustomerType;
use App\Services\RepScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('position_id')
                    ->label('Position')
                    ->options(fn (): array => static::positionOptions())
                    ->default(fn (): ?int => array_key_first(static::positionOptions()))
                    ->required()
                    ->native(false)
                    // Customer has no position_id column — it only exists here to derive
                    // territory_id at create time (see CreateCustomer). Territory is fixed
                    // afterwards, so there is nothing meaningful to edit.
                    ->hiddenOn('edit'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options(CustomerType::class)
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                TextInput::make('address')
                    ->maxLength(255),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public static function positionOptions(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        return app(RepScope::class)->positionOptions($user);
    }
}
