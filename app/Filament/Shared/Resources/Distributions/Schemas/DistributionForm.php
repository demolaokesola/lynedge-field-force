<?php

namespace App\Filament\Shared\Resources\Distributions\Schemas;

use App\Models\Customer;
use App\Models\Position;
use App\Models\Product;
use App\Services\RepScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DistributionForm
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
                Select::make('customer_id')
                    ->label('Customer')
                    ->options(fn (Get $get): array => static::customerOptions($get('position_id')))
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->live(onBlur: true),
                TextInput::make('invoice_number')
                    ->required()
                    ->maxLength(64)
                    ->unique(ignoreRecord: true),
                DatePicker::make('invoice_date')
                    ->required()
                    ->default(today())
                    ->native(false),
                Textarea::make('notes')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Repeater::make('lines')
                    ->relationship('lines')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(fn (Get $get): array => static::productOptions($get('../../position_id')))
                            ->required()
                            ->native(false)
                            ->live(onBlur: true),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                        TextInput::make('unit_price')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                    ])
                    ->columns(3)
                    ->columnSpanFull()
                    ->minItems(1)
                    ->reorderable(false),
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

        return app(RepScope::class)->activePositions($user)
            ->load('territory')
            ->mapWithKeys(fn (Position $position): array => [
                $position->id => "{$position->code} — {$position->territory->name}",
            ])
            ->all();
    }

    /**
     * Customers in the chosen position's territory.
     *
     * @return array<int, string>
     */
    public static function customerOptions(int|string|null $positionId): array
    {
        $territoryId = static::territoryIdFor($positionId);

        if ($territoryId === null) {
            return [];
        }

        return Customer::query()
            ->where('territory_id', $territoryId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Products in the chosen position's team catalogue.
     *
     * @return array<int, string>
     */
    public static function productOptions(int|string|null $positionId): array
    {
        if ($positionId === null) {
            return [];
        }

        $teamId = Position::whereKey($positionId)->value('team_id');

        if ($teamId === null) {
            return [];
        }

        return Product::query()
            ->whereHas('teams', fn (Builder $q): Builder => $q->whereKey($teamId))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected static function territoryIdFor(int|string|null $positionId): ?int
    {
        if ($positionId === null) {
            return null;
        }

        return Position::whereKey($positionId)->value('territory_id');
    }
}
