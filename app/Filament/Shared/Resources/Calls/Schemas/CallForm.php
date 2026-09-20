<?php

namespace App\Filament\Shared\Resources\Calls\Schemas;

use App\Enums\CallType;
use App\Models\DemandCreator;
use App\Models\Position;
use App\Models\Product;
use App\Services\RepScope;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class CallForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('position_id')
                    ->label('Position')
                    ->options(fn (): array => static::positionOptions())
                    ->default(fn (): ?int => array_key_first(static::positionOptions()))
                    ->required()
                    ->native(false)
                    ->live(),
                DateTimePicker::make('called_at')
                    ->required()
                    ->default(now())
                    ->seconds(false),
                Select::make('call_type')
                    ->options(CallType::class)
                    ->required()
                    ->native(false),
                Select::make('demand_creator_id')
                    ->label('Demand creator')
                    ->options(fn (Get $get): array => static::demandCreatorOptions($get('position_id')))
                    ->required()
                    ->searchable()
                    ->preload(),
                CheckboxList::make('products')
                    ->relationship(
                        'products',
                        'name',
                        modifyQueryUsing: fn (Builder $query, Get $get): Builder => static::scopeProductsToTeam($query, $get('position_id')),
                    )
                    ->columns(2)
                    ->columnSpanFull(),
                TextInput::make('latitude')
                    ->numeric()
                    ->readOnly()
                    ->hintAction(
                        Action::make('captureLocation')
                            ->label('Use my location')
                            ->icon(Heroicon::MapPin)
                            ->actionJs(static::captureLocationJs()),
                    )
                    // Auto-capture on create only; never overwrite a stored location on edit.
                    ->extraAttributes(fn (string $operation): array => $operation === 'create'
                        ? ['x-init' => static::captureLocationJs()]
                        : []),
                TextInput::make('longitude')
                    ->numeric()
                    ->readOnly(),
                Textarea::make('notes')
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * The acting rep's active positions, as id => "Code — Territory".
     *
     * @return array<int, string>
     */
    public static function positionOptions(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        return app(RepScope::class)->positionOptions($user);
    }

    /**
     * Demand creators in the chosen position's territory.
     *
     * @return array<int, string>
     */
    public static function demandCreatorOptions(int|string|null $positionId): array
    {
        $territoryId = static::territoryIdFor($positionId);

        if ($territoryId === null) {
            return [];
        }

        return DemandCreator::query()
            ->where('territory_id', $territoryId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Restrict the products list to the chosen position's team (one team per position).
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public static function scopeProductsToTeam(Builder $query, int|string|null $positionId): Builder
    {
        $teamId = $positionId === null
            ? null
            : Position::whereKey($positionId)->value('team_id');

        return $query->whereHas('teams', fn (Builder $teams): Builder => $teams->whereKey($teamId));
    }

    /**
     * Fill latitude/longitude from the browser's Geolocation API. Runs in the
     * field's Alpine scope, so $set targets sibling fields without a round trip.
     * No-op when unsupported or denied — the fields simply stay empty.
     */
    protected static function captureLocationJs(): string
    {
        return <<<'JS'
            navigator.geolocation?.getCurrentPosition(
                (position) => {
                    $set('latitude', position.coords.latitude.toFixed(7))
                    $set('longitude', position.coords.longitude.toFixed(7))
                },
                () => {},
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 },
            )
            JS;
    }

    protected static function territoryIdFor(int|string|null $positionId): ?int
    {
        if ($positionId === null) {
            return null;
        }

        return Position::whereKey($positionId)->value('territory_id');
    }
}
