<?php

namespace App\Filament\Field\Resources\Customers\Tables;

use App\Enums\CustomerType;
use App\Filament\Field\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('territory.name')
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(CustomerType::class),
            ])
            ->recordUrl(fn (Customer $record): string => CustomerResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                // Hidden automatically when CustomerPolicy::update() denies (not own row).
                EditAction::make(),
            ]);
    }
}
