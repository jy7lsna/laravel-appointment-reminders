@extends('emails.layout')

@section('content')
    <p style="margin:0 0 8px;font-size:18px;font-weight:600;">You're booked, {{ $appointment->customer_name }}.</p>
    <p style="margin:0;">Your appointment is confirmed. A calendar invite (<em>invite.ics</em>) is attached so you can add it to your calendar.</p>
    @include('emails.partials.details')
    @include('emails.partials.actions')
@endsection
