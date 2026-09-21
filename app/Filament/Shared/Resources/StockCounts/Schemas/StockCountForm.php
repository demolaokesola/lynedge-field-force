<?php

namespace App\Filament\Shared\Resources\StockCounts\Schemas;

use App\Enums\PositionStatus;
use App\Enums\StockCountKind;
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

/**
 * The stock-count form shared by the Office and Field resources. Operations picks the
 * kind and any active position; a rep is limited to Periodic counts of positions they
 * currently hold, so the kind field is hidden for them and forced server-side.
 */
class StockCountForm
{
    public static function configure(Schema $schema, bool $forOperations): Schema
    {
        return $schema
            ->components([
                Select::make('kind')
                    ->options(StockCountKind::class)
                    ->default(StockCountKind::Periodic)
                    ->required()
                    ->native(false)
                    ->helperText('An opening count sets starting balances: any catalogue product you leave out is taken as zero on hand.')
                    ->visible($forOperations),
                Select::make('position_id')
                    ->label('Position')
                    ->options(fn (): array => $forOperations
                        ? static::allActivePositionOptions()
                        : app(RepScope::class)->positionOptions(auth()->user()))
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->live(),
                DatePicker::make('count_date')
                    ->label('Count date')
                    ->required()
                    ->default(today())
                    ->maxDate(today())
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
                            ->distinct()
                            ->native(false)
                            ->live(),
                        TextInput::make('counted_quantity')
                            ->label('Counted quantity')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->minItems(1)
                    ->reorderable(false),
            ]);
    }

    /**
     * Every active position, as id => "Code — Territory".
     *
     * @return array<int, string>
     */
    public static function allActivePositionOptions(): array
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
     * Active products in the chosen position's team catalogue.
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
            ->where('active', true)
            ->whereHas('teams', fn (Builder $q): Builder => $q->whereKey($teamId))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
