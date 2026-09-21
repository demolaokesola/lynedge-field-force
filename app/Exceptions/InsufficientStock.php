<?php

namespace App\Exceptions;

use App\Services\DistributionPostingService;
use RuntimeException;

/**
 * Thrown by {@see DistributionPostingService} when block_negative_stock is on and a
 * distribution's lines would take one or more balances below zero.
 */
class InsufficientStock extends RuntimeException
{
    /**
     * @param  list<array{product: string, available: string, requested: string}>  $shortfalls
     */
    public function __construct(public readonly array $shortfalls)
    {
        parent::__construct('Insufficient stock for one or more lines.');
    }
}
