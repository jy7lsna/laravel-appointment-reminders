<?php

namespace App\Mail;

use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use InvalidArgumentException;

class OwnerBookingNotification extends AppointmentMailable
{
    public const BOOKED = 'booked';

    public const RESCHEDULED = 'rescheduled';

    public const CANCELLED = 'cancelled';

    public function __construct(
        Appointment $appointment,
        public string $event,
        public ?CarbonImmutable $previousStartsAt = null,
    ) {
        if (! in_array($event, [self::BOOKED, self::RESCHEDULED, self::CANCELLED], true)) {
            throw new InvalidArgumentException("Unknown owner notification event [{$event}].");
        }

        parent::__construct($appointment);
    }

    public function category(): string
    {
        return 'owner-notification';
    }

    public function envelope(): Envelope
    {
        // Replies go straight to the customer.
        return parent::envelope()->replyTo(new Address(
            $this->appointment->customer_email, $this->appointment->customer_name,
        ));
    }

    protected function subjectLine(): string
    {
        $label = match ($this->event) {
            self::BOOKED => 'New booking',
            self::RESCHEDULED => 'Rescheduled',
            self::CANCELLED => 'Cancelled',
        };

        return "{$label}: {$this->appointment->service->name} with {$this->appointment->customer_name} on {$this->shortWhen()}";
    }

    protected function template(): string
    {
        return 'owner';
    }

    protected function viewData(): array
    {
        return [
            ...parent::viewData(),
            'event' => $this->event,
            'previousWhen' => $this->previousStartsAt ? self::formatLong($this->previousStartsAt) : null,
            // Customer self-service links are not for the owner.
            'cancelUrl' => null,
            'rescheduleUrl' => null,
        ];
    }
}
