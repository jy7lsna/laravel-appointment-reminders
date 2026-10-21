@extends('emails.layout')

@section('content')
    <p style="margin:0 0 8px;font-size:18px;font-weight:600;">
        @switch($event)
            @case('booked') New booking @break
            @case('rescheduled') Booking rescheduled @break
            @case('cancelled') Booking cancelled @break
        @endswitch
    </p>
    @if($event === 'rescheduled')
        <p style="margin:0;"><span style="text-decoration:line-through;color:#78716c;">{{ $previousWhen }}</span> → <strong>{{ $when }}</strong></p>
    @endif
    @include('emails.partials.details')
    <p style="margin:0;font-size:14px;line-height:1.7;">
        Email: <a href="mailto:{{ $appointment->customer_email }}" style="color:#0f766e;">{{ $appointment->customer_email }}</a><br>
        Phone: {{ $appointment->customer_phone }}<br>
        Token: {{ $appointment->tokenNumber() }}
    </p>
@endsection
