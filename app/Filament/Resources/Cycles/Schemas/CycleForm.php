<?php

namespace App\Filament\Resources\Cycles\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CycleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('starts_on')
                    ->required(),
                DatePicker::make('ends_on')
                    ->required()
                    ->afterOrEqual('starts_on'),
                Toggle::make('is_current')
                    ->label('Current cycle')
                    ->default(false),
            ]);
    }
}
