@extends('layouts.app')

@section('title', 'Book '.$service->name)

@section('content')
    <div class="shell">
        @include('partials.aside', ['title' => 'Schedule a '.$service->name, 'service' => $service])

        <form method="POST" action="{{ route('booking.store', $service) }}" class="main" novalidate>
            @csrf
            <a href="{{ route('home') }}" class="back">&larr; All services</a>

            <section class="section">
                <h2 class="section-title">Select Date</h2>
                @include('partials.date-picker')
            </section>

            <section class="section">
                @include('partials.slot-picker')
            </section>

            @if($isOpen && $slots->isNotEmpty())
                <section class="section" aria-label="Your details">
                    <div class="fields">
                        <div class="field">
                            <label for="customer_name">Name</label>
                            <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" placeholder="John Smith" autocomplete="name" required maxlength="120"@error('customer_name') aria-invalid="true" aria-describedby="customer_name-error"@enderror>
                            @error('customer_name')<p class="error" id="customer_name-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="customer_phone">Phone number</label>
                            <input type="tel" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="+1 212 555 0100" autocomplete="tel" required maxlength="30"@error('customer_phone') aria-invalid="true" aria-describedby="customer_phone-error"@enderror>
                            @error('customer_phone')<p class="error" id="customer_phone-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field full">
                            <label for="customer_email">Email</label>
                            <input type="email" id="customer_email" name="customer_email" value="{{ old('customer_email') }}" placeholder="example@email.com" autocomplete="email" required maxlength="255"@error('customer_email') aria-invalid="true" aria-describedby="customer_email-error"@enderror>
                            @error('customer_email')<p class="error" id="customer_email-error">{{ $message }}</p>@else<p class="help">Your confirmation and reminders go here.</p>@enderror
                        </div>
                    </div>
                    <button type="submit" class="btn btn-block" data-busy="Confirming…">Confirm booking</button>
                </section>
            @endif
        </form>
    </div>
@endsection
