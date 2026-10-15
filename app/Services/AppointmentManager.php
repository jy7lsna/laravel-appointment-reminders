<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentNotChangeableException;
use App\Exceptions\SlotUnavailableException;
use App\Mail\AppointmentCancelled;
use App\Mail\AppointmentConfirmed;
use App\Mail\AppointmentRescheduled;
use App\Mail\OwnerBookingNotification;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Books, moves and cancels appointments, then queues the emails.
 *
 * Writes are serialised with an atomic cache lock plus a DB transaction that
 * re-checks availability, so two customers can never take the same slot.
 * Emails are queued only after the write commits, and a failure to queue is
 * logged, never surfaced: the booking itself must always succeed.
 */
class AppointmentManager
{
    public function __construct(protected AvailabilityService $availability) {}

    /**
     * @param  array{customer_name: string, customer_email: string, customer_phone: string}  $customer
     *
     * @throws SlotUnavailableException
     */
    public function book(Service $service, array $customer, CarbonImmutable $startsAt): Appointment
    {
        $startsAt = $startsAt->utc();

        $appointment = $this->exclusively(function () use ($service, $customer, $startsAt) {
            if (! $this->availability->isAvailable($service, $startsAt)) {
                throw new SlotUnavailableException;
            }

            return Appointment::create([
                ...$customer,
                'service_id' => $service->id,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->addMinutes($service->duration_minutes),
                'status' => AppointmentStatus::Confirmed,
                'sequence' => 0,
                'booked_at' => now(),
            ]);
        });

        $this->sendToCustomer($appointment, new AppointmentConfirmed($appointment));
        $this->sendToOwner(new OwnerBookingNotification($appointment, OwnerBookingNotification::BOOKED));

        return $appointment;
    }

    /**
     * @throws SlotUnavailableException|AppointmentNotChangeableException
     */
    public function reschedule(Appointment $appointment, CarbonImmutable $startsAt): Appointment
    {
        $startsAt = $startsAt->utc();

        $previousStartsAt = $this->exclusively(function () use ($appointment, $startsAt) {
            $appointment->refresh();

            if (! $appointment->isChangeable()) {
                throw new AppointmentNotChangeableException;
            }

            if ($appointment->starts_at->equalTo($startsAt)
                || ! $this->availability->isAvailable($appointment->service, $startsAt, ignore: $appointment)) {
                throw new SlotUnavailableException;
            }

            $previous = $appointment->starts_at;

            // Same UID + higher SEQUENCE = calendars move the existing event.
            // Reminders start over and are judged against the new time.
            $appointment->update([
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->addMinutes($appointment->service->duration_minutes),
                'sequence' => $appointment->sequence + 1,
                'reminder_24h_sent_at' => null,
                'reminder_2h_sent_at' => null,
                'rescheduled_at' => now(),
            ]);

            return $previous;
        });

        $this->sendToCustomer($appointment, new AppointmentRescheduled($appointment, $previousStartsAt));
        $this->sendToOwner(new OwnerBookingNotification($appointment, OwnerBookingNotification::RESCHEDULED, $previousStartsAt));

        return $appointment;
    }

    /**
     * @throws AppointmentNotChangeableException
     */
    public function cancel(Appointment $appointment): Appointment
    {
        $this->exclusively(function () use ($appointment) {
            $appointment->refresh();

            if (! $appointment->isChangeable()) {
                throw new AppointmentNotChangeableException;
            }

            // Same UID + higher SEQUENCE + METHOD:CANCEL = calendars remove the event.
            $appointment->update([
                'status' => AppointmentStatus::Cancelled,
                'cancelled_at' => now(),
                'sequence' => $appointment->sequence + 1,
            ]);
        });

        $this->sendToCustomer($appointment, new AppointmentCancelled($appointment));
        $this->sendToOwner(new OwnerBookingNotification($appointment, OwnerBookingNotification::CANCELLED));

        return $appointment;
    }

    /**
     * Run a write under a global booking lock inside a transaction.
     *
     * The cache lock covers databases (like SQLite) where SELECT … FOR UPDATE
     * is not available; the transaction keeps the write atomic.
     */
    protected function exclusively(Closure $callback): mixed
    {
        try {
            return Cache::lock('appointments:write', 10)->block(5, fn () => DB::transaction($callback));
        } catch (LockTimeoutException) {
            throw new SlotUnavailableException('We are busy handling another booking, please try again.');
        }
    }

    protected function sendToCustomer(Appointment $appointment, Mailable $mailable): void
    {
        $this->queueSafely($appointment->customer_email, $mailable);
    }

    protected function sendToOwner(Mailable $mailable): void
    {
        if ($owner = config('booking.owner_email')) {
            $this->queueSafely($owner, $mailable);
        }
    }

    protected function queueSafely(string $to, Mailable $mailable): void
    {
        try {
            Mail::to($to)->queue($mailable);
        } catch (Throwable $e) {
            Log::error('Failed to queue appointment email', [
                'mailable' => $mailable::class,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
