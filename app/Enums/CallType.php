<?php

namespace App\Enums;

use App\Support\Enums\HasFilamentEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * How a rep made contact on a call. A fixed vocabulary, so a backed enum rather than a
 * lookup table (cf. {@see DemandCreatorType}).
 */
enum CallType: string implements HasColor, HasLabel
{
    use HasFilamentEnum;

    case PhysicalVisit = 'physical_visit';
    case PhoneCall = 'phone_call';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PhysicalVisit => 'info',
            self::PhoneCall => 'success',
        };
    }
}
