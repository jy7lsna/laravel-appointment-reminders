<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Business details
    |--------------------------------------------------------------------------
    |
    | Shown on the public booking pages, in every email and in the calendar
    | invite. All appointment times are stored in UTC and converted to the
    | business timezone for display.
    |
    */

    'business_name' => env('BUSINESS_NAME', env('APP_NAME', 'Laravel')),

    'timezone' => env('BUSINESS_TIMEZONE', 'America/New_York'),

    'address' => env('BUSINESS_ADDRESS', ''),

    'owner_email' => env('OWNER_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Slot rules
    |--------------------------------------------------------------------------
    */

    // Slots start every N minutes from opening time.
    'slot_interval_minutes' => (int) env('BOOKING_SLOT_INTERVAL_MINUTES', 30),

    // Earliest bookable slot is now + N minutes.
    'min_notice_minutes' => (int) env('BOOKING_MIN_NOTICE_MINUTES', 60),

    // How many days (including today) customers can book ahead.
    'max_days_ahead' => (int) env('BOOKING_MAX_DAYS_AHEAD', 30),

];
