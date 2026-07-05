<?php

namespace App\Filament\Office\Resources\TargetAssignments\Schemas;

use App\Enums\AssignmentReason;
use App\Enums\TargetBasis;
use App\Models\Cycle;
use App\Models\TargetTier;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TargetAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('cycle_id')
                    ->label('Cycle')
                    ->options(fn (): array => Cycle::orderByDesc('starts_on')->pluck('name', 'id')->all())
                    ->required()
                    ->searchable(),

                Select::make('user_id')
                    ->label('Rep')
                    ->options(fn (): array => User::orderBy('name')->pluck('name', 'id')->all())
                    ->required()
                    ->searchable(),

                Select::make('basis')
                    ->label('Basis')
                    ->options(TargetBasis::class)
                    ->required()
                    ->live(),

                Select::make('target_tier_id')
                    ->label('Tier')
                    ->options(fn (): array => TargetTier::where('active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->visible(fn (Get $get): bool => $get('basis') === TargetBasis::Tier->value),

                Select::make('reason')
                    ->label('Reason')
                    ->options(AssignmentReason::class)
                    ->required(),

                DatePicker::make('effective_from')
                    ->label('Effective from')
                    ->required(),

                DatePicker::make('effective_to')
                    ->label('Effective to')
                    ->helperText('Leave blank if this assignment has no end date.'),

                Textarea::make('notes')
                    ->columnSpanFull()
                    ->nullable(),

                Repeater::make('lines')
                    ->relationship()
                    ->label('Custom annual volumes')
                    ->columnSpanFull()
                    ->visible(fn (Get $get): bool => $get('basis') === TargetBasis::Custom->value)
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        TextInput::make('annual_volume')
                            ->label('Annual volume')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                    ])
                    ->columns(2),
            ]);
    }
}
