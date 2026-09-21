<?php

namespace App\Filament\Office\Resources\Users\Schemas;

use App\Models\Region;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText(fn (string $operation): ?string => $operation === 'create'
                        ? 'An invitation link to set a password will be emailed to this address.'
                        : null),
                Toggle::make('is_active')
                    ->default(true),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->live()
                    ->required(),
                Select::make('region_id')
                    ->label('Region')
                    ->options(fn (): array => Region::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->helperText('Anchors a regional_head (and optionally an accountant) to one region. Reps derive their region from their position.')
                    ->visible(fn (Get $get): bool => Role::query()
                        ->whereKey($get('roles') ?? [])
                        ->where('name', 'regional_head')
                        ->exists()),
            ]);
    }
}
