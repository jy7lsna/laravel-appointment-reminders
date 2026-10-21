@extends('layouts.app')

@section('content')
    <div class="shell">
        @include('partials.aside', ['title' => 'Book an appointment'])

        <section class="main">
            <h2 class="section-title">Choose a service</h2>

            @if($services->isEmpty())
                <div class="empty">There are no services open for booking right now. Please check back later.</div>
            @else
                <div class="service-list">
                    @foreach($services as $service)
                        <a class="service" href="{{ route('booking.show', $service) }}">
                            <span>
                                <span class="name">{{ $service->name }}</span>
                                @if($service->description)
                                    <span class="detail">{{ $service->description }}</span>
                                @endif
                            </span>
                            <span class="time">{{ $service->duration_minutes }} min</span>
                            <span class="chev" aria-hidden="true"></span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
