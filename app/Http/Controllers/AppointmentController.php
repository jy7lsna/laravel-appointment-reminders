<?php

namespace App\Http\Controllers;

use App\Exceptions\AppointmentNotChangeableException;
use App\Exceptions\SlotUnavailableException;
use App\Http\Requests\RescheduleAppointmentRequest;
use App\Models\Appointment;
use App\Services\AppointmentManager;
use App\Services\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Customer self-service. Every route here sits behind the `signed`
 * middleware, so the URL itself is the credential. GET requests never
 * change anything — email link scanners prefetch links.
 */
class AppointmentController extends Controller
{
    public function __construct(
        protected AvailabilityService $availability,
        protected AppointmentManager $manager,
    ) {}

    public function show(Appointment $appointment): View
    {
        return view('appointments.show', ['appointment' => $appointment->load('service')]);
    }

    public function confirmCancel(Appointment $appointment): View
    {
        if (! $appointment->isChangeable()) {
            return $this->unavailable($appointment);
        }

        return view('appointments.cancel', ['appointment' => $appointment->load('service')]);
    }

    public function cancel(Appointment $appointment): View|RedirectResponse
    {
        try {
            $this->manager->cancel($appointment);
        } catch (AppointmentNotChangeableException) {
            return $this->unavailable($appointment->refresh());
        }

        return redirect()
            ->to($appointment->signedUrl('appointments.show'))
            ->with('status', 'cancelled');
    }

    public function editReschedule(Request $request, Appointment $appointment): View
    {
        if (! $appointment->isChangeable()) {
            return $this->unavailable($appointment);
        }

        $appointment->load('service');
        $dates = $this->availability->bookableDates();
        $date = $this->availability->parseDate($request->query('date'));

        if (! $date || ! $this->availability->isBookableDate($date)) {
            $current = $appointment->localStartsAt()->startOfDay();
            $date = $this->availability->isBookableDate($current)
                ? $current
                : ($dates->firstWhere('open', true)['date'] ?? $dates->first()['date']);
        }

        $isOpen = (bool) $dates->first(fn ($day) => $day['date']->isSameDay($date))['open'];

        return view('appointments.reschedule', [
            'appointment' => $appointment,
            'service' => $appointment->service,
            'dates' => $dates,
            'selectedDate' => $date,
            'isOpen' => $isOpen,
            // The appointment's own slot does not block itself.
            'slots' => $this->availability->slotsFor($appointment->service, $date, ignore: $appointment),
            'dateUrl' => fn ($day) => $appointment->signedUrl('appointments.reschedule', ['date' => $day->format('Y-m-d')]),
        ]);
    }

    public function reschedule(RescheduleAppointmentRequest $request, Appointment $appointment): View|RedirectResponse
    {
        $startsAt = $request->startsAt();

        try {
            $this->manager->reschedule($appointment, $startsAt);
        } catch (AppointmentNotChangeableException) {
            return $this->unavailable($appointment->refresh());
        } catch (SlotUnavailableException $e) {
            return redirect()
                ->to($appointment->signedUrl('appointments.reschedule', [
                    'date' => $startsAt->setTimezone($this->availability->timezone())->format('Y-m-d'),
                ]))
                ->withErrors(['starts_at' => $e->getMessage()]);
        }

        return redirect()
            ->to($appointment->signedUrl('appointments.show'))
            ->with('status', 'rescheduled');
    }

    protected function unavailable(Appointment $appointment): View
    {
        return view('appointments.unavailable', ['appointment' => $appointment->load('service')]);
    }
}
