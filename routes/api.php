<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\DemoProviderController;
use Illuminate\Support\Facades\Route;

Route::post('/bookings', [BookingController::class, 'store']);
Route::get('/bookings/statuses', [BookingController::class, 'statuses']);

Route::post('/demo/providers/property/confirm', [
    DemoProviderController::class, 'ConfirmProperty',
]);
Route::post('/demo/providers/flights/confirm', [
    DemoProviderController::class, 'ConfirmFlight',
]);
Route::post('/demo/providers/vehicles/confirm', [
    DemoProviderController::class, 'confirmVehicle'
]);
