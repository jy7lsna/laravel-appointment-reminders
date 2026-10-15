See you soon, {!! $appointment->customer_name !!}

{!! $lead !!}

@include('emails.text.details')

Can't make it? Please let us know so someone else can take the slot.
@if($rescheduleUrl)
Reschedule: {!! $rescheduleUrl !!}
Cancel: {!! $cancelUrl !!}
@endif
