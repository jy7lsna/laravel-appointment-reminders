@extends('emails.layout')

@section('content')
    <h1 style="font-size:20px;margin:0 0 8px;">
        @switch($event)
            @case('booked') New booking @break
            @case('rescheduled') Booking rescheduled @break
            @case('cancelled') Booking cancelled @break
        @endswitch
    </h1>
    @if($event === 'rescheduled')
        <p style="margin:0;"><span style="text-decoration:line-through;color:#71717a;">{{ $previousWhen }}</span> → <strong>{{ $when }}</strong></p>
    @endif
    @include('emails.partials.details')
    <p style="margin:0;font-size:14px;">
        Email: <a href="mailto:{{ $appointment->customer_email }}">{{ $appointment->customer_email }}</a><br>
        Phone: {{ $appointment->customer_phone }}<br>
        Token: {{ $appointment->tokenNumber() }}
    </p>
@endsection
