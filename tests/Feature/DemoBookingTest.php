<?php

namespace Tests\Feature;

use App\Jobs\ProcessBookingWorkflowJob;
use App\Models\Booking;
use App\Services\Workflows\WorkflowCoordinator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DemoBookingTest extends TestCase
{
    use RefreshDatabase;

    /** @return iterable<string, array{string, list<string>, list<string>}> */
    public static function combinations(): iterable
    {
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
    }

    /**
     * @param  list<string>  $types
     * @param  list<string>  $workflows
     */
    #[DataProvider('combinations')]
    public function test_creates_only_selected_items_and_signoff_waits_only_for_their_workflows(string $scenario, array $types, array $workflows): void
    {
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
    }

    #[TestWith([[], 'item_types'])]
    #[TestWith([['item_types' => []], 'item_types'])]
    #[TestWith([['item_types' => 'flight'], 'item_types'])]
    #[TestWith([['item_types' => ['cruise']], 'item_types.0'])]
    #[TestWith([['item_types' => ['flight', 'flight']], 'item_types.0'])]
    public function test_rejects_invalid_selection_without_creating_a_booking(array $payload, string $error): void
    {
        Queue::fake();

        $this->from('/bookings/create')->post('/demo/bookings/successful', $payload)
            ->assertRedirect('/bookings/create')->assertSessionHasErrors($error);

        $this->assertDatabaseCount('bookings', 0);
        Queue::assertNothingPushed();
    }

    public function test_create_page_shows_selection_and_missing_selection_error(): void
    {
        $this->withoutVite();
        $this->followingRedirects()->from('/bookings/create')->post('/demo/bookings/successful')
            ->assertSee('Choose booking items')
            ->assertSee('name="item_types[]"', false)
            ->assertSee('Choose at least one booking item.');
    }

    public function test_bookings_created_at_the_same_time_have_unique_references(): void
    {
        Queue::fake([ProcessBookingWorkflowJob::class]);
        $this->freezeTime();

        $this->post('/demo/bookings/successful', ['item_types' => ['flight']])->assertRedirect();
        $this->post('/demo/bookings/successful', ['item_types' => ['car_hire']])->assertRedirect();

        $this->assertDatabaseCount('bookings', 2);
        Queue::assertPushed(ProcessBookingWorkflowJob::class, 2);
    }
}
