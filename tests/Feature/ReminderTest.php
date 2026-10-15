<?php

use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use App\Services\AppointmentManager;
use Illuminate\Support\Facades\Mail;

// Appointment on Thursday 22 Oct 2026, 15:00 IST (09:30Z).

beforeEach(function () {
    Mail::fake();
    $this->startsAt = local('2026-10-22 15:00');
});

function bookAt(string $bookedAt, $startsAt): Appointment
{
    test()->travelTo(local($bookedAt));

    return makeAppointment($startsAt, ['booked_at' => now()]);
}

function runReminders(): void
{
    test()->artisan('appointments:send-reminders')->assertSuccessful();
}

function assertReminderQueued(Appointment $appointment, string $type, int $times = 1): void
{
    Mail::assertQueued(
        AppointmentReminder::class,
        fn ($mail) => $mail->type === $type && $mail->appointment->is($appointment) && $mail->hasTo($appointment->customer_email)
    );
    expect(Mail::queued(AppointmentReminder::class, fn ($mail) => $mail->type === $type)->count())->toBe($times);
}

it('sends the 24h reminder once the 24h point is reached', function () {
    $appointment = bookAt('2026-10-19 10:00', $this->startsAt);

    $this->travelTo($this->startsAt->subHours(24)->subMinute());
    runReminders();
    Mail::assertNothingQueued();

    $this->travelTo($this->startsAt->subHours(24));
    runReminders();

    assertReminderQueued($appointment, '24h');
    expect($appointment->fresh()->reminder_24h_sent_at->equalTo(now()))->toBeTrue()
        ->and($appointment->fresh()->reminder_2h_sent_at)->toBeNull();
});

it('sends the 2h reminder once the 2h point is reached', function () {
    $appointment = bookAt('2026-10-19 10:00', $this->startsAt);

    $this->travelTo($this->startsAt->subHours(24));
    runReminders();

    $this->travelTo($this->startsAt->subHours(2)->subMinute());
    runReminders();
    expect(Mail::queued(AppointmentReminder::class, fn ($m) => $m->type === '2h'))->toHaveCount(0);

    $this->travelTo($this->startsAt->subHours(2));
    runReminders();

    assertReminderQueued($appointment, '24h');
    assertReminderQueued($appointment, '2h');
    expect($appointment->fresh()->reminder_2h_sent_at)->not->toBeNull();
});

it('skips the 24h reminder for bookings made less than 24h ahead but still sends the 2h one', function () {
    $appointment = bookAt('2026-10-22 05:00', $this->startsAt); // 10h ahead

    foreach ([5, 4, 3, 2] as $hoursBefore) {
        $this->travelTo($this->startsAt->subHours($hoursBefore));
        runReminders();
    }

    expect(Mail::queued(AppointmentReminder::class, fn ($m) => $m->type === '24h'))->toHaveCount(0);
    assertReminderQueued($appointment, '2h');
    expect($appointment->fresh()->reminder_24h_sent_at)->toBeNull();
});

it('still sends the 24h reminder when the scheduler runs late', function () {
    $appointment = bookAt('2026-10-19 10:00', $this->startsAt);

    // Scheduler was down from the 24h point until 3h before.
    $this->travelTo($this->startsAt->subHours(3));
    runReminders();

    assertReminderQueued($appointment, '24h');
    expect(Mail::queued(AppointmentReminder::class, fn ($m) => $m->type === '2h'))->toHaveCount(0);
});

it('does not send a missed 24h reminder after the 2h point', function () {
    $appointment = bookAt('2026-10-19 10:00', $this->startsAt);

    // Scheduler was down until 1h before the appointment.
    $this->travelTo($this->startsAt->subHour());
    runReminders();

    assertReminderQueued($appointment, '2h');
    expect(Mail::queued(AppointmentReminder::class, fn ($m) => $m->type === '24h'))->toHaveCount(0)
        ->and($appointment->fresh()->reminder_24h_sent_at)->toBeNull();
});

it('sends nothing once the appointment has started', function () {
    bookAt('2026-10-19 10:00', $this->startsAt);

    $this->travelTo($this->startsAt->addMinute());
    runReminders();

    Mail::assertNothingQueued();
});

