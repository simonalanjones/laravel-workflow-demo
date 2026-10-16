<?php

use App\Enums\BookingStatus;
use App\Jobs\ProcessBookingWorkflowJob;
use App\Models\Booking;
use App\Services\Workflows\WorkflowProcessorResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('demo can omit signoff', function (string $scenario): void {
    Queue::fake([ProcessBookingWorkflowJob::class]);
    $this->withoutVite();

    $this->post('/demo/bookings/'.$scenario, [
        'item_types' => ['car_hire'],
        'requires_signoff' => '0',
    ])->assertRedirect();

    $booking = Booking::sole();
    $this->assertSame(['vehicle_confirmation'], $booking->workflows()->pluck('type')->all());
    Queue::assertPushed(ProcessBookingWorkflowJob::class, 1);
    $this->get('/bookings/'.$booking->id)->assertDontSee('Operations signoff')->assertDontSee('Approve signoff');
})->with([
    ['successful'],
    ['failing'],
    ['random-failure'],
]);

test('api booking without signoff completes after all selected providers confirm', function (array $types): void {
    Queue::fake([ProcessBookingWorkflowJob::class]);
    Http::preventStrayRequests();
    Http::fake([
        url('/api/demo/providers/property/confirm') => Http::response(['provider_reference' => 'PROPERTY-123']),
        url('/api/demo/providers/flights/confirm') => Http::response(['provider_reference' => 'FLIGHT-123']),
        url('/api/demo/providers/vehicles/confirm') => Http::response(['provider_reference' => 'VEHICLE-123']),
    ]);

    $this->postJson('/api/bookings', [
        'reference' => 'OPTIONAL-001',
        'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com',
        'requires_signoff' => false,
        'items' => array_map(fn (string $type): array => [
            'type' => $type,
            'description' => $type,
            'quantity' => 1,
            'unit_price' => 100,
        ], $types),
    ])->assertCreated()->assertJsonCount(count($types), 'workflows');

    $booking = Booking::sole();
    foreach ($booking->workflows as $workflow) {
        $this->assertNotSame(BookingStatus::Processed, $booking->fresh()->status);
        app(WorkflowProcessorResolver::class)->resolve($workflow)->process($workflow);
    }

    $this->assertSame(BookingStatus::Processed, $booking->fresh()->status);
    $this->assertDatabaseHas('booking_activities', [
        'booking_id' => $booking->id,
        'type' => 'booking_processed',
    ]);
    Http::assertSentCount(count($types));
    Queue::assertPushed(ProcessBookingWorkflowJob::class, count($types));
    Queue::assertNotPushed(ProcessBookingWorkflowJob::class, fn (ProcessBookingWorkflowJob $job): bool => $job->workflow->type === 'operations_signoff');
})->with([
    [['accommodation']],
    [['flight']],
    [['car_hire']],
    [['accommodation', 'flight']],
    [['accommodation', 'car_hire']],
    [['flight', 'car_hire']],
    [['accommodation', 'flight', 'car_hire']],
]);

test('explicit signoff selection creates the approval step', function (): void {
    Queue::fake([ProcessBookingWorkflowJob::class]);

    $this->post('/demo/bookings/successful', [
        'item_types' => ['car_hire'],
        'requires_signoff' => '1',
    ])->assertRedirect();

    $this->assertDatabaseHas('booking_workflows', ['type' => 'operations_signoff', 'status' => 'pending']);
    Queue::assertPushed(ProcessBookingWorkflowJob::class, 1);
});

test('rejects invalid signoff option', function (string $endpoint): void {
    Queue::fake();

    $this->postJson($endpoint, [
        'item_types' => ['car_hire'],
        'requires_signoff' => 'invalid',
    ])->assertUnprocessable()->assertJsonValidationErrors('requires_signoff');

    $this->assertDatabaseCount('bookings', 0);
    Queue::assertNothingPushed();
})->with([
    ['/demo/bookings/successful'],
    ['/api/bookings'],
]);

test('booking form offers signoff selection', function (): void {
    $this->withoutVite();

    $this->get('/bookings/create')->assertSee('Require operations signoff')
        ->assertSee('name="requires_signoff"', false)
        ->assertSee('Uncheck to complete the booking automatically once all items are confirmed.');
});
