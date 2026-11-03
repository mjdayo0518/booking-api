<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\BookingStatus;

class Booking extends Model
{
    protected $fillable = ['user_id', 'resource_id', 'start_time', 'end_time', 'status'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'status' => BookingStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}