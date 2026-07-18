<?php

namespace App\Filament\Widgets;

use App\Enums\DepositStatus;
use App\Filament\Exports\UnreconciledDepositsExporter;
use App\Models\Deposit;
use App\Support\Money;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class UnreconciledDepositsWidget extends BaseWidget
{
    protected static ?string $heading = 'Unreconciled Deposits';

    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['superuser', 'accountant']) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->baseQuery())
            ->columns([
                TextColumn::make('deposit_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('territory.name')
                    ->label('Territory'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (mixed $state): ?string => match (true) {
                        $state === null => null,
                        $state instanceof Money => $state->format(),
                        default => Money::of($state)->format(),
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('reference')
                    ->label('Bank Ref')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        DepositStatus::Unreconciled->value => 'Unreconciled',
                        DepositStatus::PartiallyReconciled->value => 'Partially Reconciled',
                    ]),
            ])
            ->defaultSort('deposit_date')
            ->striped()
            ->headerActions([
                ExportAction::make()
                    ->exporter(UnreconciledDepositsExporter::class)
                    ->formats([ExportFormat::Csv])
                    ->modifyQueryUsing(fn (Builder $query) => $this->baseQuery()),
            ]);
    }

    private function baseQuery(): Builder
    {
        return Deposit::query()
            ->whereIn('status', [
                DepositStatus::Unreconciled->value,
                DepositStatus::PartiallyReconciled->value,
            ])
            ->with('customer', 'territory');
    }
}
