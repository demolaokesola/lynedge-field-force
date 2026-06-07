<?php

namespace App\Filament\Exports;

use App\Models\Distribution;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CompanyRollupExporter extends Exporter
{
    protected static ?string $model = Distribution::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('region_name')->label('Region'),
            ExportColumn::make('territory_name')->label('Territory'),
            ExportColumn::make('team_name')->label('Team'),
            ExportColumn::make('invoice_count')->label('Invoices'),
            ExportColumn::make('total_value')->label('Total Value (₦)'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "Company roll-up export: {$export->successful_rows} rows.";
    }
}
