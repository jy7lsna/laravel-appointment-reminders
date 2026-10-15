<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
