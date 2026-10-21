{{-- Expects: $title. Optional: $service (adds duration), $note (adds a "Current" line). --}}
<aside class="side">
    <p class="eyebrow">{{ config('booking.business_name') }}</p>
    <h1>{{ $title }}</h1>
    <ul class="facts">
        @isset($service)
            <li><span>Duration</span><span>{{ $service->duration_minutes }} min</span></li>
        @endisset
        <li><span>Location</span><span>{{ config('booking.address') ?: 'Video call' }}</span></li>
        @isset($note)
            <li><span>Current</span><span>{{ $note }}</span></li>
        @endisset
    </ul>
    @include('partials.art')
</aside>
