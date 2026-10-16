<?php

use App\Jobs\ProcessBookingWorkflowJob;
use App\Models\Booking;
use App\Services\Workflows\VehicleConfirmationProcessor;
use App\Services\Workflows\WorkflowCoordinator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('demo bookings include car hire', function (string $scenario): void {
    Queue::fake([ProcessBookingWorkflowJob::class]);
    $this->withoutVite();

    $response = $this->post('/demo/bookings/'.$scenario, ['item_types' => ['accommodation', 'flight', 'car_hire']]);

    $booking = Booking::sole();
    $response->assertRedirect('/bookings/'.$booking->id);
    $this->assertDatabaseHas('booking_items', [
        'booking_id' => $booking->id,
        'type' => 'car_hire',
        'description' => 'Car hire',
        'quantity' => 1,
        'unit_price' => 250,
    ]);
    Queue::assertPushed(ProcessBookingWorkflowJob::class, fn (ProcessBookingWorkflowJob $job): bool => $job->workflow->booking_id === $booking->id && $job->workflow->type === 'vehicle_confirmation'
    );
    $this->get('/bookings/'.$booking->id)
        ->assertSee('Car hire')
        ->assertSee('Vehicle confirmation')
        ->assertSee('2,150.00');
})->with([
    ['successful'],
    ['failing'],
    ['random-failure'],
]);

test('car hire can be booked without flights or accommodation', function (): void {
    Queue::fake([ProcessBookingWorkflowJob::class]);

    $response = $this->postJson('/api/bookings', [
        'reference' => 'CAR-001',
        'customer_name' => 'Car Hire Customer',
        'customer_email' => 'customer@example.com',
        'items' => [[
            'type' => 'car_hire',
            'description' => 'Compact car',
            'quantity' => 1,
            'unit_price' => 250,
        ]],
    ]);

    $response->assertCreated()->assertJsonPath('items.0.type', 'car_hire')
        ->assertJsonCount(2, 'workflows');
    $this->assertDatabaseHas('booking_workflows', [
        'booking_id' => $response->json('id'),
        'type' => 'vehicle_confirmation',
        'status' => 'queued',
    ]);
    Queue::assertPushed(ProcessBookingWorkflowJob::class, fn (ProcessBookingWorkflowJob $job): bool => $job->workflow->type === 'vehicle_confirmation'
    );
});

test('signoff waits for vehicle confirmation after other providers complete', function (): void {
    Queue::fake([ProcessBookingWorkflowJob::class]);
    Http::preventStrayRequests();
    Http::fake([
        url('/api/demo/providers/vehicles/confirm') => Http::response([
            'provider_reference' => 'VEHICLE-123456',
        ]),
    ]);
    $this->post('/demo/bookings/successful', ['item_types' => ['accommodation', 'flight', 'car_hire']])->assertRedirect();
    $booking = Booking::sole();
    $booking->workflows()->whereIn('type', ['property_confirmation', 'flight_confirmation'])
        ->update(['status' => 'completed']);
    $signoff = $booking->workflows()->where('type', 'operations_signoff')->sole();
    app(WorkflowCoordinator::class)->startIfReady($signoff);
    Queue::assertNotPushed(ProcessBookingWorkflowJob::class, fn (ProcessBookingWorkflowJob $job): bool => $job->workflow->type === 'operations_signoff'
    );
    $vehicle = $booking->workflows()->where('type', 'vehicle_confirmation')->sole();

    app(VehicleConfirmationProcessor::class)->process($vehicle);

    Http::assertSent(fn (Request $request): bool => $request->url() === url('/api/demo/providers/vehicles/confirm')
        && $request['booking_reference'] === $booking->reference
    );
    $this->assertDatabaseHas('booking_workflows', [
        'id' => $vehicle->id,
        'status' => 'completed',
        'message' => 'Vehicle confirmed with provider (VEHICLE-123456)',
    ]);
    $this->assertDatabaseHas('booking_activities', [
        'booking_workflow_id' => $vehicle->id,
        'type' => 'workflow_item_completed',
    ]);
    Queue::assertPushed(ProcessBookingWorkflowJob::class, fn (ProcessBookingWorkflowJob $job): bool => $job->workflow->id === $signoff->id
    );
});