it('never sends the same reminder twice', function () {
    $appointment = bookAt('2026-10-19 10:00', $this->startsAt);

    $this->travelTo($this->startsAt->subHours(24));
    runReminders();
    runReminders();

    $this->travelTo($this->startsAt->subHours(2));
    runReminders();
    runReminders();

    assertReminderQueued($appointment, '24h', times: 1);
    assertReminderQueued($appointment, '2h', times: 1);

    $fresh = $appointment->fresh();
    expect($fresh->reminder_24h_sent_at)->not->toBeNull()
        ->and($fresh->reminder_2h_sent_at)->not->toBeNull();
});

it('does not send when another run already claimed the reminder', function () {
    $appointment = bookAt('2026-10-19 10:00', $this->startsAt);
    $this->travelTo($this->startsAt->subHours(24));

    // Simulate a concurrent run claiming the row between our SELECT and UPDATE.
    Appointment::retrieved(function (Appointment $model) {
        Appointment::query()->whereKey($model->id)->toBase()->update(['reminder_24h_sent_at' => now()]);
    });

    runReminders();

    Mail::assertNothingQueued();
});

it('sends no reminders for cancelled appointments', function () {
    $appointment = bookAt('2026-10-19 10:00', $this->startsAt);
    $appointment->update(['status' => 'cancelled', 'cancelled_at' => now()]);

    foreach ([24, 2] as $hoursBefore) {
        $this->travelTo($this->startsAt->subHours($hoursBefore));
        runReminders();
    }

    Mail::assertNothingQueued();
});

it('prints due reminders without sending them in dry-run mode', function () {
    $appointment = bookAt('2026-10-19 10:00', $this->startsAt);
    $this->travelTo($this->startsAt->subHours(24));

    $this->artisan('appointments:send-reminders --dry-run')
        ->expectsOutputToContain("Would send 24h reminder → {$appointment->customer_name} ({$appointment->customer_email})")
        ->expectsOutputToContain('1 reminder(s) due.')
        ->assertSuccessful();

    Mail::assertNothingQueued();
    expect($appointment->fresh()->reminder_24h_sent_at)->toBeNull();
});

it('resets reminders on reschedule and re-evaluates the 24h rule against the new time', function () {
    $appointment = bookAt('2026-10-19 10:00', $this->startsAt);

    $this->travelTo($this->startsAt->subHours(24));
    runReminders();
    assertReminderQueued($appointment, '24h');

    // Moved to Monday 26 Oct 11:00 — more than 24h ahead, so a new 24h reminder is due.
    $newStart = local('2026-10-26 11:00');
    app(AppointmentManager::class)->reschedule($appointment, $newStart);
    expect($appointment->fresh()->reminder_24h_sent_at)->toBeNull();

    $this->travelTo($newStart->subHours(24));
    runReminders();
    assertReminderQueued($appointment, '24h', times: 2);
});

it('skips the 24h reminder when rescheduled to less than 24h ahead', function () {
    $appointment = bookAt('2026-10-19 10:00', $this->startsAt);

    // On the 22nd at 09:00 the customer moves it to 17:00 the same day.
    $this->travelTo(local('2026-10-22 09:00'));
    $newStart = local('2026-10-22 17:00');
    app(AppointmentManager::class)->reschedule($appointment, $newStart);

    foreach ([7, 4, 2] as $hoursBefore) {
        $this->travelTo($newStart->subHours($hoursBefore));
        runReminders();
    }

    expect(Mail::queued(AppointmentReminder::class, fn ($m) => $m->type === '24h'))->toHaveCount(0);
    assertReminderQueued($appointment, '2h');
});

it('includes cancel and reschedule links in reminders', function () {
    $appointment = bookAt('2026-10-19 10:00', $this->startsAt);
    $this->travelTo($this->startsAt->subHours(24));
    runReminders();

    $email = renderEmail(Mail::queued(AppointmentReminder::class)->first());

    expect($email->getSubject())->toBe('Reminder: tomorrow – Consultation on Thu 22 Oct, 3:00 PM IST')
        ->and($email->getHtmlBody())->toContain("/appointments/{$appointment->uid}/cancel?expires=")
        ->and($email->getTextBody())->toContain("/appointments/{$appointment->uid}/reschedule?expires=");
});
