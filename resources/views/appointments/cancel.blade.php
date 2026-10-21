@extends('layouts.app')

@section('title', 'Cancel appointment')

@section('content')
    <div class="shell single"><div class="main">
        <h1>Cancel this appointment?</h1>
        <p class="muted">Once cancelled, the time goes back to the calendar and you'd need to book again.</p>

        @include('appointments._summary')

        {{-- The signed URL is the form action; GET never cancels (link scanners prefetch). --}}
        <form method="POST" action="{{ $appointment->cancelUrl() }}" class="actions">
            @csrf
            <button type="submit" class="btn btn-danger" data-busy="Cancelling…">Yes, cancel it</button>
            <a class="btn btn-secondary" href="{{ $appointment->rescheduleUrl() }}">Reschedule instead</a>
        </form>
    </div></div>
@endsection
