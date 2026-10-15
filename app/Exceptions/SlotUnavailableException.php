<?php

namespace App\Exceptions;

use RuntimeException;

class SlotUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'That slot was just taken, please pick another.')
    {
        parent::__construct($message);
    }
}
