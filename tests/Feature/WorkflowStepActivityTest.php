<?php

use App\Jobs\ProcessBookingWorkflowStepJob;
use App\Livewire\Bookings\Detail;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('item attempt has one start entry and completion is scoped to item', function (): void {
    $booking = createBookingForWorkflowStepActivity();
    Http::preventStrayRequests();
    Http::fake([url('/api/demo/providers/flights/confirm') => Http::response(['provider_reference' => 'FLIGHT-1'])]);
    $workflowStep = $booking->workflowSteps()->sole();

    app()->call([new ProcessBookingWorkflowStepJob($workflowStep), 'handle']);

    $this->assertSame(['workflow_step_attempt_started', 'workflow_step_completed'], $workflowStep->activities()->orderBy('id')->pluck('type')->all());
    Livewire::test(Detail::class, ['booking' => $booking])
        ->assertSee('Flight confirmation — attempt 1 started')
        ->assertSee('workflow_step_completed')
        ->assertDontSee('workflow_processing');
    Http::assertSentCount(1);
});

test('legacy types migrate and processing duplicates are hidden in all and filtered history', function (): void {
    $booking = createBookingForWorkflowStepActivity();
    $workflowStep = $booking->workflowSteps()->sole();
    foreach (['workflow_attempt', 'workflow_processing', 'workflow_completed', 'workflow_attempt_failed', 'workflow_failed', 'workflow_retried', 'workflow_awaiting_approval', 'workflow_approved'] as $type) {
        $workflowStep->activities()->create(['booking_id' => $booking->id, 'type' => $type, 'message' => 'Legacy '.$type]);
    }
    $migration = require database_path('migrations/2026_10_08_095746_rename_workflow_activity_types.php');

    $rename = require database_path('migrations/2026_10_20_094629_rename_booking_workflows_to_booking_workflow_steps.php');
    $rename->down();
    $migration->up();
    $rename->up();

    $this->assertSame([
        'workflow_step_attempt_started', 'workflow_step_processing', 'workflow_step_completed',
        'workflow_step_attempt_failed', 'workflow_step_failed', 'workflow_step_retry_requested',
        'workflow_step_awaiting_approval', 'workflow_step_approved',
    ], $workflowStep->activities()->orderBy('id')->pluck('type')->all());
    Livewire::test(Detail::class, ['booking' => $booking])
        ->assertDontSee('Legacy workflow_processing')
        ->assertSee('workflow_step_completed')
        ->call('selectWorkflowStep', $workflowStep->id)
        ->assertDontSee('Legacy workflow_processing')
        ->assertSee('workflow_step_attempt_failed');
    $this->assertDatabaseHas('booking_activities', ['booking_id' => $booking->id, 'type' => 'booking_created']);
    $this->assertSame(8, $workflowStep->activities()->count());

    $rename->down();
    $migration->down();
    $rename->up();

    $this->assertDatabaseHas('booking_activities', ['booking_workflow_step_id' => $workflowStep->id, 'type' => 'workflow_completed']);
});

test('obsolete booking job events are hidden but diagnostic events remain', function (): void {
    $booking = createBookingForWorkflowStepActivity();
    foreach (['processing_started', 'booking_processing_complete', 'booking_processing_failed'] as $type) {
        $booking->activities()->create(['type' => $type, 'message' => 'Obsolete '.$type]);
    }

    Livewire::test(Detail::class, ['booking' => $booking])
        ->assertSee('Booking created')
        ->assertDontSee('Obsolete processing_started')
        ->assertDontSee('Obsolete booking_processing_complete')
        ->assertDontSee('Obsolete booking_processing_failed');
});

function createBookingForWorkflowStepActivity(): Booking
{
    Queue::fake();
    test()->post('/demo/bookings/successful', ['item_types' => ['flight'], 'requires_signoff' => false])->assertRedirect();
    Queue::assertPushed(ProcessBookingWorkflowStepJob::class, 1);
    Queue::assertCount(1);

    return Booking::sole();
}
