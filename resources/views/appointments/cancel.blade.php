@extends('layouts.app')

@section('title', 'Cancel appointment')

@section('content')
    <h1>Cancel this appointment?</h1>

    <div class="card">
        @include('appointments._summary')
    </div>

    {{-- The signed URL is the form action; GET never cancels (link scanners prefetch). --}}
    <form method="POST" action="{{ $appointment->cancelUrl() }}" class="actions">
        @csrf
        <button type="submit" class="btn btn-danger">Yes, cancel it</button>
        <a class="btn btn-secondary" href="{{ $appointment->rescheduleUrl() }}">Reschedule instead</a>
    </form>
@endsection
