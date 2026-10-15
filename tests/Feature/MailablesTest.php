<?php

use App\Mail\AppointmentCancelled;
use App\Mail\AppointmentConfirmed;
use App\Mail\AppointmentReminder;
use App\Mail\AppointmentRescheduled;
use App\Mail\OwnerBookingNotification;
use App\Services\AppointmentManager;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mailtrap\EmailHeader\CategoryHeader;

beforeEach(function () {
    $this->travelTo(local('2026-10-18 12:00'));
    $this->appointment = makeAppointment(local('2026-10-20 10:00'));
});

it('tags every email with its Mailtrap category', function (Closure $make, string $category) {
    $email = renderEmail($make($this->appointment));

    $header = $email->getHeaders()->get('category');

    expect($header)->toBeInstanceOf(CategoryHeader::class)
        ->and($header->getValue())->toBe($category)
        ->and(iterator_to_array($email->getHeaders()->all('category'), false))->toHaveCount(1); // Mailtrap allows exactly one
})->with([
    'confirmation' => [fn ($a) => new AppointmentConfirmed($a), 'appointment-confirmation'],
    'reschedule' => [fn ($a) => new AppointmentRescheduled($a, $a->starts_at->subDay()), 'appointment-reschedule'],
    'cancellation' => [fn ($a) => new AppointmentCancelled($a), 'appointment-cancellation'],
    'reminder 24h' => [fn ($a) => new AppointmentReminder($a, '24h'), 'appointment-reminder-24h'],
    'reminder 2h' => [fn ($a) => new AppointmentReminder($a, '2h'), 'appointment-reminder-2h'],
    'owner booked' => [fn ($a) => new OwnerBookingNotification($a, 'booked'), 'owner-notification'],
    'owner rescheduled' => [fn ($a) => new OwnerBookingNotification($a, 'rescheduled', $a->starts_at->subDay()), 'owner-notification'],
    'owner cancelled' => [fn ($a) => new OwnerBookingNotification($a, 'cancelled'), 'owner-notification'],
]);

it('renders an HTML and a plain-text body for every email', function (Closure $make) {
    $email = renderEmail($make($this->appointment));

    expect($email->getHtmlBody())->toContain('<html')
        ->and($email->getTextBody())->not->toBeEmpty()->not->toContain('<html')
        ->not->toContain('&amp;'); // signed URLs must not be HTML-escaped in text

    foreach ([$email->getHtmlBody(), $email->getTextBody()] as $body) {
        expect($body)->not->toContain('Timezone:')->not->toContain('Where:')->not->toContain('friendly');
    }
})->with([
    'confirmation' => [fn ($a) => new AppointmentConfirmed($a)],
    'reschedule' => [fn ($a) => new AppointmentRescheduled($a, $a->starts_at->subDay())],
    'cancellation' => [fn ($a) => new AppointmentCancelled($a)],
    'reminder' => [fn ($a) => new AppointmentReminder($a, '2h')],
    'owner' => [fn ($a) => new OwnerBookingNotification($a, 'booked')],
]);

it('queues mailables with retries, backoff and after-commit dispatch', function () {
    Queue::fake();

    app(AppointmentManager::class)->book(consultation(), [
        'customer_name' => 'Priya', 'customer_email' => 'priya@example.com', 'customer_phone' => '123456',
    ], local('2026-10-19 11:00'));

    Queue::assertPushed(SendQueuedMailable::class, 2);
    Queue::assertPushed(SendQueuedMailable::class, fn (SendQueuedMailable $job) => $job->mailable instanceof AppointmentConfirmed
        && $job->tries === 3
        && $job->backoff() === [60, 300, 900]
        && $job->afterCommit === true);
    Queue::assertPushed(SendQueuedMailable::class, fn (SendQueuedMailable $job) => $job->mailable instanceof OwnerBookingNotification
        && $job->tries === 3);
});

it('logs when an email finally fails', function () {
    Log::spy();

    (new AppointmentConfirmed($this->appointment))->failed(new RuntimeException('401 Unauthorized'));

    Log::shouldHaveReceived('error')->with('Appointment email failed after all retries', Mockery::on(
        fn ($context) => $context['mailable'] === AppointmentConfirmed::class && $context['error'] === '401 Unauthorized'
    ));
});

it('sends owner notifications to OWNER_EMAIL with reply-to set to the customer', function () {
    $email = renderEmail((new OwnerBookingNotification($this->appointment, 'booked'))->to(config('booking.owner_email')));

    expect($email->getTo()[0]->getAddress())->toBe('owner@booking.test')
        ->and($email->getReplyTo()[0]->getAddress())->toBe($this->appointment->customer_email)
        ->and($email->getSubject())->toStartWith('New booking: Consultation with');
});

it('skips owner notifications when OWNER_EMAIL is not set', function () {
    Mail::fake();
    config(['booking.owner_email' => null]);

    app(AppointmentManager::class)->cancel($this->appointment);

    Mail::assertQueued(AppointmentCancelled::class);
    Mail::assertNotQueued(OwnerBookingNotification::class);
});
