<?php

namespace App\Http\Controllers;

use App\Exceptions\SlotUnavailableException;
use App\Http\Requests\BookAppointmentRequest;
use App\Models\Service;
use App\Services\AppointmentManager;
use App\Services\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(protected AvailabilityService $availability) {}

    public function show(Request $request, Service $service): View
    {
        abort_unless($service->is_active, 404);

        $dates = $this->availability->bookableDates();
        $date = $this->availability->parseDate($request->query('date'));

        if (! $date || ! $this->availability->isBookableDate($date)) {
            $date = $dates->firstWhere('open', true)['date'] ?? $dates->first()['date'];
        }

        $isOpen = (bool) $dates->first(fn ($day) => $day['date']->isSameDay($date))['open'];

        return view('booking.show', [
            'service' => $service,
            'dates' => $dates,
            'selectedDate' => $date,
            'isOpen' => $isOpen,
            'slots' => $this->availability->slotsFor($service, $date),
            'dateUrl' => fn ($day) => route('booking.show', [$service, 'date' => $day->format('Y-m-d')]),
        ]);
    }

    public function store(BookAppointmentRequest $request, Service $service, AppointmentManager $manager): RedirectResponse
    {
        abort_unless($service->is_active, 404);

        $startsAt = $request->startsAt();

        try {
            $appointment = $manager->book($service, $request->customer(), $startsAt);
        } catch (SlotUnavailableException $e) {
            return redirect()
                ->route('booking.show', [$service, 'date' => $startsAt->setTimezone($this->availability->timezone())->format('Y-m-d')])
                ->withInput()
                ->withErrors(['starts_at' => $e->getMessage()]);
        }

        return redirect()
            ->to($appointment->signedUrl('appointments.show'))
            ->with('status', 'booked');
    }
}
