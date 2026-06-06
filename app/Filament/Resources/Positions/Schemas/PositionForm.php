<?php

namespace App\Filament\Resources\Positions\Schemas;

use App\Enums\PositionStatus;
use App\Enums\TeamPolicy;
use App\Models\Position;
use App\Models\Team;
use App\Models\Territory;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('territory_id')
                    ->relationship('territory', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live(),
                Select::make('team_id')
                    ->label('Team')
                    // Only teams whose kind matches the selected territory's policy.
                    ->options(fn (Get $get): Collection => self::eligibleTeams($get('territory_id')))
                    ->required()
                    ->searchable()
                    ->disabled(fn (Get $get): bool => blank($get('territory_id')))
                    ->helperText('Filtered to teams whose kind matches the territory policy.')
                    ->rules([
                        // (a) team.kind MUST equal territory.team_policy (both directions).
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                            $territory = Territory::find($get('territory_id'));
                            $team = Team::find($value);

                            if ($territory && $team && $team->kind->value !== $territory->team_policy->value) {
                                $fail("A {$territory->team_policy->value} territory can only hold {$territory->team_policy->value} teams.");
                            }
                        },
                        // (b) one active position per (territory, team) in STRICT territories.
                        fn (Get $get, ?Position $record): Closure => function (string $attribute, $value, Closure $fail) use ($get, $record): void {
                            $territory = Territory::find($get('territory_id'));

                            if (! $territory || $territory->team_policy !== TeamPolicy::Strict) {
                                return;
                            }

                            $clash = Position::query()
                                ->where('territory_id', $territory->id)
                                ->where('team_id', $value)
                                ->where('status', PositionStatus::Active)
                                ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                                ->exists();

                            if ($clash) {
                                $fail('This team is already manned in this (strict) territory.');
                            }
                        },
                    ]),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                Select::make('status')
                    ->options(PositionStatus::class)
                    ->default(PositionStatus::Active)
                    ->required(),
            ]);
    }

    /**
     * Teams eligible for a position in the given territory: active and of the kind
     * that matches the territory's policy.
     *
     * @return Collection<int, string>
     */
    protected static function eligibleTeams(mixed $territoryId): Collection
    {
        $territory = filled($territoryId) ? Territory::find($territoryId) : null;

        if (! $territory) {
            return collect();
        }

        return Team::query()
            ->where('active', true)
            ->where('kind', $territory->team_policy->value)
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
