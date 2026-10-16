<?php

use App\Exceptions\ProviderResponseLostException;
use App\Jobs\ProcessBookingWorkflowJob;
use App\Livewire\Bookings\Detail;
use App\Models\Booking;
use App\Models\DemoProviderOperation;
use App\Models\ProviderRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('detail shows lost response until retry completes and keeps activity', function (string $item, string $endpoint): void {
    Queue::fake([ProcessBookingWorkflowJob::class]);
    Http::preventStrayRequests();
    $this->post('/demo/bookings/successful', [
        'item_types' => [$item],
        'requires_signoff' => false,
        'simulate_lost_response' => true,
    ])->assertRedirect();
    $booking = Booking::sole();
    $workflow = $booking->workflows()->sole();
    $url = '/api/demo/providers/'.$endpoint.'/confirm';
    $lost = $this->postJson($url, [
        'booking_reference' => $booking->reference,
        'simulate_lost_response' => true,
    ])->assertServiceUnavailable()->assertJsonPath('code', 'simulated_response_lost');
    $saved = DemoProviderOperation::sole()->response_payload;
    Http::fake([url($url) => Http::sequence()->push($lost->json(), 503)->push($saved)]);

    try {
        app()->call([new ProcessBookingWorkflowJob($workflow), 'handle']);
        $this->fail('The lost response must trigger a queue retry.');
    } catch (ProviderResponseLostException $exception) {
        $this->assertSame('Provider response lost. Confirmation outcome is unknown.', $exception->getMessage());
    }

    $this->assertSame('processing', $workflow->fresh()->status);
    $this->assertSame('unknown', ProviderRequest::sole()->status);
    $this->assertNull(ProviderRequest::sole()->response_payload);
    $component = Livewire::test(Detail::class, ['booking' => $booking])
        ->call('selectWorkflow', $workflow->id)
        ->assertSee('No response received — outcome unknown.')
        ->assertDontSee($saved['provider_reference'])
        ->assertSee('Response lost — retrying to retrieve confirmation')
        ->assertSee('Provider response lost. Confirmation outcome is unknown.');

    app()->call([new ProcessBookingWorkflowJob($workflow->fresh()), 'handle']);

    $this->assertSame('completed', $workflow->fresh()->status);
    $component->call('$refresh')
        ->assertDontSee('Response lost — retrying to retrieve confirmation')
        ->assertSee($saved['provider_reference'])
        ->assertSee('Provider response lost. Confirmation outcome is unknown.');
    $this->assertDatabaseCount('provider_requests', 1);
    Http::assertSentCount(2);
    Queue::assertPushed(ProcessBookingWorkflowJob::class, 1);
})->with([
    ['accommodation', 'property'],
    ['flight', 'flights'],
    ['car_hire', 'vehicles'],
]);

test('ordinary provider failure is not labelled as a lost response', function (): void {
    Queue::fake([ProcessBookingWorkflowJob::class]);
    Http::preventStrayRequests();
    Http::fake([url('/api/demo/providers/flights/confirm') => Http::response(['status' => 'unavailable'], 503)]);
    $this->post('/demo/bookings/successful', [
        'item_types' => ['flight'],
        'requires_signoff' => false,
        'simulate_lost_response' => true,
    ])->assertRedirect();
    $booking = Booking::sole();

    try {
        app()->call([new ProcessBookingWorkflowJob($booking->workflows()->sole()), 'handle']);
        $this->fail('The provider failure must trigger a queue retry.');
    } catch (RuntimeException $exception) {
        $this->assertSame('Provider returned HTTP 503', $exception->getMessage());
    }

    Livewire::test(Detail::class, ['booking' => $booking])
        ->assertDontSee('Response lost')
        ->assertSee('Flight confirmation — attempt 1 failed (HTTP 503)');
    Http::assertSentCount(1);
    Queue::assertPushed(ProcessBookingWorkflowJob::class, 1);
});
