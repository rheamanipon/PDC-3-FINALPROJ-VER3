<?php

namespace App\Models;

use App\Models\ConcertTicketType;
use Illuminate\Database\Eloquent\Model;

class ConcertSeat extends Model
{
    protected $fillable = [
        'concert_id',
        'concert_ticket_type_id',
        'seat_id',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function concert()
    {
        return $this->belongsTo(Concert::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public function concertTicketType()
    {
        return $this->belongsTo(ConcertTicketType::class);
    }
}