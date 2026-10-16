<?php

use App\Jobs\ProcessBookingWorkflowJob;
use App\Livewire\Bookings\Detail;
use App\Models\Booking;
use App\Models\DemoProviderOperation;
use App\Models\ProviderRequest;
use App\Models\ProviderRequestAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('selected provider step shows its saved response', function (string $item, string $endpoint, string $workflowType): void {
    $booking = createBookingForBookingProviderResponse([$item]);
    $response = $this->postJson('/api/demo/providers/'.$endpoint.'/confirm', ['booking_reference' => $booking->reference])->assertOk();
    $workflow = $booking->workflows()->where('type', $workflowType)->sole();
    $record = ProviderRequest::factory()->succeeded()->create([
        'booking_workflow_id' => $workflow->id,
        'provider' => DemoProviderOperation::sole()->provider,
        'idempotency_key' => hash('sha256', 'confirm_booking:'.$booking->reference),
        'request_payload' => ['booking_reference' => $booking->reference],
        'response_payload' => $response->json(),
    ]);

    $activity = $workflow->activities()->create(['booking_id' => $booking->id, 'type' => 'workflow_item_attempt_started', 'message' => 'Confirmation requested']);
    ProviderRequestAttempt::factory()->create([
        'provider_request_id' => $record->id,
        'booking_activity_id' => $activity->id,
        'status' => 'succeeded',
        'response_body' => json_encode($response->json()),
    ]);

    Livewire::test(Detail::class, ['booking' => $booking])
        ->assertDontSee('View API response')
        ->call('selectWorkflow', $workflow->id)
        ->assertSee('View attempt details')
        ->assertSee('confirmed')
        ->assertSee($response->json('provider_reference'))
        ->assertSee('Received response')
        ->assertDontSee('Provider reference')
        ->call('showAllActivity')
        ->assertDontSee('View API response');
})->with([
    ['accommodation', 'property', 'property_confirmation'],
    ['flight', 'flights', 'flight_confirmation'],
    ['car_hire', 'vehicles', 'vehicle_confirmation'],
]);

test('selection does not show another providers response or a signoff panel', function (): void {
    $booking = createBookingForBookingProviderResponse(['flight', 'car_hire']);
    $vehicle = $booking->workflows()->where('type', 'vehicle_confirmation')->sole();
    $signoff = $booking->workflows()->where('type', 'operations_signoff')->sole();
    ProviderRequest::factory()->succeeded()->create([
        'booking_workflow_id' => $booking->workflows()->where('type', 'flight_confirmation')->sole()->id,
        'provider' => 'Flight',
        'idempotency_key' => hash('sha256', 'confirm_booking:'.$booking->reference),
        'request_payload' => ['booking_reference' => $booking->reference],
        'response_payload' => ['status' => 'confirmed', 'provider_reference' => 'FLIGHT-PRIVATE'],
    ]);

    Livewire::test(Detail::class, ['booking' => $booking])
        ->call('selectWorkflow', $vehicle->id)
        ->assertDontSee('View attempt details')
        ->assertDontSee('FLIGHT-PRIVATE')
        ->call('selectWorkflow', $signoff->id)
        ->assertDontSee('View API response');
});

test('response updates after retry and escapes provider content', function (): void {
    $booking = createBookingForBookingProviderResponse(['car_hire']);
    $workflow = $booking->workflows()->where('type', 'vehicle_confirmation')->sole();
    $record = ProviderRequest::factory()->create([
        'booking_workflow_id' => $workflow->id,
        'provider' => 'Vehicle',
        'idempotency_key' => hash('sha256', 'confirm_booking:'.$booking->reference),
        'request_payload' => ['booking_reference' => $booking->reference],
        'status' => 'failed',
        'response_payload' => ['status' => 'unavailable', 'message' => '<script>alert(1)</script>'],
    ]);
    $activity = $workflow->activities()->create(['booking_id' => $booking->id, 'type' => 'workflow_item_attempt_started', 'message' => 'Confirmation requested']);
    $attempt = ProviderRequestAttempt::factory()->create([
        'provider_request_id' => $record->id,
        'booking_activity_id' => $activity->id,
        'response_body' => json_encode($record->response_payload),
    ]);
    $component = Livewire::test(Detail::class, ['booking' => $booking])
        ->call('selectWorkflow', $workflow->id)
        ->assertSee('unavailable')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);

    $attempt->update(['status' => 'succeeded', 'response_body' => json_encode(['status' => 'confirmed', 'provider_reference' => 'RECOVERED-001'])]);

    $component->call('$refresh')->assertSee('confirmed')->assertSee('RECOVERED-001')->assertDontSee('alert(1)');
});

test('workflow selection is scoped to the current booking', function (): void {
    $booking = createBookingForBookingProviderResponse(['car_hire']);
    $otherBooking = createBookingForBookingProviderResponse(['car_hire']);
    $otherWorkflow = $otherBooking->workflows()->where('type', 'vehicle_confirmation')->sole();
    $this->postJson('/api/demo/providers/vehicles/confirm', ['booking_reference' => $otherBooking->reference])->assertOk();
    $reference = DemoProviderOperation::sole()->response_payload['provider_reference'];

    Livewire::test(Detail::class, ['booking' => $booking])
        ->call('selectWorkflow', $otherWorkflow->id)
        ->assertSet('selectedWorkflowId', null)
        ->assertDontSee($reference)
        ->set('selectedWorkflowId', $otherWorkflow->id)
        ->assertDontSee('View API response')
        ->assertDontSee($reference);
});

/** @param list<string> $items */
function createBookingForBookingProviderResponse(array $items): Booking
{
    Queue::fake([ProcessBookingWorkflowJob::class]);
    test()->post('/demo/bookings/successful', ['item_types' => $items])->assertRedirect();
    Queue::assertPushed(ProcessBookingWorkflowJob::class, count($items));

    return Booking::latest('id')->firstOrFail();
}
