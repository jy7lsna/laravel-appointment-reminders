<?php

use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->travelTo(local('2026-10-18 12:00'));
    $this->appointment = makeAppointment(local('2026-10-20 10:00'));
});

it('accepts valid signed links', function (string $route) {
    $this->get($this->appointment->signedUrl($route))->assertOk();
})->with(['appointments.show', 'appointments.cancel', 'appointments.reschedule']);

it('rejects a tampered signature with a friendly 403 page', function () {
    $url = $this->appointment->cancelUrl();
    $tampered = preg_replace('/signature=([0-9a-f])/', 'signature=0$1', $url);

    $this->get($tampered)
        ->assertForbidden()
        ->assertSee("This link isn't valid anymore", false);

    $this->post($tampered)->assertForbidden();
    expect($this->appointment->fresh()->isCancelled())->toBeFalse();
});

it('rejects a changed expiry or a different appointment', function () {
    $url = $this->appointment->cancelUrl();
    $other = makeAppointment(local('2026-10-21 10:00'));

    $this->get(preg_replace('/expires=\d+/', 'expires=9999999999', $url))->assertForbidden();
    $this->get(str_replace($this->appointment->uid, $other->uid, $url))->assertForbidden();
});

it('rejects links without a signature', function () {
    $this->get("/appointments/{$this->appointment->uid}/cancel")->assertForbidden();
    $this->post("/appointments/{$this->appointment->uid}/cancel")->assertForbidden();
});

it('expires links when the appointment starts', function () {
    $cancel = $this->appointment->cancelUrl();
    $reschedule = $this->appointment->rescheduleUrl();

    $this->travelTo(local('2026-10-20 09:59'));
    $this->get($cancel)->assertOk();

    $this->travelTo(local('2026-10-20 10:01'));
    $this->get($cancel)->assertForbidden()->assertSee("This link isn't valid anymore", false);
    $this->get($reschedule)->assertForbidden();
    $this->post($cancel)->assertForbidden();

    expect($this->appointment->fresh()->isCancelled())->toBeFalse();
});
