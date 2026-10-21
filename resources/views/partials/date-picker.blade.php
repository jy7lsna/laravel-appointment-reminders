{{-- Expects: $dates, $selectedDate, $dateUrl (closure date => url). A strip of the bookable days. --}}
<div class="strip-wrap">
    <button type="button" class="strip-nav prev" data-strip-dir="-1" aria-label="Earlier dates" hidden>&larr;</button>
    <nav class="strip" data-strip aria-label="Choose a date">
        @foreach($dates as $day)
            @php
                $selected = $day['date']->isSameDay($selectedDate);
                $label = $day['date']->format('l j F');
            @endphp
            @if($day['open'] || $selected)
                <a href="{{ $dateUrl($day['date']) }}" class="chip-day {{ $selected ? 'is-selected' : '' }}" aria-label="{{ $label }}"@if($selected) aria-current="date"@endif>
                    <span class="mon">{{ $day['date']->format('M') }}</span>
                    <span class="dom">{{ $day['date']->format('j') }}</span>
                    <span class="dow">{{ $day['date']->format('D') }}</span>
                </a>
            @else
                <span class="chip-day is-off" aria-label="{{ $label }}, closed">
                    <span class="mon">{{ $day['date']->format('M') }}</span>
                    <span class="dom">{{ $day['date']->format('j') }}</span>
                    <span class="dow">{{ $day['date']->format('D') }}</span>
                </span>
            @endif
        @endforeach
    </nav>
    <button type="button" class="strip-nav next" data-strip-dir="1" aria-label="Later dates" hidden>&rarr;</button>
</div>
