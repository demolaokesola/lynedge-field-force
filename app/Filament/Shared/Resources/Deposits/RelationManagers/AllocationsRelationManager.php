<?php

namespace App\Filament\Shared\Resources\Deposits\RelationManagers;

use App\Enums\DistributionStatus;
use App\Models\Deposit;
use App\Models\Distribution;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AllocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'allocations';

    protected static ?string $title = 'Allocations';

    public function form(Schema $schema): Schema
    {
        /** @var Deposit $deposit */
        $deposit = $this->ownerRecord;
        $remaining = $deposit->remainingBalance()->amount;

        return $schema
            ->components([
                Select::make('distribution_id')
                    ->label('Invoice (optional)')
                    ->options(fn (): array => $this->postedDistributionOptions())
                    ->searchable()
                    ->native(false)
                    ->placeholder('Unallocated cash reserve'),
                TextInput::make('amount')
                    ->label('Amount (₦)')
                    ->numeric()
                    ->minValue(0.01)
                    ->maxValue($remaining)
                    ->required()
                    ->helperText("Remaining balance: ₦{$remaining}"),
                DateTimePicker::make('allocated_at')
                    ->required()
                    ->default(now())
                    ->native(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('allocated_at', 'desc')
            ->columns([
                TextColumn::make('distribution.invoice_number')
                    ->label('Invoice')
                    ->placeholder('— unallocated')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount (₦)')
                    ->sortable(),
                TextColumn::make('allocatedBy.name')
                    ->label('Allocated By')
                    ->sortable(),
                TextColumn::make('allocated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => array_merge($data, [
                        'allocated_by_user_id' => auth()->id(),
                    ])),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Posted distributions in the same territory as the deposit, for allocation matching.
     *
     * @return array<int, string>
     */
    private function postedDistributionOptions(): array
    {
        /** @var Deposit $deposit */
        $deposit = $this->ownerRecord;

        return Distribution::query()
            ->where('territory_id', $deposit->territory_id)
            ->where('status', DistributionStatus::Posted)
            ->orderBy('invoice_date', 'desc')
            ->pluck('invoice_number', 'id')
            ->all();
    }
}
