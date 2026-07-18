<?php

namespace App\Filament\Office\Resources\StockDispatches\Schemas;

use App\Enums\PositionStatus;
use App\Models\Position;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class StockDispatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('position_id')
                    ->label('Position')
                    ->options(fn (): array => static::positionOptions())
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->live(),
                DatePicker::make('dispatch_date')
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
                            ->live(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0.01)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->minItems(1)
                    ->reorderable(false),
            ]);
    }

    /**
     * Every active position, as id => "Code — Territory" — Operations may dispatch to any
     * position, unlike a rep who is scoped to their own.
     *
     * @return array<int, string>
     */
    public static function positionOptions(): array
    {
        return Position::query()
            ->where('status', PositionStatus::Active)
            ->with('territory')
            ->get()
            ->mapWithKeys(fn (Position $position): array => [
                $position->id => "{$position->code} — {$position->territory->name}",
            ])
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
}
