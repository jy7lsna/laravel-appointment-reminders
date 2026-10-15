{{-- Expects: $dates, $selectedDate, $dateUrl (closure date => url) --}}
<nav class="date-picker" aria-label="Choose a date">
    @foreach($dates as $day)
        @php($active = $day['date']->isSameDay($selectedDate))
        <a href="{{ $dateUrl($day['date']) }}"
           class="date {{ $active ? 'is-active' : '' }} {{ $day['open'] ? '' : 'is-closed' }}"
           @if($active) aria-current="date" @endif>
            <span class="dow">{{ $day['date']->format('D') }}</span>
            <span class="dom">{{ $day['date']->format('j') }}</span>
            <span class="mon">{{ $day['date']->format('M') }}</span>
        </a>
    @endforeach
</nav>
