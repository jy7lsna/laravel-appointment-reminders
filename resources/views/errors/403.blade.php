@extends('layouts.app')

@section('title', 'Link not valid')

@section('content')
    <h1>This link isn't valid anymore</h1>
    <p class="muted">
        Links to cancel or reschedule stop working once the appointment starts, and they can't be edited.
        Please use the most recent email we sent you, or contact us directly.
    </p>
    <p><a class="btn" href="{{ route('home') }}">Go to booking page</a></p>
@endsection
