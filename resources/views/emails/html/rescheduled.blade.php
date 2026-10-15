@extends('emails.layout')

@section('content')
    <h1 style="font-size:20px;margin:0 0 8px;">Your appointment has moved</h1>
    <p style="margin:0;">
        <span style="text-decoration:line-through;color:#71717a;">{{ $previousWhen }}</span><br>
        <strong>{{ $when }}</strong>
    </p>
    <p style="margin:12px 0 0;">The attached invite updates the event already in your calendar.</p>
    @include('emails.partials.details')
    @include('emails.partials.actions')
@endsection
