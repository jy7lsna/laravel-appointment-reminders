<?php

use App\Enums\AppointmentStatus;
use App\Exceptions\SlotUnavailableException;
use App\Mail\AppointmentConfirmed;
use App\Mail\OwnerBookingNotification;
use App\Models\Appointment;
use App\Models\Service;
use App\Services\AppointmentManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->travelTo(local('2026-10-18 12:00')); // Sunday; Monday 19 Oct is bookable
});

function bookingPayload(array $overrides = []): array
{
    return [
        'customer_name' => 'Priya Sharma',
        'customer_email' => 'priya@example.com',
        'customer_phone' => '+91 98765 43210',
        'starts_at' => local('2026-10-19 09:00')->toIso8601ZuluString(),
        ...$overrides,
    ];
}

it('lists active services on the home page', function () {
    Service::where('slug', 'quick-call')->update(['is_active' => false]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Consultation')
        ->assertSee('Session')
        ->assertDontSee('Quick Call');
});

it('returns 404 for an inactive service', function () {
    Service::where('slug', 'quick-call')->update(['is_active' => false]);

    $this->get('/book/quick-call')->assertNotFound();
});

it('books an appointment and queues the confirmation and owner notification', function () {
    Mail::fake();

    $response = $this->post('/book/consultation', bookingPayload());

    $appointment = Appointment::sole();
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain("/appointments/{$appointment->uid}?expires=");

    expect($appointment->status)->toBe(AppointmentStatus::Confirmed)
        ->and($appointment->sequence)->toBe(0)
        ->and($appointment->customer_email)->toBe('priya@example.com')
        ->and($appointment->starts_at->toIso8601ZuluString())->toBe('2026-10-19T03:30:00Z')
        ->and($appointment->ends_at->toIso8601ZuluString())->toBe('2026-10-19T04:00:00Z')
        ->and($appointment->booked_at->equalTo(now()))->toBeTrue()
        ->and($appointment->uid)->toMatch('/^[0-9a-f-]{36}$/');

    Mail::assertQueued(AppointmentConfirmed::class, fn ($mail) => $mail->hasTo('priya@example.com')
        && $mail->appointment->is($appointment));
    Mail::assertQueued(OwnerBookingNotification::class, fn ($mail) => $mail->hasTo('owner@booking.test')
        && $mail->event === OwnerBookingNotification::BOOKED);
});

it('shows the confirmation page with cancel and reschedule links', function () {
    Mail::fake();

    $this->followingRedirects()
        ->post('/book/consultation', bookingPayload())
        ->assertOk()
        ->assertSee("You're booked!", false)
        ->assertSee('Monday 19 October 2026, 9:00 AM')
        ->assertSee('/cancel?expires=', false)
        ->assertSee('/reschedule?expires=', false);
});

it('attaches an invite.ics calendar REQUEST to the confirmation', function () {
    Mail::fake();
    $this->post('/book/consultation', bookingPayload());
    $appointment = Appointment::sole();

    $mailable = Mail::queued(AppointmentConfirmed::class)->first();
    $email = renderEmail($mailable);
    $attachments = emailAttachments($email);

    expect($attachments)->toHaveKey('invite.ics');

    $part = $attachments['invite.ics'];
    $contentType = $part->getPreparedHeaders()->get('Content-Type')->getBodyAsString();
    expect($contentType)->toStartWith('text/calendar')
        ->toContain('method=REQUEST')
        ->toContain('charset=UTF-8');

    $ics = unfoldIcs($part->getBody());
    expect($ics)
        ->toContain("METHOD:REQUEST\r\n")
        ->toContain("UID:{$appointment->uid}@booking.test\r\n")
        ->toContain("SEQUENCE:0\r\n")
        ->toContain("DTSTART:20261019T033000Z\r\n")
        ->toContain("DTEND:20261019T040000Z\r\n")
        ->toContain("STATUS:CONFIRMED\r\n");
});

it('prevents double booking of the same slot', function () {
    Mail::fake();
    $this->post('/book/consultation', bookingPayload());

    $this->from('/book/consultation?date=2026-10-19')
        ->post('/book/consultation', bookingPayload(['customer_email' => 'second@example.com']))
        ->assertRedirect('/book/consultation?date=2026-10-19')
        ->assertSessionHasErrors(['starts_at' => 'That slot was just taken, please pick another.']);

    expect(Appointment::count())->toBe(1);
});

it('prevents booking a slot that overlaps another service', function () {
    Mail::fake();
    $session = Service::where('slug', 'session')->first();
    makeAppointment(local('2026-10-19 09:00'), service: $session); // 09:00–10:00

    $this->post('/book/consultation', bookingPayload(['starts_at' => local('2026-10-19 09:30')->toIso8601ZuluString()]))
        ->assertSessionHasErrors('starts_at');

    expect(Appointment::count())->toBe(1);
});

it('re-checks availability inside the transaction', function () {
    $manager = app(AppointmentManager::class);
    $customer = ['customer_name' => 'A', 'customer_email' => 'a@example.com', 'customer_phone' => '123456'];

    Mail::fake();
    $manager->book(consultation(), $customer, local('2026-10-19 11:00'));

    expect(fn () => $manager->book(consultation(), $customer, local('2026-10-19 11:00')))
        ->toThrow(SlotUnavailableException::class);
});

it('rejects times that are not real slots with a clear message', function (string $time, string $message) {
    Mail::fake();

    $this->post('/book/consultation', bookingPayload(['starts_at' => local($time)->toIso8601ZuluString()]))
        ->assertSessionHasErrors(['starts_at' => $message]);

    expect(Appointment::count())->toBe(0);
    Mail::assertNothingQueued();
})->with([
    'not aligned to the interval' => ['2026-10-19 09:10', 'That time is outside our available hours, please pick another.'],
    'before opening' => ['2026-10-19 08:30', 'That time is outside our available hours, please pick another.'],
    'runs past closing' => ['2026-10-19 17:45', 'That time is outside our available hours, please pick another.'],
    'closed day' => ['2026-10-25 10:00', 'That time is outside our available hours, please pick another.'],
    'beyond the booking window' => ['2027-03-01 10:00', 'That time is outside our available hours, please pick another.'],
    'in the past' => ['2026-10-17 10:00', 'That time has already passed, please pick another.'],
]);

it('rejects a time inside the minimum notice period with a clear message', function () {
    Mail::fake();
    $this->travelTo(local('2026-10-19 09:00'));

    $this->post('/book/consultation', bookingPayload(['starts_at' => local('2026-10-19 09:30')->toIso8601ZuluString()]))
        ->assertSessionHasErrors(['starts_at' => 'That time is too soon to book, please pick a later one.']);

    expect(Appointment::count())->toBe(0);
});

it('rejects a starts_at that is not a date without a slot message', function () {
    $this->post('/book/consultation', bookingPayload(['starts_at' => 'not-a-date']))
        ->assertSessionHasErrors('starts_at')
        ->assertSessionDoesntHaveErrors(['starts_at' => 'That time has already passed, please pick another.']);
});

it('validates the customer details', function () {
    $this->post('/book/consultation', [])
        ->assertSessionHasErrors(['customer_name', 'customer_email', 'customer_phone', 'starts_at']);

    $this->post('/book/consultation', bookingPayload(['customer_email' => 'not-an-email', 'customer_phone' => 'abc']))
        ->assertSessionHasErrors(['customer_email', 'customer_phone']);
});

it('still books the appointment when sending email fails', function () {
    Log::spy();
    Mail::shouldReceive('to')->andThrow(new RuntimeException('Mailtrap is down'));

    $this->post('/book/consultation', bookingPayload())->assertRedirect();

    expect(Appointment::count())->toBe(1);
    Log::shouldHaveReceived('error')
        ->with('Failed to queue appointment email', Mockery::on(fn ($context) => $context['error'] === 'Mailtrap is down'))
        ->twice(); // customer + owner
});

it('rate limits booking attempts', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->post('/book/consultation', []);
    }

    $this->post('/book/consultation', [])->assertStatus(429);
});

