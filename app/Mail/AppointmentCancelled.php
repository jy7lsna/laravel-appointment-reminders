<?php

namespace App\Mail;

use App\Services\IcsBuilder;
use Illuminate\Mail\Attachment;

class AppointmentCancelled extends AppointmentMailable
{
    public function category(): string
    {
        return 'appointment-cancellation';
    }

    protected function subjectLine(): string
    {
        return "Cancelled: {$this->appointment->service->name} on {$this->shortWhen()}";
    }

    protected function template(): string
    {
        return 'cancelled';
    }

    public function attachments(): array
    {
        // METHOD:CANCEL with the same UID removes the event from the calendar.
        return [
            Attachment::fromData(fn () => app(IcsBuilder::class)->cancel($this->appointment), 'cancel.ics')
                ->withMime(IcsBuilder::mimeType(IcsBuilder::METHOD_CANCEL)),
        ];
    }
}
