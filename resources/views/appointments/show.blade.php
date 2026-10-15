@extends('layouts.app')

@section('title', 'Your appointment')

@section('content')
    @php($status = session('status'))

    @if($appointment->isCancelled())
        <h1>Appointment cancelled</h1>
        <p class="muted">
            @if($status === 'cancelled')
                Done — we've emailed you a confirmation with a file that removes it from your calendar.
            @else
                This appointment has been cancelled.
            @endif
        </p>
    @elseif($status === 'rescheduled')
        <h1>Appointment rescheduled</h1>
        <p class="muted">We've emailed you an updated calendar invite — it moves the event already in your calendar.</p>
    @elseif($status === 'booked')
        <h1>You're booked!</h1>
        <p class="muted">A confirmation email with a calendar invite is on its way to {{ $appointment->customer_email }}.</p>
    @else
        <h1>Your appointment</h1>
    @endif

    <div class="card">
        @include('appointments._summary')
    </div>

    @if($appointment->isChangeable())
        <div class="actions">
            <a class="btn" href="{{ $appointment->rescheduleUrl() }}">Reschedule</a>
            <a class="btn btn-danger-outline" href="{{ $appointment->cancelUrl() }}">Cancel appointment</a>
        </div>
        <p class="muted small">These links are also in your email and work until the appointment starts.</p>
    @else
        <p><a class="btn" href="{{ route('home') }}">Book another appointment</a></p>
    @endif
@endsection
