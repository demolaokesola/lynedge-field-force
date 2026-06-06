<?php

namespace App\Support;

use App\Casts\MoneyCast;
use InvalidArgumentException;
use Stringable;

/**
 * Immutable Naira money value object backed by a fixed-scale decimal string.
 *
 * All arithmetic is performed with BCMath at scale 2 (kobo) so values never touch
 * floating point. Stored and retrieved as decimal(18,2) via the {@see MoneyCast}.
 */
final class Money implements Stringable
{
    public const SCALE = 2;

    public const CURRENCY = 'NGN';

    public const SYMBOL = '₦';

    /**
     * @var string Normalised decimal string with exactly self::SCALE decimal places.
     */
    public readonly string $amount;

    public function __construct(string|int|float $amount)
    {
        $this->amount = self::normalise($amount);
    }

    public static function of(string|int|float $amount): self
    {
        return new self($amount);
    }

    public static function zero(): self
    {
        return new self('0');
    }

    public function plus(self $other): self
    {
        return new self(bcadd($this->amount, $other->amount, self::SCALE));
    }

    public function minus(self $other): self
    {
        return new self(bcsub($this->amount, $other->amount, self::SCALE));
    }

    /**
     * @param  string|int|float  $factor  A scalar multiplier (e.g. a quantity).
     */
    public function times(string|int|float $factor): self
    {
        return new self(bcmul($this->amount, (string) $factor, self::SCALE));
    }

    public function isZero(): bool
    {
        return bccomp($this->amount, '0', self::SCALE) === 0;
    }

    public function equals(self $other): bool
    {
        return bccomp($this->amount, $other->amount, self::SCALE) === 0;
    }

    public function format(): string
    {
        return self::SYMBOL.number_format((float) $this->amount, self::SCALE);
    }

    public function __toString(): string
    {
        return $this->amount;
    }

    private static function normalise(string|int|float $amount): string
    {
        $value = is_float($amount) ? sprintf('%.'.self::SCALE.'f', $amount) : (string) $amount;

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Invalid money amount: [{$value}].");
        }

        return bcadd($value, '0', self::SCALE);
    }
}
