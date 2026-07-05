<?php

namespace App\Filament\Office\Resources\Products\Schemas;

use App\Models\Product;
use App\Models\Team;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('pack_size')
                    ->required()
                    ->maxLength(255),
                TextInput::make('unit_price')
                    ->numeric()
                    ->prefix('₦')
                    ->helperText('Leave blank if priced per distribution.'),
                Toggle::make('active')
                    ->default(true),
                Select::make('teams')
                    ->relationship('teams', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Team $record): string => "{$record->name} ({$record->kind->getLabel()})")
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText('A product may belong to at most one strict team, and any number of liberal teams.')
                    ->rules([
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (Product::wouldExceedOneStrictTeam(array_filter((array) $value))) {
                                $fail('A product can belong to at most one strict team. Remove it from the other strict team first, or make this team liberal.');
                            }
                        },
                    ]),
            ]);
    }
}
