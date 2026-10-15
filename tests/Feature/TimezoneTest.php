<?php

use App\Mail\AppointmentConfirmed;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->travelTo(local('2026-10-18 12:00'));
});

it('stores appointment times in UTC', function () {
    Mail::fake();

    $this->post('/book/consultation', [
        'customer_name' => 'Priya',
        'customer_email' => 'priya@example.com',
        'customer_phone' => '+91 98765 43210',
        'starts_at' => '2026-10-19T03:30:00Z', // 09:00 IST
    ])->assertRedirect();

    $raw = DB::table('appointments')->first();

    expect($raw->starts_at)->toBe('2026-10-19 03:30:00')
        ->and($raw->ends_at)->toBe('2026-10-19 04:00:00');
});

it('shows business-timezone times in emails', function () {
    $appointment = makeAppointment(local('2026-10-19 09:00'));

    $email = renderEmail(new AppointmentConfirmed($appointment));

    expect($email->getSubject())->toBe('Confirmed: Consultation on Mon 19 Oct, 9:00 AM IST')
        ->and($email->getHtmlBody())->toContain('Monday 19 October 2026, 9:00 AM IST')
        ->and($email->getTextBody())->toContain('Monday 19 October 2026, 9:00 AM IST')
        ->and($email->getTextBody())->not->toContain('3:30 AM');
});

it('follows BUSINESS_TIMEZONE for display while storage stays UTC', function () {
    $appointment = makeAppointment(local('2026-10-19 09:00')); // 03:30Z
    config(['booking.timezone' => 'Europe/London']);

    expect($appointment->localStartsAt()->format('H:i T'))->toBe('04:30 BST')
        ->and(Appointment::first()->starts_at->format('H:i e'))->toBe('03:30 UTC');
});
