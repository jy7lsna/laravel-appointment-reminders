<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * Mirror the column defaults so a freshly created model (not yet reloaded)
     * still has them, e.g. for SEQUENCE in the calendar invite.
     */
    protected $attributes = [
        'status' => 'confirmed',
        'sequence' => 0,
    ];

    protected $fillable = [
        'service_id', 'customer_name', 'customer_email', 'customer_phone',
        'starts_at', 'ends_at', 'status', 'sequence',
        'reminder_24h_sent_at', 'reminder_2h_sent_at',
        'booked_at', 'rescheduled_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'reminder_24h_sent_at' => 'immutable_datetime',
            'reminder_2h_sent_at' => 'immutable_datetime',
            'booked_at' => 'immutable_datetime',
            'rescheduled_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'status' => AppointmentStatus::class,
            'sequence' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // The UID is the iCalendar event identity: set once, never touched again.
        static::creating(function (Appointment $appointment) {
            $appointment->uid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function scopeConfirmed(Builder $query): void
    {
        $query->where('status', AppointmentStatus::Confirmed);
    }

    public function isCancelled(): bool
    {
        return $this->status === AppointmentStatus::Cancelled;
    }

    public function hasStarted(): bool
    {
        return $this->starts_at->lte(now());
    }

    /** True when the customer may still cancel or reschedule. */
    public function isChangeable(): bool
    {
        return ! $this->isCancelled() && ! $this->hasStarted();
    }

    /** Short, human-friendly booking number for people, e.g. "#0042". */
    public function tokenNumber(): string
    {
        return '#'.str_pad((string) $this->getKey(), 4, '0', STR_PAD_LEFT);
    }

    /** Globally unique iCalendar UID, e.g. "{uuid}@example.com". */
    public function icalUid(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return "{$this->uid}@{$host}";
    }

    /** The moment the current slot was chosen (booking or latest reschedule). */
    public function scheduledAt(): CarbonImmutable
    {
        return $this->rescheduled_at ?? $this->booked_at;
    }

    public function localStartsAt(): CarbonImmutable
    {
        return $this->starts_at->setTimezone(config('booking.timezone'));
    }

    public function localEndsAt(): CarbonImmutable
    {
        return $this->ends_at->setTimezone(config('booking.timezone'));
    }

    /** Signed link that stops working once the appointment starts. */
    public function signedUrl(string $route, array $parameters = []): string
    {
        return URL::temporarySignedRoute(
            $route,
            $this->starts_at,
            ['appointment' => $this->uid, ...$parameters],
        );
    }

    public function cancelUrl(): string
    {
        return $this->signedUrl('appointments.cancel');
    }

    public function rescheduleUrl(): string
    {
        return $this->signedUrl('appointments.reschedule');
    }
}
