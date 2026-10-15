@extends('layouts.app')

@section('title', 'Reschedule')

@section('content')
    <h1>Reschedule your {{ $service->name }}</h1>
    <p class="muted">Currently booked for <strong>{{ $appointment->localStartsAt()->format('l j F, g:i A T') }}</strong>.</p>

    <section class="card">
        <h2 class="step">1. Choose a new date</h2>
        @include('partials.date-picker')
    </section>

    <form method="POST" action="{{ $appointment->rescheduleUrl() }}" class="card">
        @csrf
        <h2 class="step">2. Choose a new time</h2>
        @include('partials.slot-picker', ['current' => $appointment->starts_at])

        @if($isOpen && $slots->isNotEmpty())
            <button type="submit" class="btn">Move my appointment</button>
        @endif
    </form>
@endsection
