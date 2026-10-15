<?php

use App\Mail\AppointmentRescheduled;
use App\Mail\OwnerBookingNotification;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->travelTo(local('2026-10-18 12:00'));
    $this->appointment = makeAppointment(local('2026-10-20 10:00'), [
        'customer_email' => 'priya@example.com',
        'booked_at' => now(),
        'reminder_24h_sent_at' => now(),
        'reminder_2h_sent_at' => now(),
    ]);
    $this->originalUid = $this->appointment->uid;
});

it('shows a slot picker for the same service with the current slot marked', function () {
    $this->get($this->appointment->signedUrl('appointments.reschedule', ['date' => '2026-10-20']))
        ->assertOk()
        ->assertSee('Reschedule your Consultation')
        ->assertSee('(current)', false)
        ->assertSee(local('2026-10-20 10:30')->toIso8601ZuluString());
});

it('moves the appointment, keeps the UID, bumps SEQUENCE and resets reminders', function () {
    $newStart = local('2026-10-21 16:00');

    $this->post($this->appointment->rescheduleUrl(), ['starts_at' => $newStart->toIso8601ZuluString()])
        ->assertRedirect();

    $appointment = $this->appointment->fresh();

    expect($appointment->uid)->toBe($this->originalUid)
        ->and($appointment->sequence)->toBe(1)
        ->and($appointment->starts_at->equalTo($newStart))->toBeTrue()
        ->and($appointment->ends_at->equalTo($newStart->addMinutes(30)))->toBeTrue()
        ->and($appointment->reminder_24h_sent_at)->toBeNull()
        ->and($appointment->reminder_2h_sent_at)->toBeNull()
        ->and($appointment->rescheduled_at)->not->toBeNull();
});

it('sends an updated REQUEST invite with the same UID and SEQUENCE 1', function () {
    $this->post($this->appointment->rescheduleUrl(), ['starts_at' => local('2026-10-21 16:00')->toIso8601ZuluString()]);

    Mail::assertQueued(AppointmentRescheduled::class, fn ($mail) => $mail->hasTo('priya@example.com'));

    $email = renderEmail(Mail::queued(AppointmentRescheduled::class)->first());
    $part = emailAttachments($email)['invite.ics'];
    $ics = unfoldIcs($part->getBody());

    expect($part->getPreparedHeaders()->get('Content-Type')->getBodyAsString())->toContain('method=REQUEST')
        ->and($ics)->toContain("METHOD:REQUEST\r\n")
        ->toContain("UID:{$this->originalUid}@booking.test\r\n")
        ->toContain("SEQUENCE:1\r\n")
        ->toContain("DTSTART:20261021T103000Z\r\n")
        ->and($email->getSubject())->toBe('Rescheduled: Consultation moved to Wed 21 Oct, 4:00 PM IST')
        ->and($email->getTextBody())->toContain('Was: Tuesday 20 October 2026, 10:00 AM IST');
});

it('notifies the owner with the old and new time', function () {
    $this->post($this->appointment->rescheduleUrl(), ['starts_at' => local('2026-10-21 16:00')->toIso8601ZuluString()]);

    Mail::assertQueued(OwnerBookingNotification::class, fn ($mail) => $mail->hasTo('owner@booking.test')
        && $mail->event === OwnerBookingNotification::RESCHEDULED);

    $email = renderEmail(Mail::queued(OwnerBookingNotification::class)->first());

    expect($email->getTextBody())
        ->toContain('Tuesday 20 October 2026, 10:00 AM IST → Wednesday 21 October 2026, 4:00 PM IST');
});

it('frees the old slot and takes the new one', function () {
    $this->post($this->appointment->rescheduleUrl(), ['starts_at' => local('2026-10-21 16:00')->toIso8601ZuluString()]);

    $availability = app(AvailabilityService::class);
    $tuesday = $availability->slotsFor(consultation(), CarbonImmutable::parse('2026-10-20', 'Asia/Kolkata'));
    $wednesday = $availability->slotsFor(consultation(), CarbonImmutable::parse('2026-10-21', 'Asia/Kolkata'));

    expect($tuesday->contains(fn ($s) => $s->equalTo(local('2026-10-20 10:00'))))->toBeTrue()
        ->and($wednesday->contains(fn ($s) => $s->equalTo(local('2026-10-21 16:00'))))->toBeFalse();
});

it('can move to a slot overlapping its own current time', function () {
    $this->post($this->appointment->rescheduleUrl(), ['starts_at' => local('2026-10-20 10:30')->toIso8601ZuluString()])
        ->assertSessionHasNoErrors();

    expect($this->appointment->fresh()->starts_at->equalTo(local('2026-10-20 10:30')))->toBeTrue();
});

it('refuses to move into a taken slot', function () {
    makeAppointment(local('2026-10-21 16:00'));

    $this->post($this->appointment->rescheduleUrl(), ['starts_at' => local('2026-10-21 16:00')->toIso8601ZuluString()])
        ->assertSessionHasErrors(['starts_at' => 'That slot was just taken, please pick another.']);

    expect($this->appointment->fresh()->sequence)->toBe(0);
    Mail::assertNothingQueued();
});

it('shows a friendly message for a cancelled appointment', function () {
    $this->appointment->update(['status' => 'cancelled', 'cancelled_at' => now()]);

    $this->get($this->appointment->rescheduleUrl())->assertOk()->assertSee('already cancelled');

    $this->post($this->appointment->rescheduleUrl(), ['starts_at' => local('2026-10-21 16:00')->toIso8601ZuluString()])
        ->assertOk()
        ->assertSee('already cancelled');

    Mail::assertNothingQueued();
});
