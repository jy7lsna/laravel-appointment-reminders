@extends('layouts.app')

@section('title', 'Reschedule')

@section('content')
    <div class="shell">
        @include('partials.aside', [
            'title' => 'Reschedule your '.$service->name,
            'service' => $service,
            'note' => $appointment->localStartsAt()->format('D j M, g:i A'),
        ])

        <form method="POST" action="{{ $appointment->rescheduleUrl() }}" class="main" novalidate>
            @csrf
            <p class="muted">Currently booked for {{ $appointment->localStartsAt()->format('l j F, g:i A T') }}. Pick a new day and time.</p>

            <section class="section">
                <h2 class="section-title">Select Date</h2>
                @include('partials.date-picker')
            </section>

            <section class="section">
                @include('partials.slot-picker', ['current' => $appointment->starts_at])
            </section>

            @if($isOpen && $slots->isNotEmpty())
                <button type="submit" class="btn btn-block" data-busy="Moving…">Move my appointment</button>
            @endif
        </form>
    </div>
@endsection
