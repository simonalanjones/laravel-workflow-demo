<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('bookings.index');
    }

    public function show(Booking $booking): View
    {
        $booking->load([
            'items',
            'workflowSteps',
            'activities' => fn ($query) => $query->oldest(),
        ]);

        return view('bookings.show', [
            'booking' => $booking,
        ]);
    }

    public function create(Request $request): View
    {
        return view('bookings.create', [
            'isLostResponseScenario' => $request->query('scenario') === 'lost-response',
        ]);
    }
}
