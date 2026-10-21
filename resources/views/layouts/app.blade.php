<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@hasSection('title')@yield('title') · @endif{{ config('booking.business_name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="{{ route('home') }}" class="brand">{{ config('booking.business_name') }}</a>
    </div>
</header>

<main class="container">
    @yield('content')
</main>

<footer class="site-footer">
    <div class="container">
        <span>{{ config('booking.business_name') }}</span>
        <span>Reminder emails sent via Mailtrap Email API · <a href="https://github.com/manishgupta412/laravel-appointment-reminders">Source on GitHub</a></span>
    </div>
</footer>

<script>
    // Disable the submit button once a form is sent, so a double click can't book twice.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        var btn = form.querySelector('button[type=submit]');
        if (e.defaultPrevented || !btn || !form.checkValidity()) return;
        btn.disabled = true;
        if (btn.dataset.busy) btn.textContent = btn.dataset.busy;
    });
    // Date strip: arrows scroll it; the selected day starts in view.
    document.querySelectorAll('[data-strip]').forEach(function (strip) {
        var wrap = strip.parentElement;
        var prev = wrap.querySelector('[data-strip-dir="-1"]');
        var next = wrap.querySelector('[data-strip-dir="1"]');
        var sync = function () {
            var scrollable = strip.scrollWidth > strip.clientWidth + 1;
            prev.hidden = !scrollable || strip.scrollLeft < 4;
            next.hidden = !scrollable || strip.scrollLeft + strip.clientWidth >= strip.scrollWidth - 4;
        };
        [prev, next].forEach(function (b) {
            b.addEventListener('click', function () {
                strip.scrollBy({ left: b.dataset.stripDir * strip.clientWidth * 0.8 });
            });
        });
        var current = strip.querySelector('[aria-current]');
        if (current) strip.scrollLeft = current.offsetLeft - strip.offsetLeft - 8;
        strip.addEventListener('scroll', sync, { passive: true });
        window.addEventListener('resize', sync);
        sync();
    });
    // Restored from the back/forward cache: make the buttons usable again.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('button[type=submit][disabled]').forEach(function (b) { b.disabled = false; });
    });
</script>
</body>
</html>
