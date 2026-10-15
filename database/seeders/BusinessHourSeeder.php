<?php

namespace Database\Seeders;

use App\Models\BusinessHour;
use Illuminate\Database\Seeder;

class BusinessHourSeeder extends Seeder
{
    /**
     * Mon–Fri 09:00–18:00, Sat 10:00–14:00, Sun closed (BUSINESS_TIMEZONE).
     */
    public function run(): void
    {
        $hours = [
            0 => null,
            1 => ['09:00', '18:00'],
            2 => ['09:00', '18:00'],
            3 => ['09:00', '18:00'],
            4 => ['09:00', '18:00'],
            5 => ['09:00', '18:00'],
            6 => ['10:00', '14:00'],
        ];

        foreach ($hours as $weekday => $range) {
            BusinessHour::updateOrCreate(['weekday' => $weekday], [
                'opens_at' => $range[0] ?? null,
                'closes_at' => $range[1] ?? null,
                'is_closed' => $range === null,
            ]);
        }
    }
}
