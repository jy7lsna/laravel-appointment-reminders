<?php

namespace App\Services;

use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;
use Spatie\IcalendarGenerator\Enums\EventStatus;
use Spatie\IcalendarGenerator\Properties\CalendarAddressProperty;
use Spatie\IcalendarGenerator\Properties\DateTimeProperty;
use Spatie\IcalendarGenerator\Properties\Parameter;
use Spatie\IcalendarGenerator\Properties\TextProperty;
use Spatie\IcalendarGenerator\ValueObjects\CalendarAddress;

/**
 * Builds iTIP calendar invites (RFC 5546) for an appointment.
 *
 * - UID never changes, so every email refers to the same calendar event.
 * - SEQUENCE increases on every change; calendars apply the highest one.
 * - METHOD:REQUEST adds/updates the event, METHOD:CANCEL removes it.
 *
 * All times are written in UTC ("…Z"), which every major calendar converts
 * to the viewer's local time correctly without needing a VTIMEZONE block.
 */
class IcsBuilder
{
    public const METHOD_REQUEST = 'REQUEST';

    public const METHOD_CANCEL = 'CANCEL';

    public function request(Appointment $appointment): string
    {
        return $this->build($appointment, self::METHOD_REQUEST, EventStatus::Confirmed);
    }

    public function cancel(Appointment $appointment): string
    {
        return $this->build($appointment, self::METHOD_CANCEL, EventStatus::Cancelled);
    }

    public static function mimeType(string $method): string
    {
        return "text/calendar; charset=UTF-8; method={$method}";
    }

    protected function build(Appointment $appointment, string $method, EventStatus $status): string
    {
        $business = config('booking.business_name');
        $service = $appointment->service;

        $event = Event::create("{$service->name} with {$business}")
            ->uniqueIdentifier($appointment->icalUid())
            ->createdAt($this->utc(now())) // DTSTAMP
            ->startsAt($this->utc($appointment->starts_at))
            ->endsAt($this->utc($appointment->ends_at))
            ->description($this->description($appointment, $method))
            ->organizer(config('mail.from.address'), $business)
            ->status($status);

        if ($address = config('booking.address')) {
            $event->address($address);
        }

        // The package omits SEQUENCE when it is 0 and has no RSVP=FALSE,
        // so these are appended explicitly.
        $event
            ->appendProperty(TextProperty::create('SEQUENCE', (string) $appointment->sequence))
            ->appendProperty(DateTimeProperty::fromDateTime(
                'LAST-MODIFIED', $this->utc($appointment->updated_at ?? now()), true
            ))
            ->appendProperty(
                CalendarAddressProperty::create('ATTENDEE', new CalendarAddress(
                    $appointment->customer_email, $appointment->customer_name,
                ))
                    ->addParameter(Parameter::create('ROLE', 'REQ-PARTICIPANT'))
                    ->addParameter(Parameter::create('RSVP', 'FALSE'))
            );

        return Calendar::create()
            ->productIdentifier('-//'.$business.'//Appointments//EN')
            ->withoutAutoTimezoneComponents()
            ->appendProperty(TextProperty::create('METHOD', $method))
            ->appendProperty(TextProperty::create('CALSCALE', 'GREGORIAN'))
            ->event($event)
            ->get();
    }

    protected function description(Appointment $appointment, string $method): string
    {
        if ($method === self::METHOD_CANCEL) {
            return "This appointment has been cancelled.\n\nBook again: ".route('home');
        }

        $when = $appointment->localStartsAt()->format('l j F Y, g:i A T');

        return implode("\n", [
            "{$appointment->service->name} with ".config('booking.business_name'),
            "When: {$when}",
            '',
            "Need to cancel? {$appointment->cancelUrl()}",
            "Need to reschedule? {$appointment->rescheduleUrl()}",
        ]);
    }

    protected function utc($dateTime): CarbonImmutable
    {
        return CarbonImmutable::instance($dateTime)->setTimezone('UTC');
    }
}
