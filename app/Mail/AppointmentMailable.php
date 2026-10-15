<?php

namespace App\Mail;

use App\Models\Appointment;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Mailtrap\EmailHeader\CategoryHeader;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * Shared behaviour for every appointment email.
 *
 * Queued (only after the surrounding DB transaction commits), retried 3 times
 * with increasing backoff, tagged with a Mailtrap category, and rendered as
 * HTML + plain text with times in the business timezone.
 */
abstract class AppointmentMailable extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300, 900];

    public function __construct(public Appointment $appointment) {}

    /** Mailtrap category, e.g. "appointment-confirmation". */
    abstract public function category(): string;

    abstract protected function subjectLine(): string;

    /** Base name of the views in resources/views/emails/{html,text}. */
    abstract protected function template(): string;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine(),
            using: [
                // Mailtrap accepts exactly one category per email.
                fn (Email $email) => $email->getHeaders()->add(new CategoryHeader($this->category())),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.html.'.$this->template(),
            text: 'emails.text.'.$this->template(),
            with: $this->viewData(),
        );
    }

    protected function viewData(): array
    {
        $appointment = $this->appointment;

        return [
            'appointment' => $appointment,
            'service' => $appointment->service,
            'businessName' => config('booking.business_name'),
            'businessAddress' => config('booking.address'),
            'timezone' => config('booking.timezone'),
            'when' => self::formatLong($appointment->localStartsAt()),
            'endsAt' => $appointment->localEndsAt()->format('g:i A'),
            'cancelUrl' => $appointment->isChangeable() ? $appointment->cancelUrl() : null,
            'rescheduleUrl' => $appointment->isChangeable() ? $appointment->rescheduleUrl() : null,
        ];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Appointment email failed after all retries', [
            'mailable' => static::class,
            'appointment' => $this->appointment->uid ?? null,
            'error' => $exception->getMessage(),
        ]);
    }

    /** "Fri 17 Oct, 3:00 PM IST" in the business timezone. */
    public static function formatShort(CarbonInterface $time): string
    {
        return $time->setTimezone(config('booking.timezone'))->format('D j M, g:i A T');
    }

    /** "Friday 17 October 2026, 3:00 PM IST" in the business timezone. */
    public static function formatLong(CarbonInterface $time): string
    {
        return $time->setTimezone(config('booking.timezone'))->format('l j F Y, g:i A T');
    }

    protected function shortWhen(): string
    {
        return self::formatShort($this->appointment->starts_at);
    }
}
