<?php

namespace App\Mail;

use App\Services\IcsBuilder;
use Illuminate\Mail\Attachment;

class AppointmentConfirmed extends AppointmentMailable
{
    public function category(): string
    {
        return 'appointment-confirmation';
    }

    protected function subjectLine(): string
    {
        return "Confirmed: {$this->appointment->service->name} on {$this->shortWhen()}";
    }

    protected function template(): string
    {
        return 'confirmed';
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => app(IcsBuilder::class)->request($this->appointment), 'invite.ics')
                ->withMime(IcsBuilder::mimeType(IcsBuilder::METHOD_REQUEST)),
        ];
    }
}
