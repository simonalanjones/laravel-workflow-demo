<?php

use App\Jobs\ProcessBookingWorkflowStepJob;
use App\Models\Booking;
use App\Services\Workflows\WorkflowCoordinator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/** @return iterable<string, array{string, list<string>, list<string>}> */
dataset('demo booking combinations', function (): iterable {
    $combinations = [
        [['accommodation'], ['property_confirmation']],
        [['flight'], ['flight_confirmation']],
        [['car_hire'], ['vehicle_confirmation']],
        [['accommodation', 'flight'], ['property_confirmation', 'flight_confirmation']],
        [['accommodation', 'car_hire'], ['property_confirmation', 'vehicle_confirmation']],
        [['flight', 'car_hire'], ['flight_confirmation', 'vehicle_confirmation']],
        [['accommodation', 'flight', 'car_hire'], ['property_confirmation', 'flight_confirmation', 'vehicle_confirmation']],
    ];

    foreach (['successful', 'failing', 'random-failure'] as $scenario) {
        foreach ($combinations as [$types, $workflowSteps]) {
            yield $scenario.' '.implode(', ', $types) => [$scenario, $types, $workflowSteps];
        }
    }
});

/**
 * @param  list<string>  $types
 * @param  list<string>  $workflowSteps
 */
test('creates only selected items and signoff waits only for their workflows', function (string $scenario, array $types, array $workflowSteps): void {
    Queue::fake([ProcessBookingWorkflowStepJob::class]);

    $response = $this->post('/demo/bookings/'.$scenario, ['item_types' => $types]);

    $booking = Booking::sole();
    $response->assertRedirect('/bookings/'.$booking->public_id);
    $this->assertSame($types, $booking->items()->orderBy('id')->pluck('type')->all());
    $this->assertSame([...$workflowSteps, 'operations_signoff'], $booking->workflowSteps()->orderBy('id')->pluck('type')->all());
    Queue::assertPushed(ProcessBookingWorkflowStepJob::class, count($workflowSteps));
    Queue::assertNotPushed(ProcessBookingWorkflowStepJob::class, fn (ProcessBookingWorkflowStepJob $job): bool => $job->workflowStep->type === 'operations_signoff');

    $booking->workflowSteps()->whereIn('type', $workflowSteps)->update(['status' => 'completed']);
    $signoff = $booking->workflowSteps()->where('type', 'operations_signoff')->sole();
    app(WorkflowCoordinator::class)->queueIfReady($signoff);

    Queue::assertPushed(ProcessBookingWorkflowStepJob::class, fn (ProcessBookingWorkflowStepJob $job): bool => $job->workflowStep->id === $signoff->id);
})->with('demo booking combinations');

test('rejects invalid selection without creating a booking', function (array $payload, string $error): void {
    Queue::fake();

    $this->from('/bookings/create')->post('/demo/bookings/successful', $payload)
        ->assertRedirect('/bookings/create')->assertSessionHasErrors($error);

    $this->assertDatabaseCount('bookings', 0);
    Queue::assertNothingPushed();
})->with([
    [[], 'item_types'],
    [['item_types' => []], 'item_types'],
    [['item_types' => 'flight'], 'item_types'],
    [['item_types' => ['cruise']], 'item_types.0'],
    [['item_types' => ['flight', 'flight']], 'item_types.0'],
]);

test('create page shows selection and missing selection error', function (): void {
    $this->withoutVite();
    $this->followingRedirects()->from('/bookings/create')->post('/demo/bookings/successful')
        ->assertSee('Choose booking items')
        ->assertSee('name="item_types[]"', false)
        ->assertSee('Choose at least one booking item.');
});

test('bookings created at the same time have unique references', function (): void {
    Queue::fake([ProcessBookingWorkflowStepJob::class]);
    $this->freezeTime();

    $this->post('/demo/bookings/successful', ['item_types' => ['flight']])->assertRedirect();
    $this->post('/demo/bookings/successful', ['item_types' => ['car_hire']])->assertRedirect();

    $this->assertDatabaseCount('bookings', 2);
    Queue::assertPushed(ProcessBookingWorkflowStepJob::class, 2);
});
