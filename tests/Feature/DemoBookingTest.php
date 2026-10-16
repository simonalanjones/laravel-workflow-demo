<?php

use App\Jobs\ProcessBookingWorkflowJob;
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
        foreach ($combinations as [$types, $workflows]) {
            yield $scenario.' '.implode(', ', $types) => [$scenario, $types, $workflows];
        }
    }
});

/**
 * @param  list<string>  $types
 * @param  list<string>  $workflows
 */
test('creates only selected items and signoff waits only for their workflows', function (string $scenario, array $types, array $workflows): void {
    Queue::fake([ProcessBookingWorkflowJob::class]);

    $response = $this->post('/demo/bookings/'.$scenario, ['item_types' => $types]);

    $booking = Booking::sole();
    $response->assertRedirect('/bookings/'.$booking->id);
    $this->assertSame($types, $booking->items()->orderBy('id')->pluck('type')->all());
    $this->assertSame([...$workflows, 'operations_signoff'], $booking->workflows()->orderBy('id')->pluck('type')->all());
    Queue::assertPushed(ProcessBookingWorkflowJob::class, count($workflows));
    Queue::assertNotPushed(ProcessBookingWorkflowJob::class, fn (ProcessBookingWorkflowJob $job): bool => $job->workflow->type === 'operations_signoff');

    $booking->workflows()->whereIn('type', $workflows)->update(['status' => 'completed']);
    $signoff = $booking->workflows()->where('type', 'operations_signoff')->sole();
    app(WorkflowCoordinator::class)->startIfReady($signoff);

    Queue::assertPushed(ProcessBookingWorkflowJob::class, fn (ProcessBookingWorkflowJob $job): bool => $job->workflow->id === $signoff->id);
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
    Queue::fake([ProcessBookingWorkflowJob::class]);
    $this->freezeTime();

    $this->post('/demo/bookings/successful', ['item_types' => ['flight']])->assertRedirect();
    $this->post('/demo/bookings/successful', ['item_types' => ['car_hire']])->assertRedirect();

    $this->assertDatabaseCount('bookings', 2);
    Queue::assertPushed(ProcessBookingWorkflowJob::class, 2);
});
