<?php

namespace App\Exceptions;

use Exception;

class UtrRateLimitException extends Exception
{
    public function __construct(string $message, public readonly ?int $retryAfterSeconds = null)
    {
        parent::__construct($message);
    }
}
