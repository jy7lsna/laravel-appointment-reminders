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

</body>
</html>
