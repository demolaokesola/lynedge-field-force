<?php

namespace App\Filament\Field\Resources\StockAdjustments\Tables;

use App\Enums\StockAdjustmentStatus;
use App\Filament\Field\Resources\StockAdjustments\StockAdjustmentResource;
use App\Models\StockAdjustment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockAdjustmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('adjustment_date', 'desc')
            ->columns([
                TextColumn::make('position.code')
                    ->label('Position')
                    ->sortable(),
                TextColumn::make('adjustment_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('adjustedBy.name')
                    ->label('Adjusted by'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(StockAdjustmentStatus::class),
            ])
            ->recordUrl(fn (StockAdjustment $record): string => StockAdjustmentResource::getUrl('view', ['record' => $record]));
    }
}
