@switch($event)
@case('booked')
New booking
@break
@case('rescheduled')
Booking rescheduled: {!! $previousWhen !!} → {!! $when !!}
@break
@case('cancelled')
Booking cancelled
@break
@endswitch

@include('emails.text.details')
Email: {!! $appointment->customer_email !!}
Phone: {!! $appointment->customer_phone !!}
Reference: {!! $appointment->uid !!}
