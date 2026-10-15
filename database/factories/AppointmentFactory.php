<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = now()->utc()->addDays(3)->startOfHour();

        return [
            'service_id' => Service::factory(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('+91 98#######'),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'status' => AppointmentStatus::Confirmed,
            'sequence' => 0,
            'booked_at' => now(),
        ];
    }

    public function startingAt($startsAt, int $minutes = 30): static
    {
        return $this->state(fn () => [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($minutes),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => AppointmentStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
