<?php

namespace App\Filament\Office\Resources\Territories\Schemas;

use App\Enums\TeamPolicy;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TerritoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('region_id')
                    ->relationship('region', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('team_policy')
                    ->options(TeamPolicy::class)
                    ->default(TeamPolicy::Strict)
                    ->required()
                    ->helperText('strict = products partitioned across reps; liberal = product groups may overlap.'),
            ]);
    }
}
