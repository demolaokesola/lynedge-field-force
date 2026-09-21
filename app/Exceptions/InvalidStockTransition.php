<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a stock document is asked to move to a status it cannot reach from its
 * current one — most often a double-submit racing the first request. The services
 * re-check status under a row lock, so the policy's earlier check is not enough.
 */
class InvalidStockTransition extends RuntimeException {}
