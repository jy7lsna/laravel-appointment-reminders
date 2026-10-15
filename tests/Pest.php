<?php

use App\Models\Appointment;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->seed(DatabaseSeeder::class);
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/** A business-local (Asia/Kolkata) time as a UTC CarbonImmutable. */
function local(string $time): CarbonImmutable
{
    return CarbonImmutable::parse($time, config('booking.timezone'))->utc();
}

function consultation(): Service
{
    return Service::where('slug', 'consultation')->firstOrFail();
}

function makeAppointment(CarbonImmutable $startsAt, array $attributes = [], ?Service $service = null): Appointment
{
    $service ??= consultation();

    return Appointment::factory()
        ->for($service)
        ->startingAt($startsAt, $service->duration_minutes)
        ->create($attributes);
}

/**
 * Render a mailable exactly as it would be sent, through the in-memory
 * "array" transport, and return the Symfony message.
 */
function renderEmail(Mailable $mailable): Email
{
    $manager = Mail::isFake() ? Mail::getFacadeRoot()->manager : app('mail.manager');
    $mailer = $manager->mailer('array');
    $transport = $mailer->getSymfonyTransport();
    $transport->flush();

    if (empty($mailable->to)) {
        $mailable->to('someone@example.com');
    }
    $mailable->send($mailer);

    return $transport->messages()->last()->getOriginalMessage();
}

/** Attachment parts of a rendered email, keyed by filename. */
function emailAttachments(Email $email): array
{
    $parts = [];

    foreach ($email->getAttachments() as $part) {
        $parts[$part->getFilename()] = $part;
    }

    return $parts;
}

/** Unfold iCalendar content lines (RFC 5545 §3.1) so assertions can match whole lines. */
function unfoldIcs(string $ics): string
{
    return str_replace(["\r\n ", "\r\n\t"], '', $ics);
}
