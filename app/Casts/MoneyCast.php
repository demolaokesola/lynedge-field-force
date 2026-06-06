<?php

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts a decimal(18,2) Naira column to and from the {@see Money} value object.
 *
 * @implements CastsAttributes<Money, Money|string|int|float>
 */
class MoneyCast implements CastsAttributes, SerializesCastableAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::of($value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof Money ? $value->amount : Money::of($value)->amount;
    }

    /**
     * Serialize to a plain decimal string for array/JSON output. This keeps the
     * value scalar so consumers such as Filament form fields (which floatval the
     * hydrated state) never receive the {@see Money} object itself.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof Money ? $value->amount : Money::of($value)->amount;
    }
}
