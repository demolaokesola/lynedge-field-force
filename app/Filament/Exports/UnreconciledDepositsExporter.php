<?php

namespace App\Filament\Exports;

use App\Models\Deposit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class UnreconciledDepositsExporter extends Exporter
{
    protected static ?string $model = Deposit::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('deposit_date')->label('Date'),
            ExportColumn::make('customer.name')->label('Customer'),
            ExportColumn::make('territory.name')->label('Territory'),
            ExportColumn::make('amount')->label('Amount (₦)'),
            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('reference')->label('Bank Ref'),
            ExportColumn::make('bank')->label('Bank'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "Unreconciled deposits export: {$export->successful_rows} deposits.";
    }
}
