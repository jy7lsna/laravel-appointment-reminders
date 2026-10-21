@extends('emails.layout')

@section('content')
    <p style="margin:0 0 8px;font-size:18px;font-weight:600;">Your appointment is cancelled.</p>
    <p style="margin:0;">We've cancelled your {{ $service->name }} on <strong>{{ $when }}</strong>. The attached file (<em>cancel.ics</em>) removes it from your calendar.</p>
    <p style="margin:24px 0 0;">
        <a href="{{ route('home') }}" style="display:inline-block;background:#0f766e;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:15px;font-weight:600;">Book another time</a>
    </p>
@endsection
