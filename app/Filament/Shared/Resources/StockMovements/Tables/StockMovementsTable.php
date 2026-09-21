<?php

namespace App\Filament\Shared\Resources\StockMovements\Tables;

use App\Enums\StockMovementType;
use App\Models\DistributionLine;
use App\Models\StockAdjustmentLine;
use App\Models\StockCountLine;
use App\Models\StockDispatchLine;
use App\Models\StockMovement;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The immutable stock ledger, shared by the Field and Office resources. One row per
 * change to a (position, product) balance, with the document line that caused it.
 */
class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['position', 'product', 'causedBy', 'source']))
            ->columns([
                TextColumn::make('effective_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('position.code')
                    ->label('Position')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('quantity_delta')
                    ->label('Change')
                    ->numeric(2)
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => ((float) $state > 0 ? '+' : '').number_format((float) $state, 2))
                    ->color(fn (string $state): string => (float) $state < 0 ? 'danger' : 'success'),
                TextColumn::make('source')
                    ->label('Source')
                    ->state(fn (StockMovement $record): string => static::describeSource($record->source)),
                TextColumn::make('causedBy.name')
                    ->label('By')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Recorded')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(StockMovementType::class),
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('position_id')
                    ->label('Position')
                    ->relationship('position', 'code')
                    ->searchable()
                    ->preload(),
                Filter::make('effective_date')
                    ->schema([
                        DatePicker::make('from')->native(false),
                        DatePicker::make('until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $from): Builder => $q->whereDate('effective_date', '>=', $from))
                        ->when($data['until'] ?? null, fn (Builder $q, string $until): Builder => $q->whereDate('effective_date', '<=', $until))),
            ])
            ->defaultSort('effective_date', 'desc');
    }

    /**
     * A short human label for the document line behind a movement.
     */
    public static function describeSource(?Model $source): string
    {
        return match (true) {
            $source instanceof StockDispatchLine => "Dispatch #{$source->stock_dispatch_id}",
            $source instanceof StockAdjustmentLine => "Adjustment #{$source->stock_adjustment_id}",
            $source instanceof StockCountLine => "Count #{$source->stock_count_id}",
            $source instanceof DistributionLine => 'Invoice '.($source->distribution?->invoice_number ?? "#{$source->distribution_id}"),
            default => '—',
        };
    }
}
