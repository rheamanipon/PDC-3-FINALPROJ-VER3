<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Concert extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'title',
        'description',
        'artist',
        'venue_id',
        'date',
        'time',
        'poster_url',
        'seat_plan_image',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',
    ];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function concertSeats()
    {
        return $this->hasMany(ConcertSeat::class);
    }

    public function concertTicketTypes()
    {
        return $this->hasMany(ConcertTicketType::class);
    }

    public function ticketPrices()
    {
        return $this->hasMany(ConcertTicketType::class);
    }


}
