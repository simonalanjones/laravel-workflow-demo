<?php

namespace Tests\Feature;

use App\Jobs\ProcessBookingWorkflowJob;
use App\Models\Booking;
use App\Services\Providers\FlightProviderClient;
use App\Services\Providers\PropertyProviderClient;
use App\Services\Providers\VehicleProviderClient;
use Carbon\CarbonInterval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class NetworkDelayTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['successful'])]
    #[TestWith(['failing'])]
    #[TestWith(['random-failure'])]
    public function test_demo_saves_delay_setting_and_all_clients_pass_it_to_the_api(string $scenario): void
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
            'simulate_network_delay' => '1',
        ])->assertRedirect();

        $booking = Booking::sole();
        $this->assertTrue($booking->simulate_network_delay);
        foreach ([PropertyProviderClient::class, FlightProviderClient::class, VehicleProviderClient::class] as $client) {
            app($client)->confirm($booking);
        }
        foreach (['property', 'flights', 'vehicles'] as $endpoint) {
            Http::assertSent(fn (Request $request): bool => $request->url() === url('/api/demo/providers/'.$endpoint.'/confirm')
                && $request['simulate_network_delay'] === true
                && $request['booking_reference'] === $booking->reference);
        }
        Http::assertSentCount(3);
        Queue::assertPushed(ProcessBookingWorkflowJob::class, 3);
    }

    #[TestWith(['property'])]
    #[TestWith(['flights'])]
    #[TestWith(['vehicles'])]
    public function test_provider_delays_each_attempt_when_enabled(string $endpoint): void
    {
        Sleep::fake();
        $payload = ['booking_reference' => 'DEMO-001', 'simulate_network_delay' => true];

        $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)->assertOk();
        $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)->assertOk();

        Sleep::assertSlept(fn (CarbonInterval $duration): bool => $duration->totalMilliseconds >= 1000 && $duration->totalMilliseconds <= 5000, times: 2);
        Sleep::assertSleptTimes(2);
    }

    public function test_failed_response_is_also_delayed(): void
    {
        Sleep::fake();

        $this->postJson('/api/demo/providers/flights/confirm', [
            'booking_reference' => 'DEMO-FAIL-001',
            'simulate_network_delay' => true,
        ])->assertServiceUnavailable();

        Sleep::assertSleptTimes(1);
    }

    #[TestWith(['property'])]
    #[TestWith(['flights'])]
    #[TestWith(['vehicles'])]
    public function test_provider_does_not_delay_when_disabled_or_omitted(string $endpoint): void
    {
        Sleep::fake();

        $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', ['booking_reference' => 'DEMO-001'])->assertOk();
        $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', [
            'booking_reference' => 'DEMO-002',
            'simulate_network_delay' => false,
        ])->assertOk();

        Sleep::assertNeverSlept();
    }

    public function test_demo_defaults_to_no_delay(): void
    {
        Queue::fake([ProcessBookingWorkflowJob::class]);

        $this->post('/demo/bookings/successful', ['item_types' => ['car_hire']])->assertRedirect();

        $this->assertFalse(Booking::sole()->simulate_network_delay);
        Queue::assertPushed(ProcessBookingWorkflowJob::class, 1);
    }

    public function test_invalid_delay_setting_is_rejected(): void
    {
        Queue::fake();

        $this->postJson('/demo/bookings/successful', [
            'item_types' => ['car_hire'],
            'simulate_network_delay' => 'invalid',
        ])->assertUnprocessable()->assertJsonValidationErrors('simulate_network_delay');

        $this->assertDatabaseCount('bookings', 0);
        Queue::assertNothingPushed();
    }

    public function test_booking_form_offers_network_delay_option(): void
    {
        $this->withoutVite();

        $this->get('/bookings/create')->assertSee('Simulate network delay')->assertSee('name="simulate_network_delay"', false);
    }
}
