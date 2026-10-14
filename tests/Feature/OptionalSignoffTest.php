<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Jobs\ProcessBookingWorkflowJob;
use App\Models\Booking;
use App\Services\Workflows\WorkflowProcessorResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class OptionalSignoffTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['successful'])]
    #[TestWith(['failing'])]
    #[TestWith(['random-failure'])]
    public function test_demo_can_omit_signoff(string $scenario): void
    {
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
    }

    #[TestWith([['accommodation']])]
    #[TestWith([['flight']])]
    #[TestWith([['car_hire']])]
    #[TestWith([['accommodation', 'flight']])]
    #[TestWith([['accommodation', 'car_hire']])]
    #[TestWith([['flight', 'car_hire']])]
    #[TestWith([['accommodation', 'flight', 'car_hire']])]
    public function test_api_booking_without_signoff_completes_after_all_selected_providers_confirm(array $types): void
    {
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
    }

    public function test_explicit_signoff_selection_creates_the_approval_step(): void
    {
        Queue::fake([ProcessBookingWorkflowJob::class]);

        $this->post('/demo/bookings/successful', [
            'item_types' => ['car_hire'],
            'requires_signoff' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('booking_workflows', ['type' => 'operations_signoff', 'status' => 'pending']);
        Queue::assertPushed(ProcessBookingWorkflowJob::class, 1);
    }

    #[TestWith(['/demo/bookings/successful'])]
    #[TestWith(['/api/bookings'])]
    public function test_rejects_invalid_signoff_option(string $endpoint): void
    {
        Queue::fake();

        $this->postJson($endpoint, [
            'item_types' => ['car_hire'],
            'requires_signoff' => 'invalid',
        ])->assertUnprocessable()->assertJsonValidationErrors('requires_signoff');

        $this->assertDatabaseCount('bookings', 0);
        Queue::assertNothingPushed();
    }

    public function test_booking_form_offers_signoff_selection(): void
    {
        $this->withoutVite();

        $this->get('/bookings/create')->assertSee('Require operations signoff')
            ->assertSee('name="requires_signoff"', false)
            ->assertSee('Uncheck to complete the booking automatically once all items are confirmed.');
    }
}
