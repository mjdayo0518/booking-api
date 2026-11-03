<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class BookingConflictException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'The requested time slot is already booked.',
        ], 409);
    }
}