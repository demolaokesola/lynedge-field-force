<?php

namespace App\Support\Enums;

use Illuminate\Support\Str;

/**
 * Shared defaults for backed enums that drive Filament UI.
 *
 * Apply alongside the Filament contracts, e.g.:
 *
 *   enum TeamKind: string implements HasColor, HasIcon, HasLabel
 *   {
 *       use HasFilamentEnum;
 *
 *       case Strict = 'strict';
 *       case Liberal = 'liberal';
 *   }
 *
 * {@see getLabel()} gives a sensible default; override {@see getColor()} /
 * {@see getIcon()} per enum where the UI needs colour or icon coding.
 *
 * @mixin \BackedEnum
 */
trait HasFilamentEnum
{
    /**
     * Human-readable label derived from the case name (e.g. SalesRep -> "Sales Rep").
     */
    public function getLabel(): string
    {
        return Str::headline($this->name);
    }

    /**
     * Filament colour for this case. Override per enum; defaults to none.
     */
    public function getColor(): string|array|null
    {
        return null;
    }

    /**
     * Filament icon for this case. Override per enum; defaults to none.
     */
    public function getIcon(): ?string
    {
        return null;
    }

    /**
     * value => label map for select/filter options.
     *
     * @return array<string|int, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->getLabel()])
            ->all();
    }
}