it('rate limits an IP even when every attempt uses a different email', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->post('/book/consultation', bookingPayload(['customer_email' => "guest{$i}@example.com", 'customer_phone' => 'abc']))
            ->assertRedirect();
    }

    $this->post('/book/consultation', bookingPayload(['customer_email' => 'guest10@example.com', 'customer_phone' => 'abc']))
        ->assertStatus(429);
});

it('rate limits an email even when every attempt uses a different IP', function () {
    for ($i = 1; $i <= 5; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => "203.0.113.{$i}"])
            ->post('/book/consultation', bookingPayload(['customer_phone' => 'abc']))
            ->assertRedirect();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.6'])
        ->post('/book/consultation', bookingPayload(['customer_phone' => 'abc']))
        ->assertStatus(429);
});

it('treats differently cased and spaced emails as the same address when rate limiting', function () {
    for ($i = 1; $i <= 5; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => "203.0.113.{$i}"])
            ->post('/book/consultation', bookingPayload(['customer_phone' => 'abc']));
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.6'])
        ->post('/book/consultation', bookingPayload(['customer_email' => '  PRIYA@Example.com ', 'customer_phone' => 'abc']))
        ->assertStatus(429);
});

it('does not rate limit other customers when one email hits its limit', function () {
    for ($i = 1; $i <= 6; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => "203.0.113.{$i}"])
            ->post('/book/consultation', bookingPayload(['customer_phone' => 'abc']));
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->post('/book/consultation', bookingPayload(['customer_email' => 'other@example.com', 'customer_phone' => 'abc']))
        ->assertRedirect();
});

it('renders the honeypot field on the booking form', function () {
    $this->get('/book/consultation?date=2026-10-19')
        ->assertOk()
        ->assertSee('name="website"', false);
});

it('rejects a booking when the honeypot is filled', function () {
    Mail::fake();

    $this->post('/book/consultation', bookingPayload(['website' => 'https://spam.example']))
        ->assertSessionHasErrors('website');

    expect(Appointment::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('accepts a booking when the honeypot is left empty', function () {
    Mail::fake();

    $this->post('/book/consultation', bookingPayload(['website' => '']))
        ->assertSessionHasNoErrors();

    expect(Appointment::count())->toBe(1);
});

it('does not crash the rate limiter when the email is not a string', function () {
    $this->post('/book/consultation', bookingPayload(['customer_email' => ['a@example.com']]))
        ->assertSessionHasErrors('customer_email');
});
