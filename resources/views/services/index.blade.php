@extends('layouts.app')

@section('content')
    <section class="hero">
        <h1>Book an appointment</h1>
        <p class="muted">Pick a service, choose a time, and you'll get a confirmation email with a calendar invite.</p>
    </section>

    @if($services->isEmpty())
        <div class="card">No services are available right now. Please check back later.</div>
    @else
        <div class="service-list">
            @foreach($services as $service)
                <a class="card service" href="{{ route('booking.show', $service) }}">
                    <div>
                        <h2>{{ $service->name }}</h2>
                        @if($service->description)
                            <p class="muted">{{ $service->description }}</p>
                        @endif
                    </div>
                    <span class="pill">{{ $service->duration_minutes }} min</span>
                </a>
            @endforeach
        </div>
    @endif
@endsection
