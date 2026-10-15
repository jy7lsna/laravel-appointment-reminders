<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BusinessHour;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Works out which start times can be booked.
 *
 * Business hours are wall-clock times in the business timezone. Each day's
 * opening/closing time is resolved to an absolute instant first, and slots
 * are then stepped in UTC, so DST transitions never produce duplicate or
 * missing slots. Everything returned is UTC.
 */
class AvailabilityService
{
    public function timezone(): string
    {
        return config('booking.timezone');
    }

    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone())->startOfDay();
    }

    /**
     * Local dates customers can pick from, with an "open" flag per day.
     *
     * @return Collection<int, array{date: CarbonImmutable, open: bool}>
     */
    public function bookableDates(): Collection
    {
        $hours = $this->businessHours();
        $today = $this->today();

        return collect(range(0, max(1, (int) config('booking.max_days_ahead')) - 1))
            ->map(function (int $offset) use ($today, $hours) {
                $date = $today->addDays($offset);

                return ['date' => $date, 'open' => (bool) $hours->get($date->dayOfWeek)?->isOpen()];
            });
    }

    public function isBookableDate(CarbonInterface $localDate): bool
    {
        return $this->bookableDates()->contains(
            fn (array $day) => $day['date']->isSameDay($localDate)
        );
    }

    /** Parse a YYYY-MM-DD string as a business-local date (null if invalid). */
    public function parseDate(?string $date): ?CarbonImmutable
    {
        if (! $date || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date, $this->timezone());

        return $parsed && $parsed->format('Y-m-d') === $date ? $parsed : null;
    }

    /**
     * Free slot start times (UTC) for a service on a business-local date.
     *
     * @return Collection<int, CarbonImmutable>
     */
    public function slotsFor(Service $service, CarbonInterface $localDate, ?Appointment $ignore = null): Collection
    {
        $day = CarbonImmutable::parse($localDate->format('Y-m-d'), $this->timezone())->startOfDay();

        if (! $this->isBookableDate($day)) {
            return collect();
        }

        $hours = $this->businessHours()->get($day->dayOfWeek);

        if (! $hours?->isOpen()) {
            return collect();
        }

        $opensAt = $this->localTimeOn($day, $hours->opens_at)->utc();
        $closesAt = $this->localTimeOn($day, $hours->closes_at)->utc();

        if ($closesAt->lte($opensAt)) {
            return collect();
        }

        $earliest = CarbonImmutable::now('UTC')->addMinutes((int) config('booking.min_notice_minutes'));
        $interval = max(5, (int) config('booking.slot_interval_minutes'));
        $duration = $service->duration_minutes;

        $taken = Appointment::query()
            ->confirmed()
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->where('starts_at', '<', $closesAt)
            ->where('ends_at', '>', $opensAt)
            ->get(['starts_at', 'ends_at']);

        $slots = collect();

        for ($start = $opensAt; $start->addMinutes($duration)->lte($closesAt); $start = $start->addMinutes($interval)) {
            $end = $start->addMinutes($duration);

            if ($start->lt($earliest)) {
                continue;
            }

            $overlaps = $taken->contains(
                fn (Appointment $other) => $start->lt($other->ends_at) && $end->gt($other->starts_at)
            );

            if (! $overlaps) {
                $slots->push($start);
            }
        }

        return $slots;
    }

    /** Whether a UTC start time is one of the currently free slots. */
    public function isAvailable(Service $service, CarbonInterface $startsAt, ?Appointment $ignore = null): bool
    {
        $startsAt = CarbonImmutable::instance($startsAt)->utc();

        return $this->slotsFor($service, $startsAt->setTimezone($this->timezone()), $ignore)
            ->contains(fn (CarbonImmutable $slot) => $slot->equalTo($startsAt));
    }

    /** @return Collection<int, BusinessHour> keyed by weekday */
    protected function businessHours(): Collection
    {
        return BusinessHour::query()->get()->keyBy('weekday');
    }

    protected function localTimeOn(CarbonImmutable $day, string $time): CarbonImmutable
    {
        return CarbonImmutable::parse($day->format('Y-m-d').' '.$time, $this->timezone());
    }
}
