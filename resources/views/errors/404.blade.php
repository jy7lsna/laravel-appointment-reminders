@extends('layouts.app')

@section('title', 'Not found')

@section('content')
    <div class="shell single"><div class="main">
        <h1>Page not found</h1>
        <p class="muted">We couldn't find what you were looking for.</p>
        <p><a class="btn" href="{{ route('home') }}">Go to booking page</a></p>
    </div></div>
@endsection
