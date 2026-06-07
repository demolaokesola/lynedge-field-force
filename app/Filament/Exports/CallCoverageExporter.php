<?php

namespace App\Filament\Exports;

use App\Models\Territory;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CallCoverageExporter extends Exporter
{
    protected static ?string $model = Territory::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('region_name')->label('Region'),
            ExportColumn::make('name')->label('Territory'),
            ExportColumn::make('call_count')->label('Calls This Month'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "Call coverage export: {$export->successful_rows} territories.";
    }
}
