You're booked, {!! $appointment->customer_name !!}!

Your appointment with {!! $businessName !!} is confirmed. A calendar invite (invite.ics) is attached.

@include('emails.text.details')

Reschedule: {!! $rescheduleUrl !!}
Cancel: {!! $cancelUrl !!}
(These links work until your appointment starts.)
