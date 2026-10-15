@extends('layouts.app')

@section('title', 'Appointment')

@section('content')
    @if($appointment->isCancelled())
        <h1>This appointment is already cancelled</h1>
        <p class="muted">There's nothing more to do. You're welcome to book a new time.</p>
    @else
        <h1>This appointment has already started</h1>
        <p class="muted">It can no longer be changed online. Please contact us directly if you need help.</p>
    @endif

    <p><a class="btn" href="{{ route('home') }}">Book an appointment</a></p>
@endsection
