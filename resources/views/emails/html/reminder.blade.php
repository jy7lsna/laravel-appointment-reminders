@extends('emails.layout')

@section('content')
    <p style="margin:0 0 8px;font-size:18px;font-weight:600;">See you soon, {{ $appointment->customer_name }}.</p>
    <p style="margin:0;">{{ $lead }}</p>
    @include('emails.partials.details')
    <p style="margin:0;">Can't make it? Please let us know so someone else can take the slot.</p>
    @include('emails.partials.actions')
@endsection
