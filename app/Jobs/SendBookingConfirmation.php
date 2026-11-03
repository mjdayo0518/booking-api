<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendBookingConfirmation implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking,
    ) {}

    public function handle(): void
    {
        // Sa totoong production, dito magpapadala ng email o SMS
        // Sa MVP, gumagamit ng log para ipakita na na-execute ang job
        Log::info('Booking confirmation sent', [
            'booking_id' => $this->booking->id,
            'user_id' => $this->booking->user_id,
            'resource_id' => $this->booking->resource_id,
        ]);
    }
}