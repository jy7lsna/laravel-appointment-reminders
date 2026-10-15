@extends('emails.layout')

@section('content')
    <h1 style="font-size:20px;margin:0 0 8px;">You're booked, {{ $appointment->customer_name }}!</h1>
    <p style="margin:0;">Your appointment is confirmed. We've attached a calendar invite (<em>invite.ics</em>) so you can add it to your calendar in one click.</p>
    @include('emails.partials.details')
    @include('emails.partials.actions')
@endsection
