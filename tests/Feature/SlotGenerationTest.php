<?php

use App\Models\BusinessHour;
use App\Models\Service;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;

// 2026-10-19 is a Monday, 2026-10-24 a Saturday, 2026-10-25 a Sunday.

function slotTimes(Service $service, string $date): array
{
    return app(AvailabilityService::class)
        ->slotsFor($service, CarbonImmutable::parse($date, config('booking.timezone')))
        ->map(fn ($slot) => $slot->setTimezone(config('booking.timezone'))->format('H:i'))
        ->all();
}

beforeEach(function () {
    $this->travelTo(local('2026-10-18 12:00'));
});

it('generates slots within business hours aligned to the interval', function () {
    $slots = slotTimes(consultation(), '2026-10-19');

    expect($slots)->toHaveCount(18)
        ->and($slots[0])->toBe('09:00')
        ->and($slots[1])->toBe('09:30')
        ->and(end($slots))->toBe('17:30'); // 17:30 + 30 min = closing time
});

it('only offers slots whose full duration fits before closing', function () {
    $session = Service::where('slug', 'session')->first(); // 60 min

    $slots = slotTimes($session, '2026-10-19');

    expect(end($slots))->toBe('17:00')->and($slots)->not->toContain('17:30');
});

it('uses the configured slot interval', function () {
    config(['booking.slot_interval_minutes' => 15]);

    $slots = slotTimes(consultation(), '2026-10-19');

    expect($slots)->toHaveCount(35)->and(array_slice($slots, 0, 3))->toBe(['09:00', '09:15', '09:30']);
});

it('uses Saturday hours', function () {
    expect(slotTimes(consultation(), '2026-10-24'))
        ->toBe(['10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30']);
});

it('respects the minimum notice period', function () {
    $this->travelTo(local('2026-10-19 10:10'));

    // now + 60 min = 11:10, so the first slot is 11:30.
    expect(slotTimes(consultation(), '2026-10-19')[0])->toBe('11:30');
});

it('returns no slots on a closed day and shows a friendly message', function () {
    expect(slotTimes(consultation(), '2026-10-25'))->toBe([]);

    $this->get('/book/consultation?date=2026-10-25')
        ->assertOk()
        ->assertSee('closed on Sundays');
});

it('returns no slots beyond the booking horizon or in the past', function () {
    expect(slotTimes(consultation(), '2026-12-01'))->toBe([])
        ->and(slotTimes(consultation(), '2026-10-16'))->toBe([]);
});

it('excludes slots overlapping a confirmed appointment of any service', function () {
    $session = Service::where('slug', 'session')->first();
    makeAppointment(local('2026-10-19 10:00'), service: $session); // 10:00–11:00

    $consultation = slotTimes(consultation(), '2026-10-19');
    expect($consultation)->toContain('09:30') // ends exactly when the other starts
        ->toContain('11:00')                 // starts exactly when the other ends
        ->not->toContain('10:00')
        ->not->toContain('10:30');

    // A 60-minute session at 09:30 would run into 10:00.
    expect(slotTimes($session, '2026-10-19'))->not->toContain('09:30')->toContain('09:00');
});

it('ignores cancelled appointments when computing availability', function () {
    makeAppointment(local('2026-10-19 10:00'), ['status' => 'cancelled', 'cancelled_at' => now()]);

    expect(slotTimes(consultation(), '2026-10-19'))->toContain('10:00');
});

it('shows free slots in business time on the booking page', function () {
    makeAppointment(local('2026-10-19 10:00'));

    $this->get('/book/consultation?date=2026-10-19')
        ->assertOk()
        ->assertSee('9:00 AM')
        ->assertSee(local('2026-10-19 09:00')->toIso8601ZuluString())
        ->assertDontSee(local('2026-10-19 10:00')->toIso8601ZuluString());
});

it('is DST safe: slots follow local wall-clock time across a DST change', function () {
    config(['booking.timezone' => 'America/New_York']);
    BusinessHour::where('weekday', 0)->update(['opens_at' => '09:00', 'closes_at' => '18:00', 'is_closed' => false]);
    $this->travelTo(CarbonImmutable::parse('2026-10-28 12:00', 'UTC'));

    $availability = app(AvailabilityService::class);

    // Saturday 31 Oct is still EDT (UTC-4): 10:00 local = 14:00Z.
    $saturday = $availability->slotsFor(consultation(), CarbonImmutable::parse('2026-10-31', 'America/New_York'));
    // Sunday 1 Nov clocks fall back to EST (UTC-5): 09:00 local = 14:00Z.
    $sunday = $availability->slotsFor(consultation(), CarbonImmutable::parse('2026-11-01', 'America/New_York'));

    expect($saturday->first()->format('H:i\\Z'))->toBe('14:00Z')
        ->and($sunday->first()->format('H:i\\Z'))->toBe('14:00Z')
        ->and($sunday)->toHaveCount(18)
        ->and($sunday->last()->setTimezone('America/New_York')->format('H:i'))->toBe('17:30');

    // Every slot is exactly 30 real minutes after the previous one.
    $sunday->sliding(2)->each(fn ($pair) => expect((int) $pair->first()->diffInMinutes($pair->last()))->toBe(30));
});
