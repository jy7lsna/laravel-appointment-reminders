@extends('emails.layout')

@section('content')
    <h1 style="font-size:20px;margin:0 0 8px;">See you soon, {{ $appointment->customer_name }}</h1>
    <p style="margin:0;">{{ $lead }}</p>
    @include('emails.partials.details')
    <p style="margin:0;">Can't make it? Please let us know so someone else can take the slot.</p>
    @include('emails.partials.actions')
@endsection
