<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['slug' => 'consultation', 'name' => 'Consultation', 'duration_minutes' => 30,
                'description' => 'A focused 30-minute consultation to understand your needs and plan next steps.'],
            ['slug' => 'session', 'name' => 'Session', 'duration_minutes' => 60,
                'description' => 'A full 60-minute working session.'],
            ['slug' => 'quick-call', 'name' => 'Quick Call', 'duration_minutes' => 15,
                'description' => 'A short 15-minute call for quick questions.'],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(['slug' => $service['slug']], $service + ['is_active' => true]);
        }
    }
}
