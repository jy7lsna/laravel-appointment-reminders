<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ServiceController::class, 'index'])->name('home');

Route::get('/book/{service:slug}', [BookingController::class, 'show'])->name('booking.show');
Route::post('/book/{service:slug}', [BookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('booking.store');

// Self-service links from emails. Signed + expiring at the appointment start;
// a tampered or expired signature returns 403. The POST routes share the GET
// path, so the signed GET URL doubles as the form action.
Route::middleware('signed')->prefix('appointments/{appointment}')->group(function () {
    Route::get('/', [AppointmentController::class, 'show'])->name('appointments.show');

    Route::get('/cancel', [AppointmentController::class, 'confirmCancel'])->name('appointments.cancel');
    Route::post('/cancel', [AppointmentController::class, 'cancel'])
        ->middleware('throttle:10,1')
        ->name('appointments.cancel.store');

    Route::get('/reschedule', [AppointmentController::class, 'editReschedule'])->name('appointments.reschedule');
    Route::post('/reschedule', [AppointmentController::class, 'reschedule'])
        ->middleware('throttle:10,1')
        ->name('appointments.reschedule.store');
});
