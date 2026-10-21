@extends('emails.layout')

@section('content')
    <p style="margin:0 0 8px;font-size:18px;font-weight:600;">Your appointment has moved.</p>
    <p style="margin:0;">
        <span style="text-decoration:line-through;color:#78716c;">{{ $previousWhen }}</span><br>
        <strong>{{ $when }}</strong>
    </p>
    <p style="margin:12px 0 0;">The attached invite updates the event already in your calendar.</p>
    @include('emails.partials.details')
    @include('emails.partials.actions')
@endsection
