<?php

namespace App\Filament\Office\Resources\Teams\Schemas;

use App\Enums\TeamKind;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('kind')
                    ->options(TeamKind::class)
                    ->required()
                    ->helperText('A product may belong to at most one strict team, and any number of liberal teams.'),
                Toggle::make('active')
                    ->default(true),
            ]);
    }
}
