@extends('layouts.app')

@section('title', 'Your appointment')

@section('content')
    @php($status = session('status'))

    <div class="shell single"><div class="main">
        @if($appointment->isCancelled())
            <h1>Appointment cancelled</h1>
            <p class="muted">
                @if($status === 'cancelled')
                    Done. We've emailed you a confirmation with a file that removes it from your calendar.
                @else
                    This appointment has been cancelled.
                @endif
            </p>
        @elseif($status === 'rescheduled')
            <div class="status"><span class="check" aria-hidden="true"></span><h1>Appointment rescheduled</h1></div>
            <p class="muted">We've emailed you an updated calendar invite. It moves the event already in your calendar.</p>
        @elseif($status === 'booked')
            <div class="status"><span class="check" aria-hidden="true"></span><h1>You're booked!</h1></div>
            <p class="muted">A confirmation email with a calendar invite has been sent to {{ $appointment->customer_email }}.</p>
        @else
            <h1>Your appointment</h1>
        @endif

        @include('appointments._summary')

        @if($appointment->isChangeable())
            <div class="actions">
                <a class="btn btn-secondary" href="{{ $appointment->rescheduleUrl() }}">Reschedule</a>
                <a class="btn btn-secondary" href="{{ $appointment->cancelUrl() }}">Cancel appointment</a>
            </div>
            <p class="muted small">These links are also in your email and work until the appointment starts.</p>
        @else
            <p><a class="btn" href="{{ route('home') }}">Book another appointment</a></p>
        @endif
    </div></div>
@endsection
