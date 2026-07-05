<?php

namespace App\Filament\Exports;

use App\Models\Position;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class VacantPositionsExporter extends Exporter
{
    protected static ?string $model = Position::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('territory.region.name')->label('Region'),
            ExportColumn::make('territory.name')->label('Territory'),
            ExportColumn::make('team.name')->label('Team'),
            ExportColumn::make('code')->label('Position'),
            ExportColumn::make('status')->label('Status'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "Vacant positions export: {$export->successful_rows} positions.";
    }
}
