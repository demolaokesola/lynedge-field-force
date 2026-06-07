<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LeaderboardExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Rep'),
            ExportColumn::make('target_ytd')->label('Target YTD'),
            ExportColumn::make('actual_ytd')->label('Actual YTD'),
            ExportColumn::make('attainment_pct')->label('Attainment %'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "Attainment leaderboard export: {$export->successful_rows} reps.";
    }
}
