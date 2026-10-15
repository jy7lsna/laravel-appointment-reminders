<?php

use App\Enums\AppointmentStatus;
use App\Mail\AppointmentCancelled;
use App\Mail\AppointmentReminder;
use App\Mail\OwnerBookingNotification;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Mail::fake();
    $this->travelTo(local('2026-10-18 12:00'));
    $this->appointment = makeAppointment(local('2026-10-20 10:00'), [
        'customer_email' => 'priya@example.com',
        'booked_at' => now(),
    ]);
});

it('shows a confirmation page on GET without cancelling', function () {
    $this->get($this->appointment->cancelUrl())
        ->assertOk()
        ->assertSee('Cancel this appointment?')
        ->assertSee('action="'.e($this->appointment->cancelUrl()).'"', false);

    expect($this->appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed);
    Mail::assertNothingQueued();
});

it('cancels on POST and sends a CANCEL invite with the same UID and a higher SEQUENCE', function () {
    $this->post($this->appointment->cancelUrl())->assertRedirect();

    $appointment = $this->appointment->fresh();
    expect($appointment->status)->toBe(AppointmentStatus::Cancelled)
        ->and($appointment->cancelled_at)->not->toBeNull()
        ->and($appointment->sequence)->toBe(1);

    Mail::assertQueued(AppointmentCancelled::class, fn ($mail) => $mail->hasTo('priya@example.com'));
    Mail::assertQueued(OwnerBookingNotification::class, fn ($mail) => $mail->event === OwnerBookingNotification::CANCELLED);

    $email = renderEmail(Mail::queued(AppointmentCancelled::class)->first());
    $attachments = emailAttachments($email);
    expect($attachments)->toHaveKey('cancel.ics');

    $part = $attachments['cancel.ics'];
    $ics = unfoldIcs($part->getBody());

    expect($part->getPreparedHeaders()->get('Content-Type')->getBodyAsString())
        ->toStartWith('text/calendar')->toContain('method=CANCEL')
        ->and($ics)->toContain("METHOD:CANCEL\r\n")
        ->toContain("UID:{$appointment->uid}@booking.test\r\n")
        ->toContain("SEQUENCE:1\r\n")
        ->toContain("STATUS:CANCELLED\r\n")
        ->not->toContain('METHOD:REQUEST');
});

it('frees the slot so it can be booked again', function () {
    $slotsOn = fn () => app(AvailabilityService::class)
        ->slotsFor(consultation(), CarbonImmutable::parse('2026-10-20', 'Asia/Kolkata'))
        ->contains(fn ($slot) => $slot->equalTo(local('2026-10-20 10:00')));

    expect($slotsOn())->toBeFalse();

    $this->post($this->appointment->cancelUrl());

    expect($slotsOn())->toBeTrue();
});

it('sends no reminders after cancelling', function () {
    $this->post($this->appointment->cancelUrl());

    foreach ([24, 2] as $hoursBefore) {
        $this->travelTo(local('2026-10-20 10:00')->subHours($hoursBefore));
        $this->artisan('appointments:send-reminders')->assertSuccessful();
    }

    Mail::assertNotQueued(AppointmentReminder::class);
});

it('does not send a queued reminder if the appointment was cancelled before it went out', function () {
    $mailer = Mail::getFacadeRoot()->manager->mailer('array'); // the real mailer, not the fake
    $transport = $mailer->getSymfonyTransport();
    $transport->flush();

    (new AppointmentReminder($this->appointment, '24h'))->to('priya@example.com')->send($mailer);
    expect($transport->messages())->toHaveCount(1); // control: a confirmed appointment does send

    $reminder = new AppointmentReminder($this->appointment, '24h');
    $this->appointment->update(['status' => 'cancelled']);
    $reminder->to('priya@example.com')->send($mailer);

    expect($transport->messages())->toHaveCount(1);
});

it('shows a friendly message when already cancelled and does nothing', function () {
    $this->post($this->appointment->cancelUrl());
    Mail::fake(); // reset recorded mail

    $this->get($this->appointment->cancelUrl())->assertOk()->assertSee('already cancelled');
    $this->post($this->appointment->cancelUrl())->assertOk()->assertSee('already cancelled');

    expect($this->appointment->fresh()->sequence)->toBe(1);
    Mail::assertNothingQueued();
});

it('shows a friendly message when the appointment has already started', function () {
    // A link that is still validly signed (e.g. issued before a reschedule to an earlier time).
    $url = URL::temporarySignedRoute(
        'appointments.cancel', now()->addDays(10), ['appointment' => $this->appointment->uid]
    );
    $this->travelTo(local('2026-10-20 10:05'));

    $this->get($url)->assertOk()->assertSee('already started');
    $this->post($url)->assertOk()->assertSee('already started');

    expect($this->appointment->fresh()->status)->toBe(AppointmentStatus::Confirmed);
});
