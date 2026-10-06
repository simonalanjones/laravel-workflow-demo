<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DemoProviderController extends Controller
{
    public function confirmProperty(Request $request)
    {
        return response()->json([
            'status' => 'confirmed',
            'provider_reference' => 'PROPERTY-'.fake()->numerify('######'),
        ]);
    }

    public function confirmVehicle(Request $request)
    {
        return response()->json([
            'status' => 'confirmed',
            'provider_reference' => 'VEHICLE-'.fake()->numerify('######'),
        ]);
    }

    public function confirmFlight(Request $request)
    {
        $bookingReference = $request->input('booking_reference');

        if (str_contains($bookingReference, 'FAILR')) {
            if (random_int(0, 1) === 1) {
                return response()->json([
                    'status' => 'unavailable',
                    'message' => 'Flight provider randomly failed',
                ], 503);
            }

            return response()->json([
                'status' => 'confirmed',
                'provider_reference' => 'FLIGHT-'.fake()->numerify('######'),
            ]);
        }

        if (str_contains($bookingReference, 'FAIL')) {
            return response()->json([
                'status' => 'unavailable',
                'message' => 'Flight provider is temporarily unavailable',
            ], 503);
        }

        return response()->json([
            'status' => 'confirmed',
            'provider_reference' => 'FLIGHT-'.fake()->numerify('######'),
        ]);
    }
}
