@extends('emails.layout')

@section('content')
    <h1 style="font-size:20px;margin:0 0 8px;">Your appointment is cancelled</h1>
    <p style="margin:0;">We've cancelled your {{ $service->name }} on <strong>{{ $when }}</strong>. The attached file (<em>cancel.ics</em>) removes it from your calendar.</p>
    <p style="margin:20px 0 0;">
        <a href="{{ route('home') }}" style="display:inline-block;background:#4338ca;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:8px;font-weight:600;">Book another time</a>
    </p>
@endsection
