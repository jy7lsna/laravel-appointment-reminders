<?php

use App\Models\Appointment;
use App\Services\IcsBuilder;

beforeEach(function () {
    $this->travelTo(local('2026-10-18 12:00'));
    $this->appointment = makeAppointment(local('2026-10-20 10:00'), [
        'customer_name' => 'Priya Sharma',
        'customer_email' => 'priya@example.com',
    ]);
});

it('builds a RFC 5545 REQUEST with UTC times and the required properties', function () {
    $ics = unfoldIcs(app(IcsBuilder::class)->request($this->appointment));

    expect($ics)
        ->toStartWith("BEGIN:VCALENDAR\r\n")
        ->toContain("VERSION:2.0\r\n")
        ->toContain("METHOD:REQUEST\r\n")
        ->toContain("UID:{$this->appointment->uid}@booking.test\r\n")
        ->toContain("SEQUENCE:0\r\n")
        ->toContain("STATUS:CONFIRMED\r\n")
        ->toContain("SUMMARY:Consultation with Laravel\r\n")
        ->toContain("DTSTART:20261020T043000Z\r\n")
        ->toContain("DTEND:20261020T050000Z\r\n")
        ->toContain("DTSTAMP:20261018T063000Z\r\n")
        ->toContain('LAST-MODIFIED:20261018T063000Z')
        ->toContain('LOCATION:12 MG Road\, Bengaluru')
        ->toContain('ORGANIZER;CN=Laravel:MAILTO:appointments@booking.test')
        ->toContain('ATTENDEE;CN=Priya Sharma;ROLE=REQ-PARTICIPANT;RSVP=FALSE:MAILTO:priya@example.com')
        ->toContain('Need to cancel? https://booking.test/appointments/'.$this->appointment->uid.'/cancel?expires=')
        ->toContain('Need to reschedule? https://booking.test/appointments/')
        ->not->toContain('TZID')
        ->not->toContain('VTIMEZONE');
});

it('builds a CANCEL with the same UID', function () {
    $this->appointment->update(['status' => 'cancelled', 'sequence' => 2]);

    $ics = unfoldIcs(app(IcsBuilder::class)->cancel($this->appointment));

    expect($ics)
        ->toContain("METHOD:CANCEL\r\n")
        ->toContain("UID:{$this->appointment->uid}@booking.test\r\n")
        ->toContain("SEQUENCE:2\r\n")
        ->toContain("STATUS:CANCELLED\r\n");
});

it('never changes the UID', function () {
    $uid = $this->appointment->uid;

    $this->appointment->update(['starts_at' => local('2026-10-21 10:00'), 'sequence' => 1]);

    expect($this->appointment->fresh()->uid)->toBe($uid);
});

it('writes SEQUENCE:0 for a freshly created appointment that never set it', function () {
    $appointment = Appointment::create([
        'service_id' => consultation()->id,
        'customer_name' => 'Priya',
        'customer_email' => 'priya@example.com',
        'customer_phone' => '123456',
        'starts_at' => local('2026-10-21 10:00'),
        'ends_at' => local('2026-10-21 10:30'),
        'booked_at' => now(),
    ]);

    expect(unfoldIcs(app(IcsBuilder::class)->request($appointment)))->toContain("SEQUENCE:0\r\n");
});
