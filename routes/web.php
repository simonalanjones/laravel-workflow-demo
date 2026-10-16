<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoBookingController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('home');

Route::get('/bookings', [
    DashboardController::class, 'index',
])->name('bookings.index');

Route::get('/bookings/create', [
    DashboardController::class, 'create',
])->name('bookings.create');

Route::get('/bookings/{booking}', [
    DashboardController::class, 'show',
]);

Route::post('/demo/bookings/successful', [
    DemoBookingController::class, 'successful',
]);

Route::post('/demo/bookings/failing', [
    DemoBookingController::class, 'failing',
]);

Route::post('/demo/bookings/random-failure', [
    DemoBookingController::class, 'randomFailure',
]);
