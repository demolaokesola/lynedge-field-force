<?php

namespace App\Filament\Office\Resources\DemandCreators\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DemandCreatorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('demand_creator_type_id')
                    ->label('Type')
                    ->relationship('type', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('territory_id')
                    ->relationship('territory', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('affiliation')
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                TextInput::make('address')
                    ->maxLength(255),
            ]);
    }
}
