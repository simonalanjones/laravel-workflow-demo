<?php

use App\Jobs\ProcessBookingWorkflowStepJob;
use App\Livewire\Bookings\Detail;
use App\Models\Booking;
use App\Models\ProviderRequest;
use App\Models\ProviderRequestAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('retry preserves each response and links details to its own activity', function (): void {
    $booking = createBookingForProviderRequestAttempt();
    Http::preventStrayRequests();
    Http::fake([url('/api/demo/providers/flights/confirm') => Http::sequence()
        ->push('<script>failed</script>', 503)
        ->push(['status' => 'confirmed', 'provider_reference' => 'RECOVERED-123'])]);
    $workflowStep = $booking->workflowSteps()->sole();

    try {
        app()->call([new ProcessBookingWorkflowStepJob($workflowStep), 'handle']);
        $this->fail('First attempt should fail.');
    } catch (RuntimeException) {
        $first = ProviderRequestAttempt::sole();
    }

    $this->assertSame(503, $first->http_status);
    $this->assertSame('<script>failed</script>', $first->response_body);
    $this->assertSame('workflow_step_attempt_started', $first->activity->type);
    $this->assertNotNull($first->finished_at);
    $this->assertGreaterThanOrEqual(0, $first->duration_ms);
    $component = Livewire::test(Detail::class, ['booking' => $booking])
        ->assertSee('View attempt details')
        ->assertSee('&lt;script&gt;failed&lt;/script&gt;', false)
        ->assertDontSee('<script>failed</script>', false)
        ->assertSee('wire:ignore.self', false);

    app()->call([new ProcessBookingWorkflowStepJob($workflowStep->fresh()), 'handle']);

    $attempts = ProviderRequestAttempt::orderBy('id')->get();
    $this->assertCount(2, $attempts);
    $this->assertSame($first->response_body, $first->fresh()->response_body);
    $this->assertSame(200, $attempts[1]->http_status);
    $this->assertSame('succeeded', $attempts[1]->status);
    $this->assertNotSame($first->booking_activity_id, $attempts[1]->booking_activity_id);
    $this->assertSame($first->provider_request_id, $attempts[1]->provider_request_id);
    $component->call('$refresh')
        ->assertSee('RECOVERED-123')
        ->assertSee('&lt;script&gt;failed&lt;/script&gt;', false)
        ->call('selectWorkflowStep', $workflowStep->id)
        ->assertSee('RECOVERED-123');
    $other = createBookingForProviderRequestAttempt();
    Livewire::test(Detail::class, ['booking' => $other])->assertDontSee('RECOVERED-123');
    Http::assertSentCount(2);
});

test('missing response records unknown without a fabricated http status or body', function (bool $simulated): void {
    $booking = createBookingForProviderRequestAttempt();
    Http::preventStrayRequests();
    Http::fake([url('/api/demo/providers/flights/confirm') => $simulated
        ? Http::response(['code' => 'simulated_response_lost', 'message' => 'Provider-only information'], 503)
        : Http::failedConnection()]);

    try {
        app()->call([new ProcessBookingWorkflowStepJob($booking->workflowSteps()->sole()), 'handle']);
        $this->fail('Missing response should trigger a retry.');
    } catch (Exception) {
        $attempt = ProviderRequestAttempt::sole();
    }

    $this->assertSame('unknown', $attempt->status);
    $this->assertNull($attempt->http_status);
    $this->assertNull($attempt->response_body);
    $this->assertNotEmpty($attempt->error_message);
    $this->assertNotNull($attempt->finished_at);
    $this->assertSame('unknown', ProviderRequest::sole()->status);
    Livewire::test(Detail::class, ['booking' => $booking])
        ->assertSee('No response received — outcome unknown.')
        ->assertDontSee('Provider-only information');
    Http::assertSentCount(1);
})->with([
    [true],
    [false],
]);

function createBookingForProviderRequestAttempt(): Booking
{
    Queue::fake([ProcessBookingWorkflowStepJob::class]);
    test()->post('/demo/bookings/successful', ['item_types' => ['flight'], 'requires_signoff' => false])->assertRedirect();
    Queue::assertPushed(ProcessBookingWorkflowStepJob::class, 1);

    return Booking::latest('id')->firstOrFail();
}
