<?php

namespace App\Filament\Exports;

use App\Models\RepMonthlyTarget;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class AttainmentExporter extends Exporter
{
    protected static ?string $model = RepMonthlyTarget::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('product_name')->label('Product'),
            ExportColumn::make('target_ytd')->label('Target YTD (units)'),
            ExportColumn::make('actual_ytd')->label('Actual YTD (units)'),
            ExportColumn::make('attainment_pct')->label('Attainment %'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "YTD attainment export: {$export->successful_rows} rows.";
    }
}
