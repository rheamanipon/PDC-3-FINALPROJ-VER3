<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'venue_id',
        'seat_number',
        'section',
    ];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function concertSeats()
    {
        return $this->hasMany(ConcertSeat::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function concerts()
    {
        return $this->belongsToMany(Concert::class, 'concert_seats')->withPivot('status');
    }
}
