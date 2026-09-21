<?php

namespace App\Filament\Field\Widgets;

use App\Filament\Field\Resources\StockLevels\StockLevelResource;
use App\Models\PositionProductStock;
use App\Services\StockSettings;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Products at or below the Office-set low-stock threshold, for the positions the rep
 * holds or supervises. Hidden entirely when the threshold is zero.
 */
class LowStockWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->hasAnyRole(['sales_rep', 'superuser'])
            && app(StockSettings::class)->lowStockThreshold() > 0;
    }

    public function table(Table $table): Table
    {
        $threshold = app(StockSettings::class)->lowStockThreshold();

        return $table
            ->heading((auth()->user()?->isSupervisor() ? 'Supervised Low Stock' : 'My Low Stock')." (≤ {$threshold})")
            ->query(
                PositionProductStock::query()
                    ->visibleTo(auth()->user())
                    ->where('quantity', '<=', $threshold)
                    ->with(['position', 'product'])
            )
            ->columns([
                TextColumn::make('position.code')
                    ->label('Position'),
                TextColumn::make('product.name')
                    ->label('Product'),
                TextColumn::make('quantity')
                    ->label('On hand')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn (string $state): string => (float) $state < 0 ? 'danger' : 'warning'),
            ])
            ->defaultSort('quantity', 'asc')
            ->emptyStateHeading('No products running low')
            ->paginated([5, 10])
            ->headerActions([
                Action::make('all')
                    ->label('All stock levels')
                    ->link()
                    ->url(StockLevelResource::getUrl('index')),
            ]);
    }
}
