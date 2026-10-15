@extends('layouts.app')

@section('title', 'Book '.$service->name)

@section('content')
    <p><a href="{{ route('home') }}" class="back">← All services</a></p>
    <h1>{{ $service->name }} <span class="pill">{{ $service->duration_minutes }} min</span></h1>
    @if($service->description)
        <p class="muted">{{ $service->description }}</p>
    @endif

    <section class="card">
        <h2 class="step">1. Choose a date</h2>
        @include('partials.date-picker')
    </section>

    <form method="POST" action="{{ route('booking.store', $service) }}" class="card" novalidate>
        @csrf
        <h2 class="step">2. Choose a time</h2>
        @include('partials.slot-picker')

        @if($isOpen && $slots->isNotEmpty())
            <h2 class="step">3. Your details</h2>
            <div class="fields">
                <label>Name
                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" required maxlength="120">
                    @error('customer_name')<span class="error">{{ $message }}</span>@enderror
                </label>
                <label>Email
                    <input type="email" name="customer_email" value="{{ old('customer_email') }}" autocomplete="email" required maxlength="255">
                    @error('customer_email')<span class="error">{{ $message }}</span>@enderror
                </label>
                <label>Phone
                    <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" autocomplete="tel" required maxlength="30">
                    @error('customer_phone')<span class="error">{{ $message }}</span>@enderror
                </label>
            </div>
            <button type="submit" class="btn">Confirm booking</button>
        @endif
    </form>
@endsection
