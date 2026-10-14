<?php

namespace Tests\Feature;

use App\Jobs\ProcessBookingWorkflowJob;
use App\Models\Booking;
use App\Services\Providers\FlightProviderClient;
use App\Services\Providers\PropertyProviderClient;
use App\Services\Providers\VehicleProviderClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LostProviderResponseTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['successful'])]
    #[TestWith(['failing'])]
    #[TestWith(['random-failure'])]
    public function test_demo_saves_lost_response_setting_and_all_clients_pass_it_to_the_api(string $scenario): void
    {
        Queue::fake([ProcessBookingWorkflowJob::class]);
        Http::preventStrayRequests();
        Http::fake([
            url('/api/demo/providers/property/confirm') => Http::response(['provider_reference' => 'PROPERTY-1']),
            url('/api/demo/providers/flights/confirm') => Http::response(['provider_reference' => 'FLIGHT-1']),
            url('/api/demo/providers/vehicles/confirm') => Http::response(['provider_reference' => 'VEHICLE-1']),
        ]);

        $this->post('/demo/bookings/'.$scenario, [
            'item_types' => ['accommodation', 'flight', 'car_hire'],
            'simulate_lost_response' => '1',
        ])->assertRedirect();

        $booking = Booking::sole();
        $this->assertTrue($booking->simulate_lost_response);
        foreach ([PropertyProviderClient::class, FlightProviderClient::class, VehicleProviderClient::class] as $client) {
            app($client)->confirm($booking);
        }
        foreach (['property', 'flights', 'vehicles'] as $endpoint) {
            Http::assertSent(fn (Request $request): bool => $request->url() === url('/api/demo/providers/'.$endpoint.'/confirm')
                && $request['simulate_lost_response'] === true
                && $request['booking_reference'] === $booking->reference);
        }
        Http::assertSentCount(3);
        Queue::assertPushed(ProcessBookingWorkflowJob::class, 3);
    }

    public function test_demo_defaults_to_no_lost_response(): void
    {
        Queue::fake([ProcessBookingWorkflowJob::class]);

        $this->post('/demo/bookings/successful', ['item_types' => ['car_hire']])->assertRedirect();

        $this->assertFalse(Booking::sole()->simulate_lost_response);
        Queue::assertPushed(ProcessBookingWorkflowJob::class, 1);
    }

    public function test_invalid_lost_response_setting_is_rejected(): void
    {
        Queue::fake();

        $this->postJson('/demo/bookings/successful', [
            'item_types' => ['car_hire'],
            'simulate_lost_response' => 'invalid',
        ])->assertUnprocessable()->assertJsonValidationErrors('simulate_lost_response');

        $this->assertDatabaseCount('bookings', 0);
        Queue::assertNothingPushed();
    }

    public function test_booking_form_offers_lost_response_option(): void
    {
        $this->withoutVite();

        $this->get('/bookings/create')->assertSee('Simulate lost provider response')->assertSee('name="simulate_lost_response"', false);
    }
}
