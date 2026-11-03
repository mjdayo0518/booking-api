<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Jobs\SendBookingConfirmation;
use Illuminate\Support\Facades\Queue;

class BookingConflictTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Resource $resource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->resource = Resource::create(['name' => 'Meeting Room A', 'type' => 'room']);
        Sanctum::actingAs($this->user);
    }

    public function test_creates_booking_when_slot_is_free(): void
    {
        $response = $this->postJson('/api/bookings', [
            'resource_id' => $this->resource->id,
            'start_time' => '2026-11-10 09:00:00',
            'end_time' => '2026-11-10 10:00:00',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', BookingStatus::Confirmed->value);

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_rejects_overlapping_booking(): void
    {
        Booking::create([
            'user_id' => $this->user->id,
            'resource_id' => $this->resource->id,
            'start_time' => '2026-11-10 09:00:00',
            'end_time' => '2026-11-10 10:00:00',
            'status' => BookingStatus::Confirmed,
        ]);

        $response = $this->postJson('/api/bookings', [
            'resource_id' => $this->resource->id,
            'start_time' => '2026-11-10 09:30:00',
            'end_time' => '2026-11-10 10:30:00',
        ]);

        $response->assertStatus(409);
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_allows_adjacent_booking(): void
    {
        Booking::create([
            'user_id' => $this->user->id,
            'resource_id' => $this->resource->id,
            'start_time' => '2026-11-10 09:00:00',
            'end_time' => '2026-11-10 10:00:00',
            'status' => BookingStatus::Confirmed,
        ]);

        $response = $this->postJson('/api/bookings', [
            'resource_id' => $this->resource->id,
            'start_time' => '2026-11-10 10:00:00',
            'end_time' => '2026-11-10 11:00:00',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_ignores_cancelled_bookings_for_conflicts(): void
    {
        Booking::create([
            'user_id' => $this->user->id,
            'resource_id' => $this->resource->id,
            'start_time' => '2026-11-10 09:00:00',
            'end_time' => '2026-11-10 10:00:00',
            'status' => BookingStatus::Cancelled,
        ]);

        $response = $this->postJson('/api/bookings', [
            'resource_id' => $this->resource->id,
            'start_time' => '2026-11-10 09:00:00',
            'end_time' => '2026-11-10 10:00:00',
        ]);

        $response->assertStatus(201);
    }
    
    public function test_dispatches_confirmation_job_after_booking(): void
    {
        Queue::fake();

        $this->postJson('/api/bookings', [
            'resource_id' => $this->resource->id,
            'start_time' => '2026-11-10 09:00:00',
            'end_time' => '2026-11-10 10:00:00',
        ])->assertStatus(201);

        Queue::assertPushed(SendBookingConfirmation::class);
    }
}