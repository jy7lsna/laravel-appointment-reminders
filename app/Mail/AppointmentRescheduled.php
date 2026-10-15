<?php

namespace App\Mail;

use App\Models\Appointment;
use App\Services\IcsBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Mail\Attachment;

class AppointmentRescheduled extends AppointmentMailable
{
    public function __construct(Appointment $appointment, public CarbonImmutable $previousStartsAt)
    {
        parent::__construct($appointment);
    }

    public function category(): string
    {
        return 'appointment-reschedule';
    }

    protected function subjectLine(): string
    {
        return "Rescheduled: {$this->appointment->service->name} moved to {$this->shortWhen()}";
    }

    protected function template(): string
    {
        return 'rescheduled';
    }

    protected function viewData(): array
    {
        return parent::viewData() + ['previousWhen' => self::formatLong($this->previousStartsAt)];
    }

    public function attachments(): array
    {
        // Same UID, higher SEQUENCE: the customer's calendar moves the event.
        return [
            Attachment::fromData(fn () => app(IcsBuilder::class)->request($this->appointment), 'invite.ics')
                ->withMime(IcsBuilder::mimeType(IcsBuilder::METHOD_REQUEST)),
        ];
    }
}
