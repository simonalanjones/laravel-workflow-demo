<?php

use App\Jobs\ProcessBookingWorkflowJob;
use App\Livewire\Bookings\Detail;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('item attempt has one start entry and completion is scoped to item', function (): void {
    $booking = createBookingForWorkflowItemActivity();
    Http::preventStrayRequests();
    Http::fake([url('/api/demo/providers/flights/confirm') => Http::response(['provider_reference' => 'FLIGHT-1'])]);
    $workflow = $booking->workflows()->sole();

    app()->call([new ProcessBookingWorkflowJob($workflow), 'handle']);

    $this->assertSame(['workflow_item_attempt_started', 'workflow_item_completed'], $workflow->activities()->orderBy('id')->pluck('type')->all());
    Livewire::test(Detail::class, ['booking' => $booking])
        ->assertSee('Flight confirmation — attempt 1 started')
        ->assertSee('workflow_item_completed')
        ->assertDontSee('workflow_processing');
    Http::assertSentCount(1);
});

test('legacy types migrate and processing duplicates are hidden in all and filtered history', function (): void {
    $booking = createBookingForWorkflowItemActivity();
    $workflow = $booking->workflows()->sole();
    foreach (['workflow_attempt', 'workflow_processing', 'workflow_completed', 'workflow_attempt_failed', 'workflow_failed', 'workflow_retried', 'workflow_awaiting_approval', 'workflow_approved'] as $type) {
        $workflow->activities()->create(['booking_id' => $booking->id, 'type' => $type, 'message' => 'Legacy '.$type]);
    }
    $migration = require database_path('migrations/2026_10_08_095746_rename_workflow_activity_types.php');

    $migration->up();

    $this->assertSame([
        'workflow_item_attempt_started', 'workflow_item_processing', 'workflow_item_completed',
        'workflow_item_attempt_failed', 'workflow_item_failed', 'workflow_item_retry_requested',
        'workflow_item_awaiting_approval', 'workflow_item_approved',
    ], $workflow->activities()->orderBy('id')->pluck('type')->all());
    Livewire::test(Detail::class, ['booking' => $booking])
        ->assertDontSee('Legacy workflow_processing')
        ->assertSee('workflow_item_completed')
        ->call('selectWorkflow', $workflow->id)
        ->assertDontSee('Legacy workflow_processing')
        ->assertSee('workflow_item_attempt_failed');
    $this->assertDatabaseHas('booking_activities', ['booking_id' => $booking->id, 'type' => 'booking_created']);
    $this->assertSame(8, $workflow->activities()->count());

    $migration->down();

    $this->assertDatabaseHas('booking_activities', ['booking_workflow_id' => $workflow->id, 'type' => 'workflow_completed']);
});

test('obsolete booking job events are hidden but diagnostic events remain', function (): void {
    $booking = createBookingForWorkflowItemActivity();
    foreach (['processing_started', 'booking_processing_complete', 'booking_processing_failed'] as $type) {
        $booking->activities()->create(['type' => $type, 'message' => 'Obsolete '.$type]);
    }

    Livewire::test(Detail::class, ['booking' => $booking])
        ->assertSee('Booking created')
        ->assertDontSee('Obsolete processing_started')
        ->assertDontSee('Obsolete booking_processing_complete')
        ->assertDontSee('Obsolete booking_processing_failed');
});

function createBookingForWorkflowItemActivity(): Booking
{
    Queue::fake();
    test()->post('/demo/bookings/successful', ['item_types' => ['flight'], 'requires_signoff' => false])->assertRedirect();
    Queue::assertPushed(ProcessBookingWorkflowJob::class, 1);
    Queue::assertCount(1);

    return Booking::sole();
}
