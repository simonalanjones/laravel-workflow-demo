<?php

use App\Models\Booking;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('bookings receive distinct random public ids that survive updates', function (): void {
    $booking = createBookingForPublicId('PUBLIC-001');
    $otherBooking = createBookingForPublicId('PUBLIC-002');
    $publicId = $booking->public_id;

    expect(Str::isUuid($publicId, version: 4))->toBeTrue()
        ->and(Str::isUuid($otherBooking->public_id, version: 4))->toBeTrue()
        ->and($publicId)->not->toBe($otherBooking->public_id)
        ->and($booking->id)->toBeInt();

    $booking->update(['customer_name' => 'Updated Customer']);

    expect($booking->fresh()->public_id)->toBe($publicId);
});

test('booking list links to the matching detail page using its public id', function (): void {
    $this->withoutVite();
    $booking = createBookingForPublicId('PUBLIC-DETAIL');
    createBookingForPublicId('OTHER-BOOKING');

    $this->get('/bookings')
        ->assertOk()
        ->assertSee('href="'.url('/bookings/'.$booking->public_id).'"', false);

    $this->get('/bookings/'.$booking->public_id)
        ->assertOk()
        ->assertSee('PUBLIC-DETAIL')
        ->assertDontSee('OTHER-BOOKING');
});

test('booking detail rejects numeric ids and display references', function (): void {
    $booking = createBookingForPublicId('PUBLIC-PRIVATE');

    $this->get('/bookings/'.$booking->id)->assertNotFound();
    $this->get('/bookings/'.$booking->reference)->assertNotFound();
});

test('booking detail returns 404 for unknown or malformed public ids', function (string $publicId): void {
    $this->get('/bookings/'.$publicId)->assertNotFound();
})->with([
    'unknown UUID' => '550e8400-e29b-41d4-a716-446655440000',
    'malformed UUID' => 'not-a-uuid',
]);

test('public id migration backfills existing bookings and preserves their data on rollback', function (): void {
    $booking = createBookingForPublicId('LEGACY-001');
    $otherBooking = createBookingForPublicId('LEGACY-002');
    $originalRecords = DB::table('bookings')->orderBy('id')->get()
        ->map(fn (object $record): array => collect((array) $record)->except('public_id')->all())
        ->all();
    $migration = require database_path('migrations/2026_10_20_131343_add_public_id_to_bookings_table.php');

    $migration->down();

    expect(Schema::hasColumn('bookings', 'public_id'))->toBeFalse();

    $migration->up();

    $publicId = $booking->fresh()->public_id;
    $otherPublicId = $otherBooking->fresh()->public_id;

    expect(Str::isUuid($publicId, version: 4))->toBeTrue()
        ->and(Str::isUuid($otherPublicId, version: 4))->toBeTrue()
        ->and($publicId)->not->toBe($otherPublicId)
        ->and(DB::table('bookings')->orderBy('id')->get()
            ->map(fn (object $record): array => collect((array) $record)->except('public_id')->all())
            ->all())->toBe($originalRecords);
});

test('required public id migration fills missing ids and preserves existing ids through rollback', function (): void {
    $existingBooking = createBookingForPublicId('EXISTING-UUID');
    $missingBooking = createBookingForPublicId('MISSING-UUID');
    $migration = require database_path('migrations/2026_10_20_134347_make_public_id_required_on_bookings_table.php');

    $migration->down();
    DB::table('bookings')->where('id', $missingBooking->id)->update(['public_id' => null]);

    $migration->up();

    $backfilledId = $missingBooking->fresh()->public_id;
    expect(Str::isUuid($backfilledId, version: 4))->toBeTrue()
        ->and($existingBooking->fresh()->public_id)->toBe($existingBooking->public_id);

    $migration->down();

    expect($missingBooking->fresh()->public_id)->toBe($backfilledId)
        ->and($existingBooking->fresh()->public_id)->toBe($existingBooking->public_id);

    DB::table('bookings')->where('id', $missingBooking->id)->update(['public_id' => null]);
    $this->assertDatabaseHas('bookings', ['id' => $missingBooking->id, 'public_id' => null]);

    $migration->up();
});

test('database rejects null booking public ids', function (): void {
    $booking = createBookingForPublicId('REQUIRED-UUID');

    expect(fn () => DB::table('bookings')->where('id', $booking->id)->update(['public_id' => null]))
        ->toThrow(QueryException::class);
});

test('database still rejects duplicate public ids after making them required', function (): void {
    $booking = createBookingForPublicId('UNIQUE-UUID-001');
    $otherBooking = createBookingForPublicId('UNIQUE-UUID-002');

    expect(fn () => DB::table('bookings')->where('id', $otherBooking->id)->update(['public_id' => $booking->public_id]))
        ->toThrow(QueryException::class);
});

function createBookingForPublicId(string $reference): Booking
{
    return Booking::create([
        'reference' => $reference,
        'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com',
        'status' => 'pending',
    ]);
}
