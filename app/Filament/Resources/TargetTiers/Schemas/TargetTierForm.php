<?php

namespace App\Filament\Resources\TargetTiers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TargetTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->nullable()
                    ->columnSpanFull(),
                Toggle::make('active')
                    ->default(true),
            ]);
    }
}
