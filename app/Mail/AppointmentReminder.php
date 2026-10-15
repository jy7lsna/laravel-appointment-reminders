<?php

namespace App\Mail;

use App\Models\Appointment;
use InvalidArgumentException;

class AppointmentReminder extends AppointmentMailable
{
    public const TYPE_24H = '24h';

    public const TYPE_2H = '2h';

    public function __construct(Appointment $appointment, public string $type)
    {
        if (! in_array($type, [self::TYPE_24H, self::TYPE_2H], true)) {
            throw new InvalidArgumentException("Unknown reminder type [{$type}].");
        }

        parent::__construct($appointment);
    }

    public function category(): string
    {
        return "appointment-reminder-{$this->type}";
    }

    protected function subjectLine(): string
    {
        $prefix = $this->type === self::TYPE_24H ? 'Reminder: tomorrow' : 'Starting soon';

        return "{$prefix} – {$this->appointment->service->name} on {$this->shortWhen()}";
    }

    protected function template(): string
    {
        return 'reminder';
    }

    protected function viewData(): array
    {
        return parent::viewData() + [
            'lead' => $this->type === self::TYPE_24H
                ? 'This is a reminder that your appointment is tomorrow.'
                : 'Your appointment starts in about 2 hours.',
        ];
    }

    /**
     * The appointment may have been cancelled between queueing and sending.
     */
    public function send($mailer)
    {
        if ($this->appointment->isCancelled()) {
            return null;
        }

        return parent::send($mailer);
    }
}
