<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * The kind of buying customer a distribution is invoiced to (customers.type).
 *
 * Distinct from {@see DemandCreatorType}, which is a runtime-editable lookup table:
 * customer types are a fixed vocabulary, so they live as a backed enum.
 */
enum CustomerType: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case Pharmacy = 'pharmacy';
    case Hospital = 'hospital';
    case Clinic = 'clinic';
    case Wholesaler = 'wholesaler';
    case RetailChemist = 'retail_chemist';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pharmacy => 'success',
            self::Hospital => 'danger',
            self::Clinic => 'warning',
            self::Wholesaler => 'info',
            self::RetailChemist => 'gray',
        };
    }
}
