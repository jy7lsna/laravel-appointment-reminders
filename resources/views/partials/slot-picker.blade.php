{{-- Expects: $slots (UTC), $isOpen, $selectedDate; optional $current (UTC) --}}
@php($tz = config('booking.timezone'))
<h2 class="section-title">Select Time <span class="muted" style="font-weight:400">· {{ $selectedDate->format('l j F') }}</span></h2>

@if(! $isOpen)
    <div class="notice">We're closed on {{ $selectedDate->format('l') }}s. Please pick another day.</div>
@elseif($slots->isEmpty())
    <div class="notice">No free times left on this day. Please pick another day.</div>
@else
    <div class="slots" role="radiogroup" aria-label="Available times on {{ $selectedDate->format('l j F') }}">
        @foreach($slots as $slot)
            @php($value = $slot->toIso8601ZuluString())
            @php($isCurrent = isset($current) && $current->equalTo($slot))
            <label class="slot {{ $isCurrent ? 'is-current' : '' }}">
                <input type="radio" name="starts_at" value="{{ $value }}" required
                       @disabled($isCurrent) @checked(old('starts_at') === $value)>
                <span>{{ $slot->setTimezone($tz)->format('g:i A') }}@if($isCurrent) <small>(current)</small>@endif</span>
            </label>
        @endforeach
    </div>
@endif
@error('starts_at')
    <p class="error slots-error" role="alert">{{ $message }}</p>
@enderror
