<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\BookingConflictException;
use App\Jobs\SendBookingConfirmation;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Create a booking with conflict detection.
     *
     * Uses a database transaction and row-level lock to prevent
     * double-booking when multiple requests target the same slot.
     *
     * @throws BookingConflictException
     */
    public function create(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $conflict = Booking::query()
                ->where('resource_id', $data['resource_id'])
                ->where('status', '!=', BookingStatus::Cancelled)
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new BookingConflictException();
            }

            $booking =  Booking::create([
                'user_id' => auth()->id(),
                'resource_id' => $data['resource_id'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => BookingStatus::Confirmed,
            ]);

            SendBookingConfirmation::dispatch($booking);

            return $booking;
        });

    }
}