<?php

use Illuminate\Support\Facades\Schedule;

// Reminder windows are wide enough that a late run still sends the right
// email, and claims are atomic, so overlapping runs are harmless — but
// there is no reason to start a second run while one is still going.
Schedule::command('appointments:send-reminders')
    ->everyFiveMinutes()
    ->withoutOverlapping();
