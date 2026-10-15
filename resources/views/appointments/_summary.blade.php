<dl class="summary">
    <dt>Service</dt><dd>{{ $appointment->service->name }} ({{ $appointment->service->duration_minutes }} min)</dd>
    <dt>When</dt><dd>{{ $appointment->localStartsAt()->format('l j F Y, g:i A') }} – {{ $appointment->localEndsAt()->format('g:i A T') }}</dd>
    <dt>Timezone</dt><dd>{{ config('booking.timezone') }}</dd>
    @if(config('booking.address'))
        <dt>Where</dt><dd>{{ config('booking.address') }}</dd>
    @endif
    <dt>Name</dt><dd>{{ $appointment->customer_name }}</dd>
    <dt>Email</dt><dd>{{ $appointment->customer_email }}</dd>
</dl>
