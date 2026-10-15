<?php

namespace App\Exceptions;

use RuntimeException;

class AppointmentNotChangeableException extends RuntimeException
{
    public function __construct(string $message = 'This appointment can no longer be changed.')
    {
        parent::__construct($message);
    }
}
