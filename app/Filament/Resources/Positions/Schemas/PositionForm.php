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
                    ->live()
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
                    ->label('Code')
                    ->helperText('Derived automatically as {territory code}-{team code}; the persisted value (including any collision suffix) is set on save.')
                    ->live()
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn (Get $get): ?string => self::previewCode($get('territory_id'), $get('team_id'))),
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

    /**
     * Best-effort preview of the derived code for the form UI. The persisted value
     * (including any collision suffix — see PositionObserver::deriveCode()) is
     * computed authoritatively on save and may differ if a collision is resolved
     * between preview time and save time.
     */
    protected static function previewCode(mixed $territoryId, mixed $teamId): ?string
    {
        $territory = filled($territoryId) ? Territory::find($territoryId) : null;
        $team = filled($teamId) ? Team::find($teamId) : null;

        if (! $territory || ! $team) {
            return null;
        }

        return "{$territory->code}-{$team->code}";
    }
}
