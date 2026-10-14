<?php

namespace Tests\Feature;

use App\Jobs\ProcessBookingWorkflowJob;
use App\Models\Booking;
use App\Services\Workflows\OperationsSignoffProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Lottery;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DemoProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Lottery::determineResultsNormally();

        parent::tearDown();
    }

    #[TestWith(['property', 'Property'])]
    #[TestWith(['flights', 'Flight'])]
    #[TestWith(['vehicles', 'Vehicle'])]
    public function test_each_provider_can_randomly_fail_and_succeed_on_retry(string $endpoint, string $provider): void
    {
        Lottery::fix([true, false]);
        $payload = ['booking_reference' => 'DEMO-FAILR-001'];

        $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)
            ->assertServiceUnavailable()
            ->assertExactJson(['status' => 'unavailable', 'message' => $provider.' provider randomly failed']);

        $response = $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload);

        $response->assertOk()->assertJsonPath('status', 'confirmed');
        $this->assertStringStartsWith(strtoupper($provider).'-', $response->json('provider_reference'));
    }

    public function test_random_results_are_independent_for_each_component(): void
    {
        Lottery::fix([true, false, true]);
        $payload = ['booking_reference' => 'DEMO-FAILR-002'];

        $this->postJson('/api/demo/providers/property/confirm', $payload)->assertServiceUnavailable();
        $this->postJson('/api/demo/providers/flights/confirm', $payload)->assertOk()->assertJsonPath('status', 'confirmed');
        $this->postJson('/api/demo/providers/vehicles/confirm', $payload)->assertServiceUnavailable();
    }

    #[TestWith(['property'])]
    #[TestWith(['flights'])]
    #[TestWith(['vehicles'])]
    public function test_successful_scenario_does_not_randomly_fail(string $endpoint): void
    {
        Lottery::alwaysWin();

        $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', ['booking_reference' => 'DEMO-001'])
            ->assertOk()->assertJsonPath('status', 'confirmed');
    }

    public function test_guaranteed_failure_scenario_still_only_fails_flights(): void
    {
        Lottery::alwaysLose();
        $payload = ['booking_reference' => 'DEMO-FAIL-001'];

        $this->postJson('/api/demo/providers/flights/confirm', $payload)
            ->assertServiceUnavailable()->assertJsonPath('message', 'Flight provider is temporarily unavailable');
        $this->postJson('/api/demo/providers/property/confirm', $payload)->assertOk()->assertJsonPath('status', 'confirmed');
        $this->postJson('/api/demo/providers/vehicles/confirm', $payload)->assertOk()->assertJsonPath('status', 'confirmed');
    }

    public function test_signoff_is_not_subject_to_random_failure(): void
    {
        Queue::fake([ProcessBookingWorkflowJob::class]);
        Lottery::alwaysWin();
        $this->post('/demo/bookings/random-failure', [
            'item_types' => ['car_hire'],
            'requires_signoff' => true,
        ])->assertRedirect();
        $booking = Booking::sole();
        $booking->workflows()->where('type', 'vehicle_confirmation')->update(['status' => 'completed']);
        $signoff = $booking->workflows()->where('type', 'operations_signoff')->sole();

        app(OperationsSignoffProcessor::class)->process($signoff);

        $this->assertDatabaseHas('booking_workflows', ['id' => $signoff->id, 'status' => 'awaiting_approval']);
        Queue::assertPushed(ProcessBookingWorkflowJob::class, 1);
    }
}
