<?php

use App\Jobs\ProcessBookingWorkflowJob;
use App\Models\Booking;
use App\Services\Workflows\OperationsSignoffProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Lottery;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Lottery::determineResultsNormally();
});

test('each provider can randomly fail and succeed on retry', function (string $endpoint, string $provider): void {
    Lottery::fix([true, false]);
    $payload = ['booking_reference' => 'DEMO-FAILR-001'];

    $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload)
        ->assertServiceUnavailable()
        ->assertExactJson(['status' => 'unavailable', 'message' => $provider.' provider randomly failed']);

    $response = $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', $payload);

    $response->assertOk()->assertJsonPath('status', 'confirmed');
    $this->assertStringStartsWith(strtoupper($provider).'-', $response->json('provider_reference'));
})->with([
    ['property', 'Property'],
    ['flights', 'Flight'],
    ['vehicles', 'Vehicle'],
]);

test('random results are independent for each component', function (): void {
    Lottery::fix([true, false, true]);
    $payload = ['booking_reference' => 'DEMO-FAILR-002'];

    $this->postJson('/api/demo/providers/property/confirm', $payload)->assertServiceUnavailable();
    $this->postJson('/api/demo/providers/flights/confirm', $payload)->assertOk()->assertJsonPath('status', 'confirmed');
    $this->postJson('/api/demo/providers/vehicles/confirm', $payload)->assertServiceUnavailable();
});

test('successful scenario does not randomly fail', function (string $endpoint): void {
    Lottery::alwaysWin();

    $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', ['booking_reference' => 'DEMO-001'])
        ->assertOk()->assertJsonPath('status', 'confirmed');
})->with([
    ['property'],
    ['flights'],
    ['vehicles'],
]);

test('guaranteed failure scenario still only fails flights', function (): void {
    Lottery::alwaysLose();
    $payload = ['booking_reference' => 'DEMO-FAIL-001'];

    $this->postJson('/api/demo/providers/flights/confirm', $payload)
        ->assertServiceUnavailable()->assertJsonPath('message', 'Flight provider is temporarily unavailable');
    $this->postJson('/api/demo/providers/property/confirm', $payload)->assertOk()->assertJsonPath('status', 'confirmed');
    $this->postJson('/api/demo/providers/vehicles/confirm', $payload)->assertOk()->assertJsonPath('status', 'confirmed');
});

test('signoff is not subject to random failure', function (): void {
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
});
