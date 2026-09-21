<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Typed accessor for the Office-managed stock switches. Owns the defaults and the
 * cache so callers never touch {@see Setting} rows directly.
 *
 *  - consumption_enabled: the go-live switch. Until it is on, posting a distribution
 *    does not touch the ledger, so Operations can enter opening balances first.
 *  - block_negative_stock: when on, a distribution whose lines would take any balance
 *    below zero is refused instead of posted with a warning.
 *  - low_stock_threshold: balances at or below this are flagged to reps on their
 *    dashboard. Zero disables the flag.
 */
class StockSettings
{
    public const string CONSUMPTION_ENABLED = 'stock.consumption_enabled';

    public const string BLOCK_NEGATIVE_STOCK = 'stock.block_negative_stock';

    public const string LOW_STOCK_THRESHOLD = 'stock.low_stock_threshold';

    public const int DEFAULT_LOW_STOCK_THRESHOLD = 10;

    public function consumptionEnabled(): bool
    {
        return $this->bool(self::CONSUMPTION_ENABLED, default: false);
    }

    public function blockNegativeStock(): bool
    {
        return $this->bool(self::BLOCK_NEGATIVE_STOCK, default: false);
    }

    public function lowStockThreshold(): int
    {
        $value = Cache::rememberForever(
            $this->cacheKey(self::LOW_STOCK_THRESHOLD),
            fn (): ?int => Setting::query()->where('key', self::LOW_STOCK_THRESHOLD)->value('value'),
        );

        return $value ?? self::DEFAULT_LOW_STOCK_THRESHOLD;
    }

    public function setLowStockThreshold(int $threshold): void
    {
        $this->put(self::LOW_STOCK_THRESHOLD, max(0, $threshold));
    }

    public function setConsumptionEnabled(bool $enabled): void
    {
        $this->put(self::CONSUMPTION_ENABLED, $enabled);
    }

    public function setBlockNegativeStock(bool $block): void
    {
        $this->put(self::BLOCK_NEGATIVE_STOCK, $block);
    }

    private function bool(string $key, bool $default): bool
    {
        $value = Cache::rememberForever(
            $this->cacheKey($key),
            fn (): ?bool => Setting::query()->where('key', $key)->value('value'),
        );

        return $value ?? $default;
    }

    private function put(string $key, bool|int $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget($this->cacheKey($key));
    }

    private function cacheKey(string $key): string
    {
        return "settings:{$key}";
    }
}
