<?php

namespace App\Console\Commands;

use App\Enums\AppointmentStatus;
use App\Mail\AppointmentReminder;
use App\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Queues 24h and 2h reminder emails.
 *
 * Windows (now = time of this run):
 *   24h: starts_at − 24h <= now < starts_at − 2h, and the slot was chosen at
 *        least 24h before it starts (short-notice bookings skip it).
 *   2h:  starts_at − 2h  <= now < starts_at.
 *
 * Using windows rather than exact times means a late or skipped scheduler run
 * still sends the right reminder, and a missed 24h reminder is never sent once
 * the 2h point has passed. Each reminder is claimed with an atomic conditional
 * UPDATE before queueing, so overlapping runs can never send it twice.
 */
class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders
                            {--dry-run : Show which reminders are due without sending anything}';

    protected $description = 'Queue 24-hour and 2-hour appointment reminder emails';

    public function handle(): int
    {
        $now = CarbonImmutable::now('UTC');
        $dryRun = (bool) $this->option('dry-run');

        $due = [
            AppointmentReminder::TYPE_24H => $this->dueFor24h($now),
            AppointmentReminder::TYPE_2H => $this->dueFor2h($now),
        ];

        if ($dryRun) {
            $this->info('Dry run at '.$now->toIso8601ZuluString().' – nothing will be sent.');
        }

        $queued = 0;

        foreach ($due as $type => $appointments) {
            foreach ($appointments as $appointment) {
                $line = sprintf(
                    '%s reminder → %s (%s) for %s [%s]',
                    $type,
                    $appointment->customer_name,
                    $appointment->customer_email,
                    $appointment->localStartsAt()->format('D j M, g:i A T'),
                    $appointment->uid,
                );

                if ($dryRun) {
                    $this->line("Would send {$line}");

                    continue;
                }

                if ($this->claimAndQueue($appointment, $type, $now)) {
                    $queued++;
                    $this->line("Queued {$line}");
                }
            }
        }

        $total = $due[AppointmentReminder::TYPE_24H]->count() + $due[AppointmentReminder::TYPE_2H]->count();

        $this->info($dryRun
            ? "{$total} reminder(s) due."
            : "{$queued} reminder(s) queued.");

        return self::SUCCESS;
    }

    /** @return Collection<int, Appointment> */
    protected function dueFor24h(CarbonImmutable $now): Collection
    {
        return Appointment::query()
            ->with('service')
            ->confirmed()
            ->whereNull('reminder_24h_sent_at')
            ->where('starts_at', '<=', $now->addHours(24))
            ->where('starts_at', '>', $now->addHours(2))
            ->orderBy('starts_at')
            ->get()
            // Booked (or rescheduled) less than 24h ahead: skip the 24h reminder.
            ->filter(fn (Appointment $a) => $a->scheduledAt()->lte($a->starts_at->subHours(24)))
            ->values();
    }

    /** @return Collection<int, Appointment> */
    protected function dueFor2h(CarbonImmutable $now): Collection
    {
        return Appointment::query()
            ->with('service')
            ->confirmed()
            ->whereNull('reminder_2h_sent_at')
            ->where('starts_at', '<=', $now->addHours(2))
            ->where('starts_at', '>', $now)
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Atomically mark the reminder as sent, then queue it. Only the process
     * whose UPDATE affected the row gets to send.
     */
    protected function claimAndQueue(Appointment $appointment, string $type, CarbonImmutable $now): bool
    {
        $column = "reminder_{$type}_sent_at";

        $claimed = Appointment::query()
            ->whereKey($appointment->getKey())
            ->whereNull($column)
            ->where('status', AppointmentStatus::Confirmed)
            ->toBase()
            ->update([$column => $now]);

        if ($claimed !== 1) {
            return false;
        }

        try {
            Mail::to($appointment->customer_email, $appointment->customer_name)
                ->queue(new AppointmentReminder($appointment, $type));
        } catch (Throwable $e) {
            // Release the claim so the next run can try again.
            Appointment::query()->whereKey($appointment->getKey())->toBase()->update([$column => null]);

            Log::error('Failed to queue appointment reminder', [
                'appointment' => $appointment->uid,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            $this->error("Failed to queue {$type} reminder for {$appointment->uid}: {$e->getMessage()}");

            return false;
        }

        return true;
    }
}
