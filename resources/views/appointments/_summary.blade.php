<div class="summary-box">
    <dl class="summary">
        <dt>Service</dt><dd>{{ $appointment->service->name }}</dd>
        <dt>When</dt><dd>{{ $appointment->localStartsAt()->format('l j F Y, g:i A') }} – {{ $appointment->localEndsAt()->format('g:i A T') }}</dd>
        <dt>Duration</dt><dd>{{ $appointment->service->duration_minutes }} min</dd>
        <dt>Where</dt><dd>{{ config('booking.address') ?: 'Video call' }}</dd>
        <dt>Name</dt><dd>{{ $appointment->customer_name }}</dd>
        <dt>Email</dt><dd>{{ $appointment->customer_email }}</dd>
    </dl>
</div>
